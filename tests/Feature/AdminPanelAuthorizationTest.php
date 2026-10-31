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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        if (class_exists(Filament::class) && Filament::hasPlugin('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }
    }

    protected function createUserWithRole(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'username' => $username,
            'password' => Hash::make('SecurePassword123!'),
            'role' => $role,
            'must_change_password' => false,
        ]);
    }

    protected function createLoginHistory(array $attributes = []): LoginHistory
    {
        return LoginHistory::create(array_merge([
            'user_id' => null,
            'attempted_identifier' => 'test_identifier',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'login_at' => now(),
        ], $attributes));
    }

    public function test_superadmin_can_access_login_histories_route(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_1');

        $response = $this->actingAs($superAdmin)->get('/admin/login-histories');

        $response->assertStatus(200);
    }

    public function test_superadmin_can_access_visitor_trackings_route(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_2');

        $response = $this->actingAs($superAdmin)->get('/admin/visitor-trackings');

        $response->assertStatus(200);
    }

    public function test_admin_is_forbidden_from_login_histories_route(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'feat_admin_1');

        $response = $this->actingAs($admin)->get('/admin/login-histories');

        $response->assertStatus(403);
    }

    public function test_admin_is_forbidden_from_visitor_trackings_route(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'feat_admin_2');

        $response = $this->actingAs($admin)->get('/admin/visitor-trackings');

        $response->assertStatus(403);
    }

    public function test_staff_is_forbidden_from_login_histories_route(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'feat_staff_1');

        $response = $this->actingAs($staff)->get('/admin/login-histories');

        $response->assertStatus(403);
    }

    public function test_staff_is_forbidden_from_visitor_trackings_route(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'feat_staff_2');

        $response = $this->actingAs($staff)->get('/admin/visitor-trackings');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_guest_is_redirected_from_login_histories_route(): void
    {
        $response = $this->get('/admin/login-histories');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_guest_is_redirected_from_visitor_trackings_route(): void
    {
        $response = $this->get('/admin/visitor-trackings');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_sidebar_navigation_renders_links_for_superadmin_and_hides_for_admin_and_staff(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_side_super');
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'feat_side_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'feat_side_staff');

        $superResponse = $this->actingAs($superAdmin)->get('/admin');
        $superResponse->assertStatus(200);
        $superResponse->assertSee('/admin/login-histories');
        $superResponse->assertSee('/admin/visitor-trackings');

        $this->flushSession();

        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('/admin/login-histories');
        $adminResponse->assertDontSee('/admin/visitor-trackings');

        $this->flushSession();

        $staffResponse = $this->actingAs($staff)->get('/admin');
        $staffResponse->assertStatus(200);
        $staffResponse->assertDontSee('/admin/login-histories');
        $staffResponse->assertDontSee('/admin/visitor-trackings');
    }

    public function test_superadmin_can_render_login_histories_table_and_view_records(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_table_1');
        $this->actingAs($superAdmin);

        $record1 = $this->createLoginHistory([
            'attempted_identifier' => 'alice_tester',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '198.51.100.10',
            'login_at' => now()->subMinutes(10),
        ]);

        $record2 = $this->createLoginHistory([
            'attempted_identifier' => 'bob_tester',
            'status' => LoginHistory::STATUS_FAILED,
            'ip_address' => '198.51.100.20',
            'login_at' => now()->subMinutes(5),
        ]);

        Livewire::test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$record1, $record2])
            ->assertTableColumnExists('attempted_identifier')
            ->assertTableColumnExists('status')
            ->assertTableColumnExists('ip_address')
            ->assertTableColumnExists('login_at');
    }

    public function test_superadmin_can_render_visitor_trackings_table_and_view_records(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_table_2');
        $this->actingAs($superAdmin);

        $visitor1 = VisitorTracking::factory()->create([
            'ip_address' => '203.0.113.10',
            'visit_date' => now()->toDateString(),
            'visit_count' => 1,
        ]);

        $visitor2 = VisitorTracking::factory()->create([
            'ip_address' => '203.0.113.20',
            'visit_date' => now()->toDateString(),
            'visit_count' => 4,
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$visitor1, $visitor2])
            ->assertTableColumnExists('visitor_hash')
            ->assertTableColumnExists('ip_address')
            ->assertTableColumnExists('visit_date')
            ->assertTableColumnExists('visit_count')
            ->assertTableColumnExists('is_returning')
            ->assertTableColumnExists('last_visited_at');
    }

    public function test_login_history_table_can_search_by_identifier_and_ip(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_search_1');
        $this->actingAs($superAdmin);

        $targetRecord = $this->createLoginHistory([
            'attempted_identifier' => 'unique_target_search',
            'ip_address' => '192.0.2.111',
        ]);

        $otherRecord = $this->createLoginHistory([
            'attempted_identifier' => 'other_user_search',
            'ip_address' => '192.0.2.222',
        ]);

        Livewire::test(ListLoginHistories::class)
            ->searchTable('unique_target_search')
            ->assertCanSeeTableRecords([$targetRecord])
            ->assertCanNotSeeTableRecords([$otherRecord])
            ->searchTable('192.0.2.222')
            ->assertCanSeeTableRecords([$otherRecord])
            ->assertCanNotSeeTableRecords([$targetRecord]);
    }

    public function test_visitor_tracking_table_can_search_by_ip_and_hash(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_search_2');
        $this->actingAs($superAdmin);

        $targetVisitor = VisitorTracking::factory()->create([
            'ip_address' => '198.51.100.77',
            'visitor_hash' => str_repeat('a', 64),
        ]);

        $otherVisitor = VisitorTracking::factory()->create([
            'ip_address' => '198.51.100.88',
            'visitor_hash' => str_repeat('b', 64),
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->searchTable('198.51.100.77')
            ->assertCanSeeTableRecords([$targetVisitor])
            ->assertCanNotSeeTableRecords([$otherVisitor]);
    }

    public function test_login_history_table_can_filter_by_status(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_filter_1');
        $this->actingAs($superAdmin);

        $successRecord = $this->createLoginHistory([
            'attempted_identifier' => 'successful_login_user',
            'status' => LoginHistory::STATUS_SUCCESS,
        ]);

        $failedRecord = $this->createLoginHistory([
            'attempted_identifier' => 'failed_login_user',
            'status' => LoginHistory::STATUS_FAILED,
        ]);

        Livewire::test(ListLoginHistories::class)
            ->filterTable('status', LoginHistory::STATUS_SUCCESS)
            ->assertCanSeeTableRecords([$successRecord])
            ->assertCanNotSeeTableRecords([$failedRecord])
            ->filterTable('status', LoginHistory::STATUS_FAILED)
            ->assertCanSeeTableRecords([$failedRecord])
            ->assertCanNotSeeTableRecords([$successRecord]);
    }

    public function test_visitor_tracking_table_can_filter_by_visitor_type(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_filter_2');
        $this->actingAs($superAdmin);

        $singleVisitor = VisitorTracking::factory()->create([
            'visitor_hash' => str_repeat('1', 64),
            'visit_count' => 1,
        ]);

        $returningVisitor = VisitorTracking::factory()->create([
            'visitor_hash' => str_repeat('2', 64),
            'visit_count' => 5,
        ]);

        Livewire::test(ListVisitorTrackings::class)
            ->filterTable('visitor_type', 'returning')
            ->assertCanSeeTableRecords([$returningVisitor])
            ->assertCanNotSeeTableRecords([$singleVisitor])
            ->filterTable('visitor_type', 'single')
            ->assertCanSeeTableRecords([$singleVisitor])
            ->assertCanNotSeeTableRecords([$returningVisitor]);
    }

    public function test_login_history_disables_all_mutations_and_has_no_create_edit_delete_actions(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_actions_1');
        $this->actingAs($superAdmin);

        $dummyRecord = new class extends Model {};

        $this->assertFalse(LoginHistoryResource::canCreate());
        $this->assertFalse(LoginHistoryResource::canEdit($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDelete($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDeleteAny());

        Livewire::test(ListLoginHistories::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_visitor_tracking_disables_all_mutations_and_has_no_create_edit_delete_actions(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_actions_2');
        $this->actingAs($superAdmin);

        $dummyRecord = new class extends Model {};

        $this->assertFalse(VisitorTrackingResource::canCreate());
        $this->assertFalse(VisitorTrackingResource::canEdit($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canDelete($dummyRecord));
        $this->assertFalse(VisitorTrackingResource::canDeleteAny());

        Livewire::test(ListVisitorTrackings::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_mutating_routes_return_forbidden_or_not_found(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_mutate');

        $createHistory = $this->actingAs($superAdmin)->get('/admin/login-histories/create');
        $this->assertContains($createHistory->getStatusCode(), [403, 404]);

        $createVisitor = $this->actingAs($superAdmin)->get('/admin/visitor-trackings/create');
        $this->assertContains($createVisitor->getStatusCode(), [403, 404]);
    }

    public function test_empty_tables_render_successfully_without_records(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'feat_super_empty');
        $this->actingAs($superAdmin);

        Livewire::test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        Livewire::test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertCountTableRecords(0);
    }
}
