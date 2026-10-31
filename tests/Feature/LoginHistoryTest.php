<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\LoginHistory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Filament Admin panel context is bound for Livewire auth tests
        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }
    }

    /**
     * Test 1: Successful login via Filament Admin login page.
     * Verifies that authenticating through the Filament UI form creates
     * a login_histories record with user_id, status 'success', IP, UA, and timestamp.
     */
    public function test_successful_login_via_filament_creates_login_history_record(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin_filament',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $clientIp = '203.0.113.10';
        $clientUserAgent = 'FilamentTestAgent/1.0 (Macintosh; Intel Mac OS X)';

        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUserAgent,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'superadmin_filament',
                'password' => 'CorrectPassword123!',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        // Assert record created in database table
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'superadmin_filament',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => $clientIp,
            'user_agent' => $clientUserAgent,
        ]);

        // Assert record model attributes and relationships
        $history = LoginHistory::where('attempted_identifier', 'superadmin_filament')->latest('id')->first();
        $this->assertNotNull($history, 'LoginHistory record must exist.');
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame('superadmin_filament', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $history->status);
        $this->assertTrue($history->isSuccess());
        $this->assertFalse($history->isFailed());
        $this->assertSame($clientIp, $history->ip_address);
        $this->assertSame($clientUserAgent, $history->user_agent);
        $this->assertInstanceOf(Carbon::class, $history->login_at);
        $this->assertNotNull($history->user);
        $this->assertSame($user->id, $history->user->id);
    }

    /**
     * Test 2: Successful login via SessionGuard directly (Auth::attempt).
     * Verifies that direct guard authentication fires the Login event and records history.
     */
    public function test_successful_login_via_session_guard_creates_login_history_record(): void
    {
        $user = User::create([
            'name' => 'Admin Operator',
            'username' => 'admin_guard',
            'password' => Hash::make('GuardSecret123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $clientIp = '198.51.100.25';
        $clientUserAgent = 'CustomSessionGuard/2.0';

        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUserAgent,
        ]);

        $loginSuccess = Auth::guard('web')->attempt([
            'username' => 'admin_guard',
            'password' => 'GuardSecret123!',
        ]);

        $this->assertTrue($loginSuccess, 'SessionGuard attempt must succeed.');

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'admin_guard',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => $clientIp,
            'user_agent' => $clientUserAgent,
        ]);

        $history = LoginHistory::where('attempted_identifier', 'admin_guard')->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame('admin_guard', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $history->status);
    }

    /**
     * Test 3: Failed login with wrong password for an existing user.
     * Verifies that the existing user_id is linked to the log, attempted_identifier is saved,
     * and status is marked as 'failed'.
     */
    public function test_failed_login_with_wrong_password_records_user_id_and_failed_status(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::create([
            'name' => 'Existing Target User',
            'username' => 'existing_target',
            'password' => Hash::make('ActualPassword123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);

        $clientIp = '192.0.2.45';
        $clientUserAgent = 'BadPasswordClient/1.0';

        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUserAgent,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'existing_target',
                'password' => 'WrongPassword999!',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'existing_target',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => $clientIp,
            'user_agent' => $clientUserAgent,
        ]);

        $history = LoginHistory::where('attempted_identifier', 'existing_target')->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame('existing_target', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_FAILED, $history->status);
        $this->assertTrue($history->isFailed());
        $this->assertFalse($history->isSuccess());
        $this->assertInstanceOf(Carbon::class, $history->login_at);
        $this->assertNotNull($history->user);
        $this->assertSame($user->id, $history->user->id);
    }

    /**
     * Test 4: Failed login with non-existent username.
     * Verifies that user_id is null, attempted_identifier is saved, and status is 'failed'.
     */
    public function test_failed_login_with_non_existent_username_records_null_user_id(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $clientIp = '192.0.2.88';
        $clientUserAgent = 'GhostScanner/3.1';

        $this->withServerVariables([
            'REMOTE_ADDR' => $clientIp,
            'HTTP_USER_AGENT' => $clientUserAgent,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'ghost_intruder',
                'password' => 'ArbitraryPassword123!',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => null,
            'attempted_identifier' => 'ghost_intruder',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => $clientIp,
            'user_agent' => $clientUserAgent,
        ]);

        $history = LoginHistory::where('attempted_identifier', 'ghost_intruder')->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertNull($history->user_id);
        $this->assertNull($history->user);
        $this->assertSame('ghost_intruder', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_FAILED, $history->status);
        $this->assertTrue($history->isFailed());
    }

    /**
     * Test 5: Credential Sanitization (Passwords are NEVER logged).
     * Verifies that passwords never leak into any column of the login_histories table
     * or serialized raw database row.
     */
    public function test_passwords_are_never_persisted_in_login_history(): void
    {
        $sensitivePassword = 'UltraSecretP@ssw0rd!#987Unique';

        $user = User::create([
            'name' => 'Sanitization Target',
            'username' => 'sanitize_target',
            'password' => Hash::make('ValidKey123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        // Attempt login with wrong password containing sensitive string
        Auth::guard('web')->attempt([
            'username' => 'sanitize_target',
            'password' => $sensitivePassword,
        ]);

        $history = LoginHistory::where('attempted_identifier', 'sanitize_target')->latest('id')->first();
        $this->assertNotNull($history);

        // Verify model attribute fields
        $this->assertStringNotContainsString($sensitivePassword, $history->attempted_identifier);
        $this->assertStringNotContainsString($sensitivePassword, (string) $history->ip_address);
        $this->assertStringNotContainsString($sensitivePassword, (string) $history->user_agent);
        $this->assertStringNotContainsString($sensitivePassword, $history->status);

        // Verify raw database row serialization
        $rawRow = (array) DB::table('login_histories')->find($history->id);
        $this->assertNotEmpty($rawRow);
        $serialized = json_encode($rawRow);
        $this->assertStringNotContainsString($sensitivePassword, (string) $serialized);

        // Verify database table wide query has 0 matches
        $leakCount = DB::table('login_histories')
            ->where('attempted_identifier', 'LIKE', "%{$sensitivePassword}%")
            ->orWhere('status', 'LIKE', "%{$sensitivePassword}%")
            ->orWhere('ip_address', 'LIKE', "%{$sensitivePassword}%")
            ->orWhere('user_agent', 'LIKE', "%{$sensitivePassword}%")
            ->count();

        $this->assertSame(0, $leakCount, 'Password was found persisted in login_histories table!');
    }

    /**
     * Test 6: Fallback for missing/null request context (CLI / console / background job).
     * Verifies that authentication triggered without active HTTP request server variables
     * does not crash and applies graceful defaults.
     */
    public function test_login_history_handles_null_request_context_gracefully(): void
    {
        $user = User::create([
            'name' => 'Console Worker',
            'username' => 'console_worker',
            'password' => Hash::make('WorkerPass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        // Clear request headers and server variables
        if (request()) {
            request()->server->replace([]);
            request()->headers->replace([]);
        }

        $result = Auth::guard('web')->attempt([
            'username' => 'console_worker',
            'password' => 'WorkerPass123!',
        ]);

        $this->assertTrue($result);

        $history = LoginHistory::where('attempted_identifier', 'console_worker')->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $history->status);
        $this->assertNotEmpty($history->ip_address, 'IP address must have default fallback.');
        $this->assertNotEmpty($history->user_agent, 'User agent must have default fallback.');
    }

    /**
     * Test 7: Multiple sequential login attempts record distinct chronological audit logs.
     * Verifies that sequential failed attempts followed by a success each create individual rows.
     */
    public function test_multiple_sequential_login_attempts_record_individual_entries(): void
    {
        $user = User::create([
            'name' => 'Sequential Tester',
            'username' => 'seq_tester',
            'password' => Hash::make('Pass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);

        // 3 failed attempts
        for ($i = 1; $i <= 3; $i++) {
            Auth::guard('web')->attempt([
                'username' => 'seq_tester',
                'password' => "BadPass{$i}!",
            ]);
        }

        // 1 successful attempt
        Auth::guard('web')->attempt([
            'username' => 'seq_tester',
            'password' => 'Pass123!',
        ]);

        $attempts = LoginHistory::where('attempted_identifier', 'seq_tester')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(4, $attempts, 'Should record exactly 4 login history entries.');

        $this->assertSame(LoginHistory::STATUS_FAILED, $attempts[0]->status);
        $this->assertSame(LoginHistory::STATUS_FAILED, $attempts[1]->status);
        $this->assertSame(LoginHistory::STATUS_FAILED, $attempts[2]->status);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $attempts[3]->status);

        $this->assertSame(3, LoginHistory::failed()->count());
        $this->assertSame(1, LoginHistory::success()->count());
    }

    /**
     * Test 8: Extremely long attempted identifier is truncated safely.
     * Verifies that a massive input string (>150 characters) is bounded to 150 characters
     * without triggering database column overflow SQL errors.
     */
    public function test_extremely_long_attempted_identifier_is_truncated_safely(): void
    {
        $massiveIdentifier = str_repeat('x', 250);

        Auth::guard('web')->attempt([
            'username' => $massiveIdentifier,
            'password' => 'AnyPassword123!',
        ]);

        $history = LoginHistory::latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame(150, mb_strlen($history->attempted_identifier));
        $this->assertSame(str_repeat('x', 150), $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_FAILED, $history->status);
    }

    /**
     * Test 9: Deleting a user sets foreign key user_id to NULL without deleting logs.
     * Verifies nullOnDelete() behavior for audit compliance.
     */
    public function test_deleting_user_sets_login_history_user_id_to_null(): void
    {
        $user = User::create([
            'name' => 'Temporary User',
            'username' => 'temp_audit_user',
            'password' => Hash::make('TempPassword123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        Auth::guard('web')->attempt([
            'username' => 'temp_audit_user',
            'password' => 'TempPassword123!',
        ]);

        $history = LoginHistory::where('attempted_identifier', 'temp_audit_user')->first();
        $this->assertNotNull($history);
        $this->assertSame($user->id, $history->user_id);

        // Delete user
        $user->delete();

        // Refresh model from database
        $history->refresh();
        $this->assertNull($history->user_id, 'user_id must be set to null on user deletion.');
        $this->assertNull($history->user);
        $this->assertSame('temp_audit_user', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_SUCCESS, $history->status);
    }

    /**
     * Test 10: Empty or whitespace attempted identifier defaults to 'unknown'.
     */
    public function test_empty_or_whitespace_attempted_identifier_defaults_to_unknown(): void
    {
        Auth::guard('web')->attempt([
            'username' => '   ',
            'password' => 'AnyPassword123!',
        ]);

        $history = LoginHistory::latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('unknown', $history->attempted_identifier);
        $this->assertSame(LoginHistory::STATUS_FAILED, $history->status);
        $this->assertNull($history->user_id);
    }

    /**
     * Test 11: Existing test suite compatibility & zero side effects.
     * Verifies that actingAs() does NOT fire Login events and does not inflate login_histories.
     */
    public function test_acting_as_does_not_fire_login_events_or_pollute_history(): void
    {
        $this->seed();

        $superAdmin = User::where('role', User::ROLE_SUPERADMIN)->first();
        $this->assertNotNull($superAdmin);
        $superAdmin->update(['must_change_password' => false]);

        $initialHistoryCount = LoginHistory::count();

        // Acting as user does not trigger SessionGuard::login() or fire Login event
        $response = $this->actingAs($superAdmin)->get('/admin');
        $response->assertStatus(200);

        $this->assertSame($initialHistoryCount, LoginHistory::count(), 'actingAs() must not create LoginHistory records.');
    }
}
