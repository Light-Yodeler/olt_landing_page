<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart;
use App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview;
use App\Models\User;
use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorTrackingWidgetBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        $this->superAdmin = User::create([
            'name' => 'Super Admin Boundary',
            'username' => 'superadmin_boundary_widget',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);
    }

    public function test_widgets_render_safely_with_empty_database_zero_records(): void
    {
        $this->assertSame(0, VisitorTracking::count());

        Livewire::actingAs($this->superAdmin)
            ->test(VisitorStatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Total Visits (All-Time)')
            ->assertSee('0');

        $chart = new class extends MonthlyVisitorsChart {
            public function exposeData(): array
            {
                return $this->getData();
            }
        };

        $this->actingAs($this->superAdmin);
        $data = $chart->exposeData();

        $this->assertCount(30, $data['labels']);
        $this->assertSame(array_fill(0, 30, 0), $data['datasets'][0]['data']);
        $this->assertSame(array_fill(0, 30, 0), $data['datasets'][1]['data']);
    }

    public function test_30_day_cutoff_exact_boundary_conditions(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        // subDays(29) = 2026-10-02 (day 30 of the 30-day window, MUST be included)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'day-29-boundary'),
            'visit_date' => '2026-10-02',
            'visit_count' => 5,
            'first_visited_at' => '2026-10-02 10:00:00',
            'last_visited_at' => '2026-10-02 10:00:00',
        ]);

        // subDays(30) = 2026-10-01 (day 31, MUST be excluded from 30-day stats)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'day-30-boundary'),
            'visit_date' => '2026-10-01',
            'visit_count' => 10,
            'first_visited_at' => '2026-10-01 10:00:00',
            'last_visited_at' => '2026-10-01 10:00:00',
        ]);

        $widget = new class extends VisitorStatsOverview {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $this->actingAs($this->superAdmin);
        $stats = $widget->exposeStats();

        // All-time hits = 15
        $this->assertSame('15', $stats[0]->getValue());

        // 30-day hits = 5 (excludes the 10 from 2026-10-01)
        $this->assertSame('5', $stats[1]->getValue());
        $this->assertStringContainsString('1 Unique Visitors (30 Days)', $stats[1]->getDescription());
    }

    public function test_sparkline_7_day_window_alignment(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        // subDays(6) = 2026-10-25 (index 0 of sparkline)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'day-6-sparkline'),
            'visit_date' => '2026-10-25',
            'visit_count' => 3,
            'first_visited_at' => '2026-10-25 10:00:00',
            'last_visited_at' => '2026-10-25 10:00:00',
        ]);

        // subDays(7) = 2026-10-24 (outside 7-day sparkline window)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'day-7-outside'),
            'visit_date' => '2026-10-24',
            'visit_count' => 9,
            'first_visited_at' => '2026-10-24 10:00:00',
            'last_visited_at' => '2026-10-24 10:00:00',
        ]);

        $widget = new class extends VisitorStatsOverview {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $this->actingAs($this->superAdmin);
        $stats = $widget->exposeStats();
        $chart = $stats[1]->getChart();

        $this->assertCount(7, $chart);
        $this->assertSame(3, $chart[0]); // Day 2026-10-25 has 3 hits
        // Day 2026-10-24 (9 hits) is not in the sparkline
        $this->assertNotContains(9, $chart);
    }

    public function test_month_and_year_transition_boundary(): void
    {
        Carbon::setTestNow('2026-01-05 10:00:00');

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'dec-user'),
            'visit_date' => '2025-12-31',
            'visit_count' => 4,
            'first_visited_at' => '2025-12-31 10:00:00',
            'last_visited_at' => '2025-12-31 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'jan-user'),
            'visit_date' => '2026-01-01',
            'visit_count' => 6,
            'first_visited_at' => '2026-01-01 10:00:00',
            'last_visited_at' => '2026-01-01 10:00:00',
        ]);

        $chart = new class extends MonthlyVisitorsChart {
            public function exposeData(): array
            {
                return $this->getData();
            }
        };

        $this->actingAs($this->superAdmin);
        $data = $chart->exposeData();

        $this->assertContains('31 Dec', $data['labels']);
        $this->assertContains('01 Jan', $data['labels']);

        $decIndex = array_search('31 Dec', $data['labels'], true);
        $janIndex = array_search('01 Jan', $data['labels'], true);

        $this->assertSame(4, $data['datasets'][0]['data'][$decIndex]);
        $this->assertSame(6, $data['datasets'][0]['data'][$janIndex]);
    }

    public function test_visitor_tracking_table_date_filter_boundary_single_day_and_reversed(): void
    {
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'boundary-15'),
            'visit_date' => '2026-10-15',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-15 10:00:00',
            'last_visited_at' => '2026-10-15 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'boundary-20'),
            'visit_date' => '2026-10-20',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-20 10:00:00',
            'last_visited_at' => '2026-10-20 10:00:00',
        ]);

        // Single day filter: 2026-10-15 to 2026-10-15
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-15',
                'date_until' => '2026-10-15',
            ])
            ->assertCountTableRecords(1);

        // Reversed range: 2026-10-25 to 2026-10-10
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-25',
                'date_until' => '2026-10-10',
            ])
            ->assertCountTableRecords(0);
    }
}
