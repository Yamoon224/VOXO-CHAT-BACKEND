<?php

namespace Database\Factories;

use App\Models\ChannelConnection;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChannelConnection> */
class ChannelConnectionFactory extends Factory
{
    protected $model = ChannelConnection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'channel' => 'whatsapp',
            'enabled' => false,
            'credentials' => [],
        ];
    }
}
