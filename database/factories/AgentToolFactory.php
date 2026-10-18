<?php

namespace Database\Factories;

use App\Domains\Assistant\Enums\AgentToolHttpMethod;
use App\Models\AgentTool;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgentTool> */
class AgentToolFactory extends Factory
{
    protected $model = AgentTool::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'lookup_order',
            'description' => "Consulte le statut d'une commande à partir de son identifiant.",
            'http_method' => AgentToolHttpMethod::Get,
            'url' => 'https://exemple.fr/api/orders/{order_id}',
            'headers' => ['Authorization' => 'Bearer secret-token'],
            'parameters' => [
                ['name' => 'order_id', 'type' => 'string', 'description' => 'Identifiant de la commande', 'required' => true, 'in' => 'path'],
            ],
            'enabled' => true,
        ];
    }
}
