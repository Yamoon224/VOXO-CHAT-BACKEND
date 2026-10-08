<?php

namespace App\Domains\Users\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'email_verified' => $this->hasVerifiedEmail(),
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
