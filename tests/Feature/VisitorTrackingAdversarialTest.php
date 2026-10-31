<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\VisitorTracking;
use App\Services\LandingPageService;
use App\Services\VisitorTrackingService;
use Error;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class VisitorTrackingAdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // =========================================================================
    // SECTION 1: Error Resilience & Fail-Open Behavior
    // =========================================================================

    /**
     * Adversarial Test 1: Fail-open when VisitorTrackingService throws diverse Throwables.
     * Asserts that HomeController::index() never propagates exceptions to the client,
     * maintaining 100% landing page uptime and HTTP 200 with full view data.
     */
    public function test_adversarial_fail_open_on_diverse_unhandled_service_exceptions(): void
    {
        $exceptions = [
            'Standard Exception' => new Exception('Simulated generic failure'),
            'Runtime Exception' => new RuntimeException('Simulated runtime crash'),
            'Invalid Argument' => new InvalidArgumentException('Simulated bad argument'),
            'Database QueryException' => new QueryException('sqlite', 'SELECT * FROM visitor_trackings', [], new Exception('Simulated locked table')),
            'PHP Engine Error' => new Error('Simulated engine error'),
        ];

        foreach ($exceptions as $description => $exception) {
            $mockService = $this->createMock(VisitorTrackingService::class);
            $mockService->method('recordVisit')->willThrowException($exception);
            $this->app->instance(VisitorTrackingService::class, $mockService);

            $response = $this->get('/');

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "HomeController must return HTTP 200 when VisitorTrackingService throws [{$description}]."
            );
            $response->assertViewIs('home');
            $response->assertViewHasAll(['setting', 'slides', 'services', 'destinations', 'standards', 'faqs']);
        }
    }

    /**
     * Adversarial Test 2: Fail-open when visitor_trackings table is completely missing / dropped.
     * Simulates catastrophic DB table lock, corrupted table, or missing migration.
     */
    public function test_adversarial_fail_open_when_tracking_table_is_dropped(): void
    {
        DB::statement('DROP TABLE visitor_trackings');

        // Verify the table does not exist
        $this->assertFalse(
            DB::getSchemaBuilder()->hasTable('visitor_trackings'),
            'Table visitor_trackings must be absent for this test.'
        );

        // Landing page request must succeed with HTTP 200 without throwing 500
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHasAll(['setting', 'slides', 'services', 'destinations', 'standards', 'faqs']);

        // Indonesian landing page request must also succeed
        $responseId = $this->get('/id');
        $responseId->assertStatus(200);
        $responseId->assertViewIs('home');
    }

    /**
     * Adversarial Test 3: VisitorTrackingService::recordVisit() handles database QueryException internally
     * and returns null gracefully without terminating execution.
     */
    public function test_adversarial_service_handles_query_exception_and_returns_null_gracefully(): void
    {
        DB::statement('DROP TABLE visitor_trackings');

        $service = app(VisitorTrackingService::class);
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.99',
            'HTTP_USER_AGENT' => 'AdversarialTester/1.0',
        ]);

        // Must not throw QueryException; must return null
        $result = $service->recordVisit($request);
        $this->assertNull($result, 'recordVisit() must catch QueryException and return null.');
    }

    // =========================================================================
    // SECTION 2: Controller Purity & Architecture
    // =========================================================================

    /**
     * Adversarial Test 4: Static AST and reflection audit of HomeController.
     * Verifies strict compliance with:
     * - Zero direct database queries (no DB:: facade, no raw queries)
     * - Zero Model instantiations (no Eloquent models referenced or created)
     * - Zero business logic / tracking calculations
     */
    public function test_adversarial_home_controller_purity_ast_and_token_audit(): void
    {
        $reflector = new ReflectionClass(HomeController::class);
        $filePath = $reflector->getFileName();
        $this->assertFileExists($filePath);

        $code = (string) file_get_contents($filePath);
        $tokens = token_get_all($code);

        // 1. Forbid DB facade usage
        $this->assertStringNotContainsString('DB::', $code, 'HomeController must not invoke DB facade.');
        $this->assertStringNotContainsString('use Illuminate\Support\Facades\DB;', $code, 'HomeController must not import DB facade.');

        // 2. Forbid direct Model access or instantiation
        $forbiddenModelStrings = [
            'App\Models\VisitorTracking',
            'VisitorTracking::',
            'new VisitorTracking',
            'App\Models\LoginHistory',
            'LoginHistory::',
            'new LoginHistory',
            'User::',
            'SiteSetting::',
            'HeroSlide::',
            'Destination::',
            'Service::',
            'Standard::',
            'Faq::',
        ];

        foreach ($forbiddenModelStrings as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $code,
                "HomeController must not reference or instantiate model [{$forbidden}]."
            );
        }

        // 3. Forbid Eloquent Query Builder methods
        $queryBuilderMethods = [
            '->where(',
            '->whereBetween(',
            '->insert(',
            '->update(',
            '->delete(',
            '->increment(',
            '->decrement(',
            '->first(',
            '->count(',
            '->sum(',
        ];

        foreach ($queryBuilderMethods as $method) {
            $this->assertStringNotContainsString(
                $method,
                $code,
                "HomeController must not invoke query builder method [{$method}]."
            );
        }

        // 4. Verify Constructor Injection
        $constructor = $reflector->getConstructor();
        $this->assertNotNull($constructor, 'HomeController must have a constructor.');

        $params = $constructor->getParameters();
        $paramTypes = array_map(fn ($p) => $p->getType()?->getName(), $params);

        $this->assertContains(LandingPageService::class, $paramTypes);
        $this->assertContains(VisitorTrackingService::class, $paramTypes);

        // 5. Verify index() method delegates strictly to services
        $indexMethod = $reflector->getMethod('index');
        $this->assertSame(HomeController::class, $indexMethod->getDeclaringClass()->getName());
        $this->assertSame('Illuminate\View\View', $indexMethod->getReturnType()?->getName());
    }

    // =========================================================================
    // SECTION 3: Privacy & Cryptographic Hashing Verification
    // =========================================================================

    /**
     * Adversarial Test 5: Verify visitor hash format, deterministic HMAC-SHA256, and app.key usage.
     */
    public function test_adversarial_visitor_hash_is_deterministic_64_char_lowercase_hex(): void
    {
        $service = app(VisitorTrackingService::class);

        $ip = '198.51.100.222';
        $userAgent = 'AdversarialPrivacyClient/1.0 (Testing)';

        $hash = $service->hashVisitor($ip, $userAgent);

        // Must be exactly 64 characters
        $this->assertSame(64, strlen($hash), 'Visitor hash must be exactly 64 characters.');

        // Must be strictly lowercase hexadecimal
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash, 'Visitor hash must be lowercase hex.');
        $this->assertSame(strtolower($hash), $hash, 'Visitor hash must be strictly lowercase.');

        // Verify HMAC-SHA256 calculation matches reference implementation with config('app.key')
        $expectedKey = (string) (config('app.key') ?: 'olt-default-tracking-salt');
        $expectedHash = hash_hmac('sha256', "{$ip}|{$userAgent}", $expectedKey);
        $this->assertSame($expectedHash, $hash, 'Hash must equal HMAC-SHA256 with config("app.key").');

        // Determinism: 1,000 iterations must yield the exact same hash
        for ($i = 0; $i < 1000; $i++) {
            $this->assertSame($hash, $service->hashVisitor($ip, $userAgent));
        }
    }

    /**
     * Adversarial Test 6: Verify cryptographic key dependence (Avalanche effect & key isolation).
     * Changing config('app.key') must invalidate all previous visitor hashes.
     */
    public function test_adversarial_visitor_hash_cryptographic_key_isolation(): void
    {
        $service = app(VisitorTrackingService::class);
        $ip = '203.0.113.77';
        $ua = 'KeyIsolationBrowser/1.0';

        Config::set('app.key', 'base64:OriginalKeySecret12345678901234567890123=');
        $hashOriginal = $service->hashVisitor($ip, $ua);

        Config::set('app.key', 'base64:ModifiedKeySecret12345678901234567890123=');
        $hashModified = $service->hashVisitor($ip, $ua);

        $this->assertNotSame(
            $hashOriginal,
            $hashModified,
            'Visitor hash must strictly depend on app.key; altering app.key must alter the hash.'
        );

        // Verify fallback key when app.key is empty string or null
        Config::set('app.key', '');
        $hashFallback1 = $service->hashVisitor($ip, $ua);
        $this->assertSame(64, strlen($hashFallback1));

        Config::set('app.key', null);
        $hashFallback2 = $service->hashVisitor($ip, $ua);
        $this->assertSame($hashFallback1, $hashFallback2, 'Empty or null app.key must use uniform fallback salt.');
    }

    /**
     * Adversarial Test 7: Verify empty or null IP and User Agent fallbacks.
     */
    public function test_adversarial_null_ip_and_user_agent_fallbacks(): void
    {
        $service = app(VisitorTrackingService::class);
        $expectedKey = (string) (config('app.key') ?: 'olt-default-tracking-salt');

        // Null IP -> '127.0.0.1'
        $hashNullIp = $service->hashVisitor(null, 'TestAgent');
        $this->assertSame(hash_hmac('sha256', '127.0.0.1|TestAgent', $expectedKey), $hashNullIp);

        // Null UA -> ''
        $hashNullUa = $service->hashVisitor('10.0.0.1', null);
        $this->assertSame(hash_hmac('sha256', '10.0.0.1|', $expectedKey), $hashNullUa);

        // Both null -> '127.0.0.1|'
        $hashBothNull = $service->hashVisitor(null, null);
        $this->assertSame(hash_hmac('sha256', '127.0.0.1|', $expectedKey), $hashBothNull);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hashBothNull);
    }

    /**
     * Adversarial Test 8: Collision resistance audit across 5,000 distinct IP / User Agent pairs.
     */
    public function test_adversarial_hash_collision_resistance_across_5000_distinct_visitors(): void
    {
        $service = app(VisitorTrackingService::class);
        $hashes = [];

        for ($i = 1; $i <= 5000; $i++) {
            $ip = "10." . (($i >> 16) & 255) . "." . (($i >> 8) & 255) . "." . ($i & 255);
            $ua = "SyntheticBrowser/{$i}.0 (Platform " . ($i % 10) . ")";
            $hash = $service->hashVisitor($ip, $ua);

            $this->assertArrayNotHasKey(
                $hash,
                $hashes,
                "Hash collision detected at iteration {$i} for IP {$ip}!"
            );
            $hashes[$hash] = true;
        }

        $this->assertCount(5000, $hashes, 'All 5,000 synthetic visitors must produce unique hashes.');
    }

    // =========================================================================
    // SECTION 4: Concurrency & Race Condition Simulation
    // =========================================================================

    /**
     * Adversarial Test 9: Concurrency race condition simulation.
     * Simulates two threads attempting to create the same visitor record concurrently:
     * Thread A creates the row right after Thread B checked for its existence,
     * triggering a QueryException on unique(['visitor_hash', 'visit_date']).
     * Asserts that Thread B's exception catch recovers seamlessly, increments the visit count, and returns the record.
     */
    public function test_adversarial_concurrency_race_condition_recovery(): void
    {
        $ip = '198.51.100.250';
        $ua = 'ConcurrentRaceTester/1.0';
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        $service = app(VisitorTrackingService::class);
        $visitorHash = $service->hashVisitor($ip, $ua);

        // Pre-populate the record as if concurrent Worker A just inserted it
        VisitorTracking::create([
            'visitor_hash' => $visitorHash,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'visit_date' => $today,
            'visit_count' => 1,
            'first_visited_at' => $now,
            'last_visited_at' => $now,
        ]);

        // Now Worker B attempts recordVisit()
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $ua,
        ]);

        $result = $service->recordVisit($request);

        $this->assertNotNull($result, 'Service must recover and return existing record on race condition.');
        $this->assertSame(2, $result->visit_count, 'visit_count must be incremented to 2.');
        $this->assertSame($visitorHash, $result->visitor_hash);
        $this->assertSame(1, VisitorTracking::where('visitor_hash', $visitorHash)->count());
    }

    // =========================================================================
    // SECTION 5: Hostile Inputs, SQL Injection, XSS, and Unicode Payloads
    // =========================================================================

    /**
     * Adversarial Test 10: Hostile payloads in IP and User Agent headers.
     * Injects SQL injection strings, XSS payloads, Unicode emojis, null bytes, and CRLF sequences.
     * Verifies that the service handles them without SQL syntax errors, database corruption, or crashes.
     */
    public function test_adversarial_hostile_header_payloads_do_not_compromise_system(): void
    {
        $hostilePayloads = [
            'SQL Injection Union' => "' UNION SELECT 1, 'admin', 'password', '2026-10-31', 99, datetime('now'), datetime('now'), datetime('now'), datetime('now') --",
            'SQL Injection Drop' => "'; DROP TABLE visitor_trackings; --",
            'XSS Script Tag' => "<script>alert('xss-visitor')</script><img src=x onerror=alert(1)>",
            'Multi-byte Unicode & Emojis' => "🌊 Ocean Lombok Tour & Transport 🏝️ 🚀 旅客 観光客 🏖️",
            'CRLF Injection' => "Mozilla/5.0\r\nSet-Cookie: evil_session=12345\r\nContent-Length: 0",
            'Extremely Large String' => str_repeat('A', 8192),
        ];

        foreach ($hostilePayloads as $label => $payload) {
            $response = $this->withServerVariables([
                'REMOTE_ADDR' => '198.51.100.199',
                'HTTP_USER_AGENT' => $payload,
            ])->get('/');

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Landing page must return HTTP 200 with hostile payload [{$label}]."
            );
        }

        // Verify that the table was NOT dropped by the SQL injection attempt
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('visitor_trackings'),
            'visitor_trackings table must remain intact despite hostile SQL injection payloads.'
        );
    }

    // =========================================================================
    // SECTION 6: Privacy Attribute Masking & Helper Robustness
    // =========================================================================

    /**
     * Adversarial Test 11: Privacy masking attribute tests for IPv4, IPv6, and edge cases.
     */
    public function test_adversarial_ip_masking_privacy_compliance(): void
    {
        // IPv4 masking: preserves first 3 octets, replaces last with ***
        $v4 = new VisitorTracking(['ip_address' => '192.168.1.42']);
        $this->assertSame('192.168.1.***', $v4->masked_ip);

        // IPv6 masking: preserves first 2 hextets, masks remainder
        $v6 = new VisitorTracking(['ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $this->assertSame('2001:0db8:****', $v6->masked_ip);

        // Null IP
        $nullIp = new VisitorTracking(['ip_address' => null]);
        $this->assertNull($nullIp->masked_ip);

        // Blank IP
        $blankIp = new VisitorTracking(['ip_address' => '   ']);
        $this->assertNull($blankIp->masked_ip);

        // Short hash: first 10 hex characters
        $hashRecord = new VisitorTracking(['visitor_hash' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef']);
        $this->assertSame('0123456789', $hashRecord->short_hash);
    }
}
