<?php

namespace App\Domains\Channels\Http\Resources;

use App\Models\ChannelEmailSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChannelEmailSettings */
class ChannelEmailSettingsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'enabled' => $this->enabled,
            'reply_from_email' => $this->reply_from_email,
            'reply_from_name' => $this->reply_from_name,
        ];
    }
}
