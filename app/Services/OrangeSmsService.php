<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrangeSmsService
{
    private $clientId;

    private $clientSecret;

    private $senderName;

    private $countryCode;

    private $devMode;

    public function __construct()
    {
        $this->clientId = config('services.orange_sms.client_id');
        $this->clientSecret = config('services.orange_sms.client_secret');
        $this->senderName = config('services.orange_sms.sender_name');
        $this->countryCode = (string) config('services.orange_sms.country_code', '225');
        $this->devMode = config('services.orange_sms.dev_mode', true);
    }

    /**
     * Envoyer un SMS via Orange SMS API
     */
    public function sendSms(string $phoneNumber, string $message, array $metadata = []): array
    {
        // Nettoyer le numéro de téléphone
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        // Si en mode dev, on simule l'envoi
        if ($this->devMode) {
            Log::info("📱 [DEV MODE] SMS simulé vers {$phoneNumber}: {$message}");

            return [
                'success' => true,
                'message_id' => 'DEV-'.uniqid(),
                'status' => 'sent',
                'dev_mode' => true,
                'message' => $message,
                'recipient' => $phoneNumber,
            ];
        }

        try {
            // Obtenir le token d'accès
            $token = $this->getAccessToken();

            // Envoyer le SMS
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post('https://api.orange.com/sms/v1/sms/outbound', [
                'outboundSMSMessageRequest' => [
                    'address' => 'tel:+'.$phoneNumber,
                    'senderAddress' => 'tel:'.$this->senderName,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info("✅ SMS envoyé avec succès vers {$phoneNumber}", [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['outboundSMSMessageResponse']['resourceReference']['correlator'] ?? null,
                    'status' => 'sent',
                    'response' => $data,
                ];
            } else {
                $error = $response->json()['message'] ?? 'Erreur inconnue';
                Log::error("❌ Échec envoi SMS vers {$phoneNumber}: {$error}");

                return [
                    'success' => false,
                    'error' => $error,
                    'status' => 'failed',
                ];
            }

        } catch (\Exception $e) {
            Log::error("❌ Exception lors de l'envoi SMS: ".$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
        }
    }

    /**
     * Obtenir le token d'accès OAuth
     */
    private function getAccessToken(): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.orange.com/oauth/v3/token', [
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Impossible d\'obtenir le token Orange SMS: '.$response->body());
        }

        return $response->json()['access_token'];
    }

    /**
     * Formater le numéro de téléphone au format international.
     */
    private function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '') {
            return '';
        }

        // Déjà au format international (commence par l'indicatif) : ne rien faire.
        if (str_starts_with($digits, $this->countryCode)) {
            return $digits;
        }

        // Depuis le passage à la numérotation à 10 chiffres (2021), le 0 initial
        // fait partie du numéro ivoirien (ex: 07 07 07 07 07) : il ne doit plus
        // être retiré, sous peine de produire un numéro à 12 chiffres invalide
        // (même correctif que WhatsAppService::formatPhoneNumber()).
        return $this->countryCode.$digits;
    }
}
