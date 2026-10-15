<?php

namespace App\Domains\Channels\Support;

/**
 * Les cinq canaux de messagerie du lot 5 (section 2.5, P2) : la forme des
 * identifiants varie par prestataire, centralisée ici pour que la requête de
 * validation, le service de connexion et les pilotes concrets restent
 * d'accord sans se copier les clés les uns des autres.
 */
enum MessagingChannel: string
{
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Telegram = 'telegram';
    case Sms = 'sms';

    /**
     * @return list<string> Clés de `credentials` requises pour activer le
     *                      canal. `app_secret` (Meta) sert à vérifier
     *                      `X-Hub-Signature-256` sur chaque webhook entrant —
     *                      sans lui, n'importe qui pourrait usurper un
     *                      visiteur en postant directement sur l'URL publique.
     *                      `auth_token` (SMS) sert pareil pour `X-Twilio-Signature`.
     *                      Telegram n'a pas de secret saisi par l'espace : le
     *                      sien (`secret_token`) est engendré à la création,
     *                      voir `usesSecretToken()`.
     */
    public function requiredCredentialFields(): array
    {
        return match ($this) {
            self::WhatsApp => ['access_token', 'phone_number_id', 'app_secret'],
            self::Messenger, self::Instagram => ['page_access_token', 'app_secret'],
            self::Telegram => ['bot_token'],
            self::Sms => ['account_sid', 'auth_token', 'from_number'],
        };
    }

    /**
     * WhatsApp/Messenger/Instagram passent par le webhook unifié de Meta, qui
     * exige une poignée de main GET (`hub.verify_token`) avant d'accepter
     * l'URL dans la console de l'app — Telegram et le SMS n'en ont pas besoin.
     */
    public function usesMetaWebhookHandshake(): bool
    {
        return match ($this) {
            self::WhatsApp, self::Messenger, self::Instagram => true,
            self::Telegram, self::Sms => false,
        };
    }

    /**
     * Telegram vérifie chaque webhook entrant par un en-tête secret fixé lors
     * de l'appel à `setWebhook` (`secret_token`), engendré ici comme
     * `verify_token` pour Meta — jamais saisi par l'espace, juste à reporter
     * dans cet appel.
     */
    public function usesSecretToken(): bool
    {
        return $this === self::Telegram;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
