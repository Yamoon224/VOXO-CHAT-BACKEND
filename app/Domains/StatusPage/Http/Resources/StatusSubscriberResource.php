<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Models\StatusSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StatusSubscriber */
class StatusSubscriberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'created_at' => $this->created_at];
    }
}
