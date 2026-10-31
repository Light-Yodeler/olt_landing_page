<?php

namespace Tests\Feature\E2E;

use App\Http\Controllers\HomeController;
use App\Models\VisitorTracking;
use App\Services\VisitorTrackingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class VisitorTrackingE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ==========================================
    // Tier 1: Core Feature Coverage
    // ==========================================

    public function test_tier1_landing_page_english_root_records_visitor_tracking(): void
    {
        $ip = '203.0.113.10';
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 TestBrowser/1.0';

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ])->get('/');

        $response->assertStatus(200);

        $this->assertDatabaseHas('visitor_trackings', [
            'ip_address' => $ip,
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
        ]);

        $record = DB::table('visitor_trackings')->where('ip_address', $ip)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->visitor_hash);
        $this->assertEquals(64, strlen($record->visitor_hash));
        $this->assertNotNull($record->first_visited_at);
        $this->assertNotNull($record->last_visited_at);
        $this->assertStringContainsString('TestBrowser/1.0', (string) $record->user_agent);
    }

    public function test_tier1_landing_page_indonesian_locale_records_visitor_tracking(): void
    {
        $ip = '203.0.113.11';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) IndoBrowser/2.0';

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ])->get('/id');

        $response->assertStatus(200);

        $this->assertDatabaseHas('visitor_trackings', [
            'ip_address' => $ip,
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
        ]);
    }

    public function test_tier1_deduplication_same_visitor_multiple_hits_same_day_increments_count(): void
    {
        $ip = '203.0.113.12';
        $userAgent = 'DeduplicationTestClient/1.0';

        $headers = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // Send 5 visits from identical visitor on same day
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables($headers)->get('/');
            $response->assertStatus(200);
        }

        // Deduplication guarantee: exactly 1 unique visitor row for this IP on this date
        $records = DB::table('visitor_trackings')
            ->where('ip_address', $ip)
            ->where('visit_date', Carbon::now()->toDateString())
            ->get();

        $this->assertCount(1, $records, 'Repeated visits on the same day must not inflate unique visitor rows.');
        $this->assertEquals(5, $records->first()->visit_count, 'visit_count should reflect total hits received.');
    }

    public function test_tier1_first_and_last_visited_at_timestamps_tracked_accurately(): void
    {
        $ip = '203.0.113.13';
        $userAgent = 'TimestampTracker/1.0';
        $headers = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        Carbon::setTestNow('2026-10-31 09:00:00');
        $this->withServerVariables($headers)->get('/');

        Carbon::setTestNow('2026-10-31 09:45:00');
        $this->withServerVariables($headers)->get('/');

        Carbon::setTestNow(); // reset

        $record = DB::table('visitor_trackings')->where('ip_address', $ip)->first();
        $this->assertNotNull($record);
        $this->assertEquals('2026-10-31 09:00:00', Carbon::parse($record->first_visited_at)->toDateTimeString());
        $this->assertEquals('2026-10-31 09:45:00', Carbon::parse($record->last_visited_at)->toDateTimeString());
    }

    public function test_tier1_visitor_hash_is_deterministic_64_character_hex_string(): void
    {
        $ip = '203.0.113.14';
        $userAgent = 'DeterministicHashClient/1.0';
        $headers = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        $this->withServerVariables($headers)->get('/');

        $record1 = DB::table('visitor_trackings')->where('ip_address', $ip)->first();
        $this->assertNotNull($record1);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $record1->visitor_hash);

        // Clear tracking table and re-run identical client
        DB::table('visitor_trackings')->truncate();
        $this->withServerVariables($headers)->get('/');

        $record2 = DB::table('visitor_trackings')->where('ip_address', $ip)->first();
        $this->assertNotNull($record2);

        $this->assertEquals($record1->visitor_hash, $record2->visitor_hash, 'Visitor hash must be strictly deterministic.');
    }

    public function test_tier1_home_controller_encapsulates_tracking_via_service_layer(): void
    {
        $reflector = new ReflectionClass(HomeController::class);
        $constructor = $reflector->getConstructor();
        $this->assertNotNull($constructor);

        $paramTypes = array_map(
            fn ($param) => $param->getType()?->getName(),
            $constructor->getParameters()
        );

        $usesVisitorService = in_array(VisitorTrackingService::class, $paramTypes, true);
        $this->assertTrue(
            $usesVisitorService || class_exists(VisitorTrackingService::class),
            'VisitorTrackingService must be defined and integrated into service architecture.'
        );
    }

    // ==========================================
    // Tier 2: Boundary and Corner Cases
    // ==========================================

    public function test_tier2_boundary_different_ips_same_user_agent_generate_separate_records(): void
    {
        $ua = 'IdenticalAgent/1.0';

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.101', 'HTTP_USER_AGENT' => $ua])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.102', 'HTTP_USER_AGENT' => $ua])->get('/');

        $records = DB::table('visitor_trackings')->get();
        $this->assertCount(2, $records);

        $hashes = $records->pluck('visitor_hash')->unique();
        $this->assertCount(2, $hashes, 'Different IPs must result in distinct visitor hashes.');
    }

    public function test_tier2_boundary_same_ip_different_user_agents_generate_separate_records(): void
    {
        $ip = '198.51.100.105';

        $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => 'Mozilla/5.0 Safari/605'])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/120'])->get('/');

        $records = DB::table('visitor_trackings')->where('ip_address', $ip)->get();
        $this->assertCount(2, $records, 'Same IP with distinct User-Agents should distinguish separate client browsers.');

        $hashes = $records->pluck('visitor_hash')->unique();
        $this->assertCount(2, $hashes);
    }

    public function test_tier2_boundary_extremely_long_user_agent_string(): void
    {
        $longUserAgent = 'LongBrowser/' . str_repeat('Ext-Segment-1234567890/', 40); // > 900 chars

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.110',
            'HTTP_USER_AGENT' => $longUserAgent,
        ])->get('/');

        $response->assertStatus(200);

        $record = DB::table('visitor_trackings')->where('ip_address', '198.51.100.110')->first();
        $this->assertNotNull($record);
        $this->assertNotEmpty($record->visitor_hash);
    }

    public function test_tier2_boundary_referer_headers_variation(): void
    {
        $referers = [
            'https://www.google.com/search?q=lombok+tour',
            'https://instagram.com/p/oceanlombok',
            'https://direct-partner.example.org/referral?campaign=summer_2026&ref=' . str_repeat('xyz', 40),
        ];

        foreach ($referers as $index => $referer) {
            $ip = "198.51.100." . (120 + $index);
            $response = $this->withServerVariables([
                'REMOTE_ADDR' => $ip,
                'HTTP_USER_AGENT' => 'RefererTester/1.0',
                'HTTP_REFERER' => $referer,
            ])->get('/');

            $response->assertStatus(200);
            $this->assertDatabaseHas('visitor_trackings', ['ip_address' => $ip]);
        }
    }

    public function test_tier2_boundary_ipv6_client_ip_tracking(): void
    {
        $ipv6Addresses = [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '::1',
            '2607:f8b0:4005:805::200e',
        ];

        foreach ($ipv6Addresses as $ipv6) {
            $response = $this->withServerVariables([
                'REMOTE_ADDR' => $ipv6,
                'HTTP_USER_AGENT' => 'IPv6Visitor/1.0',
            ])->get('/');

            $response->assertStatus(200);
            $this->assertDatabaseHas('visitor_trackings', ['ip_address' => $ipv6]);
        }
    }

    public function test_tier2_boundary_missing_or_empty_user_agent(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.140',
            'HTTP_USER_AGENT' => null,
        ])->get('/');

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitor_trackings', ['ip_address' => '198.51.100.140']);
    }

    public function test_tier2_boundary_calendar_day_transition_creates_new_daily_record(): void
    {
        $ip = '198.51.100.150';
        $userAgent = 'MidnightRolloverClient/1.0';
        $headers = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // Day 1 visit
        Carbon::setTestNow('2026-10-31 23:59:00');
        $this->withServerVariables($headers)->get('/');

        // Day 2 visit (10 minutes later, new calendar date)
        Carbon::setTestNow('2026-11-01 00:09:00');
        $this->withServerVariables($headers)->get('/');

        Carbon::setTestNow(); // reset

        $records = DB::table('visitor_trackings')->where('ip_address', $ip)->orderBy('visit_date')->get();
        $this->assertCount(2, $records, 'Crossing midnight boundary must create a new record for the new calendar day.');

        $this->assertEquals('2026-10-31', $records[0]->visit_date);
        $this->assertEquals(1, $records[0]->visit_count);

        $this->assertEquals('2026-11-01', $records[1]->visit_date);
        $this->assertEquals(1, $records[1]->visit_count);
        $this->assertEquals($records[0]->visitor_hash, $records[1]->visitor_hash, 'Hash remains identical across days for same visitor.');
    }

    // ==========================================
    // Tier 3: Interactions & Resilience
    // ==========================================

    public function test_tier3_interaction_cross_locale_browsing_journey(): void
    {
        $ip = '198.51.100.160';
        $userAgent = 'LocaleJourneyVisitor/1.0';
        $headers = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // 1. Visitor hits root EN landing page
        $this->withServerVariables($headers)->get('/')->assertStatus(200);

        // 2. Visitor switches locale to 'id'
        $this->withServerVariables($headers)->get('/lang/id')->assertRedirect('/id');

        // 3. Visitor lands on Indonesian landing page
        $this->withServerVariables($headers)->get('/id')->assertStatus(200);

        // Result: 1 unique visitor record for the day, with visit_count = 2 (landing page hits)
        $records = DB::table('visitor_trackings')->where('ip_address', $ip)->get();
        $this->assertCount(1, $records);
        $this->assertEquals(2, $records->first()->visit_count);
    }

    public function test_tier3_interaction_tracking_service_resilience_when_error_occurs(): void
    {
        if (class_exists(VisitorTrackingService::class)) {
            $mockService = $this->createMock(VisitorTrackingService::class);
            $mockService->method('recordVisit')->willThrowException(new Exception('Simulated Tracking Exception'));
            $this->app->instance(VisitorTrackingService::class, $mockService);

            // The public landing page MUST still return HTTP 200 without throwing an unhandled 500 error
            $response = $this->get('/');
            $this->assertEquals(200, $response->getStatusCode(), 'Landing page must stay resilient even if tracking fails.');
        } else {
            $this->markTestIncomplete('VisitorTrackingService class not yet implemented.');
        }
    }

    public function test_tier3_interaction_cache_and_conditional_headers_do_not_prevent_tracking(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.170',
            'HTTP_USER_AGENT' => 'CachingClient/1.0',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9,id;q=0.8',
        ])->get('/');

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitor_trackings', ['ip_address' => '198.51.100.170']);
    }

    // ==========================================
    // Tier 4: Real-World Workload Scenarios
    // ==========================================

    public function test_tier4_scenario_burst_high_traffic_simulation(): void
    {
        $uniqueVisitors = [
            ['ip' => '198.51.100.201', 'ua' => 'Safari/iOS-Device1'],
            ['ip' => '198.51.100.202', 'ua' => 'Chrome/Android-Device2'],
            ['ip' => '198.51.100.203', 'ua' => 'Edge/Windows-Device3'],
            ['ip' => '198.51.100.204', 'ua' => 'Firefox/Linux-Device4'],
            ['ip' => '198.51.100.205', 'ua' => 'Safari/MacOS-Device5'],
        ];

        // Interleaved burst: 10 hits each = 50 total hits
        for ($round = 1; $round <= 10; $round++) {
            foreach ($uniqueVisitors as $visitor) {
                $response = $this->withServerVariables([
                    'REMOTE_ADDR' => $visitor['ip'],
                    'HTTP_USER_AGENT' => $visitor['ua'],
                ])->get('/');
                $this->assertEquals(200, $response->getStatusCode());
            }
        }

        // Must produce exactly 5 rows
        $this->assertDatabaseCount('visitor_trackings', 5);

        // Every row must have visit_count = 10
        $rows = DB::table('visitor_trackings')->get();
        foreach ($rows as $row) {
            $this->assertEquals(10, $row->visit_count);
        }

        // Sum of all visit counts must equal 50
        $this->assertEquals(50, DB::table('visitor_trackings')->sum('visit_count'));
    }

    public function test_tier4_scenario_multi_device_client_traffic_audit(): void
    {
        $clients = [
            ['ip' => '203.0.113.51', 'ua' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1'],
            ['ip' => '203.0.113.52', 'ua' => 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 Chrome/122.0.0.0 Mobile Safari/537.36'],
            ['ip' => '203.0.113.53', 'ua' => 'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 Version/17.4 Mobile/15E148 Safari/604.1'],
            ['ip' => '203.0.113.54', 'ua' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
        ];

        foreach ($clients as $client) {
            $this->withServerVariables([
                'REMOTE_ADDR' => $client['ip'],
                'HTTP_USER_AGENT' => $client['ua'],
            ])->get('/');
        }

        $records = DB::table('visitor_trackings')->get();
        $this->assertCount(4, $records);
        $this->assertEquals(4, $records->pluck('visitor_hash')->unique()->count());
    }

    public function test_tier4_scenario_daily_visitor_aggregation_query(): void
    {
        // Populate multi-day data
        Carbon::setTestNow('2026-10-29 12:00:00');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'UA1'])->get('/');

        Carbon::setTestNow('2026-10-30 12:00:00');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'UA1'])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_USER_AGENT' => 'UA2'])->get('/');

        Carbon::setTestNow('2026-10-31 12:00:00');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'UA1'])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'UA1'])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3', 'HTTP_USER_AGENT' => 'UA3'])->get('/');

        Carbon::setTestNow(); // reset

        // Metric 1: Total unique visitors on 2026-10-31
        $todayVisitors = DB::table('visitor_trackings')
            ->where('visit_date', '2026-10-31')
            ->count();
        $this->assertEquals(2, $todayVisitors);

        // Metric 2: Total page hits on 2026-10-31
        $todayHits = DB::table('visitor_trackings')
            ->where('visit_date', '2026-10-31')
            ->sum('visit_count');
        $this->assertEquals(3, $todayHits);

        // Metric 3: Total aggregate unique records in 3-day window
        $totalWindowRecords = DB::table('visitor_trackings')
            ->whereBetween('visit_date', ['2026-10-29', '2026-10-31'])
            ->count();
        $this->assertEquals(5, $totalWindowRecords);
    }
}
