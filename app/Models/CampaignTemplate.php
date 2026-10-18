<?php

namespace App\Models;

use App\Domains\Campaigns\Enums\CampaignChannel;
use Database\Factories\CampaignTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modèle de campagne (section 2.9) : objet et corps réutilisables, avec
 * `{{name}}`/`{{email}}` substitués à l'envoi — voir `CampaignTemplate::render()`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property CampaignChannel $channel
 * @property string|null $subject
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CampaignTemplate extends Model
{
    /** @use HasFactory<CampaignTemplateFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'channel', 'subject', 'body'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['channel' => CampaignChannel::class];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * `\r`/`\n` sont retirés des valeurs substituées, jamais du modèle
     * lui-même : un nom de contact ne doit pas pouvoir injecter un en-tête
     * de courriel supplémentaire.
     *
     * @param  array<string, string>  $variables
     */
    public function renderSubject(array $variables): ?string
    {
        if ($this->subject === null) {
            return null;
        }

        return $this->interpolate($this->subject, array_map(fn (string $value) => str_replace(["\r", "\n"], '', $value), $variables));
    }

    /**
     * Pour un canal en texte brut (chat) : les valeurs substituées ne sont
     * jamais interprétées comme du HTML côté destinataire.
     *
     * @param  array<string, string>  $variables
     */
    public function renderBody(array $variables): string
    {
        return $this->interpolate($this->body, $variables);
    }

    /**
     * Pour un envoi e-mail HTML : les valeurs substituées (ex. le nom d'un
     * contact, que n'importe quel visiteur du widget peut renseigner lui-même)
     * sont échappées avant d'être insérées dans le corps — seul le texte
     * statique du modèle, écrit par l'équipe, reste interprété tel quel.
     *
     * @param  array<string, string>  $variables
     */
    public function renderBodyForEmail(array $variables): string
    {
        return $this->interpolate($this->body, array_map(fn (string $value) => e($value), $variables));
    }

    /** @param  array<string, string>  $variables */
    private function interpolate(string $text, array $variables): string
    {
        $search = array_map(fn (string $key) => '{{'.$key.'}}', array_keys($variables));

        return str_replace($search, array_values($variables), $text);
    }
}
