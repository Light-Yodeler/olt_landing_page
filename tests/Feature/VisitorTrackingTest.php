<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\VisitorTracking;
use App\Services\VisitorTrackingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test 1: First visit to English root URL (/) records a new visitor tracking row.
     */
    public function test_first_visit_to_english_root_records_visitor_tracking(): void
    {
        $ip = '203.0.113.10';
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) FeatureTestBrowser/1.0';
        $today = Carbon::now()->toDateString();

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ])->get('/');

        $response->assertStatus(200);

        $this->assertDatabaseHas('visitor_trackings', [
            'ip_address' => $ip,
            'visit_date' => $today,
            'visit_count' => 1,
        ]);

        $record = VisitorTracking::where('ip_address', $ip)->first();
        $this->assertNotNull($record, 'VisitorTracking record must be created.');
        $this->assertSame(64, strlen($record->visitor_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $record->visitor_hash);
        $this->assertSame($userAgent, $record->user_agent);
        $this->assertSame($today, $record->visit_date->toDateString());
        $this->assertSame(1, $record->visit_count);
        $this->assertNotNull($record->first_visited_at);
        $this->assertNotNull($record->last_visited_at);
        $this->assertEquals(
            $record->first_visited_at->toDateTimeString(),
            $record->last_visited_at->toDateTimeString()
        );
    }

    /**
     * Test 2: First visit to Indonesian URL (/id) records visitor tracking.
     */
    public function test_first_visit_to_indonesian_locale_records_visitor_tracking(): void
    {
        $ip = '203.0.113.11';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) IndoFeatureClient/1.0';
        $today = Carbon::now()->toDateString();

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ])->get('/id');

        $response->assertStatus(200);

        $this->assertDatabaseHas('visitor_trackings', [
            'ip_address' => $ip,
            'visit_date' => $today,
            'visit_count' => 1,
        ]);

        $record = VisitorTracking::where('ip_address', $ip)->first();
        $this->assertNotNull($record);
        $this->assertSame(1, $record->visit_count);
    }

    /**
     * Test 3: Same-day deduplication increments visit_count without creating duplicate rows.
     */
    public function test_repeated_visits_from_same_visitor_same_day_increments_count(): void
    {
        $ip = '203.0.113.12';
        $userAgent = 'RepeatedVisitorClient/1.0';
        $today = Carbon::now()->toDateString();

        $serverVars = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // Simulate 4 requests from the same visitor
        for ($i = 1; $i <= 4; $i++) {
            $this->withServerVariables($serverVars)->get('/')->assertStatus(200);
        }

        $records = VisitorTracking::where('ip_address', $ip)
            ->where('visit_date', $today)
            ->get();

        $this->assertCount(1, $records, 'Repeated visits must not create additional database rows.');
        $this->assertSame(4, $records->first()->visit_count, 'visit_count must increment to 4.');
    }

    /**
     * Test 4: Distinct visitors with different IPs generate separate tracking records.
     */
    public function test_distinct_visitors_with_different_ips_create_separate_records(): void
    {
        $ua = 'SharedOfficeBrowser/1.0';

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.1',
            'HTTP_USER_AGENT' => $ua,
        ])->get('/');

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.2',
            'HTTP_USER_AGENT' => $ua,
        ])->get('/');

        $records = VisitorTracking::all();
        $this->assertCount(2, $records);

        $hashes = $records->pluck('visitor_hash')->unique();
        $this->assertCount(2, $hashes, 'Different IPs must result in distinct visitor hashes.');
    }

    /**
     * Test 5: Distinct visitors with same IP but different User-Agents generate separate records.
     */
    public function test_distinct_visitors_with_same_ip_different_user_agents_create_separate_records(): void
    {
        $ip = '198.51.100.15';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Safari/605.1',
        ])->get('/');

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/122.0',
        ])->get('/');

        $records = VisitorTracking::where('ip_address', $ip)->get();
        $this->assertCount(2, $records, 'Different User-Agents behind the same IP must create separate records.');

        $hashes = $records->pluck('visitor_hash')->unique();
        $this->assertCount(2, $hashes);
    }

    /**
     * Test 6: Timestamp accuracy preserves first_visited_at while updating last_visited_at.
     */
    public function test_timestamp_accuracy_preserves_first_visited_at_and_updates_last_visited_at(): void
    {
        $ip = '203.0.113.20';
        $userAgent = 'TimestampAuditClient/1.0';
        $serverVars = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // Initial visit at 08:30:00
        Carbon::setTestNow('2026-10-31 08:30:00');
        $this->withServerVariables($serverVars)->get('/');

        // Re-visit 45 minutes later at 09:15:00
        Carbon::setTestNow('2026-10-31 09:15:00');
        $this->withServerVariables($serverVars)->get('/');

        // Third visit at 11:00:00
        Carbon::setTestNow('2026-10-31 11:00:00');
        $this->withServerVariables($serverVars)->get('/');

        Carbon::setTestNow(); // Reset time mock

        $record = VisitorTracking::where('ip_address', $ip)->first();
        $this->assertNotNull($record);
        $this->assertSame(3, $record->visit_count);
        $this->assertSame('2026-10-31 08:30:00', $record->first_visited_at->toDateTimeString());
        $this->assertSame('2026-10-31 11:00:00', $record->last_visited_at->toDateTimeString());
    }

    /**
     * Test 7: Calendar day transition creates new tracking records for the new day.
     */
    public function test_calendar_day_transition_creates_distinct_daily_records(): void
    {
        $ip = '203.0.113.25';
        $userAgent = 'RolloverVisitor/1.0';
        $serverVars = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ];

        // Day 1: 2026-10-31 at 23:50:00
        Carbon::setTestNow('2026-10-31 23:50:00');
        $this->withServerVariables($serverVars)->get('/');

        // Day 2: 2026-11-01 at 00:10:00 (20 minutes later, new date)
        Carbon::setTestNow('2026-11-01 00:10:00');
        $this->withServerVariables($serverVars)->get('/');

        Carbon::setTestNow(); // Reset time mock

        $records = VisitorTracking::where('ip_address', $ip)
            ->orderBy('visit_date', 'asc')
            ->get();

        $this->assertCount(2, $records, 'Calendar day rollover must create distinct daily records.');
        $this->assertSame('2026-10-31', $records[0]->visit_date->toDateString());
        $this->assertSame(1, $records[0]->visit_count);
        $this->assertSame('2026-11-01', $records[1]->visit_date->toDateString());
        $this->assertSame(1, $records[1]->visit_count);
        $this->assertSame($records[0]->visitor_hash, $records[1]->visitor_hash, 'Visitor hash must remain identical across dates.');
    }

    /**
     * Test 8: VisitorTrackingService directly records visits and returns model instance.
     */
    public function test_visitor_tracking_service_directly_records_and_returns_model(): void
    {
        $service = app(VisitorTrackingService::class);
        $this->assertInstanceOf(VisitorTrackingService::class, $service);

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.88',
            'HTTP_USER_AGENT' => 'DirectServiceTester/1.0',
        ]);

        $result = $service->recordVisit($request);

        $this->assertInstanceOf(VisitorTracking::class, $result);
        $this->assertSame('198.51.100.88', $result->ip_address);
        $this->assertSame('DirectServiceTester/1.0', $result->user_agent);
        $this->assertSame(1, $result->visit_count);

        $expectedHash = hash_hmac('sha256', '198.51.100.88|DirectServiceTester/1.0', (string) (config('app.key') ?: 'olt-default-tracking-salt'));
        $this->assertSame($expectedHash, $result->visitor_hash);
    }

    /**
     * Test 9: Service layer encapsulation - HomeController contains zero direct database queries.
     */
    public function test_home_controller_encapsulates_tracking_with_zero_direct_db_queries(): void
    {
        $reflector = new ReflectionClass(HomeController::class);
        $sourceFile = $reflector->getFileName();
        $this->assertFileExists($sourceFile);

        $sourceCode = (string) file_get_contents($sourceFile);

        // Assert no DB facade imports or calls
        $this->assertStringNotContainsString('DB::', $sourceCode, 'HomeController must not call DB facade directly.');
        $this->assertStringNotContainsString('Illuminate\Support\Facades\DB', $sourceCode, 'HomeController must not import DB facade.');

        // Assert no direct Model query operations
        $this->assertStringNotContainsString('VisitorTracking::', $sourceCode, 'HomeController must not query VisitorTracking directly.');
        $this->assertStringNotContainsString('->where(', $sourceCode, 'HomeController must not perform query builder operations.');
        $this->assertStringNotContainsString('->insert(', $sourceCode, 'HomeController must not perform insert operations.');
        $this->assertStringNotContainsString('->update(', $sourceCode, 'HomeController must not perform update operations.');

        // Assert constructor injection of VisitorTrackingService
        $constructor = $reflector->getConstructor();
        $this->assertNotNull($constructor, 'HomeController must define a constructor.');

        $paramTypes = array_map(
            fn ($param) => $param->getType()?->getName(),
            $constructor->getParameters()
        );
        $this->assertContains(
            VisitorTrackingService::class,
            $paramTypes,
            'HomeController constructor must inject VisitorTrackingService.'
        );

        // Assert runtime query encapsulation: mocking service results in ZERO visitor_trackings queries
        $mockService = $this->createMock(VisitorTrackingService::class);
        $mockService->expects($this->once())->method('recordVisit');
        $this->app->instance(VisitorTrackingService::class, $mockService);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get('/')->assertStatus(200);

        $queries = collect(DB::getQueryLog());
        $trackingQueries = $queries->filter(
            fn ($q) => str_contains($q['query'], 'visitor_trackings')
        );

        $this->assertCount(
            0,
            $trackingQueries,
            'With VisitorTrackingService mocked, no queries should touch visitor_trackings.'
        );
    }

    /**
     * Test 10: Error resilience - landing page loads successfully when service throws exception.
     */
    public function test_landing_page_loads_successfully_when_service_throws_exception(): void
    {
        $mockService = $this->createMock(VisitorTrackingService::class);
        $mockService->method('recordVisit')
            ->willThrowException(new RuntimeException('Simulated tracking service crash'));
        $this->app->instance(VisitorTrackingService::class, $mockService);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    /**
     * Test 11: Error resilience - landing page loads when database operation fails inside service.
     */
    public function test_landing_page_loads_successfully_when_database_fails_during_tracking(): void
    {
        // Drop table temporarily to simulate database unavailability or table locking
        DB::statement('DROP TABLE visitor_trackings');

        $response = $this->get('/');

        // Must still return HTTP 200 without leaking SQL QueryException
        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    /**
     * Test 12: Database compound unique constraint enforces data integrity against race conditions.
     */
    public function test_database_compound_unique_constraint_enforces_data_integrity(): void
    {
        $hash = str_repeat('a', 64);
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        DB::table('visitor_trackings')->insert([
            'visitor_hash' => $hash,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'ConstraintTest/1.0',
            'visit_date' => $today,
            'visit_count' => 1,
            'first_visited_at' => $now,
            'last_visited_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->expectException(QueryException::class);

        // Attempt second insert with identical visitor_hash and visit_date
        DB::table('visitor_trackings')->insert([
            'visitor_hash' => $hash,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'ConstraintTest/1.0',
            'visit_date' => $today,
            'visit_count' => 1,
            'first_visited_at' => $now,
            'last_visited_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Test 13: VisitorTracking Eloquent model attributes, casts, and date formatting.
     */
    public function test_visitor_tracking_model_casts_and_attributes(): void
    {
        $record = VisitorTracking::create([
            'visitor_hash' => hash_hmac('sha256', '127.0.0.1|ModelTest/1.0', (string) (config('app.key') ?: 'salt')),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'ModelTest/1.0',
            'visit_date' => '2026-10-31',
            'visit_count' => 5,
            'first_visited_at' => '2026-10-31 10:00:00',
            'last_visited_at' => '2026-10-31 12:30:00',
        ]);

        $record->refresh();

        $this->assertInstanceOf(Carbon::class, $record->visit_date);
        $this->assertInstanceOf(Carbon::class, $record->first_visited_at);
        $this->assertInstanceOf(Carbon::class, $record->last_visited_at);
        $this->assertIsInt($record->visit_count);
        $this->assertSame(5, $record->visit_count);
        $this->assertSame('2026-10-31', $record->visit_date->toDateString());
    }

    /**
     * Test 14: Null or empty request headers fallback gracefully.
     */
    public function test_null_or_empty_request_headers_handled_gracefully(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => null,
        ])->get('/');

        $response->assertStatus(200);

        $record = VisitorTracking::where('ip_address', '127.0.0.1')->first();
        $this->assertNotNull($record);
        $this->assertSame(64, strlen($record->visitor_hash));
    }

    /**
     * Test 15: VisitorTracking model scopes and helper methods.
     */
    public function test_visitor_tracking_model_scopes_and_helpers(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        $single = VisitorTracking::factory()->create([
            'visitor_hash' => str_repeat('1', 64),
            'ip_address' => '192.168.1.100',
            'visit_date' => '2026-10-31',
            'visit_count' => 1,
        ]);

        $returning = VisitorTracking::factory()->create([
            'visitor_hash' => str_repeat('2', 64),
            'ip_address' => '2001:db8:85a3::8a2e:370:7334',
            'visit_date' => '2026-10-31',
            'visit_count' => 4,
        ]);

        $past = VisitorTracking::factory()->create([
            'visitor_hash' => str_repeat('3', 64),
            'ip_address' => null,
            'visit_date' => '2026-10-25',
            'visit_count' => 1,
        ]);

        // Helpers
        $this->assertFalse($single->isReturning());
        $this->assertTrue($returning->isReturning());
        $this->assertSame(substr(str_repeat('1', 64), 0, 10), $single->short_hash);
        $this->assertSame('192.168.1.***', $single->masked_ip);
        $this->assertSame('2001:db8:****', $returning->masked_ip);
        $this->assertNull($past->masked_ip);

        // recordHit
        $single->recordHit();
        $this->assertSame(2, $single->visit_count);
        $this->assertTrue($single->isReturning());

        // Scopes
        $this->assertCount(2, VisitorTracking::today()->get());
        $this->assertCount(1, VisitorTracking::forDate('2026-10-25')->get());
        $this->assertCount(3, VisitorTracking::dateBetween('2026-10-20', '2026-10-31')->get());
        $this->assertCount(2, VisitorTracking::returning()->get());
        $this->assertCount(1, VisitorTracking::singleVisit()->get()); // $past remains single visit

        Carbon::setTestNow();
    }

    /**
     * Test 16: VisitorTrackingFactory states.
     */
    public function test_visitor_tracking_factory_states(): void
    {
        Carbon::setTestNow('2026-10-31 15:00:00');

        $todayRecord = VisitorTracking::factory()->today()->create();
        $this->assertSame('2026-10-31', $todayRecord->visit_date->toDateString());

        $singleRecord = VisitorTracking::factory()->singleVisit()->create();
        $this->assertSame(1, $singleRecord->visit_count);

        $frequentRecord = VisitorTracking::factory()->frequentVisitor(15)->create();
        $this->assertSame(15, $frequentRecord->visit_count);

        Carbon::setTestNow();
    }

    /**
     * Test 17: VisitorTrackingService aggregation helpers.
     */
    public function test_visitor_tracking_service_aggregation_helpers(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        VisitorTracking::factory()->create([
            'visit_date' => '2026-10-31',
            'visit_count' => 3,
        ]);

        VisitorTracking::factory()->create([
            'visit_date' => '2026-10-31',
            'visit_count' => 2,
        ]);

        $service = app(VisitorTrackingService::class);
        $this->assertSame(2, $service->getTodayUniqueCount());
        $this->assertSame(5, $service->getTodayTotalHits());

        Carbon::setTestNow();
    }
}
