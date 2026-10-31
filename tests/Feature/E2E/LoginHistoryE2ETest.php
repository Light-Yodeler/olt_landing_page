<?php

namespace Tests\Feature\E2E;

use App\Filament\Pages\Auth\Login as FilamentLogin;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginHistoryE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON;');
    }

    protected function createTestUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'username' => 'testuser_' . uniqid(),
            'password' => Hash::make('ValidSecret123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ], $attributes));
    }

    // ==========================================
    // Tier 1: Core Feature Coverage
    // ==========================================

    public function test_tier1_successful_login_via_auth_attempt_records_login_history(): void
    {
        $user = $this->createTestUser([
            'username' => 'superadmin_john',
            'password' => Hash::make('StrongPass123!'),
        ]);

        $ip = '198.51.100.10';
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        $authenticated = Auth::attempt([
            'username' => 'superadmin_john',
            'password' => 'StrongPass123!',
        ]);

        $this->assertTrue($authenticated, 'Auth::attempt should succeed with valid credentials.');

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'superadmin_john',
            'status' => 'success',
            'ip_address' => $ip,
        ]);

        $record = DB::table('login_histories')->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertStringContainsString('Macintosh', (string) $record->user_agent);
        $this->assertNotNull($record->login_at);
    }

    public function test_tier1_successful_login_via_filament_login_page_records_login_history(): void
    {
        $user = $this->createTestUser([
            'username' => 'filament_admin',
            'password' => Hash::make('FilamentPass123!'),
        ]);

        $ip = '198.51.100.20';
        $userAgent = 'FilamentBrowser/1.0';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        Livewire::test(FilamentLogin::class)
            ->fillForm([
                'username' => 'filament_admin',
                'password' => 'FilamentPass123!',
            ])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'filament_admin',
            'status' => 'success',
        ]);
    }

    public function test_tier1_failed_login_with_wrong_password_records_login_history(): void
    {
        $user = $this->createTestUser([
            'username' => 'target_user',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $ip = '198.51.100.30';
        $userAgent = 'AttackerClient/1.0';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        $authenticated = Auth::attempt([
            'username' => 'target_user',
            'password' => 'IncorrectPasswordWrong!',
        ]);

        $this->assertFalse($authenticated, 'Auth::attempt must fail with wrong password.');

        $this->assertDatabaseHas('login_histories', [
            'attempted_identifier' => 'target_user',
            'status' => 'failed',
            'ip_address' => $ip,
        ]);

        $record = DB::table('login_histories')->where('attempted_identifier', 'target_user')->first();
        $this->assertNotNull($record);
        $this->assertEquals('failed', $record->status);
        $this->assertNotNull($record->login_at);
    }

    public function test_tier1_failed_login_with_non_existent_username_records_login_history(): void
    {
        $ip = '198.51.100.40';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'ProbingClient/1.0',
        ]);

        $unknownUser = 'non_existent_ghost_' . uniqid();

        $authenticated = Auth::attempt([
            'username' => $unknownUser,
            'password' => 'AnyPassword123!',
        ]);

        $this->assertFalse($authenticated);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => null,
            'attempted_identifier' => $unknownUser,
            'status' => 'failed',
            'ip_address' => $ip,
        ]);
    }

    public function test_tier1_credential_sanitization_ensures_password_is_never_stored_in_database(): void
    {
        $secretPlaintextPassword = 'UltraConfidentialSecret!#987654';

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.50',
            'HTTP_USER_AGENT' => 'LeakCheckAgent/1.0',
        ]);

        Auth::attempt([
            'username' => 'security_tester',
            'password' => $secretPlaintextPassword,
        ]);

        $records = DB::table('login_histories')->get();
        $this->assertNotEmpty($records);

        foreach ($records as $record) {
            $this->assertStringNotContainsString($secretPlaintextPassword, (string) $record->attempted_identifier);
            $this->assertStringNotContainsString($secretPlaintextPassword, (string) $record->status);
            $this->assertStringNotContainsString($secretPlaintextPassword, (string) $record->ip_address);
            $this->assertStringNotContainsString($secretPlaintextPassword, (string) $record->user_agent);
        }
    }

    public function test_tier1_login_history_belongs_to_user_relationship(): void
    {
        $user = $this->createTestUser([
            'username' => 'rel_user',
            'password' => Hash::make('Password123!'),
        ]);

        Auth::attempt([
            'username' => 'rel_user',
            'password' => 'Password123!',
        ]);

        $this->assertTrue(class_exists(LoginHistory::class), 'LoginHistory model must exist');
        $model = LoginHistory::where('attempted_identifier', 'rel_user')->first();
        $this->assertNotNull($model);
        $this->assertInstanceOf(User::class, $model->user);
        $this->assertEquals($user->id, $model->user->id);
    }

    // ==========================================
    // Tier 2: Boundary and Corner Cases
    // ==========================================

    public function test_tier2_boundary_long_username_identifier_is_handled_safely(): void
    {
        // Test boundary string with 150 characters (column capacity)
        $longIdentifier = str_repeat('a', 150);

        Auth::attempt([
            'username' => $longIdentifier,
            'password' => 'Pass123!',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'attempted_identifier' => $longIdentifier,
            'status' => 'failed',
        ]);
    }

    public function test_tier2_boundary_empty_and_whitespace_only_identifiers(): void
    {
        Auth::attempt([
            'username' => '   ',
            'password' => 'Pass123!',
        ]);

        $record = DB::table('login_histories')->latest('id')->first();
        $this->assertNotNull($record, 'A login history record should be logged even for whitespace identifier attempts.');
        $this->assertEquals('failed', $record->status);
    }

    public function test_tier2_corner_case_special_characters_and_sql_injection_payload_in_identifier(): void
    {
        $payload = "' OR '1'='1' -- /* UNION SELECT * FROM users */ <script>alert('xss')</script>";

        Auth::attempt([
            'username' => $payload,
            'password' => 'InjectedPass!',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'attempted_identifier' => substr($payload, 0, 150),
            'status' => 'failed',
        ]);
    }

    public function test_tier2_corner_case_unicode_and_emoji_characters_in_identifier(): void
    {
        $unicodeUsername = 'lombök_töur_🚀_🏝️';

        Auth::attempt([
            'username' => $unicodeUsername,
            'password' => 'UnicodePass123!',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'attempted_identifier' => $unicodeUsername,
            'status' => 'failed',
        ]);
    }

    public function test_tier2_boundary_ipv6_address_recording(): void
    {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $this->withServerVariables([
            'REMOTE_ADDR' => $ipv6,
            'HTTP_USER_AGENT' => 'IPv6Client/1.0',
        ]);

        Auth::attempt([
            'username' => 'ipv6_user',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'attempted_identifier' => 'ipv6_user',
            'ip_address' => $ipv6,
            'status' => 'failed',
        ]);
    }

    public function test_tier2_corner_case_missing_user_agent_and_ip_in_cli_or_null_context(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => null,
            'HTTP_USER_AGENT' => null,
        ]);

        Auth::attempt([
            'username' => 'cli_batch_job',
            'password' => 'Pass123!',
        ]);

        $record = DB::table('login_histories')->where('attempted_identifier', 'cli_batch_job')->first();
        $this->assertNotNull($record);
        $this->assertEquals('failed', $record->status);
    }

    // ==========================================
    // Tier 3: Interactions (Remember-me, Session, Rate Limiting)
    // ==========================================

    public function test_tier3_interaction_remember_me_login_records_valid_login_history(): void
    {
        $user = $this->createTestUser([
            'username' => 'remember_user',
            'password' => Hash::make('RememberPass123!'),
        ]);

        $authenticated = Auth::attempt([
            'username' => 'remember_user',
            'password' => 'RememberPass123!',
        ], true);

        $this->assertTrue($authenticated);
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'remember_user',
            'status' => 'success',
        ]);
    }

    public function test_tier3_interaction_session_regeneration_on_login_maintains_login_history(): void
    {
        $user = $this->createTestUser([
            'username' => 'session_test_user',
            'password' => Hash::make('SessionPass123!'),
        ]);

        Livewire::test(FilamentLogin::class)
            ->fillForm([
                'username' => 'session_test_user',
                'password' => 'SessionPass123!',
            ])
            ->call('authenticate');

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'attempted_identifier' => 'session_test_user',
            'status' => 'success',
        ]);
    }

    public function test_tier3_interaction_repeated_failed_attempts_all_logged_audit_trail(): void
    {
        $targetUser = 'repeated_fail_target';

        for ($i = 1; $i <= 5; $i++) {
            Auth::attempt([
                'username' => $targetUser,
                'password' => "BadPasswordAttempt_{$i}",
            ]);
        }

        $this->assertDatabaseCount('login_histories', 5);
        $failures = DB::table('login_histories')->where('attempted_identifier', $targetUser)->get();
        $this->assertCount(5, $failures);
        foreach ($failures as $failure) {
            $this->assertEquals('failed', $failure->status);
        }
    }

    public function test_tier3_interaction_multiple_logins_across_different_roles(): void
    {
        $superAdmin = $this->createTestUser([
            'username' => 'audit_superadmin',
            'role' => User::ROLE_SUPERADMIN,
            'password' => Hash::make('Pass123!'),
        ]);

        $admin = $this->createTestUser([
            'username' => 'audit_admin',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('Pass123!'),
        ]);

        $staff = $this->createTestUser([
            'username' => 'audit_staff',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('Pass123!'),
        ]);

        Auth::attempt(['username' => 'audit_superadmin', 'password' => 'Pass123!']);
        Auth::logout();

        Auth::attempt(['username' => 'audit_admin', 'password' => 'Pass123!']);
        Auth::logout();

        Auth::attempt(['username' => 'audit_staff', 'password' => 'Pass123!']);
        Auth::logout();

        $this->assertDatabaseHas('login_histories', ['user_id' => $superAdmin->id, 'attempted_identifier' => 'audit_superadmin', 'status' => 'success']);
        $this->assertDatabaseHas('login_histories', ['user_id' => $admin->id, 'attempted_identifier' => 'audit_admin', 'status' => 'success']);
        $this->assertDatabaseHas('login_histories', ['user_id' => $staff->id, 'attempted_identifier' => 'audit_staff', 'status' => 'success']);
    }

    // ==========================================
    // Tier 4: Real-World Workload Scenarios
    // ==========================================

    public function test_tier4_scenario_brute_force_credential_stuffing_audit_trail(): void
    {
        $dictionaryUsernames = ['admin', 'root', 'administrator', 'operator', 'olt_support'];

        foreach ($dictionaryUsernames as $dictUser) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.199',
                'HTTP_USER_AGENT' => 'Hydra/9.5 AttackSuite',
            ]);

            Auth::attempt([
                'username' => $dictUser,
                'password' => 'p@ssword123',
            ]);
        }

        // Legitimate Super Admin logs in after attack
        $legitAdmin = $this->createTestUser([
            'username' => 'olt_super_admin',
            'password' => Hash::make('CorrectAdminKey2026!'),
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.99',
            'HTTP_USER_AGENT' => 'Safari/17.4 (Macintosh; Apple Silicon)',
        ]);

        Auth::attempt([
            'username' => 'olt_super_admin',
            'password' => 'CorrectAdminKey2026!',
        ]);

        $this->assertDatabaseCount('login_histories', 6);
        $failedCount = DB::table('login_histories')->where('status', 'failed')->count();
        $successCount = DB::table('login_histories')->where('status', 'success')->count();

        $this->assertEquals(5, $failedCount);
        $this->assertEquals(1, $successCount);
    }

    public function test_tier4_scenario_user_deletion_nullifies_foreign_key_while_preserving_audit_trail(): void
    {
        $temporaryStaff = $this->createTestUser([
            'username' => 'temp_employee_bob',
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('EmployeePass123!'),
        ]);

        Auth::attempt([
            'username' => 'temp_employee_bob',
            'password' => 'EmployeePass123!',
        ]);

        $record = DB::table('login_histories')->where('attempted_identifier', 'temp_employee_bob')->first();
        $this->assertNotNull($record);
        $this->assertEquals($temporaryStaff->id, $record->user_id);

        // Delete user account
        $temporaryStaff->delete();

        // Audit row MUST remain intact, with user_id set to null via ON DELETE SET NULL
        $updatedRecord = DB::table('login_histories')->where('id', $record->id)->first();
        $this->assertNotNull($updatedRecord, 'Login history audit row must not be cascaded/deleted.');
        $this->assertNull($updatedRecord->user_id, 'user_id must be nullified to maintain referential integrity.');
        $this->assertEquals('temp_employee_bob', $updatedRecord->attempted_identifier);
        $this->assertEquals('success', $updatedRecord->status);
    }

    public function test_tier4_scenario_auditing_query_filtering_by_status_and_date_range(): void
    {
        $user = $this->createTestUser(['username' => 'range_user']);

        // Create entries across timestamps
        Carbon::setTestNow('2026-10-25 10:00:00');
        Auth::attempt(['username' => 'range_user', 'password' => 'WrongPass!']);

        Carbon::setTestNow('2026-10-28 14:00:00');
        Auth::attempt(['username' => 'range_user', 'password' => 'ValidSecret123!']);

        Carbon::setTestNow('2026-10-31 09:30:00');
        Auth::attempt(['username' => 'range_user', 'password' => 'ValidSecret123!']);

        Carbon::setTestNow(); // reset

        // Filter: status success within range 2026-10-27 to 2026-10-29
        $filtered = DB::table('login_histories')
            ->where('status', 'success')
            ->whereBetween('login_at', ['2026-10-27 00:00:00', '2026-10-29 23:59:59'])
            ->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals('range_user', $filtered->first()->attempted_identifier);
    }
}
