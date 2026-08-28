<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Intégration CinetPay (agrégateur Orange Money / MTN / Moov / Wave / carte
 * bancaire, Côte d'Ivoire) pour le paiement en ligne des extras côté parent.
 * Suit le même principe que OrangeSmsService : tant qu'aucune clé API n'est
 * configurée, dev_mode simule la passerelle au lieu d'appeler la vraie API.
 */
class CinetPayService
{
    private const INIT_URL = 'https://api-checkout.cinetpay.com/v2/payment';

    private const CHECK_URL = 'https://api-checkout.cinetpay.com/v2/payment/check';

    private $apiKey;

    private $siteId;

    private $devMode;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->devMode = config('services.cinetpay.dev_mode', true);
    }

    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * Initie un paiement. En mode réel, renvoie l'URL de la page de paiement
     * hébergée par CinetPay vers laquelle rediriger le parent.
     *
     * @param  array{transaction_id:string,amount:int,description:string,customer_name:string,customer_surname:string,customer_email:string,customer_phone_number:?string,notify_url:string,return_url:string}  $params
     */
    public function initiatePayment(array $params): array
    {
        if ($this->devMode) {
            Log::info('💳 [DEV MODE] Paiement CinetPay simulé', $params);

            return [
                'success' => true,
                'dev_mode' => true,
                'payment_token' => 'DEV-'.Str::upper(Str::random(10)),
            ];
        }

        try {
            $response = Http::asForm()->post(self::INIT_URL, array_merge([
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'currency' => 'XOF',
                'channels' => 'ALL',
                'lang' => 'fr',
            ], $params));

            $data = $response->json();

            if (($data['code'] ?? null) === '201') {
                return [
                    'success' => true,
                    'payment_url' => $data['data']['payment_url'] ?? null,
                    'payment_token' => $data['data']['payment_token'] ?? null,
                    'response' => $data,
                ];
            }

            Log::error('❌ CinetPay initiatePayment refusé', ['response' => $data]);

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Erreur inconnue',
                'response' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Exception CinetPay initiatePayment : '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Vérifie le statut réel d'une transaction directement auprès de CinetPay.
     * Ne jamais faire confiance à un statut reçu via webhook ou côté client :
     * cet appel serveur-à-serveur, authentifié par la clé API, fait foi.
     */
    public function checkStatus(string $transactionId): array
    {
        if ($this->devMode) {
            // En mode démo il n'y a pas de vraie passerelle à interroger : la décision vient
            // de la page de simulation locale (ExtraOnlinePaymentService::confirmFromSimulation).
            return ['success' => false, 'error' => 'checkStatus indisponible en mode démo (CINETPAY_DEV_MODE=true).'];
        }

        try {
            $response = Http::asForm()->post(self::CHECK_URL, [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $transactionId,
            ]);

            $data = $response->json();

            return [
                'success' => true,
                'status' => $data['data']['status'] ?? 'UNKNOWN',
                'response' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Exception CinetPay checkStatus : '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
