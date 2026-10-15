<?php

namespace App\Domains\Channels\Support;

use Illuminate\Http\Request;

/**
 * Authentifie un webhook entrant (lot 5) : sans elle, n'importe qui pourrait
 * usurper un visiteur en postant directement sur l'URL publique d'un canal
 * avec le numéro/l'identifiant de son choix. Chaque prestataire a son propre
 * schéma de signature, tous vérifiés en temps constant (`hash_equals`).
 */
final class InboundSignatureVerifier
{
    /**
     * WhatsApp, Messenger, Instagram : `X-Hub-Signature-256`, HMAC-SHA256 du
     * corps brut avec `app_secret`.
     *
     * @param  array<string, mixed>  $credentials
     */
    public static function metaSignatureValid(Request $request, array $credentials): bool
    {
        $secret = $credentials['app_secret'] ?? null;
        $header = (string) $request->header('X-Hub-Signature-256', '');

        if (! is_string($secret) || $secret === '' || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, substr($header, strlen('sha256=')));
    }

    /**
     * Telegram : `X-Telegram-Bot-Api-Secret-Token`, fixé lors de l'appel à `setWebhook` (`secret_token`).
     *
     * @param  array<string, mixed>  $credentials
     */
    public static function telegramSecretValid(Request $request, array $credentials): bool
    {
        $secret = $credentials['secret_token'] ?? null;

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        return hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''));
    }

    /**
     * SMS (forme Twilio-like) : `X-Twilio-Signature`, HMAC-SHA1 en base64 de
     * l'URL complète suivie des paramètres du formulaire triés par clé et
     * concaténés clé+valeur, avec `auth_token`.
     *
     * @param  array<string, mixed>  $credentials
     */
    public static function twilioSignatureValid(Request $request, array $credentials): bool
    {
        $authToken = $credentials['auth_token'] ?? null;
        $header = (string) $request->header('X-Twilio-Signature', '');

        if (! is_string($authToken) || $authToken === '' || $header === '') {
            return false;
        }

        $params = $request->request->all();
        ksort($params);

        $data = $request->fullUrl();
        foreach ($params as $key => $value) {
            $data .= ((string) $key).(is_scalar($value) ? (string) $value : '');
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $header);
    }
}
