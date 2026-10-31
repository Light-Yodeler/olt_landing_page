<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Models\User;
use App\Models\VisitorTracking;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorTrackingTablePrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        config(['app.url' => 'https://olt.test']);
        \Illuminate\Support\Facades\URL::forceRootUrl('https://olt.test');

        $this->superAdmin = User::create([
            'name' => 'Super Admin Privacy Tester',
            'username' => 'privacy_superadmin',
            'password' => Hash::make('PrivacySecurePass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Privacy Tester',
            'username' => 'privacy_admin',
            'password' => Hash::make('PrivacySecurePass123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $this->staff = User::create([
            'name' => 'Staff Privacy Tester',
            'username' => 'privacy_staff',
            'password' => Hash::make('PrivacySecurePass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
    }

    /**
     * Helper to create a visitor tracking record with specified attributes.
     *
     * @param array<string, mixed> $attributes
     */
    protected function createVisitorRecord(array $attributes = []): VisitorTracking
    {
        static $counter = 0;
        $counter++;

        return VisitorTracking::create(array_merge([
            'visitor_hash' => hash('sha256', "privacy-seed-hash-{$counter}"),
            'ip_address' => '203.0.113.195',
            'user_agent' => "PrivacyBrowser/{$counter}.0",
            'visit_date' => Carbon::now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => Carbon::now()->subMinutes(30),
            'last_visited_at' => Carbon::now(),
        ], $attributes));
    }

    /**
     * Test 1: Super Admin mounts ListVisitorTrackings table; table HTML displays masked
     * IPv4 and IPv6 addresses and does NOT contain raw unmasked IP strings.
     */
    public function test_superadmin_table_renders_masked_ipv4_and_ipv6_without_raw_ip_exposure(): void
    {
        $rawIps = [
            '203.0.113.195',
            '198.51.100.42',
            '127.0.0.1',
            '10.20.30.40',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ];

        $expectedMasked = [
            '203.0.113.***',
            '198.51.100.***',
            '127.0.0.***',
            '10.20.30.***',
            '2001:0db8:****',
        ];

        $records = [];
        foreach ($rawIps as $index => $ip) {
            $records[] = $this->createVisitorRecord([
                'visitor_hash' => hash('sha256', "ip-test-token-{$index}"),
                'ip_address' => $ip,
                'visit_count' => $index + 1,
            ]);
        }

        $component = Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCountTableRecords(count($rawIps));

        $html = $component->html();

        // 1. Assert all masked IP patterns appear in the rendered HTML table
        foreach ($expectedMasked as $masked) {
            $this->assertStringContainsString(
                $masked,
                $html,
                "Rendered table HTML must contain masked IP '{$masked}'."
            );
        }

        // 2. Assert NO raw unmasked IP strings appear anywhere in the rendered HTML table
        foreach ($rawIps as $rawIp) {
            $this->assertStringNotContainsString(
                $rawIp,
                $html,
                "Rendered table HTML must NOT contain raw unmasked IP '{$rawIp}'."
            );
        }
    }

    /**
     * Test 2: Clipboard copyable state and Alpine copy binding evaluate to masked IP strings.
     */
    public function test_clipboard_copy_state_and_alpine_binding_are_bound_to_masked_ip(): void
    {
        $v4Record = $this->createVisitorRecord([
            'ip_address' => '203.0.113.195',
        ]);

        $v6Record = $this->createVisitorRecord([
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful();

        $page = $component->instance();
        $table = $page->getTable();
        $columns = $table->getColumns();

        $this->assertArrayHasKey('ip_address', $columns, 'Table must define an ip_address column.');
        $ipColumn = $columns['ip_address'];
        $this->assertInstanceOf(TextColumn::class, $ipColumn);

        // Verify copyableState evaluation
        $v4CopyableState = $ipColumn->record($v4Record)->getCopyableState($v4Record->ip_address);
        $this->assertSame('203.0.113.***', $v4CopyableState);

        $v6CopyableState = $ipColumn->record($v6Record)->getCopyableState($v6Record->ip_address);
        $this->assertSame('2001:0db8:****', $v6CopyableState);

        // Verify formatState evaluation
        $v4FormattedState = $ipColumn->record($v4Record)->formatState($v4Record->ip_address);
        $this->assertSame('203.0.113.***', $v4FormattedState);

        $v6FormattedState = $ipColumn->record($v6Record)->formatState($v6Record->ip_address);
        $this->assertSame('2001:0db8:****', $v6FormattedState);

        // Verify that Alpine clipboard bindings in HTML explicitly write masked IPs to clipboard
        $html = $component->html();
        $this->assertStringContainsString("window.navigator.clipboard.writeText('203.0.113.***')", $html);
        $this->assertStringContainsString("window.navigator.clipboard.writeText('2001:0db8:****')", $html);
        $this->assertStringNotContainsString("window.navigator.clipboard.writeText('203.0.113.195')", $html);
        $this->assertStringNotContainsString("window.navigator.clipboard.writeText('2001:0db8:85a3:0000:0000:8a2e:0370:7334')", $html);
    }

    /**
     * Test 3: Null, blank, and empty IP addresses fall back gracefully to 'Hidden'.
     */
    public function test_empty_or_null_ip_address_falls_back_to_hidden_in_table_and_copy_state(): void
    {
        $nullRecord = $this->createVisitorRecord([
            'visitor_hash' => hash('sha256', 'null-ip-visitor'),
            'ip_address' => null,
        ]);

        $emptyRecord = $this->createVisitorRecord([
            'visitor_hash' => hash('sha256', 'empty-ip-visitor'),
            'ip_address' => '',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCountTableRecords(2);

        $html = $component->html();
        $this->assertStringContainsString('Hidden', $html, 'Rendered table HTML must display "Hidden" for null/empty IP.');

        $page = $component->instance();
        $table = $page->getTable();
        $ipColumn = $table->getColumns()['ip_address'];

        // Test copyable state for null record
        $nullCopyState = $ipColumn->record($nullRecord)->getCopyableState($nullRecord->ip_address);
        $this->assertSame('Hidden', $nullCopyState);

        // Test format state for null record
        $nullFormatState = $ipColumn->record($nullRecord)->formatState($nullRecord->ip_address);
        $this->assertSame('Hidden', $nullFormatState);

        // Test copyable state for empty string record
        $emptyCopyState = $ipColumn->record($emptyRecord)->getCopyableState($emptyRecord->ip_address);
        $this->assertSame('Hidden', $emptyCopyState);

        // Test format state for empty string record
        $emptyFormatState = $ipColumn->record($emptyRecord)->formatState($emptyRecord->ip_address);
        $this->assertSame('Hidden', $emptyFormatState);
    }

    /**
     * Test 4: Table date filters (date_from, date_until) handle boundary dates, inclusive
     * ranges, single-day matches, and reversed ranges correctly.
     */
    public function test_table_date_filters_boundary_conditions(): void
    {
        $recPast10 = $this->createVisitorRecord([
            'visit_date' => '2026-10-15',
            'last_visited_at' => Carbon::parse('2026-10-15 10:00:00'),
        ]);

        $recPast5 = $this->createVisitorRecord([
            'visit_date' => '2026-10-20',
            'last_visited_at' => Carbon::parse('2026-10-20 12:00:00'),
        ]);

        $recTargetA = $this->createVisitorRecord([
            'visit_date' => '2026-10-25',
            'last_visited_at' => Carbon::parse('2026-10-25 08:30:00'),
        ]);

        $recTargetB = $this->createVisitorRecord([
            'visit_date' => '2026-10-28',
            'last_visited_at' => Carbon::parse('2026-10-28 15:45:00'),
        ]);

        $recToday = $this->createVisitorRecord([
            'visit_date' => '2026-10-31',
            'last_visited_at' => Carbon::parse('2026-10-31 18:00:00'),
        ]);

        // Scenario A: date_from only (records on or after 2026-10-25)
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-25',
                'date_until' => null,
            ])
            ->assertCanSeeTableRecords([$recTargetA, $recTargetB, $recToday])
            ->assertCanNotSeeTableRecords([$recPast10, $recPast5])
            ->assertCountTableRecords(3);

        // Scenario B: date_until only (records on or before 2026-10-20)
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => null,
                'date_until' => '2026-10-20',
            ])
            ->assertCanSeeTableRecords([$recPast10, $recPast5])
            ->assertCanNotSeeTableRecords([$recTargetA, $recTargetB, $recToday])
            ->assertCountTableRecords(2);

        // Scenario C: Inclusive range boundary [2026-10-25 to 2026-10-28]
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-25',
                'date_until' => '2026-10-28',
            ])
            ->assertCanSeeTableRecords([$recTargetA, $recTargetB])
            ->assertCanNotSeeTableRecords([$recPast10, $recPast5, $recToday])
            ->assertCountTableRecords(2);

        // Scenario D: Single-day exact match (date_from == date_until == 2026-10-25)
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-25',
                'date_until' => '2026-10-25',
            ])
            ->assertCanSeeTableRecords([$recTargetA])
            ->assertCanNotSeeTableRecords([$recPast10, $recPast5, $recTargetB, $recToday])
            ->assertCountTableRecords(1);

        // Scenario E: Reversed dates (date_from > date_until) returns 0 records safely
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-31',
                'date_until' => '2026-10-15',
            ])
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        // Scenario F: Resetting filters restores all records
        Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->filterTable('visit_date', [
                'date_from' => '2026-10-25',
                'date_until' => '2026-10-28',
            ])
            ->assertCountTableRecords(2)
            ->resetTableFilters()
            ->assertCountTableRecords(5);
    }

    /**
     * Test 5: Table date filters reject SQL injection attack strings without syntax errors,
     * query crashes, or bypass of filter constraints.
     */
    public function test_table_date_filters_sql_injection_attack_vectors_handled_safely(): void
    {
        $legitRecord = $this->createVisitorRecord([
            'visitor_hash' => hash('sha256', 'legit-record-hash'),
            'ip_address' => '203.0.113.77',
            'visit_date' => '2026-10-31',
        ]);

        $sqlInjectionPayloads = [
            "' OR '1'='1' --",
            "' OR 1=1 --",
            "2026-10-31' OR '1'='1",
            "'; DROP TABLE visitor_trackings; --",
            "' UNION SELECT NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL --",
            "2026-10-31' AND SLEEP(1) --",
            "\" OR \"\"=\"",
            "'; EXEC sp_msforeachtable 'DROP TABLE ?' --",
            "' OR visit_date LIKE '%2026%' --",
            "1; SELECT * FROM users --",
            "admin'#",
            "'/*",
            "' OR true --",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            // Test payload in date_from
            Livewire::actingAs($this->superAdmin)
                ->test(ListVisitorTrackings::class)
                ->filterTable('visit_date', [
                    'date_from' => $payload,
                    'date_until' => null,
                ])
                ->assertSuccessful();

            // Test payload in date_until
            Livewire::actingAs($this->superAdmin)
                ->test(ListVisitorTrackings::class)
                ->filterTable('visit_date', [
                    'date_from' => null,
                    'date_until' => $payload,
                ])
                ->assertSuccessful();

            // Test payload in both
            Livewire::actingAs($this->superAdmin)
                ->test(ListVisitorTrackings::class)
                ->filterTable('visit_date', [
                    'date_from' => $payload,
                    'date_until' => $payload,
                ])
                ->assertSuccessful();
        }

        // Assert database table and record remain completely intact and uncorrupted
        $this->assertDatabaseHas('visitor_trackings', [
            'id' => $legitRecord->id,
            'ip_address' => '203.0.113.77',
        ]);
    }

    /**
     * Test 6: Privacy safeguards persist across interactive table states (search, sort, filter).
     */
    public function test_ip_privacy_persists_during_table_search_sort_and_filter_interactions(): void
    {
        $v1 = $this->createVisitorRecord([
            'visitor_hash' => hash('sha256', 'interactive-v1'),
            'ip_address' => '203.0.113.55',
            'visit_date' => '2026-10-30',
            'visit_count' => 1,
        ]);

        $v2 = $this->createVisitorRecord([
            'visitor_hash' => hash('sha256', 'interactive-v2'),
            'ip_address' => '198.51.100.88',
            'visit_date' => '2026-10-31',
            'visit_count' => 5,
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            // Apply search by visitor hash prefix
            ->searchTable(substr($v1->visitor_hash, 0, 10))
            // Apply sorting
            ->sortTable('last_visited_at', 'desc')
            // Apply category filter
            ->filterTable('visitor_type', 'single')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$v1])
            ->assertCanNotSeeTableRecords([$v2]);

        $html = $component->html();

        // Must display masked IP
        $this->assertStringContainsString('203.0.113.***', $html);

        // Must NOT expose unmasked raw IP
        $this->assertStringNotContainsString('203.0.113.55', $html);
        $this->assertStringNotContainsString('198.51.100.88', $html);
    }

    /**
     * Test 7: Non-superadmin users (Admin, Staff, Guest) are strictly forbidden from mounting
     * the table component, preventing any IP extraction or data leakage.
     */
    public function test_non_superadmin_users_cannot_mount_table_or_access_visitor_ips(): void
    {
        $this->createVisitorRecord([
            'ip_address' => '203.0.113.99',
        ]);

        // Admin role -> 403 Forbidden
        Livewire::actingAs($this->admin)
            ->test(ListVisitorTrackings::class)
            ->assertForbidden();

        // Staff role -> 403 Forbidden
        Livewire::actingAs($this->staff)
            ->test(ListVisitorTrackings::class)
            ->assertForbidden();

        // Guest -> 403 Forbidden
        Auth::logout();
        Livewire::test(ListVisitorTrackings::class)
            ->assertForbidden();
    }
}
