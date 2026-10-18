<?php

namespace App\Models;

use App\Domains\Assistant\Enums\AgentToolHttpMethod;
use Database\Factories\AgentToolFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Outil tiers déclaré par l'équipe, que l'agent IA peut appeler pendant une
 * conversation (section 2.2, P3) — voir `AgentToolCaller`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string $description
 * @property AgentToolHttpMethod $http_method
 * @property string $url
 * @property array<string, string>|null $headers
 * @property list<array{name: string, type: string, description: string, required: bool, in: string}> $parameters
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AgentTool extends Model
{
    /** @use HasFactory<AgentToolFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'description', 'http_method', 'url', 'headers', 'parameters', 'enabled'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'http_method' => AgentToolHttpMethod::class,
            'headers' => 'array',
            'parameters' => 'array',
            'enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
