<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LoginHistoryResource;
use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart;
use App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorTrackingWidgetPerformanceTest extends TestCase
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
            'name' => 'Super Admin Perf',
            'username' => 'superadmin_perf_widget',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);
    }

    public function test_visitor_stats_overview_query_count_is_bounded_to_two_queries(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        // Seed 30 days of visitor records
        for ($i = 0; $i < 30; $i++) {
            VisitorTracking::create([
                'visitor_hash' => hash('sha256', 'perf-user-' . $i),
                'ip_address' => '10.0.0.' . ($i + 1),
                'visit_date' => Carbon::now()->subDays($i)->toDateString(),
                'visit_count' => rand(1, 10),
                'first_visited_at' => Carbon::now()->subDays($i)->setTime(8, 0),
                'last_visited_at' => Carbon::now()->subDays($i)->setTime(18, 0),
            ]);
        }

        $widget = new class extends VisitorStatsOverview {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $this->actingAs($this->superAdmin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $stats = $widget->exposeStats();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Must execute exactly 2 queries: (1) All-time totals, (2) 30-day grouped daily breakdown
        $this->assertCount(2, $queries, 'VisitorStatsOverview must execute exactly 2 database queries.');
        $this->assertCount(3, $stats);
    }

    public function test_monthly_visitors_chart_query_count_is_exactly_one_query(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        for ($i = 0; $i < 20; $i++) {
            VisitorTracking::create([
                'visitor_hash' => hash('sha256', 'chart-perf-' . $i),
                'ip_address' => '10.1.0.' . ($i + 1),
                'visit_date' => Carbon::now()->subDays($i)->toDateString(),
                'visit_count' => rand(1, 5),
                'first_visited_at' => Carbon::now()->subDays($i)->setTime(9, 0),
                'last_visited_at' => Carbon::now()->subDays($i)->setTime(17, 0),
            ]);
        }

        $chart = new class extends MonthlyVisitorsChart {
            public function exposeData(): array
            {
                return $this->getData();
            }
        };

        $this->actingAs($this->superAdmin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $data = $chart->exposeData();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Must execute exactly 1 query with GROUP BY visit_date
        $this->assertCount(1, $queries, 'MonthlyVisitorsChart must execute exactly 1 database query.');
        $this->assertStringContainsString('group by', strtolower($queries[0]['query']));
        $this->assertArrayHasKey('labels', $data);
    }

    public function test_visitor_tracking_query_plan_uses_indexes(): void
    {
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'plan-check'),
            'ip_address' => '10.0.0.1',
            'visit_date' => '2026-10-31',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-31 10:00:00',
            'last_visited_at' => '2026-10-31 10:00:00',
        ]);

        // 1. Grouped aggregation uses covering index
        $aggregationPlan = DB::select(
            'EXPLAIN QUERY PLAN select visit_date, sum(visit_count) as total_hits, count(*) as unique_visitors from visitor_trackings where visit_date >= ? group by visit_date order by visit_date asc',
            ['2026-10-01']
        );
        $aggDetail = $aggregationPlan[0]->detail ?? '';
        $this->assertStringContainsString('USING', $aggDetail);
        $this->assertStringContainsString('INDEX', $aggDetail);

        // 2. Default sort (last_visited_at desc) uses index without temp filesort
        $sortPlan = DB::select('EXPLAIN QUERY PLAN select * from visitor_trackings order by last_visited_at desc limit 10');
        $sortDetail = $sortPlan[0]->detail ?? '';
        $this->assertStringContainsString('USING INDEX', $sortDetail);
        $this->assertStringNotContainsString('USE TEMP B-TREE', $sortDetail);
    }

    public function test_performance_indexes_exist_on_visitor_trackings_table(): void
    {
        $indexes = DB::select("PRAGMA index_list('visitor_trackings')");
        $indexNames = array_map(fn ($idx) => $idx->name, $indexes);

        $this->assertContains('visitor_trackings_last_visited_at_index', $indexNames);
        $this->assertContains('visitor_trackings_visit_date_visit_count_index', $indexNames);
    }

    public function test_login_history_resource_eager_loads_user_relation(): void
    {
        $user = User::create([
            'name' => 'Eager User',
            'username' => 'eager_user_test',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        for ($i = 0; $i < 5; $i++) {
            LoginHistory::create([
                'user_id' => $user->id,
                'attempted_identifier' => 'eager_user_test',
                'status' => LoginHistory::STATUS_SUCCESS,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PerfTest/1.0',
                'login_at' => now(),
            ]);
        }

        $query = LoginHistoryResource::getEloquentQuery();
        $eagerLoads = $query->getEagerLoads();

        $this->assertArrayHasKey('user', $eagerLoads, 'LoginHistoryResource must eager load user relation.');
    }
}
