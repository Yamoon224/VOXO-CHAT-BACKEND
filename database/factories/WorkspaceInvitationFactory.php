<?php

namespace Database\Factories;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkspaceInvitation> */
class WorkspaceInvitationFactory extends Factory
{
    protected $model = WorkspaceInvitation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => WorkspaceRole::Agent,
            'token_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }
}
