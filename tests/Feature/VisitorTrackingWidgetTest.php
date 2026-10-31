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

class VisitorTrackingWidgetTest extends TestCase
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
            'name' => 'Super Admin Test',
            'username' => 'superadmin_widget_test',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);
    }

    public function test_superadmin_can_mount_and_view_visitor_stats_overview_widget(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-1'),
            'ip_address' => '203.0.113.10',
            'visit_date' => '2026-10-31',
            'visit_count' => 5,
            'first_visited_at' => '2026-10-31 08:00:00',
            'last_visited_at' => '2026-10-31 11:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-2'),
            'ip_address' => '203.0.113.20',
            'visit_date' => '2026-10-30',
            'visit_count' => 3,
            'first_visited_at' => '2026-10-30 09:00:00',
            'last_visited_at' => '2026-10-30 10:00:00',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(VisitorStatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Total Visits (All-Time)')
            ->assertSee('Visits (Last 30 Days)')
            ->assertSee('Visits Today (WITA)');
    }

    public function test_visitor_stats_overview_computes_exact_counts_and_sparkline(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        // Today: 2 records (5 hits + 3 hits = 8 hits, 2 unique)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'user-today-1'),
            'visit_date' => '2026-10-31',
            'visit_count' => 5,
            'first_visited_at' => '2026-10-31 08:00:00',
            'last_visited_at' => '2026-10-31 09:00:00',
        ]);
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'user-today-2'),
            'visit_date' => '2026-10-31',
            'visit_count' => 3,
            'first_visited_at' => '2026-10-31 10:00:00',
            'last_visited_at' => '2026-10-31 11:00:00',
        ]);

        // Yesterday: 1 record (4 hits)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'user-yesterday'),
            'visit_date' => '2026-10-30',
            'visit_count' => 4,
            'first_visited_at' => '2026-10-30 10:00:00',
            'last_visited_at' => '2026-10-30 11:00:00',
        ]);

        // 10 days ago: 1 record (2 hits)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'user-10-days-ago'),
            'visit_date' => '2026-10-21',
            'visit_count' => 2,
            'first_visited_at' => '2026-10-21 10:00:00',
            'last_visited_at' => '2026-10-21 11:00:00',
        ]);

        // 35 days ago (outside 30-day window): 1 record (10 hits)
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'user-35-days-ago'),
            'visit_date' => '2026-09-26',
            'visit_count' => 10,
            'first_visited_at' => '2026-09-26 10:00:00',
            'last_visited_at' => '2026-09-26 11:00:00',
        ]);

        $widget = new class extends VisitorStatsOverview {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $this->actingAs($this->superAdmin);
        $stats = $widget->exposeStats();

        $this->assertCount(3, $stats);

        // Stat 1: All-Time total hits = 8 + 4 + 2 + 10 = 24; unique = 5
        $this->assertSame('Total Visits (All-Time)', $stats[0]->getLabel());
        $this->assertSame('24', $stats[0]->getValue());
        $this->assertStringContainsString('5 Unique Visitors', $stats[0]->getDescription());

        // Stat 2: 30-day hits = 8 + 4 + 2 = 14; 30-day unique = 4 (excludes 35-day record)
        $this->assertSame('Visits (Last 30 Days)', $stats[1]->getLabel());
        $this->assertSame('14', $stats[1]->getValue());
        $this->assertStringContainsString('4 Unique Visitors (30 Days)', $stats[1]->getDescription());

        // Sparkline array has 7 values: [..., 2 hits (10d ago not in 7d), yesterday (4), today (8)]
        $chart = $stats[1]->getChart();
        $this->assertCount(7, $chart);
        $this->assertSame(4, $chart[5]); // Yesterday
        $this->assertSame(8, $chart[6]); // Today

        // Stat 3: Today hits = 8; today unique = 2
        $this->assertSame('Visits Today (WITA)', $stats[2]->getLabel());
        $this->assertSame('8', $stats[2]->getValue());
        $this->assertStringContainsString('2 Unique Visitors Today', $stats[2]->getDescription());
        $this->assertSame('info', $stats[2]->getColor());
    }

    public function test_superadmin_can_mount_and_view_monthly_visitors_chart_widget(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'chart-user'),
            'ip_address' => '203.0.113.50',
            'visit_date' => '2026-10-31',
            'visit_count' => 6,
            'first_visited_at' => '2026-10-31 08:00:00',
            'last_visited_at' => '2026-10-31 11:00:00',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(MonthlyVisitorsChart::class)
            ->assertSuccessful()
            ->assertSee('Monthly Visitors Trend (Last 30 Days)');
    }

    public function test_monthly_visitors_chart_data_structure_and_30_day_completeness(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'chart-v1'),
            'visit_date' => '2026-10-31',
            'visit_count' => 7,
            'first_visited_at' => '2026-10-31 08:00:00',
            'last_visited_at' => '2026-10-31 11:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'chart-v2'),
            'visit_date' => '2026-10-31',
            'visit_count' => 3,
            'first_visited_at' => '2026-10-31 09:00:00',
            'last_visited_at' => '2026-10-31 10:00:00',
        ]);

        $chart = new class extends MonthlyVisitorsChart {
            public function exposeData(): array
            {
                return $this->getData();
            }
        };

        $this->actingAs($this->superAdmin);
        $data = $chart->exposeData();

        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertCount(30, $data['labels']);
        $this->assertSame('31 Oct', end($data['labels']));

        $this->assertCount(2, $data['datasets']);
        $this->assertSame('Total Visits (Hits)', $data['datasets'][0]['label']);
        $this->assertSame('Unique Visitors', $data['datasets'][1]['label']);

        // Today's total hits = 7 + 3 = 10; unique = 2
        $hits = end($data['datasets'][0]['data']);
        $unique = end($data['datasets'][1]['data']);
        $this->assertSame(10, $hits);
        $this->assertSame(2, $unique);
    }

    public function test_list_visitor_trackings_page_renders_header_widgets_for_superadmin(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertSeeLivewire(VisitorStatsOverview::class)
            ->assertSeeLivewire(MonthlyVisitorsChart::class);
    }
}
