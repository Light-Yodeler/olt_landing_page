<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LoginHistoryResource;
use App\Filament\Resources\LoginHistoryResource\Pages\ListLoginHistories;
use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\VisitorTracking;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTableStressTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        $this->superAdmin = User::create([
            'name' => 'Super Admin Tester',
            'username' => 'stress_superadmin',
            'password' => Hash::make('SuperSecretPassword123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $this->actingAs($this->superAdmin);
    }

    protected function createStandardUser(string $username): User
    {
        return User::create([
            'name' => 'Standard User ' . $username,
            'username' => $username,
            'password' => Hash::make('UserPassword123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);
    }

    public function test_login_history_table_renders_under_load_with_60_plus_records(): void
    {
        $users = [
            $this->createStandardUser('load_user_1'),
            $this->createStandardUser('load_user_2'),
            $this->createStandardUser('load_user_3'),
        ];

        $records = [];
        for ($i = 1; $i <= 65; $i++) {
            $user = ($i % 3 === 0) ? null : $users[$i % count($users)];
            $records[] = [
                'user_id' => $user?->id,
                'attempted_identifier' => $user ? $user->username : "unregistered_client_{$i}",
                'status' => ($i % 2 === 0) ? LoginHistory::STATUS_SUCCESS : LoginHistory::STATUS_FAILED,
                'ip_address' => '192.168.10.' . ($i % 250),
                'user_agent' => "StressAgent/{$i}.0 (Test Platform; x64)",
                'login_at' => Carbon::now()->subMinutes($i * 5),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        LoginHistory::insert($records);
        $this->assertSame(65, LoginHistory::count());

        $testable = Livewire::test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertCountTableRecords(65);

        $firstRecent = LoginHistory::orderBy('login_at', 'desc')->first();
        $testable->assertCanSeeTableRecords([$firstRecent]);
    }

    public function test_visitor_tracking_table_renders_under_load_with_60_plus_records(): void
    {
        $records = [];
        $today = Carbon::now()->toDateString();

        for ($i = 1; $i <= 65; $i++) {
            $hash = hash('sha256', "stress-visitor-token-{$i}");
            $firstTime = Carbon::now()->subHours(rand(1, 12));
            $records[] = [
                'visitor_hash' => $hash,
                'ip_address' => '172.16.5.' . ($i % 250),
                'user_agent' => "VisitorStressBot/{$i}.0",
                'visit_date' => $today,
                'visit_count' => ($i % 5 === 0) ? 8 : 1,
                'first_visited_at' => $firstTime,
                'last_visited_at' => (clone $firstTime)->addMinutes(rand(5, 60)),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        VisitorTracking::insert($records);
        $this->assertSame(65, VisitorTracking::count());

        $testable = Livewire::test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCountTableRecords(65);

        $mostRecent = VisitorTracking::orderBy('last_visited_at', 'desc')->first();
        $testable->assertCanSeeTableRecords([$mostRecent]);
    }

    public function test_login_history_search_query_exact_match(): void
    {
        $target = LoginHistory::create([
            'attempted_identifier' => 'exact_target_super_unique_user',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '198.51.100.99',
            'user_agent' => 'ExactMatchTester/1.0',
            'login_at' => Carbon::now()->subMinutes(10),
        ]);

        $decoy = LoginHistory::create([
            'attempted_identifier' => 'decoy_user_account',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '198.51.100.100',
            'user_agent' => 'DecoyTester/1.0',
            'login_at' => Carbon::now()->subMinutes(5),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('exact_target_super_unique_user')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('198.51.100.99')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);
    }

    public function test_login_history_search_query_partial_substring(): void
    {
        $recordA = LoginHistory::create([
            'attempted_identifier' => 'partial_employee_alpha',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.150.1.11',
            'login_at' => Carbon::now()->subMinutes(30),
        ]);

        $recordB = LoginHistory::create([
            'attempted_identifier' => 'partial_employee_beta',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.150.1.22',
            'login_at' => Carbon::now()->subMinutes(20),
        ]);

        $other = LoginHistory::create([
            'attempted_identifier' => 'external_contractor_gamma',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.200.5.99',
            'login_at' => Carbon::now()->subMinutes(10),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('employee')
            ->assertCanSeeTableRecords([$recordA, $recordB])
            ->assertCanNotSeeTableRecords([$other])
            ->assertCountTableRecords(2);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('10.150.1.')
            ->assertCanSeeTableRecords([$recordA, $recordB])
            ->assertCanNotSeeTableRecords([$other])
            ->assertCountTableRecords(2);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('beta')
            ->assertCanSeeTableRecords([$recordB])
            ->assertCanNotSeeTableRecords([$recordA, $other])
            ->assertCountTableRecords(1);
    }

    public function test_login_history_search_query_special_characters_and_symbols(): void
    {
        $specialEmail = LoginHistory::create([
            'attempted_identifier' => 'security+audit.special_chars@lombok-tour.co.id',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '172.31.254.1',
            'login_at' => Carbon::now()->subMinutes(15),
        ]);

        $specialIpv6 = LoginHistory::create([
            'attempted_identifier' => 'ipv6_sysadmin',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'login_at' => Carbon::now()->subMinutes(12),
        ]);

        $decoy = LoginHistory::create([
            'attempted_identifier' => 'regular_user',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '192.168.1.1',
            'login_at' => Carbon::now()->subMinutes(5),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('+audit.special_chars@lombok-tour.co.id')
            ->assertCanSeeTableRecords([$specialEmail])
            ->assertCanNotSeeTableRecords([$specialIpv6, $decoy])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('2001:0db8:85a3')
            ->assertCanSeeTableRecords([$specialIpv6])
            ->assertCanNotSeeTableRecords([$specialEmail, $decoy])
            ->assertCountTableRecords(1);

        $wildcardStrings = ['%', '_', '%%%', '___', '[]', '()', '!@#$%^&*()'];
        foreach ($wildcardStrings as $wildcard) {
            Livewire::test(ListLoginHistories::class)
                ->searchTable($wildcard)
                ->assertSuccessful();
        }
    }

    public function test_login_history_search_query_sql_injection_payloads_handled_safely(): void
    {
        $legit = LoginHistory::create([
            'attempted_identifier' => 'admin_legitimate',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.1',
            'login_at' => Carbon::now(),
        ]);

        $sqlPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE login_histories; --",
            "' UNION SELECT NULL, NULL, NULL, NULL, NULL --",
            "admin' --",
            "1' AND SLEEP(2) --",
            "\" OR \"\"=\"",
            "'; EXEC sp_msforeachtable 'DROP TABLE ?' --",
            "' OR attempted_identifier LIKE '%admin%' --",
        ];

        foreach ($sqlPayloads as $payload) {
            Livewire::test(ListLoginHistories::class)
                ->searchTable($payload)
                ->assertSuccessful()
                ->assertCountTableRecords(0);
        }

        $this->assertDatabaseHas('login_histories', [
            'id' => $legit->id,
            'attempted_identifier' => 'admin_legitimate',
        ]);
    }

    public function test_login_history_search_case_insensitivity(): void
    {
        $record = LoginHistory::create([
            'attempted_identifier' => 'SyahidLombokTourManager',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.99.88.77',
            'login_at' => Carbon::now(),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('syahidlomboktourmanager')
            ->assertCanSeeTableRecords([$record])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('SYAHIDLOMBOKTOURMANAGER')
            ->assertCanSeeTableRecords([$record])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('LombokTour')
            ->assertCanSeeTableRecords([$record])
            ->assertCountTableRecords(1);
    }

    public function test_visitor_tracking_search_exact_partial_and_sql_injection(): void
    {
        $hashTarget = hash('sha256', 'target-visitor-unique-key');
        $hashDecoy = hash('sha256', 'decoy-visitor-unique-key');

        $target = VisitorTracking::create([
            'visitor_hash' => $hashTarget,
            'ip_address' => '203.0.113.195',
            'user_agent' => 'SearchTargetBot/1.0',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 3,
            'first_visited_at' => Carbon::now()->subMinutes(60),
            'last_visited_at' => Carbon::now()->subMinutes(10),
        ]);

        $decoy = VisitorTracking::create([
            'visitor_hash' => $hashDecoy,
            'ip_address' => '198.51.100.22',
            'user_agent' => 'DecoyBot/1.0',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => Carbon::now()->subMinutes(30),
            'last_visited_at' => Carbon::now()->subMinutes(5),
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable($hashTarget)
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);

        $hashPrefix = substr($hashTarget, 0, 10);
        Livewire::test(ListVisitorTrackings::class)
            ->searchTable($hashPrefix)
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable('203.0.113.195')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable('.113.')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$decoy])
            ->assertCountTableRecords(1);

        $sqlPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE visitor_trackings; --",
            "' UNION SELECT 1, 2, 3, 4 --",
            "\" OR \"\"=\"",
        ];

        foreach ($sqlPayloads as $payload) {
            Livewire::test(ListVisitorTrackings::class)
                ->searchTable($payload)
                ->assertSuccessful()
                ->assertCountTableRecords(0);
        }

        $this->assertDatabaseHas('visitor_trackings', [
            'id' => $target->id,
            'visitor_hash' => $hashTarget,
        ]);
    }

    public function test_login_history_multiple_filter_combinations(): void
    {
        $userAlice = $this->createStandardUser('filter_alice');
        $userBob = $this->createStandardUser('filter_bob');

        $aliceSuccessRecent = LoginHistory::create([
            'user_id' => $userAlice->id,
            'attempted_identifier' => $userAlice->username,
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.1.1',
            'login_at' => Carbon::now()->subHours(2),
        ]);

        $aliceFailedRecent = LoginHistory::create([
            'user_id' => $userAlice->id,
            'attempted_identifier' => $userAlice->username,
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.0.1.2',
            'login_at' => Carbon::now()->subHours(1),
        ]);

        $bobSuccessRecent = LoginHistory::create([
            'user_id' => $userBob->id,
            'attempted_identifier' => $userBob->username,
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.2.1',
            'login_at' => Carbon::now()->subHours(3),
        ]);

        $bobSuccessOld = LoginHistory::create([
            'user_id' => $userBob->id,
            'attempted_identifier' => $userBob->username,
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.2.2',
            'login_at' => Carbon::now()->subDays(10),
        ]);

        $unregisteredFailed = LoginHistory::create([
            'user_id' => null,
            'attempted_identifier' => 'unknown_hacker',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.0.99.1',
            'login_at' => Carbon::now()->subHours(4),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_SUCCESS)
            ->filterTable('user_id', $userAlice->id)
            ->assertCanSeeTableRecords([$aliceSuccessRecent])
            ->assertCanNotSeeTableRecords([$aliceFailedRecent, $bobSuccessRecent, $bobSuccessOld, $unregisteredFailed])
            ->assertCountTableRecords(1);

        $todayStr = Carbon::now()->toDateString();
        $threeDaysAgoStr = Carbon::now()->subDays(3)->toDateString();

        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_SUCCESS)
            ->filterTable('user_id', $userBob->id)
            ->filterTable('login_at', [
                'login_from' => $threeDaysAgoStr,
                'login_until' => $todayStr,
            ])
            ->assertCanSeeTableRecords([$bobSuccessRecent])
            ->assertCanNotSeeTableRecords([$bobSuccessOld, $aliceSuccessRecent, $aliceFailedRecent, $unregisteredFailed])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_FAILED)
            ->filterTable('login_at', [
                'login_from' => Carbon::now()->subDays(20)->toDateString(),
                'login_until' => Carbon::now()->subDays(15)->toDateString(),
            ])
            ->assertCountTableRecords(0);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_SUCCESS)
            ->assertCountTableRecords(3)
            ->resetTableFilters()
            ->assertCountTableRecords(5);
    }

    public function test_visitor_tracking_multiple_filter_combinations(): void
    {
        $todayStr = Carbon::now()->toDateString();
        $fiveDaysAgoStr = Carbon::now()->subDays(5)->toDateString();

        $returningToday = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-ret-today'),
            'ip_address' => '10.10.1.1',
            'visit_date' => $todayStr,
            'visit_count' => 6,
            'first_visited_at' => Carbon::now()->subHours(4),
            'last_visited_at' => Carbon::now()->subMinutes(10),
        ]);

        $singleToday = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-single-today'),
            'ip_address' => '10.10.1.2',
            'visit_date' => $todayStr,
            'visit_count' => 1,
            'first_visited_at' => Carbon::now()->subHours(2),
            'last_visited_at' => Carbon::now()->subHours(2),
        ]);

        $returningOld = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-ret-old'),
            'ip_address' => '10.10.2.1',
            'visit_date' => $fiveDaysAgoStr,
            'visit_count' => 4,
            'first_visited_at' => Carbon::now()->subDays(5)->setTime(10, 0),
            'last_visited_at' => Carbon::now()->subDays(5)->setTime(16, 0),
        ]);

        $singleOld = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'visitor-single-old'),
            'ip_address' => '10.10.2.2',
            'visit_date' => $fiveDaysAgoStr,
            'visit_count' => 1,
            'first_visited_at' => Carbon::now()->subDays(5)->setTime(11, 0),
            'last_visited_at' => Carbon::now()->subDays(5)->setTime(11, 0),
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->filterTable('visitor_type', 'returning')
            ->filterTable('visit_date', [
                'date_from' => Carbon::now()->subDays(1)->toDateString(),
                'date_until' => $todayStr,
            ])
            ->assertCanSeeTableRecords([$returningToday])
            ->assertCanNotSeeTableRecords([$singleToday, $returningOld, $singleOld])
            ->assertCountTableRecords(1);

        Livewire::test(ListVisitorTrackings::class)
            ->filterTable('visitor_type', 'single')
            ->filterTable('visit_date', [
                'date_from' => Carbon::now()->subDays(7)->toDateString(),
                'date_until' => Carbon::now()->subDays(3)->toDateString(),
            ])
            ->assertCanSeeTableRecords([$singleOld])
            ->assertCanNotSeeTableRecords([$singleToday, $returningToday, $returningOld])
            ->assertCountTableRecords(1);

        Livewire::test(ListVisitorTrackings::class)
            ->filterTable('visitor_type', 'returning')
            ->assertCountTableRecords(2)
            ->resetTableFilters()
            ->assertCountTableRecords(4);
    }

    public function test_login_history_sorting_by_all_sortable_columns(): void
    {
        $r1 = LoginHistory::create([
            'attempted_identifier' => 'alpha_user_identifier',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.10',
            'login_at' => Carbon::now()->subDays(3),
        ]);

        $r2 = LoginHistory::create([
            'attempted_identifier' => 'middle_user_identifier',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.50',
            'login_at' => Carbon::now()->subDays(2),
        ]);

        $r3 = LoginHistory::create([
            'attempted_identifier' => 'omega_user_identifier',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.0.0.90',
            'login_at' => Carbon::now()->subDays(1),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('attempted_identifier', 'asc')
            ->assertCanSeeTableRecords([$r1, $r2, $r3], inOrder: true);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('attempted_identifier', 'desc')
            ->assertCanSeeTableRecords([$r3, $r2, $r1], inOrder: true);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('ip_address', 'asc')
            ->assertCanSeeTableRecords([$r1, $r2, $r3], inOrder: true);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('ip_address', 'desc')
            ->assertCanSeeTableRecords([$r3, $r2, $r1], inOrder: true);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('login_at', 'asc')
            ->assertCanSeeTableRecords([$r1, $r2, $r3], inOrder: true);

        Livewire::test(ListLoginHistories::class)
            ->sortTable('login_at', 'desc')
            ->assertCanSeeTableRecords([$r3, $r2, $r1], inOrder: true);
    }

    public function test_visitor_tracking_sorting_by_all_sortable_columns(): void
    {
        $v1 = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'sort-v1'),
            'ip_address' => '10.0.0.10',
            'visit_date' => Carbon::now()->subDays(3)->toDateString(),
            'visit_count' => 2,
            'first_visited_at' => Carbon::now()->subDays(3)->setTime(8, 0),
            'last_visited_at' => Carbon::now()->subDays(3)->setTime(9, 0),
        ]);

        $v2 = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'sort-v2'),
            'ip_address' => '10.0.0.50',
            'visit_date' => Carbon::now()->subDays(2)->toDateString(),
            'visit_count' => 15,
            'first_visited_at' => Carbon::now()->subDays(2)->setTime(10, 0),
            'last_visited_at' => Carbon::now()->subDays(2)->setTime(11, 0),
        ]);

        $v3 = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'sort-v3'),
            'ip_address' => '10.0.0.90',
            'visit_date' => Carbon::now()->subDays(1)->toDateString(),
            'visit_count' => 45,
            'first_visited_at' => Carbon::now()->subDays(1)->setTime(12, 0),
            'last_visited_at' => Carbon::now()->subDays(1)->setTime(13, 0),
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('visit_date', 'asc')
            ->assertCanSeeTableRecords([$v1, $v2, $v3], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('visit_date', 'desc')
            ->assertCanSeeTableRecords([$v3, $v2, $v1], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('ip_address', 'asc')
            ->assertCanSeeTableRecords([$v1, $v2, $v3], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('ip_address', 'desc')
            ->assertCanSeeTableRecords([$v3, $v2, $v1], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('visit_count', 'asc')
            ->assertCanSeeTableRecords([$v1, $v2, $v3], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('visit_count', 'desc')
            ->assertCanSeeTableRecords([$v3, $v2, $v1], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('first_visited_at', 'asc')
            ->assertCanSeeTableRecords([$v1, $v2, $v3], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('first_visited_at', 'desc')
            ->assertCanSeeTableRecords([$v3, $v2, $v1], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('last_visited_at', 'asc')
            ->assertCanSeeTableRecords([$v1, $v2, $v3], inOrder: true);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('last_visited_at', 'desc')
            ->assertCanSeeTableRecords([$v3, $v2, $v1], inOrder: true);
    }

    public function test_empty_state_rendering_and_metadata_verification(): void
    {
        $loginTestable = Livewire::test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertCountTableRecords(0)
            ->assertSee('No login history records found')
            ->assertSee('Authentication attempts will appear here automatically when users attempt to log in.');

        $visitorTestable = Livewire::test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCountTableRecords(0)
            ->assertSee('No visitor tracking records recorded')
            ->assertSee('Landing page visits will be logged here as visitors access the site.');
    }

    public function test_empty_state_when_search_query_matches_zero_records(): void
    {
        LoginHistory::create([
            'attempted_identifier' => 'existing_admin_one',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '127.0.0.1',
            'login_at' => Carbon::now(),
        ]);

        VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'existing-visitor'),
            'ip_address' => '127.0.0.1',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => Carbon::now(),
            'last_visited_at' => Carbon::now(),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('completely_non_existent_search_query_token_99999')
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable('completely_non_existent_hash_or_ip_string_99999')
            ->assertSuccessful()
            ->assertCountTableRecords(0);
    }

    public function test_simultaneous_search_filtering_and_sorting_stress(): void
    {
        $u1 = $this->createStandardUser('multi_user_1');
        $u2 = $this->createStandardUser('multi_user_2');

        $recA = LoginHistory::create([
            'user_id' => $u1->id,
            'attempted_identifier' => 'multi_user_1_dept_sales',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.50.1.10',
            'login_at' => Carbon::now()->subDays(2),
        ]);

        $recB = LoginHistory::create([
            'user_id' => $u1->id,
            'attempted_identifier' => 'multi_user_1_dept_support',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.50.1.20',
            'login_at' => Carbon::now()->subDays(1),
        ]);

        $recC = LoginHistory::create([
            'user_id' => $u1->id,
            'attempted_identifier' => 'multi_user_1_dept_admin',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.50.1.30',
            'login_at' => Carbon::now()->subHours(12),
        ]);

        $recD = LoginHistory::create([
            'user_id' => $u2->id,
            'attempted_identifier' => 'multi_user_2_dept_sales',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.50.2.10',
            'login_at' => Carbon::now()->subHours(6),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('dept')
            ->filterTable('status', LoginHistory::STATUS_SUCCESS)
            ->filterTable('user_id', $u1->id)
            ->sortTable('login_at', 'asc')
            ->assertCanSeeTableRecords([$recA, $recB], inOrder: true)
            ->assertCanNotSeeTableRecords([$recC, $recD])
            ->assertCountTableRecords(2);
    }

    public function test_pathological_search_with_extremely_long_string(): void
    {
        LoginHistory::create([
            'attempted_identifier' => 'sample_user',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.1',
            'login_at' => Carbon::now(),
        ]);

        $pathologicalQuery = str_repeat('A1b2C3d4E5f6G7h8I9j0', 50);

        Livewire::test(ListLoginHistories::class)
            ->searchTable($pathologicalQuery)
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable($pathologicalQuery)
            ->assertSuccessful()
            ->assertCountTableRecords(0);
    }

    public function test_table_handles_xss_and_html_entities_in_user_agent_safely(): void
    {
        $xssUserAgent = '<script>alert("XSS")</script><img src=x onerror=alert(1)>';

        $login = LoginHistory::create([
            'attempted_identifier' => 'xss_test_user',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '10.0.0.99',
            'user_agent' => $xssUserAgent,
            'login_at' => Carbon::now(),
        ]);

        $visitor = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'xss-visitor'),
            'ip_address' => '10.0.0.99',
            'user_agent' => $xssUserAgent,
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => Carbon::now(),
            'last_visited_at' => Carbon::now(),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertDontSeeHtml('<script>alert("XSS")</script>');

        Livewire::test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertDontSeeHtml('<script>alert("XSS")</script>');
    }

    public function test_multibyte_unicode_and_emoji_search_and_display(): void
    {
        $emojiRecord = LoginHistory::create([
            'attempted_identifier' => 'syahid_tour_🚀_lombok_üñîçødé',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.88',
            'login_at' => Carbon::now(),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('🚀_lombok')
            ->assertCanSeeTableRecords([$emojiRecord])
            ->assertCountTableRecords(1);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('üñîçødé')
            ->assertCanSeeTableRecords([$emojiRecord])
            ->assertCountTableRecords(1);
    }

    public function test_date_filter_boundary_conditions_and_reversed_ranges(): void
    {
        LoginHistory::create([
            'attempted_identifier' => 'dated_record',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '10.0.0.77',
            'login_at' => Carbon::parse('2026-06-15 12:00:00'),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('login_at', [
                'login_from' => '2026-12-31',
                'login_until' => '2026-01-01',
            ])
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('login_at', [
                'login_from' => null,
                'login_until' => null,
            ])
            ->assertSuccessful()
            ->assertCountTableRecords(1);
    }

    public function test_visitor_tracking_high_visit_count_rendering_and_sorting(): void
    {
        $low = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'low-visits'),
            'ip_address' => '10.0.0.1',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => Carbon::now()->subHours(5),
            'last_visited_at' => Carbon::now()->subHours(5),
        ]);

        $mid = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'mid-visits'),
            'ip_address' => '10.0.0.2',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 999,
            'first_visited_at' => Carbon::now()->subHours(3),
            'last_visited_at' => Carbon::now()->subHours(1),
        ]);

        $high = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'high-visits'),
            'ip_address' => '10.0.0.3',
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 99999,
            'first_visited_at' => Carbon::now()->subHours(8),
            'last_visited_at' => Carbon::now(),
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->sortTable('visit_count', 'desc')
            ->assertCanSeeTableRecords([$high, $mid, $low], inOrder: true);
    }
}

