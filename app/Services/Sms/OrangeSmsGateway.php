<?php

namespace App\Services\Sms;

use App\Models\School;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class OrangeSmsGateway implements SmsGatewayInterface
{
    protected string $apiUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $senderName;
    protected int $schoolId;

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
        $settings = $this->getSettings();
        
        $this->apiUrl = $settings['api_url'] ?? 'https://api.orange.com/sms/v1';
        $this->clientId = $settings['client_id'] ?? '';
        $this->clientSecret = $settings['client_secret'] ?? '';
        $this->senderName = $settings['sender_name'] ?? 'ECOLE';
    }

    public function send(string $to, string $message, ?string $senderName = null): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'external_id' => null,
                    'error' => 'Orange SMS API non configurée pour cette école'
                ];
            }

            // 1. Obtenir le token OAuth2
            $tokenResponse = Http::asForm()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/oauth/v2/token', [
                'grant_type' => 'client_credentials',
            ]);

            if (!$tokenResponse->successful()) {
                return [
                    'success' => false,
                    'external_id' => null,
                    'error' => 'Erreur authentification: ' . $tokenResponse->body()
                ];
            }

            $accessToken = $tokenResponse->json('access_token');

            // 2. Envoyer le SMS
            $sender = $senderName ?? $this->senderName;
            $smsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/sms', [
                'outboundSMSMessageRequest' => [
                    'address' => 'tel:+' . ltrim($to, '+'),
                    'senderAddress' => 'tel:' . $sender,
                    'outboundSMSTextMessage' => [
                        'message' => mb_substr($message, 0, 480), // Max 3 SMS
                    ],
                ],
            ]);

            if ($smsResponse->successful()) {
                $data = $smsResponse->json();
                return [
                    'success' => true,
                    'external_id' => $data['outboundSMSMessageRequest']['serverReferenceCode'] ?? null,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'external_id' => null,
                'error' => 'Erreur envoi: ' . $smsResponse->body()
            ];

        } catch (\Exception $e) {
            Log::error('Orange SMS Error', ['exception' => $e->getMessage(), 'school_id' => $this->schoolId]);
            return [
                'success' => false,
                'external_id' => null,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    // ✅ MODIFICATION ICI : On lit les paramètres depuis la table schools
    private function getSettings(): array
    {
        $school = School::find($this->schoolId);
        if (!$school) {
            return ['api_url' => '', 'client_id' => '', 'client_secret' => '', 'sender_name' => ''];
        }

        return [
            'api_url' => $school->orange_sms_api_url ?? 'https://api.orange.com/sms/v1',
            'client_id' => !empty($school->orange_sms_client_id) ? Crypt::decryptString($school->orange_sms_client_id) : '',
            'client_secret' => !empty($school->orange_sms_client_secret) ? Crypt::decryptString($school->orange_sms_client_secret) : '',
            'sender_name' => $school->orange_sms_sender_name ?? 'ECOLE',
        ];
    }
}