<?php

namespace App\Domains\Channels\Http\Resources;

use App\Models\ChannelConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChannelConnection
 *
 * Les identifiants sont masqués à la relecture (sauf `verify_token` et
 * `secret_token`, que l'espace doit reporter dans la console Meta ou l'appel
 * `setWebhook` de Telegram) : un jeton d'accès ne doit pas repasser en clair
 * une fois enregistré.
 */
class ChannelConnectionResource extends JsonResource
{
    private const UNMASKED_KEYS = ['verify_token', 'secret_token'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $credentials = [];

        foreach ($this->credentials as $key => $value) {
            $credentials[$key] = in_array($key, self::UNMASKED_KEYS, true) ? $value : $this->mask($value);
        }

        return ['channel' => $this->channel, 'enabled' => $this->enabled, 'credentials' => $credentials];
    }

    private function mask(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        return mb_strlen($value) <= 4 ? '••••' : '••••'.mb_substr($value, -4);
    }
}
