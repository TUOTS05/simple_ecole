<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de messages via l'API WhatsApp Cloud (Meta).
 *
 * Même principe que OrangeSmsService et CinetPayService : tant qu'aucun
 * identifiant n'est configuré, le service tourne en mode démo et se contente
 * de journaliser le message.
 *
 * ⚠️ Contrainte WhatsApp : un message envoyé à l'initiative de l'entreprise
 * (ce qui est le cas de tous nos rappels automatiques) DOIT utiliser un
 * modèle pré-approuvé par Meta. Le texte libre n'est autorisé que dans la
 * fenêtre de 24 h suivant un message du parent. D'où sendTemplate() comme
 * chemin principal, sendText() n'étant utilisable qu'en réponse.
 */
class WhatsAppService
{
    private ?string $phoneNumberId;

    private ?string $accessToken;

    private string $apiVersion;

    private string $countryCode;

    private bool $devMode;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->apiVersion = config('services.whatsapp.api_version', 'v21.0');
        $this->countryCode = (string) config('services.whatsapp.country_code', '225');
        $this->devMode = (bool) config('services.whatsapp.dev_mode', true);
    }

    public function isDevMode(): bool
    {
        return $this->devMode || ! $this->phoneNumberId || ! $this->accessToken;
    }

    /**
     * Envoie un modèle pré-approuvé. $params remplit les variables {{1}}, {{2}}...
     * du corps du modèle, dans l'ordre.
     *
     * @param  string  $preview  texte lisible équivalent, journalisé en mode démo
     *                           et stocké dans NotificationLog (le modèle seul
     *                           serait illisible dans l'historique).
     */
    public function sendTemplate(string $phone, string $template, array $params, string $preview, string $language = 'fr'): array
    {
        $to = $this->formatPhoneNumber($phone);

        if ($this->isDevMode()) {
            Log::info("💬 [DEV MODE] WhatsApp simulé vers {$to} (modèle {$template}) : {$preview}");

            return [
                'success' => true,
                'message_id' => 'DEV-'.uniqid(),
                'status' => 'sent',
                'dev_mode' => true,
                'recipient' => $to,
                'message' => $preview,
            ];
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($p) => ['type' => 'text', 'text' => (string) $p],
                        array_values($params)
                    ),
                ]],
            ],
        ], $to, $preview);
    }

    /**
     * Texte libre : valide uniquement dans les 24 h suivant un message du parent.
     * Hors de cette fenêtre, Meta rejette l'envoi — utiliser sendTemplate().
     */
    public function sendText(string $phone, string $message): array
    {
        $to = $this->formatPhoneNumber($phone);

        if ($this->isDevMode()) {
            Log::info("💬 [DEV MODE] WhatsApp (texte libre) simulé vers {$to} : {$message}");

            return [
                'success' => true,
                'message_id' => 'DEV-'.uniqid(),
                'status' => 'sent',
                'dev_mode' => true,
                'recipient' => $to,
                'message' => $message,
            ];
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ], $to, $message);
    }

    private function post(array $payload, string $to, string $preview): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->asJson()
                ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("✅ WhatsApp envoyé vers {$to}", ['response' => $data]);

                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'status' => 'sent',
                    'recipient' => $to,
                    'message' => $preview,
                    'response' => $data,
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Erreur inconnue';
            Log::error("❌ Échec WhatsApp vers {$to} : {$error}");

            return [
                'success' => false,
                'error' => $error,
                'status' => 'failed',
                'recipient' => $to,
                'message' => $preview,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Exception WhatsApp : '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
                'recipient' => $to,
                'message' => $preview,
            ];
        }
    }

    /**
     * Format international sans "+" ni séparateurs, attendu par l'API Meta.
     * Un numéro local ("07 07 07 07 07") est préfixé par l'indicatif pays
     * configuré (225 = Côte d'Ivoire par défaut).
     *
     * ⚠️ Le 0 initial n'est PAS retiré : depuis la migration vers 10 chiffres
     * de 2021, il fait partie du numéro ivoirien (+225 07 07 07 07 07). Le
     * retirer — comme le fait OrangeSmsService — produit un numéro invalide.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '') {
            return '';
        }

        // Déjà au format international (commence par l'indicatif) : ne rien faire.
        if (str_starts_with($digits, $this->countryCode)) {
            return $digits;
        }

        return $this->countryCode.$digits;
    }
}
