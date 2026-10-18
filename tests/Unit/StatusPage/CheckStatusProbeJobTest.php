<?php

namespace Tests\Unit\StatusPage;

use App\Domains\StatusPage\Jobs\CheckStatusProbeJob;
use App\Domains\StatusPage\Providers\ArrayProbeChecker;
use App\Models\StatusProbe;
use App\Models\StatusProbeCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckStatusProbeJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_verification_reussie_passe_la_sonde_a_up_et_remet_les_echecs_a_zero(): void
    {
        $probe = StatusProbe::factory()->create(['status' => 'down', 'consecutive_failures' => 3]);

        app()->call([new CheckStatusProbeJob($probe->id), 'handle']);

        $probe->refresh();
        $this->assertSame('up', $probe->status->value);
        $this->assertSame(0, $probe->consecutive_failures);
        $this->assertNotNull($probe->last_checked_at);
        $this->assertSame(1, $probe->checks()->count());
    }

    #[Test]
    public function une_verification_en_echec_incremente_les_echecs_consecutifs(): void
    {
        $probe = StatusProbe::factory()->create(['status' => 'up', 'consecutive_failures' => 0]);
        app(ArrayProbeChecker::class)->fail($probe->url);

        app()->call([new CheckStatusProbeJob($probe->id), 'handle']);

        $probe->refresh();
        $this->assertSame('down', $probe->status->value);
        $this->assertSame(1, $probe->consecutive_failures);
    }

    #[Test]
    public function une_sonde_supprimee_entre_la_mise_en_file_et_l_execution_ne_fait_rien(): void
    {
        app()->call([new CheckStatusProbeJob('00000000-0000-0000-0000-000000000000'), 'handle']);

        $this->assertSame(0, StatusProbeCheck::query()->count());
    }
}
