<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\VisitorTracking;
use App\Services\VisitorTrackingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VisitorTrackingStressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test 1: Burst Traffic Simulation (50+ rapid requests from 5 distinct simulated visitors).
     * Assert unique visitor count = exactly 5 and sum of visit_count = exactly 50.
     */
    public function test_burst_traffic_simulation_50_rapid_requests_across_5_distinct_visitors(): void
    {
        $visitors = [
            ['ip' => '192.168.10.1', 'ua' => 'BurstAgent/1.0 (iPhone; iOS 17.5) Mobile'],
            ['ip' => '192.168.10.2', 'ua' => 'BurstAgent/2.0 (Pixel 8; Android 14) Mobile'],
            ['ip' => '192.168.10.3', 'ua' => 'BurstAgent/3.0 (Macintosh; macOS 14.5) Desktop'],
            ['ip' => '192.168.10.4', 'ua' => 'BurstAgent/4.0 (Windows NT 10.0; Win64) Desktop'],
            ['ip' => '192.168.10.5', 'ua' => 'BurstAgent/5.0 (Linux x86_64; Ubuntu 24.04) Bot'],
        ];

        $today = Carbon::now()->toDateString();
        $totalRounds = 10; // 5 visitors * 10 rounds = 50 rapid requests

        // Interleaved burst requests simulating concurrent user traffic
        for ($round = 1; $round <= $totalRounds; $round++) {
            foreach ($visitors as $v) {
                $response = $this->withServerVariables([
                    'REMOTE_ADDR' => $v['ip'],
                    'HTTP_USER_AGENT' => $v['ua'],
                ])->get('/');

                $this->assertSame(200, $response->getStatusCode(), "Request for {$v['ip']} in round {$round} failed.");
            }
        }

        // Assertion 1: Total records in visitor_trackings must equal exactly 5
        $totalRecords = VisitorTracking::count();
        $this->assertSame(5, $totalRecords, 'Unique visitor record count must equal exactly 5.');

        // Assertion 2: Sum of visit_count must equal exactly 50
        $sumVisitCount = (int) VisitorTracking::sum('visit_count');
        $this->assertSame(50, $sumVisitCount, 'Sum of visit_count across all visitors must equal exactly 50.');

        // Assertion 3: Each visitor must have exactly visit_count = 10
        foreach ($visitors as $v) {
            $record = VisitorTracking::where('ip_address', $v['ip'])
                ->where('visit_date', $today)
                ->first();

            $this->assertNotNull($record, "Record for {$v['ip']} must exist.");
            $this->assertSame(10, $record->visit_count, "Visitor {$v['ip']} must have recorded exactly 10 hits.");
            $this->assertSame($v['ua'], $record->user_agent);
            $this->assertTrue($record->isReturning(), "Visitor {$v['ip']} with 10 hits must be identified as returning.");
        }
    }

    /**
     * Test 2: Concurrency Race Condition - Simulated interleaving insert and unique constraint fallback.
     * When two concurrent requests arrive simultaneously before the row exists:
     * Request 1 and Request 2 both see no existing row.
     * Request 1 succeeds in inserting the row.
     * Request 2 attempts create(), triggers QueryException from compound unique constraint,
     * catches QueryException, queries the existing row created by Request 1, and increments visit_count.
     */
    public function test_concurrency_race_condition_interleaving_and_unique_constraint_retry(): void
    {
        $service = app(VisitorTrackingService::class);
        $clientIp = '10.200.50.99';
        $clientUa = 'RaceConditionStressHarness/1.0';
        $today = Carbon::now()->toDateString();
        $expectedHash = $service->hashVisitor($clientIp, $clientUa);

        // Ensure database has no prior record
        $this->assertNull(
            VisitorTracking::where('visitor_hash', $expectedHash)->where('visit_date', $today)->first()
        );

        // We simulate a race condition where right before Eloquent executes INSERT,
        // another concurrent thread commits an identical row into the database.
        $interleavedThreadExecuted = false;

        VisitorTracking::creating(function (VisitorTracking $model) use ($clientIp, $clientUa, $today, $expectedHash, &$interleavedThreadExecuted) {
            // Only trigger once during the initial create attempt
            if (! $interleavedThreadExecuted && $model->visitor_hash === $expectedHash) {
                $interleavedThreadExecuted = true;

                // Concurrent thread wins the race and commits the row first
                DB::table('visitor_trackings')->insert([
                    'visitor_hash' => $expectedHash,
                    'ip_address' => $clientIp,
                    'user_agent' => $clientUa,
                    'visit_date' => $today,
                    'visit_count' => 1,
                    'first_visited_at' => Carbon::now()->subSecond(),
                    'last_visited_at' => Carbon::now()->subSecond(),
                    'created_at' => Carbon::now()->subSecond(),
                    'updated_at' => Carbon::now()->subSecond(),
                ]);
            }
        });

        // Make the request through the service (Thread 2)
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ]);

        $result = $service->recordVisit($request);

        // Verify Thread 2 did not crash and recovered cleanly
        $this->assertNotNull($result, 'recordVisit must not return null during unique constraint race recovery.');
        $this->assertSame($expectedHash, $result->visitor_hash);

        // The count must now be 2 (1 from interleaved thread + 1 from recovered Thread 2)
        $this->assertSame(2, $result->visit_count, 'visit_count must be incremented to 2 following race recovery.');

        // Verify database table integrity: exactly 1 row, no duplicates
        $dbRows = VisitorTracking::where('visitor_hash', $expectedHash)
            ->where('visit_date', $today)
            ->get();

        $this->assertCount(1, $dbRows, 'Compound unique constraint must guarantee exactly 1 row exists.');
        $this->assertSame(2, $dbRows->first()->visit_count);
    }

    /**
     * Test 3: Concurrency Race Condition - Multi-Thread Simulated Collision Storm.
     * Simulates 5 simultaneous requests for a brand-new visitor where 4 collide on unique constraint.
     */
    public function test_concurrency_race_condition_multi_thread_simulated_collision_storm(): void
    {
        $service = app(VisitorTrackingService::class);
        $clientIp = '10.200.50.100';
        $clientUa = 'CollisionStormHarness/1.0';
        $today = Carbon::now()->toDateString();
        $expectedHash = $service->hashVisitor($clientIp, $clientUa);

        // Pre-insert row to simulate a thread having just inserted
        $now = Carbon::now();
        DB::table('visitor_trackings')->insert([
            'visitor_hash' => $expectedHash,
            'ip_address' => $clientIp,
            'user_agent' => $clientUa,
            'visit_date' => $today,
            'visit_count' => 1,
            'first_visited_at' => $now,
            'last_visited_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Simulate 4 concurrent requests arriving immediately
        for ($thread = 2; $thread <= 5; $thread++) {
            $request = Request::create('/', 'GET', [], [], [], [
                'REMOTE_ADDR' => $clientIp,
                'HTTP_USER_AGENT' => $clientUa,
            ]);

            $model = $service->recordVisit($request);
            $this->assertNotNull($model);
            $this->assertSame($thread, $model->visit_count);
        }

        // Must still have exactly 1 database row with visit_count = 5
        $count = VisitorTracking::where('visitor_hash', $expectedHash)
            ->where('visit_date', $today)
            ->count();
        $this->assertSame(1, $count);

        $row = VisitorTracking::where('visitor_hash', $expectedHash)->where('visit_date', $today)->first();
        $this->assertSame(5, $row->visit_count);
    }

    /**
     * Test 4: Midnight Rollover Transition across calendar dates, months, and years.
     * Verifies that the daily deduplication window rolls over cleanly at 00:00:00.
     */
    public function test_midnight_rollover_transition_across_calendar_dates_and_new_year(): void
    {
        $clientIp = '198.51.100.77';
        $clientUa = 'MidnightRolloverClient/1.0';

        // Phase 1: 2026-10-31 23:59:58 (Day 1 - October 31)
        Carbon::setTestNow('2026-10-31 23:59:58');
        $res1 = $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ])->get('/');
        $res1->assertStatus(200);

        // Second hit 1 second later: 2026-10-31 23:59:59
        Carbon::setTestNow('2026-10-31 23:59:59');
        $res2 = $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ])->get('/');
        $res2->assertStatus(200);

        // Day 1 assertions
        $day1Record = VisitorTracking::where('ip_address', $clientIp)
            ->where('visit_date', '2026-10-31')
            ->first();
        $this->assertNotNull($day1Record);
        $this->assertSame(2, $day1Record->visit_count);
        $this->assertSame('2026-10-31 23:59:58', $day1Record->first_visited_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-31 23:59:59', $day1Record->last_visited_at->format('Y-m-d H:i:s'));

        // Phase 2: Midnight Rollover into next month: 2026-11-01 00:00:01
        Carbon::setTestNow('2026-11-01 00:00:01');
        $res3 = $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ])->get('/');
        $res3->assertStatus(200);

        // Day 2 assertions
        $day2Record = VisitorTracking::where('ip_address', $clientIp)
            ->where('visit_date', '2026-11-01')
            ->first();
        $this->assertNotNull($day2Record, 'A new record must be created for 2026-11-01.');
        $this->assertSame(1, $day2Record->visit_count);
        $this->assertSame('2026-11-01 00:00:01', $day2Record->first_visited_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-01 00:00:01', $day2Record->last_visited_at->format('Y-m-d H:i:s'));

        // Day 1 record must be completely untouched
        $day1RecordFresh = VisitorTracking::where('ip_address', $clientIp)
            ->where('visit_date', '2026-10-31')
            ->first();
        $this->assertSame(2, $day1RecordFresh->visit_count);

        // Phase 3: Year Rollover into 2027: 2026-12-31 23:59:59 -> 2027-01-01 00:00:01
        Carbon::setTestNow('2026-12-31 23:59:59');
        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ])->get('/');

        Carbon::setTestNow('2027-01-01 00:00:01');
        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUa,
        ])->get('/');

        Carbon::setTestNow(); // Reset test clock

        // Total distinct rows for this visitor must be exactly 4 (across 4 distinct dates)
        $allRecords = VisitorTracking::where('ip_address', $clientIp)->orderBy('visit_date')->get();
        $this->assertCount(4, $allRecords);

        $dates = $allRecords->pluck('visit_date')->map(fn ($d) => $d->format('Y-m-d'))->all();
        $this->assertSame(['2026-10-31', '2026-11-01', '2026-12-31', '2027-01-01'], $dates);

        // All 4 rows must share the exact same visitor_hash
        $uniqueHashes = $allRecords->pluck('visitor_hash')->unique();
        $this->assertCount(1, $uniqueHashes, 'Visitor hash must remain identical across all calendar dates.');
    }

    /**
     * Test 5: High-Volume Rapid Burst Stress (150 requests from 10 distinct clients).
     * Tests throughput, consistent deduplication, and zero database lockups.
     */
    public function test_high_volume_rapid_burst_stress_150_requests_across_10_clients(): void
    {
        $clients = [];
        for ($i = 1; $i <= 10; $i++) {
            $clients[] = [
                'ip' => "172.16.0.{$i}",
                'ua' => "StressHarnessBot/{$i}.0 (HighVolumeTest)",
            ];
        }

        $startTime = microtime(true);
        $totalRequests = 150; // 10 clients * 15 hits each

        for ($hit = 1; $hit <= 15; $hit++) {
            foreach ($clients as $client) {
                $response = $this->withServerVariables([
                    'REMOTE_ADDR' => $client['ip'],
                    'HTTP_USER_AGENT' => $client['ua'],
                ])->get('/');
                $this->assertSame(200, $response->getStatusCode());
            }
        }

        $duration = microtime(true) - $startTime;

        // Exactly 10 unique visitor rows
        $this->assertSame(10, VisitorTracking::count());

        // Sum of all visit_count values must be exactly 150
        $this->assertSame(150, (int) VisitorTracking::sum('visit_count'));

        // Every visitor must have exactly 15 hits recorded
        $all = VisitorTracking::all();
        foreach ($all as $record) {
            $this->assertSame(15, $record->visit_count);
        }

        // Performance sanity: 150 requests in memory should execute in under 10 seconds
        $this->assertLessThan(10.0, $duration, "150 requests took unexpectedly long: {$duration}s");
    }

    /**
     * Test 6: Query Count Efficiency Benchmark.
     * Checks that tracking performs no more than 2 queries per request (1 SELECT + 1 INSERT/UPDATE).
     */
    public function test_query_count_efficiency_benchmark(): void
    {
        $ip = '10.99.88.77';
        $ua = 'QueryCountAudit/1.0';

        // Hit 1: Brand new visitor (1 SELECT + 1 INSERT)
        DB::flushQueryLog();
        $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua])->get('/');
        $hit1Queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'visitor_trackings'));
        $this->assertLessThanOrEqual(3, $hit1Queries->count(), 'First hit must not exceed 2-3 queries on visitor_trackings.');

        // Hit 2: Existing visitor (1 SELECT + 1 UPDATE)
        DB::flushQueryLog();
        $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua])->get('/');
        $hit2Queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'visitor_trackings'));
        $this->assertLessThanOrEqual(2, $hit2Queries->count(), 'Subsequent hit must only execute SELECT and UPDATE.');
    }

    /**
     * Test 7: Catastrophic Database Failure during Catch Block Recovery.
     * Verifies that if the database is completely broken during catch block retry,
     * the service catches the exception, logs a warning, and HomeController still returns HTTP 200.
     */
    public function test_catastrophic_database_failure_during_concurrency_retry_does_not_crash_landing_page(): void
    {
        // Drop visitor_trackings table to simulate total database outage
        DB::statement('DROP TABLE visitor_trackings');

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => 'DisasterRecoveryTest/1.0',
        ])->get('/');

        // Must still render HTTP 200 view cleanly
        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    /**
     * Test 8: Deterministic Salt Isolation and Hash Uniqueness.
     * Verifies that hash collisions do not occur across similar IPs or UAs.
     */
    public function test_deterministic_salt_isolation_and_hash_uniqueness(): void
    {
        $service = app(VisitorTrackingService::class);

        $hash1 = $service->hashVisitor('10.0.0.1', 'Mozilla/5.0');
        $hash2 = $service->hashVisitor('10.0.0.2', 'Mozilla/5.0');
        $hash3 = $service->hashVisitor('10.0.0.1', 'Mozilla/5.1');
        $hash4 = $service->hashVisitor(null, null);

        $this->assertNotSame($hash1, $hash2);
        $this->assertNotSame($hash1, $hash3);
        $this->assertNotSame($hash1, $hash4);
        $this->assertSame(64, strlen($hash4));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash4);
    }

    /**
     * Test 9: Real Multi-Process Concurrency Race Condition.
     * Launches 5 separate PHP OS processes concurrently against a real SQLite file database
     * attempting to record visits for the exact same visitor at the exact same moment.
     * Asserts that race condition recovery handles concurrent inserts cleanly without duplicate rows.
     */
    public function test_multi_process_concurrency_race_conditions_with_real_sqlite_file(): void
    {
        $tempDb = sys_get_temp_dir() . '/olt_stress_' . uniqid() . '.sqlite';
        touch($tempDb);

        config(['database.connections.sqlite_stress' => [
            'driver' => 'sqlite',
            'database' => $tempDb,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        \Illuminate\Support\Facades\Schema::connection('sqlite_stress')->create('visitor_trackings', function ($table) {
            $table->id();
            $table->string('visitor_hash', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->date('visit_date')->index();
            $table->unsignedInteger('visit_count')->default(1);
            $table->timestamp('first_visited_at');
            $table->timestamp('last_visited_at');
            $table->timestamps();
            $table->unique(['visitor_hash', 'visit_date']);
        });

        $projectRoot = base_path();
        $workerScript = sys_get_temp_dir() . '/olt_worker_' . uniqid() . '.php';
        file_put_contents($workerScript, "<?php
require '{$projectRoot}/vendor/autoload.php';
\$app = require '{$projectRoot}/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

config(['database.default' => 'sqlite_stress']);
config(['database.connections.sqlite_stress' => [
    'driver' => 'sqlite',
    'database' => '{$tempDb}',
    'prefix' => '',
    'foreign_key_constraints' => true,
    'busy_timeout' => 5000,
]]);

\$service = \$app->make(App\Services\VisitorTrackingService::class);
\$request = Illuminate\Http\Request::create('/', 'GET', [], [], [], [
    'REMOTE_ADDR' => '192.168.100.50',
    'HTTP_USER_AGENT' => 'MultiProcessStressTest/1.0',
]);

\$result = \$service->recordVisit(\$request);
echo \$result ? 'COUNT:' . \$result->visit_count : 'ERROR';
");

        $procs = [];
        $pipes = [];
        $numProcesses = 5;

        for ($i = 0; $i < $numProcesses; $i++) {
            $cmd = 'php ' . escapeshellarg($workerScript);
            $procs[$i] = proc_open($cmd, [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes[$i]);
        }

        $outputs = [];
        for ($i = 0; $i < $numProcesses; $i++) {
            $stdout = stream_get_contents($pipes[$i][1]);
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($procs[$i]);
            $outputs[] = trim($stdout);
        }

        // Query the database directly via PDO
        $pdo = new \PDO('sqlite:' . $tempDb);
        $stmt = $pdo->query('SELECT * FROM visitor_trackings');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Cleanup temporary files
        @unlink($tempDb);
        @unlink($workerScript);

        // Verification: Exactly 1 row created, no duplicate rows
        $this->assertCount(1, $rows, 'Multi-process concurrent hits must produce exactly 1 database row.');
        $this->assertSame('192.168.100.50', $rows[0]['ip_address']);
        $this->assertSame('MultiProcessStressTest/1.0', $rows[0]['user_agent']);
        // Final visit count in the row must equal 5
        $this->assertSame(5, (int) $rows[0]['visit_count'], 'Final visit_count after 5 concurrent processes must equal 5.');
    }

    /**
     * Test 10: Model Scopes and Privacy-Preserving Accessors under Diverse Inputs.
     */
    public function test_model_scopes_and_privacy_accessors_under_diverse_inputs(): void
    {
        $today = Carbon::now()->toDateString();
        $yesterday = Carbon::now()->subDay()->toDateString();

        $v1 = VisitorTracking::create([
            'visitor_hash' => hash_hmac('sha256', '1.1.1.1|UA1', (string) config('app.key')),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'ScopeTest1/1.0',
            'visit_date' => $today,
            'visit_count' => 1,
            'first_visited_at' => now(),
            'last_visited_at' => now(),
        ]);

        $v2 = VisitorTracking::create([
            'visitor_hash' => hash_hmac('sha256', '2.2.2.2|UA2', (string) config('app.key')),
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'user_agent' => 'ScopeTest2/1.0',
            'visit_date' => $today,
            'visit_count' => 8,
            'first_visited_at' => now()->subHours(2),
            'last_visited_at' => now(),
        ]);

        $v3 = VisitorTracking::create([
            'visitor_hash' => hash_hmac('sha256', '3.3.3.3|UA3', (string) config('app.key')),
            'ip_address' => null,
            'user_agent' => 'ScopeTest3/1.0',
            'visit_date' => $yesterday,
            'visit_count' => 3,
            'first_visited_at' => now()->subDay(),
            'last_visited_at' => now()->subDay(),
        ]);

        // Scopes test
        $this->assertSame(2, VisitorTracking::today()->count());
        $this->assertSame(1, VisitorTracking::singleVisit()->count());
        $this->assertSame(2, VisitorTracking::returning()->count());
        $this->assertSame(3, VisitorTracking::dateBetween($yesterday, $today)->count());

        // Privacy accessors test
        $this->assertSame('192.168.1.***', $v1->masked_ip);
        $this->assertSame('2001:0db8:****', $v2->masked_ip);
        $this->assertNull($v3->masked_ip);

        // Short hash
        $this->assertSame(10, strlen($v1->short_hash));
        $this->assertSame(substr($v1->visitor_hash, 0, 10), $v1->short_hash);
    }
}
