<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Pages\ListVisitorTrackings;
use App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart;
use App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview;
use App\Models\User;
use App\Models\VisitorTracking;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorTrackingWidgetSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        $this->superAdmin = User::create([
            'name' => 'Super Admin Sec',
            'username' => 'superadmin_sec_widget',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sec',
            'username' => 'admin_sec_widget',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $this->staff = User::create([
            'name' => 'Staff Sec',
            'username' => 'staff_sec_widget',
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => User::ROLE_STAFF,
            'must_change_password' => false,
        ]);
    }

    public function test_admin_is_forbidden_from_mounting_visitor_stats_overview(): void
    {
        Livewire::actingAs($this->admin)
            ->test(VisitorStatsOverview::class)
            ->assertForbidden();
    }

    public function test_staff_is_forbidden_from_mounting_visitor_stats_overview(): void
    {
        Livewire::actingAs($this->staff)
            ->test(VisitorStatsOverview::class)
            ->assertForbidden();
    }

    public function test_guest_is_forbidden_from_mounting_visitor_stats_overview(): void
    {
        Auth::logout();

        Livewire::test(VisitorStatsOverview::class)
            ->assertForbidden();
    }

    public function test_admin_is_forbidden_from_mounting_monthly_visitors_chart(): void
    {
        Livewire::actingAs($this->admin)
            ->test(MonthlyVisitorsChart::class)
            ->assertForbidden();
    }

    public function test_staff_is_forbidden_from_mounting_monthly_visitors_chart(): void
    {
        Livewire::actingAs($this->staff)
            ->test(MonthlyVisitorsChart::class)
            ->assertForbidden();
    }

    public function test_guest_is_forbidden_from_mounting_monthly_visitors_chart(): void
    {
        Auth::logout();

        Livewire::test(MonthlyVisitorsChart::class)
            ->assertForbidden();
    }

    public function test_static_can_view_contract_enforces_superadmin_strictly(): void
    {
        Auth::login($this->superAdmin);
        $this->assertTrue(VisitorStatsOverview::canView());
        $this->assertTrue(MonthlyVisitorsChart::canView());

        Auth::login($this->admin);
        $this->assertFalse(VisitorStatsOverview::canView());
        $this->assertFalse(MonthlyVisitorsChart::canView());

        Auth::login($this->staff);
        $this->assertFalse(VisitorStatsOverview::canView());
        $this->assertFalse(MonthlyVisitorsChart::canView());

        Auth::logout();
        $this->assertFalse(VisitorStatsOverview::canView());
        $this->assertFalse(MonthlyVisitorsChart::canView());
    }

    public function test_visitor_tracking_table_renders_masked_ip_and_safeguards_privacy(): void
    {
        $v4 = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'privacy-ip-v4'),
            'ip_address' => '203.0.113.195',
            'user_agent' => 'PrivacyBot/1.0',
            'visit_date' => now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => now(),
            'last_visited_at' => now(),
        ]);

        $v6 = VisitorTracking::create([
            'visitor_hash' => hash('sha256', 'privacy-ip-v6'),
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'user_agent' => 'PrivacyBot/1.0',
            'visit_date' => now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => now(),
            'last_visited_at' => now(),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful();

        $html = $component->html();

        // Must display masked IPs
        $this->assertStringContainsString('203.0.113.***', $html);
        $this->assertStringContainsString('2001:0db8:****', $html);

        // Must NOT leak full raw public IP addresses in the rendered HTML table
        $this->assertStringNotContainsString('203.0.113.195', $html);
        $this->assertStringNotContainsString('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $html);
    }

    public function test_visitor_tracking_ip_copyable_state_is_masked(): void
    {
        $this->actingAs($this->superAdmin);

        $record = new VisitorTracking([
            'ip_address' => '198.51.100.42',
        ]);

        $page = Livewire::test(ListVisitorTrackings::class)->instance();
        $table = $page->getTable();
        $columns = $table->getColumns();

        $this->assertArrayHasKey('ip_address', $columns);
        $ipCol = $columns['ip_address'];

        // copyableState must evaluate to masked IP
        $copyableValue = $ipCol->record($record)->getCopyableState('198.51.100.42');
        $this->assertSame('198.51.100.***', $copyableValue);

        // formatState must also format to masked IP
        $formatted = $ipCol->record($record)->formatState('198.51.100.42');
        $this->assertSame('198.51.100.***', $formatted);
    }
}
