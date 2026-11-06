<?php

namespace App\Services\Sms;

/**
 * Contrat commun aux passerelles d'envoi de SMS.
 */
interface SmsGatewayInterface
{
    /**
     * Envoyer un SMS
     *
     * @return array ['success' => bool, 'external_id' => ?string, 'error' => ?string]
     */
    public function send(string $to, string $message, ?string $senderName = null): array;

    /**
     * Vérifier si le gateway est configuré
     */
    public function isConfigured(): bool;
}
