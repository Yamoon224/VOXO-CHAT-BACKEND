<?php

namespace App\Domains\Widget\Http\Resources;

use App\Models\WidgetSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue publique : ce que le widget a besoin de savoir pour s'afficher, rien
 * de plus.
 *
 * @mixin WidgetSettings
 */
class PublicWidgetSettingsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'primary_color' => $this->primary_color,
            'logo_url' => $this->logo_url,
            'position' => $this->position->value,
            'welcome_message' => $this->welcome_message,
            'language' => $this->language,
            'is_open_now' => $this->isOpenAt(now()),
            'offline_message' => $this->offline_message,
            'hide_branding' => $this->hide_branding,
            'realtime' => $this->realtimeConnection(),
        ];
    }

    /**
     * Paramètres de connexion Reverb (section 4.1), jamais secrets — même
     * nature qu'une clé d'app Pusher publique. `null` si aucun serveur
     * Reverb n'est configuré (`BROADCAST_CONNECTION` autre que `reverb`) :
     * le widget retombe alors sur le sondage, comme avant ce lot.
     *
     * @return array{key: string, host: string, port: int, scheme: string}|null
     */
    private function realtimeConnection(): ?array
    {
        if ((string) config('broadcasting.default') !== 'reverb') {
            return null;
        }

        $key = config('broadcasting.connections.reverb.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        return [
            'key' => $key,
            'host' => (string) config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'scheme' => (string) config('broadcasting.connections.reverb.options.scheme'),
        ];
    }
}
