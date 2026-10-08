<?php

namespace Tests\Unit\Widget;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class WidgetSettingsBusinessHoursTest extends TestCase
{
    #[Test]
    public function sans_horaires_regles_le_widget_est_toujours_ouvert(): void
    {
        $settings = ModelFactory::widgetSettings();

        $this->assertTrue($settings->isOpenAt(Carbon::now()));
    }

    #[Test]
    public function en_dehors_du_creneau_regle_le_widget_est_ferme(): void
    {
        $settings = ModelFactory::widgetSettings([
            'business_hours' => json_encode([['day' => 1, 'opens_at' => '09:00', 'closes_at' => '18:00']]),
        ]);

        // Lundi (1) 20h : hors créneau.
        $this->assertFalse($settings->isOpenAt(Carbon::parse('2026-10-05 20:00')));
        // Lundi (1) 10h : dans le créneau.
        $this->assertTrue($settings->isOpenAt(Carbon::parse('2026-10-05 10:00')));
    }
}
