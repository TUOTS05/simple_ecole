<?php

namespace App\Http\Controllers;

use App\Services\ExtraOnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée public (notify_url) appelé par les serveurs CinetPay après
 * une tentative de paiement. Ne fait jamais confiance au corps de la requête :
 * ExtraOnlinePaymentService::confirmFromGateway revérifie le statut réel
 * auprès de CinetPay avant de matérialiser quoi que ce soit.
 */
class CinetPayWebhookController extends Controller
{
    public function notify(Request $request, ExtraOnlinePaymentService $service): Response
    {
        $transactionId = $request->input('cpm_trans_id') ?? $request->input('transaction_id');

        if (! $transactionId) {
            return response('missing transaction_id', 400);
        }

        try {
            $service->confirmFromGateway($transactionId);
        } catch (\Exception $e) {
            Log::error('❌ Webhook CinetPay extras : '.$e->getMessage(), ['transaction_id' => $transactionId]);
        }

        return response('OK', 200);
    }
}
