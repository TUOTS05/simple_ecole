<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrangeSmsService
{
    private $clientId;

    private $clientSecret;

    private $senderName;

    private $devMode;

    public function __construct()
    {
        $this->clientId = config('services.orange_sms.client_id');
        $this->clientSecret = config('services.orange_sms.client_secret');
        $this->senderName = config('services.orange_sms.sender_name');
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
     * Formater le numéro de téléphone au format international
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si le numéro commence par 0, le remplacer par l'indicatif pays
        if (substr($phone, 0, 1) === '0') {
            // ⚠️ ADAPTEZ SELON VOTRE PAYS :
            // 221 = Sénégal
            // 225 = Côte d'Ivoire
            // 237 = Cameroun
            // 223 = Mali
            // 226 = Burkina Faso
            $countryCode = '221'; // ← Changez ici selon votre pays
            $phone = $countryCode.substr($phone, 1);
        }

        return $phone;
    }
}
