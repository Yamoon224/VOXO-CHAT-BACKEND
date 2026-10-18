<?php

namespace Tests\Unit\StatusPage;

use App\Domains\StatusPage\Jobs\CheckStatusProbeJob;
use App\Models\StatusProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckStatusProbesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seules_les_sondes_dues_sont_mises_en_file(): void
    {
        Queue::fake();
        $due = StatusProbe::factory()->create(['check_interval_minutes' => 5, 'last_checked_at' => now()->subMinutes(10)]);
        $notDue = StatusProbe::factory()->create(['check_interval_minutes' => 5, 'last_checked_at' => now()->subMinute()]);
        $neverChecked = StatusProbe::factory()->create(['last_checked_at' => null]);

        $this->artisan('status-page:check-probes')->assertSuccessful();

        Queue::assertPushed(CheckStatusProbeJob::class, fn (CheckStatusProbeJob $job) => $job->probeId === $due->id);
        Queue::assertPushed(CheckStatusProbeJob::class, fn (CheckStatusProbeJob $job) => $job->probeId === $neverChecked->id);
        Queue::assertNotPushed(CheckStatusProbeJob::class, fn (CheckStatusProbeJob $job) => $job->probeId === $notDue->id);
    }
}
