<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LoginHistoryResource;
use App\Filament\Resources\LoginHistoryResource\Pages\ListLoginHistories;
use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Http\Controllers\HomeController;
use App\Listeners\RecordFailedLogin;
use App\Listeners\RecordSuccessfulLogin;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\VisitorTracking;
use App\Services\VisitorTrackingService;
use DateTime;
use Error;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class Tier5AdversarialHardeningTest extends TestCase
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
            'name' => 'Super Admin Hardening',
            'username' => 't5_superadmin',
            'password' => Hash::make('SuperHardenedPass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Hardening',
            'username' => 't5_admin',
            'password' => Hash::make('AdminHardenedPass123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $this->staffUser = User::create([
            'name' => 'Staff Hardening',
            'username' => 't5_staff',
            'password' => Hash::make('StaffHardenedPass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
    }

    // =========================================================================
    // SECTION 1: VisitorTracking Model Boundary & Scopes Audit
    // =========================================================================

    /**
     * Test 1: VisitorTracking::isReturning boundary conditions.
     */
    public function test_visitor_tracking_is_returning_boundary_conditions(): void
    {
        $v0 = new VisitorTracking(['visit_count' => 0]);
        $this->assertFalse($v0->isReturning(), 'visit_count 0 must not be returning');

        $v1 = new VisitorTracking(['visit_count' => 1]);
        $this->assertFalse($v1->isReturning(), 'visit_count 1 must not be returning');

        $v2 = new VisitorTracking(['visit_count' => 2]);
        $this->assertTrue($v2->isReturning(), 'visit_count 2 must be returning');

        $v100 = new VisitorTracking(['visit_count' => 100]);
        $this->assertTrue($v100->isReturning(), 'visit_count 100 must be returning');
    }

    /**
     * Test 2: VisitorTracking::recordHit with default null and explicit DateTimeInterface.
     */
    public function test_visitor_tracking_record_hit_with_explicit_and_null_timestamps(): void
    {
        $visitor = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'explicit-hit-test'),
            'ip_address' => '203.0.113.10',
            'user_agent' => 'ExplicitHitAgent/1.0',
            'visit_date' => '2026-10-31',
            'visit_count' => 1,
            'first_visited_at' => Carbon::parse('2026-10-31 08:00:00'),
            'last_visited_at' => Carbon::parse('2026-10-31 08:00:00'),
        ]);

        // Hit 1: Null timestamp (defaults to now)
        Carbon::setTestNow('2026-10-31 10:30:00');
        $result1 = $visitor->recordHit();
        $this->assertTrue($result1);
        $visitor->refresh();
        $this->assertSame(2, $visitor->visit_count);
        $this->assertSame('2026-10-31 10:30:00', $visitor->last_visited_at->toDateTimeString());
        $this->assertSame('2026-10-31 08:00:00', $visitor->first_visited_at->toDateTimeString());

        // Hit 2: Explicit DateTimeInterface
        $customTime = new DateTime('2026-10-31 14:45:00');
        $result2 = $visitor->recordHit($customTime);
        $this->assertTrue($result2);
        $visitor->refresh();
        $this->assertSame(3, $visitor->visit_count);
        $this->assertSame('2026-10-31 14:45:00', $visitor->last_visited_at->toDateTimeString());
        $this->assertSame('2026-10-31 08:00:00', $visitor->first_visited_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /**
     * Test 3: VisitorTracking::scopeForDate accepts string, CarbonInterface, and DateTimeInterface.
     */
    public function test_visitor_tracking_scope_for_date_accepts_all_contract_types(): void
    {
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'date-scope-target'),
            'visit_date' => '2026-10-25',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-25 09:00:00',
            'last_visited_at' => '2026-10-25 09:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'date-scope-decoy'),
            'visit_date' => '2026-10-26',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-26 09:00:00',
            'last_visited_at' => '2026-10-26 09:00:00',
        ]);

        // Branch 1: string
        $fromStr = VisitorTracking::forDate('2026-10-25')->get();
        $this->assertCount(1, $fromStr);
        $this->assertSame('2026-10-25', $fromStr->first()->visit_date->toDateString());

        // Branch 2: CarbonInterface
        $carbonDate = Carbon::parse('2026-10-25');
        $fromCarbon = VisitorTracking::forDate($carbonDate)->get();
        $this->assertCount(1, $fromCarbon);
        $this->assertSame('2026-10-25', $fromCarbon->first()->visit_date->toDateString());

        // Branch 3: DateTimeInterface
        $dtDate = new DateTime('2026-10-25');
        $fromDt = VisitorTracking::forDate($dtDate)->get();
        $this->assertCount(1, $fromDt);
        $this->assertSame('2026-10-25', $fromDt->first()->visit_date->toDateString());
    }

    /**
     * Test 4: VisitorTracking::scopeDateBetween accepts mixed combinations of string, Carbon, and DateTime.
     */
    public function test_visitor_tracking_scope_date_between_accepts_all_contract_type_permutations(): void
    {
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'range-record-1'),
            'visit_date' => '2026-10-15',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-15 10:00:00',
            'last_visited_at' => '2026-10-15 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'range-record-2'),
            'visit_date' => '2026-10-20',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-20 10:00:00',
            'last_visited_at' => '2026-10-20 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'range-record-3'),
            'visit_date' => '2026-10-28',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-28 10:00:00',
            'last_visited_at' => '2026-10-28 10:00:00',
        ]);

        // String to String
        $results1 = VisitorTracking::dateBetween('2026-10-18', '2026-10-30')->get();
        $this->assertCount(2, $results1);

        // Carbon to Carbon
        $results2 = VisitorTracking::dateBetween(Carbon::parse('2026-10-18'), Carbon::parse('2026-10-30'))->get();
        $this->assertCount(2, $results2);

        // DateTime to DateTime
        $results3 = VisitorTracking::dateBetween(new DateTime('2026-10-18'), new DateTime('2026-10-30'))->get();
        $this->assertCount(2, $results3);

        // Mixed: String start, DateTimeInterface end
        $results4 = VisitorTracking::dateBetween('2026-10-10', new DateTime('2026-10-22'))->get();
        $this->assertCount(2, $results4);
    }

    /**
     * Test 5: VisitorTracking short_hash attribute under edge cases.
     */
    public function test_visitor_tracking_short_hash_boundaries(): void
    {
        // 64-char full hash
        $v1 = new VisitorTracking(['visitor_hash' => str_repeat('a', 64)]);
        $this->assertSame('aaaaaaaaaa', $v1->short_hash);
        $this->assertSame(10, strlen($v1->short_hash));

        // Short string (< 10 chars)
        $v2 = new VisitorTracking(['visitor_hash' => 'short']);
        $this->assertSame('short', $v2->short_hash);

        // Empty string
        $v3 = new VisitorTracking(['visitor_hash' => '']);
        $this->assertSame('', $v3->short_hash);
    }

    /**
     * Test 6: VisitorTracking masked_ip attribute comprehensive matrix.
     */
    public function test_visitor_tracking_masked_ip_comprehensive_matrix(): void
    {
        // 1. Null, empty, and whitespace
        $this->assertNull((new VisitorTracking(['ip_address' => null]))->masked_ip);
        $this->assertNull((new VisitorTracking(['ip_address' => '']))->masked_ip);
        $this->assertNull((new VisitorTracking(['ip_address' => '   ']))->masked_ip);

        // 2. Standard IPv4
        $this->assertSame('192.168.1.***', (new VisitorTracking(['ip_address' => '192.168.1.50']))->masked_ip);
        $this->assertSame('10.0.0.***', (new VisitorTracking(['ip_address' => '10.0.0.1']))->masked_ip);
        $this->assertSame('127.0.0.***', (new VisitorTracking(['ip_address' => '127.0.0.1']))->masked_ip);

        // 3. Standard IPv6
        $this->assertSame(
            '2001:0db8:****',
            (new VisitorTracking(['ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']))->masked_ip
        );

        // 4. Compressed IPv6
        $this->assertSame('fe80::****', (new VisitorTracking(['ip_address' => 'fe80::1']))->masked_ip);
        $this->assertSame('::****', (new VisitorTracking(['ip_address' => '::1']))->masked_ip);

        // 5. Hostname / non-IP fallback branch (line 152)
        $this->assertSame('gateway.internal.lan', (new VisitorTracking(['ip_address' => 'gateway.internal.lan']))->masked_ip);
        $this->assertSame('invalid-ip-string', (new VisitorTracking(['ip_address' => 'invalid-ip-string']))->masked_ip);
        $this->assertSame('999.999.999.999', (new VisitorTracking(['ip_address' => '999.999.999.999']))->masked_ip);
    }

    // =========================================================================
    // SECTION 2: VisitorTrackingService White-Box Hardening
    // =========================================================================

    /**
     * Test 7: VisitorTrackingService hash deterministic behavior with empty/null app key.
     */
    public function test_visitor_tracking_service_hash_with_null_and_empty_app_key_fallback(): void
    {
        $service = app(VisitorTrackingService::class);

        // 1. App key is null
        Config::set('app.key', null);
        $hash1 = $service->hashVisitor('203.0.113.1', 'Mozilla/5.0 TestAgent');
        $this->assertSame(64, strlen($hash1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash1);

        // 2. App key is empty string
        Config::set('app.key', '');
        $hash2 = $service->hashVisitor('203.0.113.1', 'Mozilla/5.0 TestAgent');
        $this->assertSame($hash1, $hash2, 'Empty string key must match null key fallback salt');

        // 3. Null IP and null UserAgent fallback
        $hash3 = $service->hashVisitor(null, null);
        $this->assertSame(64, strlen($hash3));
        $expectedFallback = hash_hmac('sha256', '127.0.0.1|', 'olt-default-tracking-salt');
        $this->assertSame($expectedFallback, $hash3);
    }

    /**
     * Test 8: VisitorTrackingService QueryException rethrow and outer catch fail-open.
     */
    public function test_visitor_tracking_service_query_exception_rethrow_and_outer_catch_fail_open(): void
    {
        $service = app(VisitorTrackingService::class);

        // Drop the visitor_trackings table temporarily to trigger a non-recoverable QueryException
        DB::statement('DROP TABLE visitor_trackings');

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Visitor tracking skipped due to error')
                    && isset($context['exception'])
                    && $context['exception'] instanceof QueryException;
            });

        $request = Request::create('/', 'GET');
        $result = $service->recordVisit($request);

        $this->assertNull($result, 'Service must fail open and return null when table is missing');

        // Restore table for subsequent tests
        $this->artisan('migrate');
    }

    /**
     * Test 9: VisitorTrackingService aggregation queries with zero records and multi-day isolation.
     */
    public function test_visitor_tracking_service_aggregation_empty_and_populated_dates(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $service = app(VisitorTrackingService::class);

        // Zero records
        $this->assertSame(0, $service->getTodayUniqueCount());
        $this->assertSame(0, $service->getTodayTotalHits());

        // Create records for yesterday
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'yesterday-visitor-1'),
            'visit_date' => '2026-10-30',
            'visit_count' => 10,
            'first_visited_at' => '2026-10-30 10:00:00',
            'last_visited_at' => '2026-10-30 10:00:00',
        ]);

        // Yesterday records must NOT count toward today's stats
        $this->assertSame(0, $service->getTodayUniqueCount());
        $this->assertSame(0, $service->getTodayTotalHits());

        // Create 2 records for today with 3 and 7 hits
        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'today-visitor-1'),
            'visit_date' => '2026-10-31',
            'visit_count' => 3,
            'first_visited_at' => '2026-10-31 10:00:00',
            'last_visited_at' => '2026-10-31 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'today-visitor-2'),
            'visit_date' => '2026-10-31',
            'visit_count' => 7,
            'first_visited_at' => '2026-10-31 11:00:00',
            'last_visited_at' => '2026-10-31 11:00:00',
        ]);

        $this->assertSame(2, $service->getTodayUniqueCount());
        $this->assertSame(10, $service->getTodayTotalHits());

        Carbon::setTestNow();
    }

    // =========================================================================
    // SECTION 3: LoginHistory Model & Listeners White-Box Hardening
    // =========================================================================

    /**
     * Test 10: LoginHistory status helpers and Eloquent scopes.
     */
    public function test_login_history_status_helpers_and_scopes(): void
    {
        $success = LoginHistory::create([
            'attempted_identifier' => 'test_user_success',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.1',
            'login_at' => now(),
        ]);

        $failed = LoginHistory::create([
            'attempted_identifier' => 'test_user_failed',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.0.0.2',
            'login_at' => now(),
        ]);

        $other = new LoginHistory(['status' => 'pending']);

        // Helpers
        $this->assertTrue($success->isSuccess());
        $this->assertFalse($success->isFailed());

        $this->assertFalse($failed->isSuccess());
        $this->assertTrue($failed->isFailed());

        $this->assertFalse($other->isSuccess());
        $this->assertFalse($other->isFailed());

        // Scopes
        $this->assertSame(1, LoginHistory::success()->count());
        $this->assertSame($success->id, LoginHistory::success()->first()->id);

        $this->assertSame(1, LoginHistory::failed()->count());
        $this->assertSame($failed->id, LoginHistory::failed()->first()->id);
    }

    /**
     * Test 11: RecordSuccessfulLogin with abnormal, non-numeric, or null user identifiers.
     */
    public function test_record_successful_login_with_abnormal_user_identifiers(): void
    {
        $listener = new RecordSuccessfulLogin();

        // 1. User with numeric ID persisted in database but null username and email
        $realUser = User::create([
            'name' => 'Real Numeric Fallback User',
            'username' => 'real_num_fallback',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
        $realUser->username = null;
        $realUser->email = null;

        $event1 = new Login('web', $realUser, false);
        $listener->handle($event1);

        $record1 = LoginHistory::latest('id')->first();
        $this->assertSame($realUser->id, $record1->user_id);
        $this->assertSame((string) $realUser->id, $record1->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $record1->status);

        // 2. User with non-numeric string ID (e.g. UUID)
        $userUuid = new User();
        $userUuid->id = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $userUuid->username = 'uuid_user';
        $event2 = new Login('web', $userUuid, false);
        $listener->handle($event2);

        $record2 = LoginHistory::latest('id')->first();
        $this->assertNull($record2->user_id, 'Non-numeric user ID must result in null user_id');
        $this->assertSame('uuid_user', $record2->attempted_identifier);

        // 3. User with ID 0 or negative ID
        $userZeroId = new User();
        $userZeroId->id = 0;
        $userZeroId->username = 'zero_id_user';
        $event3 = new Login('web', $userZeroId, false);
        $listener->handle($event3);

        $record3 = LoginHistory::latest('id')->first();
        $this->assertNull($record3->user_id, 'Zero user ID must result in null user_id');

        // 4. Whitespace-only username
        $userWhitespace = new User();
        $userWhitespace->id = null;
        $userWhitespace->username = '   ';
        $userWhitespace->email = null;
        $event4 = new Login('web', $userWhitespace, false);
        $listener->handle($event4);

        $record4 = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record4->attempted_identifier);

        // 5. Extremely long multibyte identifier (> 150 chars)
        $longIdentifier = str_repeat('🌐LombokTour', 20); // > 200 chars
        $userLong = new User();
        $userLong->id = null;
        $userLong->username = $longIdentifier;
        $event5 = new Login('web', $userLong, false);
        $listener->handle($event5);

        $record5 = LoginHistory::latest('id')->first();
        $this->assertSame(150, mb_strlen($record5->attempted_identifier));
        $this->assertSame(mb_substr($longIdentifier, 0, 150), $record5->attempted_identifier);
    }

    /**
     * Test 12: RecordFailedLogin credentials matrix and edge cases.
     */
    public function test_record_failed_login_credentials_matrix_and_fallbacks(): void
    {
        $listener = new RecordFailedLogin();

        // 1. Non-array credentials (e.g. null or scalar)
        $eventNullCreds = new Failed('web', null, ['malformed' => true]);
        $refCreds = new ReflectionClass($eventNullCreds);
        $prop = $refCreds->getProperty('credentials');
        $prop->setAccessible(true);
        $prop->setValue($eventNullCreds, 'not-an-array-string');

        $listener->handle($eventNullCreds);
        $record1 = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record1->attempted_identifier);
        $this->assertNull($record1->user_id);
        $this->assertSame(LoginHistory::STATUS_FAILED, $record1->status);

        // 2. Non-scalar username (nested array payload attack) falls back to email
        $eventNestedAttack = new Failed('web', null, [
            'username' => ['injection' => 'exploit'],
            'email' => 'fallback@lomboktour.com',
        ]);
        $listener->handle($eventNestedAttack);
        $record2 = LoginHistory::latest('id')->first();
        $this->assertSame('fallback@lomboktour.com', $record2->attempted_identifier);

        // 3. Neither username nor email in credentials, but user object provided with username
        $userWithUsername = new User();
        $userWithUsername->id = null;
        $userWithUsername->username = 'authenticated_username_attempt';
        $eventUserUsername = new Failed('web', $userWithUsername, []);
        $listener->handle($eventUserUsername);
        $record3 = LoginHistory::latest('id')->first();
        $this->assertSame('authenticated_username_attempt', $record3->attempted_identifier);

        // 4. Neither username nor email in credentials, but user object provided with email only
        $userWithEmail = new User();
        $userWithEmail->id = null;
        $userWithEmail->username = null;
        $userWithEmail->email = 'only_email@lomboktour.com';
        $eventUserEmail = new Failed('web', $userWithEmail, []);
        $listener->handle($eventUserEmail);
        $record4 = LoginHistory::latest('id')->first();
        $this->assertSame('only_email@lomboktour.com', $record4->attempted_identifier);

        // 5. Completely empty credentials and null user
        $eventEmpty = new Failed('web', null, []);
        $listener->handle($eventEmpty);
        $record5 = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record5->attempted_identifier);

        // 6. Non-numeric or zero user ID on failed attempt
        $userNonNumeric = new User();
        $userNonNumeric->id = 'non-numeric-id';
        $userNonNumeric->username = 'failed_non_numeric';
        $eventNonNum = new Failed('web', $userNonNumeric, ['username' => 'failed_non_numeric']);
        $listener->handle($eventNonNum);
        $record6 = LoginHistory::latest('id')->first();
        $this->assertNull($record6->user_id);
    }

    /**
     * Test 13: Strict password and sensitive token isolation on failed logins.
     */
    public function test_record_failed_login_strict_password_isolation(): void
    {
        $listener = new RecordFailedLogin();

        $sensitivePayload = [
            'username' => 'victim_account',
            'password' => 'UltraSecretP@ssw0rd!#99',
            'token' => 'jwt.eyJhGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.sensitive_payload',
            'api_key' => 'olt_live_secret_key_abcdef123456789',
            'pin' => '889900',
        ];

        $event = new Failed('web', null, $sensitivePayload);
        $listener->handle($event);

        $record = LoginHistory::latest('id')->first();
        $this->assertSame('victim_account', $record->attempted_identifier);

        // Ensure sensitive secrets never leaked into any field
        $this->assertStringNotContainsString('UltraSecretP@ssw0rd!#99', (string) $record->attempted_identifier);
        $this->assertStringNotContainsString('UltraSecretP@ssw0rd!#99', (string) $record->user_agent);
        $this->assertStringNotContainsString('UltraSecretP@ssw0rd!#99', (string) $record->ip_address);

        $this->assertStringNotContainsString('sensitive_payload', (string) $record->attempted_identifier);
        $this->assertStringNotContainsString('olt_live_secret_key', (string) $record->attempted_identifier);
        $this->assertStringNotContainsString('889900', (string) $record->attempted_identifier);
    }

    // =========================================================================
    // SECTION 4: Filament Admin Resources White-Box Hardening
    // =========================================================================

    /**
     * Test 14: All 9 mutating methods on LoginHistoryResource return false unconditionally.
     */
    public function test_login_history_resource_all_mutating_actions_disabled(): void
    {
        $dummyRecord = new LoginHistory();

        $this->assertFalse(LoginHistoryResource::canCreate());
        $this->assertFalse(LoginHistoryResource::canEdit($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDelete($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDeleteAny());
        $this->assertFalse(LoginHistoryResource::canForceDelete($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canForceDeleteAny());
        $this->assertFalse(LoginHistoryResource::canReplicate($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canRestore($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canRestoreAny());
    }

    /**
     * Test 15: All 9 mutating methods on VisitorTrackingResource return false unconditionally.
     */
    public function test_visitor_tracking_resource_all_mutating_actions_disabled(): void
    {
        $dummyRecord = new VisitorTracking();

        $this->assertFalse(VisitorTrackingResource::canCreate());
        $this->assertFalse(VisitorTrackingResource::canEdit($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canDelete($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canDeleteAny());
        $this->assertFalse(VisitorTrackingResource::canForceDelete($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canForceDeleteAny());
        $this->assertFalse(VisitorTrackingResource::canReplicate($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canRestore($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canRestoreAny());
    }

    /**
     * Test 16: Form schema returns empty array for both read-only resources.
     */
    public function test_filament_resources_form_schema_is_empty(): void
    {
        $this->actingAs($this->superAdmin);

        $loginPage = Livewire::test(ListLoginHistories::class)->instance();
        $form1 = LoginHistoryResource::form(Form::make($loginPage));
        $this->assertEmpty($form1->getComponents());

        $visitorPage = Livewire::test(ListVisitorTrackings::class)->instance();
        $form2 = VisitorTrackingResource::form(Form::make($visitorPage));
        $this->assertEmpty($form2->getComponents());
    }

    /**
     * Test 17: Static authorization matrix across Guest, Staff, Admin, SuperAdmin, and arbitrary roles.
     */
    public function test_filament_resources_static_authorization_matrix(): void
    {
        $customRoleUser = User::create([
            'name' => 'Custom Role User',
            'username' => 'custom_role_user',
            'password' => Hash::make('CustomPass123!'),
            'role' => 'marketing_analyst',
            'must_change_password' => false,
        ]);

        $rolesToTest = [
            'guest' => ['user' => null, 'expected' => false],
            'staff' => ['user' => $this->staffUser, 'expected' => false],
            'admin' => ['user' => $this->adminUser, 'expected' => false],
            'custom' => ['user' => $customRoleUser, 'expected' => false],
            'superadmin' => ['user' => $this->superAdmin, 'expected' => true],
        ];

        foreach ($rolesToTest as $roleName => $data) {
            if ($data['user'] !== null) {
                $this->actingAs($data['user']);
            } else {
                auth()->logout();
            }

            $this->assertSame(
                $data['expected'],
                LoginHistoryResource::canAccess(),
                "LoginHistoryResource::canAccess() failed for {$roleName}"
            );
            $this->assertSame(
                $data['expected'],
                LoginHistoryResource::shouldRegisterNavigation(),
                "LoginHistoryResource::shouldRegisterNavigation() failed for {$roleName}"
            );

            $this->assertSame(
                $data['expected'],
                VisitorTrackingResource::canAccess(),
                "VisitorTrackingResource::canAccess() failed for {$roleName}"
            );
            $this->assertSame(
                $data['expected'],
                VisitorTrackingResource::shouldRegisterNavigation(),
                "VisitorTrackingResource::shouldRegisterNavigation() failed for {$roleName}"
            );

            $navLogin = LoginHistoryResource::getNavigationItems();
            $this->assertNotEmpty($navLogin);
            $this->assertSame($data['expected'], $navLogin[0]->isVisible());

            $navVisitor = VisitorTrackingResource::getNavigationItems();
            $this->assertNotEmpty($navVisitor);
            $this->assertSame($data['expected'], $navVisitor[0]->isVisible());
        }
    }

    /**
     * Test 18: LoginHistoryResource table closures and date filter branches.
     */
    public function test_login_history_resource_table_closures_and_filters(): void
    {
        $this->actingAs($this->superAdmin);

        $page = Livewire::test(ListLoginHistories::class)->instance();
        $table = $page->getTable();

        // 1. Column attempted_identifier description closure
        $columns = $table->getColumns();
        $this->assertArrayHasKey('attempted_identifier', $columns);
        $userColumn = $columns['attempted_identifier'];

        $historyWithUser = new LoginHistory();
        $historyWithUser->user = $this->superAdmin;
        $descriptionWithUser = $userColumn->record($historyWithUser)->getDescriptionBelow();
        $this->assertSame(
            $this->superAdmin->name . ' (' . $this->superAdmin->role . ')',
            $descriptionWithUser
        );

        $historyWithoutUser = new LoginHistory();
        $historyWithoutUser->user = null;
        $descriptionWithoutUser = $userColumn->record($historyWithoutUser)->getDescriptionBelow();
        $this->assertSame('Unregistered / Unknown', $descriptionWithoutUser);

        // 2. Date filter login_at query closure branches
        $filters = $table->getFilters();
        $this->assertArrayHasKey('login_at', $filters);
        $dateFilter = $filters['login_at'];

        LoginHistory::create([
            'attempted_identifier' => 'log_filter_test_1',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.1',
            'login_at' => Carbon::parse('2026-10-15 10:00:00'),
        ]);

        LoginHistory::create([
            'attempted_identifier' => 'log_filter_test_2',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.2',
            'login_at' => Carbon::parse('2026-10-25 10:00:00'),
        ]);

        // Branch A: login_from only
        $q1 = LoginHistory::query();
        $dateFilter->apply($q1, ['login_from' => '2026-10-20', 'login_until' => null]);
        $this->assertSame(1, $q1->count());

        // Branch B: login_until only
        $q2 = LoginHistory::query();
        $dateFilter->apply($q2, ['login_from' => null, 'login_until' => '2026-10-20']);
        $this->assertSame(1, $q2->count());

        // Branch C: both login_from and login_until
        $q3 = LoginHistory::query();
        $dateFilter->apply($q3, ['login_from' => '2026-10-10', 'login_until' => '2026-10-30']);
        $this->assertSame(2, $q3->count());

        // Branch D: neither
        $q4 = LoginHistory::query();
        $dateFilter->apply($q4, ['login_from' => null, 'login_until' => null]);
        $this->assertSame(2, $q4->count());
    }

    /**
     * Test 19: VisitorTrackingResource table closures and filter query branches.
     */
    public function test_visitor_tracking_resource_table_closures_and_filters(): void
    {
        $this->actingAs($this->superAdmin);

        $page = Livewire::test(ListVisitorTrackings::class)->instance();
        $table = $page->getTable();

        // 1. Column visitor_hash formatted state (first 12 chars + ...)
        $columns = $table->getColumns();
        $this->assertArrayHasKey('visitor_hash', $columns);
        $hashCol = $columns['visitor_hash'];
        $dummyRecord = new VisitorTracking(['visitor_hash' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef']);
        $formattedHash = $hashCol->formatState($dummyRecord->visitor_hash);
        $this->assertSame('0123456789ab...', $formattedHash);

        // 2. Column ip_address placeholder callback (record with null IP returns 'Hidden')
        $this->assertArrayHasKey('ip_address', $columns);
        $ipCol = $columns['ip_address'];
        $nullIpRecord = new VisitorTracking(['ip_address' => null]);
        $placeholder = $ipCol->record($nullIpRecord)->getPlaceholder();
        $this->assertSame('Hidden', $placeholder);

        $ipRecord = new VisitorTracking(['ip_address' => '192.168.1.1']);
        $placeholderWithIp = $ipCol->record($ipRecord)->getPlaceholder();
        $this->assertSame('192.168.1.***', $placeholderWithIp);

        // 3. Column is_returning state callback
        $this->assertArrayHasKey('is_returning', $columns);
        $catCol = $columns['is_returning'];
        $singleRecord = new VisitorTracking(['visit_count' => 1]);
        $returningRecord = new VisitorTracking(['visit_count' => 4]);
        $this->assertSame('First Visit', $catCol->record($singleRecord)->getState());
        $this->assertSame('Returning', $catCol->record($returningRecord)->getState());

        // 4. Filter visit_date query branches
        $filters = $table->getFilters();
        $this->assertArrayHasKey('visit_date', $filters);
        $dateFilter = $filters['visit_date'];

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'vt_filter_1'),
            'visit_date' => '2026-10-15',
            'visit_count' => 1,
            'first_visited_at' => '2026-10-15 10:00:00',
            'last_visited_at' => '2026-10-15 10:00:00',
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'vt_filter_2'),
            'visit_date' => '2026-10-25',
            'visit_count' => 5,
            'first_visited_at' => '2026-10-25 10:00:00',
            'last_visited_at' => '2026-10-25 10:00:00',
        ]);

        // Branch A: date_from only
        $q1 = VisitorTracking::query();
        $dateFilter->apply($q1, ['date_from' => '2026-10-20', 'date_until' => null]);
        $this->assertSame(1, $q1->count());

        // Branch B: date_until only
        $q2 = VisitorTracking::query();
        $dateFilter->apply($q2, ['date_from' => null, 'date_until' => '2026-10-20']);
        $this->assertSame(1, $q2->count());

        // Branch C: both
        $q3 = VisitorTracking::query();
        $dateFilter->apply($q3, ['date_from' => '2026-10-10', 'date_until' => '2026-10-30']);
        $this->assertSame(2, $q3->count());

        // 5. Filter visitor_type branches ('returning', 'single', default)
        $this->assertArrayHasKey('visitor_type', $filters);
        $typeFilter = $filters['visitor_type'];

        // Branch: returning
        $qRet = VisitorTracking::query();
        $typeFilter->apply($qRet, ['value' => 'returning']);
        $this->assertSame(1, $qRet->count());
        $this->assertSame(5, $qRet->first()->visit_count);

        // Branch: single
        $qSingle = VisitorTracking::query();
        $typeFilter->apply($qSingle, ['value' => 'single']);
        $this->assertSame(1, $qSingle->count());
        $this->assertSame(1, $qSingle->first()->visit_count);

        // Branch: default (null / invalid)
        $qDefault = VisitorTracking::query();
        $typeFilter->apply($qDefault, ['value' => 'unexpected_value']);
        $this->assertSame(2, $qDefault->count());
    }

    // =========================================================================
    // SECTION 5: HomeController Purity, Error Resilience & Locale Switching
    // =========================================================================

    /**
     * Test 20: HomeController catches unhandled Throwable and still returns view.
     */
    public function test_home_controller_error_resilience_when_service_throws_fatal_error(): void
    {
        $mockService = $this->createMock(VisitorTrackingService::class);
        $mockService->method('recordVisit')
            ->willThrowException(new Error('Simulated fatal PHP engine error'));

        $this->app->instance(VisitorTrackingService::class, $mockService);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Visitor tracking skipped in HomeController: Simulated fatal PHP engine error');
            });

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('destinations');
        $response->assertViewHas('services');
    }

    /**
     * Test 21: HomeController locale switching method and redirection.
     */
    public function test_home_controller_locale_switching(): void
    {
        $controller = app(HomeController::class);

        $responseEn = $controller->switchLocale('en');
        $this->assertInstanceOf(RedirectResponse::class, $responseEn);
        $this->assertSame(url('/'), $responseEn->getTargetUrl());

        $responseId = $controller->switchLocale('id');
        $this->assertInstanceOf(RedirectResponse::class, $responseId);
        $this->assertSame(url('/id'), $responseId->getTargetUrl());

        $responseHttpEn = $this->get('/lang/en');
        $responseHttpEn->assertRedirect('/');
        $responseHttpEn->assertCookie('olt_locale', 'en');

        $responseHttpId = $this->get('/lang/id');
        $responseHttpId->assertRedirect('/id');
        $responseHttpId->assertCookie('olt_locale', 'id');
    }
}
