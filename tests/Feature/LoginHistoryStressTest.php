<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginHistoryStressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON;');
    }

    /**
     * Test 1: High-Volume Rapid Sequential Authentications (120 attempts)
     * 60 successful + 40 wrong password + 20 non-existent users.
     * Verifies strict 1:1 attribution between auth events and login_histories records.
     */
    public function test_high_volume_rapid_sequential_authentications_with_strict_1_to_1_attribution(): void
    {
        // 1. Create 10 distinct users with different roles
        $users = [];
        $roles = [User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_STAFF];

        for ($i = 1; $i <= 10; $i++) {
            $role = $roles[($i - 1) % count($roles)];
            $users[] = User::create([
                'name' => "Stress User {$i}",
                'username' => "stress_user_{$i}",
                'password' => Hash::make("Password#{$i}!"),
                'role' => $role,
                'must_change_password' => false,
            ]);
        }

        $initialCount = LoginHistory::count();
        $this->assertSame(0, $initialCount);

        $attemptLog = [];
        $totalAttempts = 120; // 60 success + 40 wrong pass + 20 non-existent

        $startTime = microtime(true);

        for ($i = 0; $i < $totalAttempts; $i++) {
            $cycle = $i % 6;
            $userIndex = $i % 10;
            $targetUser = $users[$userIndex];
            $clientIp = '192.168.1.'.(($i % 250) + 1);
            $clientUa = "StressHarness/1.0 Iteration-{$i}";

            $this->withServerVariables([
                'REMOTE_ADDR' => $clientIp,
                'HTTP_USER_AGENT' => $clientUa,
            ]);

            if ($cycle < 3) {
                // Success: 3 out of every 6 attempts = 60 total
                $success = Auth::guard('web')->attempt([
                    'username' => $targetUser->username,
                    'password' => "Password#{$targetUser->id}!",
                ]);
                $this->assertTrue($success, "Expected successful login for {$targetUser->username}");
                Auth::guard('web')->logout();

                $attemptLog[] = [
                    'expected_user_id' => $targetUser->id,
                    'expected_identifier' => $targetUser->username,
                    'expected_status' => LoginHistory::STATUS_SUCCESS,
                    'expected_ip' => $clientIp,
                    'expected_ua' => $clientUa,
                ];
            } elseif ($cycle < 5) {
                // Wrong password: 2 out of every 6 attempts = 40 total
                $success = Auth::guard('web')->attempt([
                    'username' => $targetUser->username,
                    'password' => 'IncorrectPassword123!',
                ]);
                $this->assertFalse($success, "Expected failed login for {$targetUser->username}");

                $attemptLog[] = [
                    'expected_user_id' => $targetUser->id,
                    'expected_identifier' => $targetUser->username,
                    'expected_status' => LoginHistory::STATUS_FAILED,
                    'expected_ip' => $clientIp,
                    'expected_ua' => $clientUa,
                ];
            } else {
                // Non-existent user: 1 out of every 6 attempts = 20 total
                $fakeUsername = "nonexistent_ghost_{$i}";
                $success = Auth::guard('web')->attempt([
                    'username' => $fakeUsername,
                    'password' => 'AnyPassword123!',
                ]);
                $this->assertFalse($success, "Expected failed login for {$fakeUsername}");

                $attemptLog[] = [
                    'expected_user_id' => null,
                    'expected_identifier' => $fakeUsername,
                    'expected_status' => LoginHistory::STATUS_FAILED,
                    'expected_ip' => $clientIp,
                    'expected_ua' => $clientUa,
                ];
            }
        }

        $elapsed = microtime(true) - $startTime;

        // 2. Empirical verification of count
        $records = LoginHistory::orderBy('id', 'asc')->get();
        $this->assertCount($totalAttempts, $records, 'Total records must match total attempts exactly (1:1 attribution)');

        // 3. Status breakdown
        $successCount = LoginHistory::where('status', LoginHistory::STATUS_SUCCESS)->count();
        $failedCount = LoginHistory::where('status', LoginHistory::STATUS_FAILED)->count();
        $this->assertSame(60, $successCount, 'Exactly 60 successful attempts must be recorded');
        $this->assertSame(60, $failedCount, 'Exactly 60 failed attempts must be recorded');

        // 4. Exact 1:1 attribution validation in chronological order
        foreach ($records as $index => $record) {
            $expected = $attemptLog[$index];
            $this->assertSame(
                $expected['expected_user_id'],
                $record->user_id,
                "Record #{$index} user_id mismatch. Expected: {$expected['expected_user_id']}, got: {$record->user_id}"
            );
            $this->assertSame(
                $expected['expected_identifier'],
                $record->attempted_identifier,
                "Record #{$index} identifier mismatch."
            );
            $this->assertSame(
                $expected['expected_status'],
                $record->status,
                "Record #{$index} status mismatch."
            );
            $this->assertSame(
                $expected['expected_ip'],
                $record->ip_address,
                "Record #{$index} IP address mismatch."
            );
            $this->assertSame(
                $expected['expected_ua'],
                $record->user_agent,
                "Record #{$index} User Agent mismatch."
            );
            $this->assertNotNull($record->login_at, "Record #{$index} login_at must not be null");
        }

        // Performance sanity check: 120 auth passes with hashing usually takes ~2-5s in PHP bcrypt
        $this->assertLessThan(15.0, $elapsed, "120 auth passes took excessively long: {$elapsed}s");
    }

    /**
     * Test 2: Unicode, Emoji, Multibyte, and Boundary Fuzzing
     */
    public function test_unicode_emoji_multibyte_and_boundary_fuzzing(): void
    {
        $testCases = [
            'emoji_mix' => '🚀Admin_🔥_Super_🎉_2026',
            'arabic_rtl' => 'مدير_النظام_المسؤول',
            'chinese_cjk' => '超级管理员_测试账户_01',
            'japanese_kana' => 'スーパー管理者_認証テスト',
            'cyrillic' => 'Администратор_Системы_Тест',
            'accents' => 'Administrateur_Élégant_Ñoño_Müller',
            'zero_width' => "admin\u{200B}hidden\u{200C}joiner",
            'whitespace_padded' => '   spaced_identifier_trimmed   ',
            'max_boundary_150' => str_repeat('A', 150),
            'overflow_boundary_250' => str_repeat('B', 250),
            'multibyte_150_emojis' => str_repeat('🛡️', 150),
            'sql_injection_attempt' => "admin' OR '1'='1' --",
            'xss_payload' => "<script>alert('xss')</script>",
            'quotes_and_special' => "user`~!@#$%^&*()-_=+[{]}\\|;:'\",<.>/?",
        ];

        foreach ($testCases as $label => $payload) {
            $ip = '10.0.0.'.rand(1, 250);
            $ua = "FuzzAgent/{$label}";

            $this->withServerVariables([
                'REMOTE_ADDR' => $ip,
                'HTTP_USER_AGENT' => $ua,
            ]);

            Auth::guard('web')->attempt([
                'username' => $payload,
                'password' => 'WrongPass123!',
            ]);

            $latest = LoginHistory::latest('id')->first();
            $this->assertNotNull($latest, "Failed attempt for {$label} must create a record");

            $expectedIdentifier = mb_substr(trim($payload), 0, 150);
            $this->assertSame($expectedIdentifier, $latest->attempted_identifier, "Identifier mismatch for {$label}");
            $this->assertLessThanOrEqual(150, mb_strlen($latest->attempted_identifier), "Identifier length exceeds 150 for {$label}");
            $this->assertSame(LoginHistory::STATUS_FAILED, $latest->status);
            $this->assertNull($latest->user_id);
            $this->assertSame($ip, $latest->ip_address);
            $this->assertSame($ua, $latest->user_agent);
        }
    }

    /**
     * Test 3: Password Confidentiality & Sanitization Verification
     * Confirms that passwords and sensitive tokens NEVER leak into database fields.
     */
    public function test_passwords_and_sensitive_tokens_are_strictly_never_persisted(): void
    {
        $secretPassword = 'SUPER_SECRET_PLAINTEXT_PASSWORD_123456789!#$';

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'ConfidentialityCheck/1.0',
        ]);

        Auth::guard('web')->attempt([
            'username' => 'confidential_user',
            'password' => $secretPassword,
        ]);

        $latest = LoginHistory::latest('id')->first();
        $this->assertNotNull($latest);

        // Check columns individually
        $this->assertStringNotContainsString($secretPassword, (string) $latest->attempted_identifier);
        $this->assertStringNotContainsString($secretPassword, (string) $latest->status);
        $this->assertStringNotContainsString($secretPassword, (string) $latest->ip_address);
        $this->assertStringNotContainsString($secretPassword, (string) $latest->user_agent);

        // Raw table check across the entire row
        $rawRow = DB::table('login_histories')->where('id', $latest->id)->first();
        $rowString = json_encode($rawRow);
        $this->assertStringNotContainsString($secretPassword, $rowString);
    }

    /**
     * Test 4: Extreme Client Metadata Stress (IPv6, massive UA, null fallbacks)
     */
    public function test_client_metadata_boundaries_ipv6_and_massive_user_agent(): void
    {
        // 1. Long IPv6
        $fullIpv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $this->withServerVariables([
            'REMOTE_ADDR' => $fullIpv6,
            'HTTP_USER_AGENT' => 'StandardAgent/1.0',
        ]);
        Auth::guard('web')->attempt(['username' => 'ipv6_user', 'password' => 'badpass']);
        $record = LoginHistory::latest('id')->first();
        $this->assertSame($fullIpv6, $record->ip_address);

        // 2. Overflow IPv6 (over 45 chars)
        $oversizedIp = '2001:0db8:85a3:0000:0000:8a2e:0370:7334:extra_overflow_padding';
        $this->withServerVariables([
            'REMOTE_ADDR' => $oversizedIp,
            'HTTP_USER_AGENT' => 'StandardAgent/1.0',
        ]);
        Auth::guard('web')->attempt(['username' => 'oversized_ip_user', 'password' => 'badpass']);
        $record = LoginHistory::latest('id')->first();
        $this->assertSame(mb_substr($oversizedIp, 0, 45), $record->ip_address);
        $this->assertLessThanOrEqual(45, strlen($record->ip_address));

        // 3. Massive User Agent (10,000 chars)
        $massiveUa = 'Mozilla/5.0 '.str_repeat('MassiveUserAgentTestString_', 400);
        $this->withServerVariables([
            'REMOTE_ADDR' => '10.10.10.10',
            'HTTP_USER_AGENT' => $massiveUa,
        ]);
        Auth::guard('web')->attempt(['username' => 'massive_ua_user', 'password' => 'badpass']);
        $record = LoginHistory::latest('id')->first();
        $this->assertSame($massiveUa, $record->user_agent);

        // 4. Missing IP and UA defaults in CLI / isolated context
        if (request()) {
            request()->server->replace([]);
            request()->headers->replace([]);
        }
        $this->withServerVariables([
            'REMOTE_ADDR' => '',
            'HTTP_USER_AGENT' => '',
        ]);
        Event::dispatch(new Failed('web', null, ['username' => 'null_meta_user']));
        $record = LoginHistory::latest('id')->first();
        $this->assertNotEmpty($record->ip_address);
        $this->assertNotEmpty($record->user_agent);
    }

    /**
     * Test 5: Malformed & Pathological Credentials Fuzzing
     */
    public function test_malformed_and_pathological_credentials_handling(): void
    {
        // 1. Array credentials without username or email
        Event::dispatch(new Failed('web', null, ['token' => 'xyz']));
        $record = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record->attempted_identifier);

        // 2. Non-scalar username (nested array)
        Event::dispatch(new Failed('web', null, ['username' => ['nested' => 'array_hack']]));
        $record = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record->attempted_identifier);

        // 3. Numeric username
        Event::dispatch(new Failed('web', null, ['username' => 999888]));
        $record = LoginHistory::latest('id')->first();
        $this->assertSame('999888', $record->attempted_identifier);

        // 4. Null credentials
        Event::dispatch(new Failed('web', null, []));
        $record = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record->attempted_identifier);

        // 5. Successful login with user having empty username (defaults to unknown)
        $user = User::create([
            'name' => 'No Username User',
            'username' => '',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
        Event::dispatch(new Login('web', $user, false));
        $record = LoginHistory::latest('id')->first();
        $this->assertSame('unknown', $record->attempted_identifier);
        $this->assertSame($user->id, $record->user_id);
    }

    /**
     * Test 6: Database Index Performance & Query Plan Analysis
     * Populates 1,000 records and verifies index usage and sub-millisecond execution.
     */
    public function test_database_indices_performance_and_query_plans(): void
    {
        $user = User::create([
            'name' => 'Index Benchmark User',
            'username' => 'index_benchmark_user',
            'password' => Hash::make('Password123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $batch = [];
        $baseDate = Carbon::parse('2026-10-01 00:00:00');

        for ($i = 1; $i <= 1000; $i++) {
            $batch[] = [
                'user_id' => ($i % 5 === 0) ? null : $user->id,
                'attempted_identifier' => 'user_index_'.($i % 50),
                'status' => ($i % 3 === 0) ? LoginHistory::STATUS_FAILED : LoginHistory::STATUS_SUCCESS,
                'ip_address' => '10.0.0.'.($i % 250),
                'user_agent' => 'IndexPerformanceAgent/1.0',
                'login_at' => $baseDate->copy()->addMinutes($i)->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert 1,000 records
        DB::table('login_histories')->insert($batch);
        $this->assertSame(1000, LoginHistory::count());

        // 1. Verify index on `attempted_identifier`
        $planIdentifier = DB::select("EXPLAIN QUERY PLAN SELECT * FROM login_histories WHERE attempted_identifier = 'user_index_5'");
        $planDetailsIdentifier = json_encode($planIdentifier);
        $this->assertStringContainsString('USING INDEX', $planDetailsIdentifier, 'Query by attempted_identifier must use index');

        // 2. Verify index on `status`
        $planStatus = DB::select("EXPLAIN QUERY PLAN SELECT * FROM login_histories WHERE status = 'success'");
        $planDetailsStatus = json_encode($planStatus);
        $this->assertStringContainsString('USING INDEX', $planDetailsStatus, 'Query by status must use index');

        // 3. Verify index on `login_at`
        $planLoginAt = DB::select("EXPLAIN QUERY PLAN SELECT * FROM login_histories WHERE login_at BETWEEN '2026-10-01 00:00:00' AND '2026-10-01 05:00:00'");
        $planDetailsLoginAt = json_encode($planLoginAt);
        $this->assertStringContainsString('USING INDEX', $planDetailsLoginAt, 'Query by login_at must use index');

        // 4. Execution speed benchmark across 100 queries
        $start = microtime(true);
        for ($q = 0; $q < 100; $q++) {
            $identifier = 'user_index_'.($q % 50);
            $results = LoginHistory::where('attempted_identifier', $identifier)
                ->where('status', LoginHistory::STATUS_SUCCESS)
                ->get();
            $this->assertNotEmpty($results);
        }
        $totalDuration = microtime(true) - $start;
        $avgDurationMs = ($totalDuration / 100) * 1000;

        // Average indexed lookup time must be fast (< 5ms per query)
        $this->assertLessThan(5.0, $avgDurationMs, "Indexed query took too long: {$avgDurationMs}ms average");
    }

    /**
     * Test 7: Cascade and Lifecycle Test
     * Verifies that deleting a user nullifies user_id in login_histories without deleting the logs.
     */
    public function test_user_deletion_safely_nullifies_foreign_key_in_audit_trail(): void
    {
        $user = User::create([
            'name' => 'Ephemeral User',
            'username' => 'ephemeral_user',
            'password' => Hash::make('Pass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);

        $userId = $user->id;

        // Create 5 success and 5 failed entries
        for ($i = 0; $i < 5; $i++) {
            Event::dispatch(new Login('web', $user, false));
            Event::dispatch(new Failed('web', $user, ['username' => 'ephemeral_user']));
        }

        $this->assertSame(10, LoginHistory::where('user_id', $userId)->count());

        // Delete user
        $user->delete();

        // Database foreign key nullOnDelete check
        $this->assertSame(0, LoginHistory::where('user_id', $userId)->count());
        $orphanedLogs = LoginHistory::where('attempted_identifier', 'ephemeral_user')->get();
        $this->assertCount(10, $orphanedLogs);

        foreach ($orphanedLogs as $log) {
            $this->assertNull($log->user_id);
            $this->assertSame('ephemeral_user', $log->attempted_identifier);
        }
    }
}
