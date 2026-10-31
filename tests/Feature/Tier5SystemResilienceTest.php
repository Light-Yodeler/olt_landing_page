<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\DestinationResource;
use App\Filament\Resources\LoginHistoryResource;
use App\Filament\Resources\LoginHistoryResource\Pages\ListLoginHistories;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Listeners\RecordFailedLogin;
use App\Listeners\RecordSuccessfulLogin;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\VisitorTracking;
use App\Services\VisitorTrackingService;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Milestone 4 Tier 5 Adversarial System Resilience & Integrity Audit.
 *
 * Covers:
 * 1. Auth events during simultaneous login floods & request metadata mutation.
 * 2. Visitor tracking service resilience when application key is missing or rotated.
 * 3. IP masking logic across diverse IPv4 / IPv6 addresses and pathological inputs.
 * 4. Filament Resource tables under extreme pagination, empty states, and simultaneous multi-filter selections.
 * 5. Existing Filament resources and public routes regression resilience.
 */
class Tier5SystemResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        $this->superAdmin = User::create([
            'name' => 'Resilience Super Admin',
            'username' => 'resilience_superadmin',
            'password' => Hash::make('SuperAdminPass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $this->adminUser = User::create([
            'name' => 'Resilience Admin',
            'username' => 'resilience_admin',
            'password' => Hash::make('AdminPass123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $this->staffUser = User::create([
            'name' => 'Resilience Staff',
            'username' => 'resilience_staff',
            'password' => Hash::make('StaffPass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
    }

    // =========================================================================
    // DOMAIN 1: AUTH EVENT LISTENER INTERCEPTION DURING SIMULTANEOUS LOGIN FLOODS
    // =========================================================================

    /**
     * Adversarial Test 1.1: Interleaved login flood across multiple users & roles.
     * Fires 100 rapid login events (mix of valid logins, invalid passwords, non-existent users)
     * and asserts strict 1:1 attribution, correct user linkage, and accurate statuses.
     */
    public function test_simultaneous_login_flood_interleaved_success_and_failures(): void
    {
        $poolUsers = [
            $this->superAdmin,
            $this->adminUser,
            $this->staffUser,
        ];

        for ($i = 1; $i <= 5; $i++) {
            $poolUsers[] = User::create([
                'name' => "Flood User {$i}",
                'username' => "flood_user_{$i}",
                'password' => Hash::make("SecretPassword{$i}!"),
                'role' => ($i % 2 === 0) ? User::ROLE_ADMIN : User::ROLE_STAFF,
                'must_change_password' => false,
            ]);
        }

        $initialCount = LoginHistory::count();
        $this->assertSame(0, $initialCount);

        $totalEvents = 100;
        $expectedSuccess = 0;
        $expectedFailedExisting = 0;
        $expectedFailedNonExistent = 0;

        for ($i = 0; $i < $totalEvents; $i++) {
            $scenario = $i % 4;

            if ($scenario === 0 || $scenario === 1) {
                // Successful login
                $user = $poolUsers[$i % count($poolUsers)];
                event(new Login('web', $user, false));
                $expectedSuccess++;
            } elseif ($scenario === 2) {
                // Failed login with known user (wrong password)
                $user = $poolUsers[$i % count($poolUsers)];
                event(new Failed('web', $user, ['username' => $user->username, 'password' => 'WrongPassword!']));
                $expectedFailedExisting++;
            } else {
                // Failed login with non-existent identifier
                $fakeUsername = "ghost_intruder_{$i}";
                event(new Failed('web', null, ['username' => $fakeUsername, 'password' => 'Invalid!']));
                $expectedFailedNonExistent++;
            }
        }

        $finalCount = LoginHistory::count();
        $this->assertSame($totalEvents, $finalCount);

        $this->assertSame($expectedSuccess, LoginHistory::where('status', LoginHistory::STATUS_SUCCESS)->count());
        $this->assertSame(
            $expectedFailedExisting + $expectedFailedNonExistent,
            LoginHistory::where('status', LoginHistory::STATUS_FAILED)->count()
        );

        // Every successful record must link to an existing user
        $successfulRecords = LoginHistory::where('status', LoginHistory::STATUS_SUCCESS)->get();
        foreach ($successfulRecords as $record) {
            $this->assertNotNull($record->user_id);
            $this->assertNotEmpty($record->attempted_identifier);
            $this->assertDatabaseHas('users', ['id' => $record->user_id]);
        }

        // Every failed attempt for a non-existent user must have null user_id
        $ghostRecords = LoginHistory::where('attempted_identifier', 'like', 'ghost_intruder_%')->get();
        $this->assertSame($expectedFailedNonExistent, $ghostRecords->count());
        foreach ($ghostRecords as $record) {
            $this->assertNull($record->user_id);
            $this->assertSame(LoginHistory::STATUS_FAILED, $record->status);
        }
    }

    /**
     * Adversarial Test 1.2: Login flood under rapid request metadata mutation.
     * Simulates 30 consecutive login events while mutating IP addresses and User Agents
     * to prove zero state leak or cross-contamination between successive invocations.
     */
    public function test_login_flood_under_request_metadata_mutation(): void
    {
        $testIps = [
            '192.168.1.10',
            '10.0.0.5',
            '172.16.20.1',
            '2001:db8::ff00:42',
            '::1',
            '127.0.0.1',
        ];

        $testAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'curl/7.88.1',
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'OLT-MobileApp/1.2.0 (Android 14; Pixel 8)',
        ];

        for ($i = 0; $i < 30; $i++) {
            $targetIp = $testIps[$i % count($testIps)];
            $targetUa = $testAgents[$i % count($testAgents)];

            $this->withServerVariables([
                'REMOTE_ADDR' => $targetIp,
                'HTTP_USER_AGENT' => $targetUa,
            ]);

            if ($i % 2 === 0) {
                event(new Login('web', $this->superAdmin, false));
            } else {
                event(new Failed('web', null, ['username' => "mutated_user_{$i}"]));
            }

            $latestRecord = LoginHistory::latest('id')->first();
            $this->assertNotNull($latestRecord);
            $this->assertSame($targetIp, $latestRecord->ip_address);
            $this->assertSame($targetUa, $latestRecord->user_agent);
        }

        $this->assertSame(30, LoginHistory::count());
    }

    /**
     * Adversarial Test 1.3: Pathological and hostile authentication payloads.
     * Tests overlong identifiers (5000 chars), emojis, password isolation in credentials,
     * and empty credentials fallback.
     */
    public function test_login_event_listeners_under_pathological_payloads(): void
    {
        // 1. Overlong identifier (5000 characters) - must be truncated to 150 chars without database failure
        $overlongIdentifier = str_repeat('adversary_payload_', 300);
        event(new Failed('web', null, ['username' => $overlongIdentifier]));

        $recordOverlong = LoginHistory::latest('id')->first();
        $this->assertNotNull($recordOverlong);
        $this->assertLessThanOrEqual(150, mb_strlen($recordOverlong->attempted_identifier));
        $this->assertSame(mb_substr($overlongIdentifier, 0, 150), $recordOverlong->attempted_identifier);

        // 2. Multibyte unicode and emojis in identifier
        $unicodeIdentifier = '🌴🏝️_lombok_tour_admin_🌊✨';
        event(new Failed('web', null, ['username' => $unicodeIdentifier]));

        $recordUnicode = LoginHistory::latest('id')->first();
        $this->assertNotNull($recordUnicode);
        $this->assertSame($unicodeIdentifier, $recordUnicode->attempted_identifier);

        // 3. Strict password non-persistence: credentials payload containing plaintext passwords
        $sensitivePassword = 'SuperSecretPlaintextPassword!#99';
        $sensitiveToken = 'bearer_token_xyz_secret_9999';
        event(new Failed('web', null, [
            'username' => 'victim_user',
            'password' => $sensitivePassword,
            'token' => $sensitiveToken,
        ]));

        $recordSec = LoginHistory::latest('id')->first();
        $this->assertNotNull($recordSec);
        $this->assertSame('victim_user', $recordSec->attempted_identifier);
        $this->assertStringNotContainsString($sensitivePassword, (string) $recordSec->attempted_identifier);
        $this->assertStringNotContainsString($sensitivePassword, (string) $recordSec->ip_address);
        $this->assertStringNotContainsString($sensitivePassword, (string) $recordSec->user_agent);

        // 4. Empty or whitespace-only identifier defaults safely to 'unknown'
        event(new Failed('web', null, ['username' => '   ']));
        $recordEmpty = LoginHistory::latest('id')->first();
        $this->assertNotNull($recordEmpty);
        $this->assertSame('unknown', $recordEmpty->attempted_identifier);

        // 5. Completely empty credentials array defaults to 'unknown'
        event(new Failed('web', null, []));
        $recordNone = LoginHistory::latest('id')->first();
        $this->assertNotNull($recordNone);
        $this->assertSame('unknown', $recordNone->attempted_identifier);
    }

    // =========================================================================
    // DOMAIN 2: VISITOR TRACKING SERVICE RESILIENCE (MISSING / ROTATED APP KEY)
    // =========================================================================

    /**
     * Adversarial Test 2.1: Missing application key fallback resilience.
     * Verifies that when config('app.key') is null or empty, hashVisitor falls back
     * to a hardcoded salt without fatal errors, generating a deterministic 64-char hex hash.
     */
    public function test_visitor_tracking_hash_when_app_key_is_missing(): void
    {
        $service = app(VisitorTrackingService::class);

        // Test with null app.key
        Config::set('app.key', null);
        $hashNull = $service->hashVisitor('192.168.1.1', 'Mozilla/5.0');
        $this->assertSame(64, strlen($hashNull));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hashNull);

        // Must be deterministic
        $this->assertSame($hashNull, $service->hashVisitor('192.168.1.1', 'Mozilla/5.0'));

        // Test with empty string app.key
        Config::set('app.key', '');
        $hashEmpty = $service->hashVisitor('192.168.1.1', 'Mozilla/5.0');
        $this->assertSame($hashNull, $hashEmpty);

        // Verify recordVisit works end-to-end even with missing key
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.20.30.40',
            'HTTP_USER_AGENT' => 'NoKeyBrowser/1.0',
        ]);
        $record = $service->recordVisit($request);
        $this->assertNotNull($record);
        $this->assertSame(64, strlen($record->visitor_hash));
        $this->assertSame(1, $record->visit_count);
    }

    /**
     * Adversarial Test 2.2: Mid-day application key rotation resilience.
     * When the application encryption key is rotated during active production traffic:
     * 1. Existing visits were recorded under Old Key (Hash A).
     * 2. Subsequent visits from the same visitor use New Key (Hash B).
     * 3. The compound unique index (['visitor_hash', 'visit_date']) does NOT collide.
     * 4. Both records persist cleanly and hit aggregation accurately sums hits.
     */
    public function test_visitor_tracking_resilience_when_app_key_is_rotated_mid_day(): void
    {
        $service = app(VisitorTrackingService::class);

        // Phase 1: Traffic under Initial Key
        Config::set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.195',
            'HTTP_USER_AGENT' => 'RotatedClient/1.0',
        ]);

        $firstVisit = $service->recordVisit($request);
        $this->assertNotNull($firstVisit);
        $hashBeforeRotation = $firstVisit->visitor_hash;
        $this->assertSame(1, $firstVisit->visit_count);

        // Revisit under Key A increments count
        $revisitBefore = $service->recordVisit($request);
        $this->assertSame(2, $revisitBefore->visit_count);
        $this->assertSame($firstVisit->id, $revisitBefore->id);

        // Phase 2: Key Rotation Occurs Mid-Day
        Config::set('app.key', 'base64:BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=');

        // Phase 3: Traffic from Same Visitor under New Key
        $visitAfterRotation = $service->recordVisit($request);
        $this->assertNotNull($visitAfterRotation);
        $hashAfterRotation = $visitAfterRotation->visitor_hash;

        // Hashes must differ because key/salt changed
        $this->assertNotSame($hashBeforeRotation, $hashAfterRotation);

        // Both records must exist in database without collision
        $this->assertSame(2, VisitorTracking::count());
        $this->assertSame(1, $visitAfterRotation->visit_count);

        // Phase 4: Subsequent hit under New Key increments new record
        $revisitAfter = $service->recordVisit($request);
        $this->assertSame(2, $revisitAfter->visit_count);
        $this->assertSame($visitAfterRotation->id, $revisitAfter->id);

        // Total daily hits must be 2 + 2 = 4
        $this->assertSame(4, $service->getTodayTotalHits());
        $this->assertSame(2, $service->getTodayUniqueCount());
    }

    /**
     * Adversarial Test 2.3: Arbitrary and unusual key format resilience.
     * Tests 32-char ASCII, base64, 64-char hex, and non-ASCII binary strings.
     */
    public function test_visitor_tracking_with_arbitrary_key_formats(): void
    {
        $service = app(VisitorTrackingService::class);

        $keys = [
            'Standard 32-char' => '12345678901234567890123456789012',
            'Base64 encoded' => 'base64:' . base64_encode(random_bytes(32)),
            '64-char hex' => bin2hex(random_bytes(32)),
            'Non-ASCII UTF-8' => '🔐🔒🔑_kunci_rahasia_lombok_!@#',
            'Single char key' => 'X',
        ];

        foreach ($keys as $label => $key) {
            Config::set('app.key', $key);
            $hash = $service->hashVisitor('10.0.0.1', 'ArbitraryKeyTest');
            $this->assertSame(64, strlen($hash), "Failed for key type: {$label}");
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
        }
    }

    // =========================================================================
    // DOMAIN 3: IP MASKING LOGIC ACROSS DIVERSE IPv4 / IPv6 ADDRESSES & EDGE CASES
    // =========================================================================

    /**
     * Adversarial Test 3.1: IP Masking exhaustive matrix.
     * Verifies masking rules across public IPv4, private IPv4, edge IPv4,
     * standard IPv6, compressed IPv6, loopback IPv6, link-local IPv6,
     * truncated IPs, overlong strings, SQL injection strings, and null/empty inputs.
     */
    public function test_ip_masking_comprehensive_matrix(): void
    {
        $testMatrix = [
            // Standard IPv4 Public
            '8.8.8.8' => '8.8.8.***',
            '142.250.190.46' => '142.250.190.***',
            '1.1.1.1' => '1.1.1.***',

            // Standard IPv4 Private
            '192.168.1.1' => '192.168.1.***',
            '10.0.0.1' => '10.0.0.***',
            '172.16.254.1' => '172.16.254.***',

            // Boundary IPv4
            '127.0.0.1' => '127.0.0.***',
            '0.0.0.0' => '0.0.0.***',
            '255.255.255.255' => '255.255.255.***',

            // IPv6 Standard & Full
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334' => '2001:0db8:****',
            '2607:f8b0:4005:805::200e' => '2607:f8b0:****',

            // IPv6 Compressed & Local
            '2001:db8::1' => '2001:db8:****',
            'fe80::1' => 'fe80::****',
            '::1' => '::****',
            '::' => '::****',
            '::ffff:192.0.2.1' => '::****',

            // Invalid / Pathological IPs (must gracefully fallback to raw string without throwing)
            '192.168.1' => '192.168.1',
            '192.168.1.1.1' => '192.168.1.1.1',
            '300.300.300.300' => '300.300.300.300',
            'invalid-ip-string' => 'invalid-ip-string',
            '127.0.0.1; DROP TABLE users;' => '127.0.0.1; DROP TABLE users;',
            '<script>alert("ip")</script>' => '<script>alert("ip")</script>',
        ];

        foreach ($testMatrix as $inputIp => $expectedMasked) {
            $visitor = new VisitorTracking(['ip_address' => $inputIp]);
            $actual = $visitor->masked_ip;
            $this->assertSame($expectedMasked, $actual, "Failed for IP input: {$inputIp}");
        }

        // Null and blank edge cases
        $nullVisitor = new VisitorTracking(['ip_address' => null]);
        $this->assertNull($nullVisitor->masked_ip);

        $emptyVisitor = new VisitorTracking(['ip_address' => '']);
        $this->assertNull($emptyVisitor->masked_ip);

        $whitespaceVisitor = new VisitorTracking(['ip_address' => '   ']);
        $this->assertNull($whitespaceVisitor->masked_ip);
    }

    // =========================================================================
    // DOMAIN 4: FILAMENT RESOURCE TABLES (EXTREME PAGINATION, EMPTY STATES, MULTI-FILTER)
    // =========================================================================

    /**
     * Adversarial Test 4.1: Extreme pagination beyond existing dataset bounds.
     * Requests page 999999 when only 10 records exist via Livewire and HTTP endpoints.
     * Verifies that neither table throws NotFoundHttpException or DivisionByZero.
     */
    public function test_filament_tables_extreme_pagination(): void
    {
        $this->actingAs($this->superAdmin);

        // Seed 10 records for login history
        for ($i = 1; $i <= 10; $i++) {
            LoginHistory::create([
                'user_id' => $this->superAdmin->id,
                'attempted_identifier' => "page_user_{$i}",
                'status' => LoginHistory::STATUS_SUCCESS,
                'ip_address' => "10.0.0.{$i}",
                'login_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        // Seed 10 records for visitor tracking
        for ($i = 1; $i <= 10; $i++) {
            VisitorTracking::create([
                'visitor_hash' => hash('sha256', "pagination_seed_{$i}"),
                'ip_address' => "172.16.0.{$i}",
                'visit_date' => Carbon::now()->toDateString(),
                'visit_count' => 1,
                'first_visited_at' => Carbon::now(),
                'last_visited_at' => Carbon::now(),
            ]);
        }

        // 1. Livewire component extreme pagination
        Livewire::test(ListLoginHistories::class)
            ->call('gotoPage', 999999)
            ->assertSuccessful();

        Livewire::test(ListVisitorTrackings::class)
            ->call('gotoPage', 999999)
            ->assertSuccessful();

        // 2. HTTP direct request extreme pagination
        $this->get('/admin/login-histories?page=999999')->assertStatus(200);
        $this->get('/admin/visitor-trackings?page=999999')->assertStatus(200);
    }

    /**
     * Adversarial Test 4.2: Pathological simultaneous multi-filter combinations.
     * Applies conflicting and inverted filters simultaneously:
     * - Status filter + Non-existent User filter + Inverted Date Range (start > end) + Search Query
     * Proves query builder handles all conditions safely and renders empty state cleanly.
     */
    public function test_filament_simultaneous_multi_filter_stress(): void
    {
        $this->actingAs($this->superAdmin);

        // Populate baseline data
        LoginHistory::create([
            'user_id' => $this->superAdmin->id,
            'attempted_identifier' => $this->superAdmin->username,
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '192.168.1.1',
            'login_at' => Carbon::now(),
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'multi_filter_visitor'),
            'ip_address' => '192.168.1.1',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 5,
            'first_visited_at' => Carbon::now(),
            'last_visited_at' => Carbon::now(),
        ]);

        // 1. Multi-filter stress on Login History table
        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_FAILED)
            ->filterTable('user_id', 999999) // Non-existent user
            ->filterTable('login_at', [
                'login_from' => '2099-01-01',
                'login_until' => '2020-01-01', // Inverted date range
            ])
            ->searchTable('impossible_search_query_#$!')
            ->assertSuccessful()
            ->assertCountTableRecords(0)
            ->assertSee('No login history records found');

        // 2. Multi-filter stress on Visitor Tracking table
        Livewire::test(ListVisitorTrackings::class)
            ->filterTable('visitor_type', 'single') // Visit count is 5 (returning), so single matches 0
            ->filterTable('visit_date', [
                'date_from' => '2099-01-01',
                'date_until' => '2020-01-01', // Inverted date range
            ])
            ->searchTable('nonexistent_hash_string')
            ->assertSuccessful()
            ->assertCountTableRecords(0)
            ->assertSee('No visitor tracking records recorded');
    }

    /**
     * Adversarial Test 4.3: Completely empty table rendering & sorting resilience.
     * When database tables contain zero records, verifies both tables mount cleanly,
     * render antislop empty states, and survive sorting across all sortable columns.
     */
    public function test_filament_tables_completely_empty_database_resilience(): void
    {
        $this->actingAs($this->superAdmin);

        LoginHistory::query()->delete();
        VisitorTracking::query()->delete();

        $this->assertSame(0, LoginHistory::count());
        $this->assertSame(0, VisitorTracking::count());

        // Test Login History Empty State & All Column Sorts
        $loginHistorySortColumns = ['attempted_identifier', 'ip_address', 'login_at'];
        foreach ($loginHistorySortColumns as $col) {
            Livewire::test(ListLoginHistories::class)
                ->sortTable($col, 'asc')
                ->assertSuccessful()
                ->assertSee('No login history records found');

            Livewire::test(ListLoginHistories::class)
                ->sortTable($col, 'desc')
                ->assertSuccessful()
                ->assertSee('No login history records found');
        }

        // Test Visitor Tracking Empty State & All Column Sorts
        $visitorTrackingSortColumns = ['visit_date', 'ip_address', 'visit_count', 'last_visited_at'];
        foreach ($visitorTrackingSortColumns as $col) {
            Livewire::test(ListVisitorTrackings::class)
                ->sortTable($col, 'asc')
                ->assertSuccessful()
                ->assertSee('No visitor tracking records recorded');

            Livewire::test(ListVisitorTrackings::class)
                ->sortTable($col, 'desc')
                ->assertSuccessful()
                ->assertSee('No visitor tracking records recorded');
        }
    }

    // =========================================================================
    // DOMAIN 5: EXISTING FEATURES & PUBLIC ROUTES REGRESSION RESILIENCE
    // =========================================================================

    /**
     * Adversarial Test 5.1: Public routes regression resilience.
     * Asserts that public landing page routes ('/', '/id', '/lang/{locale}') remain 100% operational,
     * render localized views, and preserve visitor tracking without side effects.
     */
    public function test_public_routes_regression_resilience(): void
    {
        // 1. English landing page '/'
        $responseEn = $this->get('/');
        $responseEn->assertStatus(200);
        $responseEn->assertViewIs('home');

        // Verify visitor tracking recorded visit
        $this->assertSame(1, VisitorTracking::count());

        // 2. Indonesian landing page '/id'
        $responseId = $this->get('/id');
        $responseId->assertStatus(200);
        $responseId->assertViewIs('home');

        // 3. Locale switch endpoint: redirects and sets cookie 'olt_locale'
        $responseSwitch = $this->get('/lang/id');
        $responseSwitch->assertStatus(302);
        $responseSwitch->assertRedirect('/id');
        $responseSwitch->assertCookie('olt_locale', 'id');

        $responseSwitchEn = $this->get('/lang/en');
        $responseSwitchEn->assertStatus(302);
        $responseSwitchEn->assertRedirect('/');
        $responseSwitchEn->assertCookie('olt_locale', 'en');
    }

    /**
     * Adversarial Test 5.2: SuperAdmin access verification across all panel resources.
     */
    public function test_superadmin_has_full_access_to_all_filament_resources(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get('/admin/destinations')->assertStatus(200);
        $this->get('/admin/services')->assertStatus(200);
        $this->get('/admin/faqs')->assertStatus(200);
        $this->get('/admin/hero-slides')->assertStatus(200);
        $this->get('/admin/users')->assertStatus(200);
        $this->get('/admin/manage-site-settings')->assertStatus(200);
        $this->get('/admin/login-histories')->assertStatus(200);
        $this->get('/admin/visitor-trackings')->assertStatus(200);
    }

    /**
     * Adversarial Test 5.3: Admin role access matrix to panel resources.
     * Admin can manage content and site settings, but is strictly FORBIDDEN
     * from User management, Login History, and Visitor Tracking.
     */
    public function test_admin_access_matrix_to_filament_resources(): void
    {
        $this->actingAs($this->adminUser);

        $this->get('/admin/destinations')->assertStatus(200);
        $this->get('/admin/services')->assertStatus(200);
        $this->get('/admin/manage-site-settings')->assertStatus(200);

        // Forbidden boundaries for Admin
        $this->get('/admin/users')->assertStatus(403);
        $this->get('/admin/login-histories')->assertStatus(403);
        $this->get('/admin/visitor-trackings')->assertStatus(403);
    }

    /**
     * Adversarial Test 5.4: Staff role access matrix to panel resources.
     * Staff can manage destinations/services, but is strictly FORBIDDEN
     * from Site Settings, User management, Login History, and Visitor Tracking.
     */
    public function test_staff_access_matrix_to_filament_resources(): void
    {
        $this->actingAs($this->staffUser);

        $this->get('/admin/destinations')->assertStatus(200);
        $this->get('/admin/services')->assertStatus(200);

        // Forbidden boundaries for Staff
        $this->get('/admin/manage-site-settings')->assertStatus(403);
        $this->get('/admin/users')->assertStatus(403);
        $this->get('/admin/login-histories')->assertStatus(403);
        $this->get('/admin/visitor-trackings')->assertStatus(403);
    }

    /**
     * Adversarial Test 5.5: Unauthenticated guest redirected from all panel resources.
     */
    public function test_unauthenticated_guest_redirected_from_all_filament_resources(): void
    {
        Auth::logout();

        $this->get('/admin/destinations')->assertStatus(302)->assertRedirect('/admin/login');
        $this->get('/admin/manage-site-settings')->assertStatus(302)->assertRedirect('/admin/login');
        $this->get('/admin/users')->assertStatus(302)->assertRedirect('/admin/login');
        $this->get('/admin/login-histories')->assertStatus(302)->assertRedirect('/admin/login');
        $this->get('/admin/visitor-trackings')->assertStatus(302)->assertRedirect('/admin/login');
    }

    /**
     * Adversarial Test 5.6: Landing page fail-open resilience under catastrophic visitor service failure.
     * Even if the visitor tracking service throws an unexpected exception during execution,
     * HomeController::index must gracefully catch it and return HTTP 200 with full view data.
     */
    public function test_landing_page_fails_open_when_tracking_service_crashes(): void
    {
        $mockService = $this->createMock(VisitorTrackingService::class);
        $mockService->method('recordVisit')
            ->willThrowException(new Exception('Catastrophic failure in visitor tracking service!'));

        $this->app->instance(VisitorTrackingService::class, $mockService);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('services');
        $response->assertViewHas('destinations');
    }
}
