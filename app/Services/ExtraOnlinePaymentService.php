<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ExtraOnlinePayment;
use App\Models\ExtraPayment;
use Illuminate\Support\Facades\DB;

/**
 * Matérialise un paiement en ligne (CinetPay) accepté en un ExtraPayment réel,
 * via la même logique d'allocation FIFO que les paiements enregistrés au
 * guichet (ExtraSubscription::allocatePayment). Idempotent : un
 * ExtraOnlinePayment déjà finalisé (completed/failed) n'est jamais retraité,
 * qu'il s'agisse d'un rejeu du webhook ou d'un second appel depuis return_url.
 */
class ExtraOnlinePaymentService
{
    public function __construct(private CinetPayService $cinetPay) {}

    /**
     * Chemin réel : webhook ou retour navigateur, vérifie le statut
     * serveur-à-serveur auprès de CinetPay avant de matérialiser quoi que ce soit.
     */
    public function confirmFromGateway(string $transactionId): ExtraOnlinePayment
    {
        $onlinePayment = ExtraOnlinePayment::where('transaction_id', $transactionId)->firstOrFail();

        if ($onlinePayment->status !== 'pending') {
            return $onlinePayment;
        }

        $check = $this->cinetPay->checkStatus($transactionId);

        if (! ($check['success'] ?? false)) {
            // Échec de la vérification elle-même (réseau, config...) : on laisse en pending,
            // un rejeu ultérieur du webhook ou un nouveau passage par return_url retentera.
            return $onlinePayment;
        }

        return $this->finalize($onlinePayment, $check['status'] === 'ACCEPTED', $check['response'] ?? $check);
    }

    /**
     * Chemin mode démo uniquement (CINETPAY_DEV_MODE=true) : décision explicite
     * de l'utilisateur sur la page de simulation locale, aucun appel réseau.
     */
    public function confirmFromSimulation(string $transactionId, bool $accepted): ExtraOnlinePayment
    {
        $onlinePayment = ExtraOnlinePayment::where('transaction_id', $transactionId)->firstOrFail();

        if ($onlinePayment->status !== 'pending') {
            return $onlinePayment;
        }

        return $this->finalize($onlinePayment, $accepted, ['dev_mode' => true, 'simulated' => true]);
    }

    private function finalize(ExtraOnlinePayment $onlinePayment, bool $accepted, $rawResponse): ExtraOnlinePayment
    {
        if ($accepted) {
            DB::transaction(function () use ($onlinePayment) {
                $subscription = $onlinePayment->subscription;

                $payment = ExtraPayment::create([
                    'school_id' => $onlinePayment->school_id,
                    'extra_subscription_id' => $subscription->id,
                    'amount' => $onlinePayment->amount,
                    'payment_date' => now(),
                    'payment_method' => 'mobile_money',
                    'reference' => $onlinePayment->transaction_id,
                    'notes' => 'Paiement en ligne (CinetPay)',
                ]);
                // Le hook ExtraPayment::booted() recalcule paid_amount/remaining_amount/status.
                $subscription->refresh()->allocatePayment((float) $onlinePayment->amount);

                $onlinePayment->status = 'completed';
                $onlinePayment->extra_payment_id = $payment->id;
                $onlinePayment->completed_at = now();
                $onlinePayment->save();
            });

            (new ExtraPaymentNotifier)->sendConfirmation($onlinePayment->payment);

            ActivityLog::logAction(
                'extras.online_payment.completed',
                'Paiement en ligne de '.number_format($onlinePayment->amount, 0, ',', ' ')." FCFA confirmé pour « {$onlinePayment->subscription->extra->name} » (élève : {$onlinePayment->subscription->student->first_name} {$onlinePayment->subscription->student->last_name})"
            );
        } else {
            $onlinePayment->status = 'failed';
            ActivityLog::logAction(
                'extras.online_payment.failed',
                "Paiement en ligne échoué/annulé pour « {$onlinePayment->subscription->extra->name} »"
            );
        }

        $onlinePayment->gateway_response = json_encode($rawResponse);
        $onlinePayment->save();

        return $onlinePayment->fresh();
    }
}
