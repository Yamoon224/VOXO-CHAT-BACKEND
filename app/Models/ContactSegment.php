<?php

namespace App\Models;

use Database\Factories\ContactSegmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Segment de contacts (section 2.6) : un nom et une liste de critères simples
 * `{field, operator, value}`, évalués par `ContactSegmentService::matching()`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property list<array{field: string, operator: string, value: mixed}> $criteria
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ContactSegment extends Model
{
    /** @use HasFactory<ContactSegmentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'criteria'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }
}
