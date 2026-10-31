<?php

namespace Tests\Feature\E2E;

use App\Filament\Resources\LoginHistoryResource;
use App\Filament\Resources\VisitorTrackingResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createUserWithRole(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test User',
            'username' => $username,
            'password' => Hash::make('SecurePassword123!'),
            'role' => $role,
            'must_change_password' => false,
        ]);
    }

    // ==========================================
    // Tier 1: Core Authorization & Route Access
    // ==========================================

    public function test_tier1_superadmin_can_access_login_histories_page(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'superadmin_auth_1');

        $response = $this->actingAs($superAdmin)->get('/admin/login-histories');

        $response->assertStatus(200);
    }

    public function test_tier1_superadmin_can_access_visitor_trackings_page(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'superadmin_auth_2');

        $response = $this->actingAs($superAdmin)->get('/admin/visitor-trackings');

        $response->assertStatus(200);
    }

    public function test_tier1_admin_is_forbidden_from_login_histories_page(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'admin_auth_1');

        $response = $this->actingAs($admin)->get('/admin/login-histories');

        $response->assertStatus(403);
    }

    public function test_tier1_admin_is_forbidden_from_visitor_trackings_page(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'admin_auth_2');

        $response = $this->actingAs($admin)->get('/admin/visitor-trackings');

        $response->assertStatus(403);
    }

    public function test_tier1_staff_is_forbidden_from_login_histories_page(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'staff_auth_1');

        $response = $this->actingAs($staff)->get('/admin/login-histories');

        $response->assertStatus(403);
    }

    public function test_tier1_staff_is_forbidden_from_visitor_trackings_page(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'staff_auth_2');

        $response = $this->actingAs($staff)->get('/admin/visitor-trackings');

        $response->assertStatus(403);
    }

    public function test_tier1_unauthenticated_guest_is_redirected_to_admin_login_from_login_histories(): void
    {
        $response = $this->get('/admin/login-histories');

        $response->assertRedirect('/admin/login');
    }

    public function test_tier1_unauthenticated_guest_is_redirected_to_admin_login_from_visitor_trackings(): void
    {
        $response = $this->get('/admin/visitor-trackings');

        $response->assertRedirect('/admin/login');
    }

    // ==========================================
    // Tier 2: Resource Methods & Sidebar Navigation Visibility
    // ==========================================

    public function test_tier2_resource_can_access_strictly_requires_superadmin_role(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class), 'LoginHistoryResource class must exist.');
        $this->assertTrue(class_exists(VisitorTrackingResource::class), 'VisitorTrackingResource class must exist.');

        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'check_super');
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'check_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'check_staff');

        // Super Admin must be granted access
        $this->actingAs($superAdmin);
        $this->assertTrue(LoginHistoryResource::canAccess());
        $this->assertTrue(VisitorTrackingResource::canAccess());

        $this->flushSession();

        // Admin must be denied
        $this->actingAs($admin);
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::canAccess());

        $this->flushSession();

        // Staff must be denied
        $this->actingAs($staff);
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::canAccess());

        $this->flushSession();

        // Guest must be denied
        auth()->logout();
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::canAccess());
    }

    public function test_tier2_resource_navigation_registration_strictly_requires_superadmin_role(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class));
        $this->assertTrue(class_exists(VisitorTrackingResource::class));

        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'nav_super');
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'nav_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'nav_staff');

        $this->actingAs($superAdmin);
        $this->assertTrue(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertTrue(VisitorTrackingResource::shouldRegisterNavigation());

        $this->flushSession();

        $this->actingAs($admin);
        $this->assertFalse(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertFalse(VisitorTrackingResource::shouldRegisterNavigation());

        $this->flushSession();

        $this->actingAs($staff);
        $this->assertFalse(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertFalse(VisitorTrackingResource::shouldRegisterNavigation());
    }

    public function test_tier2_sidebar_navigation_renders_links_for_superadmin_and_hides_for_admin_and_staff(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sidebar_super');
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sidebar_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sidebar_staff');

        // Super Admin dashboard must display navigation items
        $superResponse = $this->actingAs($superAdmin)->get('/admin');
        $superResponse->assertStatus(200);
        $superResponse->assertSee('/admin/login-histories');
        $superResponse->assertSee('/admin/visitor-trackings');

        $this->flushSession();

        // Admin dashboard must NOT display navigation links
        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('/admin/login-histories');
        $adminResponse->assertDontSee('/admin/visitor-trackings');

        $this->flushSession();

        // Staff dashboard must NOT display navigation links
        $staffResponse = $this->actingAs($staff)->get('/admin');
        $staffResponse->assertStatus(200);
        $staffResponse->assertDontSee('/admin/login-histories');
        $staffResponse->assertDontSee('/admin/visitor-trackings');
    }

    public function test_tier2_mutating_actions_disabled_can_create_returns_false(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class));
        $this->assertTrue(class_exists(VisitorTrackingResource::class));

        $this->assertFalse(LoginHistoryResource::canCreate(), 'Login history is strictly read-only and cannot be manually created.');
        $this->assertFalse(VisitorTrackingResource::canCreate(), 'Visitor tracking is strictly read-only and cannot be manually created.');
    }

    public function test_tier2_mutating_actions_disabled_can_edit_returns_false(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class));
        $this->assertTrue(class_exists(VisitorTrackingResource::class));

        $dummyRecord = new class extends Model {};

        $this->assertFalse(LoginHistoryResource::canEdit($dummyRecord), 'Login history records cannot be modified.');
        $this->assertFalse(VisitorTrackingResource::canEdit($dummyRecord), 'Visitor tracking records cannot be modified.');
    }

    public function test_tier2_mutating_actions_disabled_can_delete_returns_false(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class));
        $this->assertTrue(class_exists(VisitorTrackingResource::class));

        $dummyRecord = new class extends Model {};

        $this->assertFalse(LoginHistoryResource::canDelete($dummyRecord), 'Single deletion must be prohibited for audit logs.');
        $this->assertFalse(LoginHistoryResource::canDeleteAny(), 'Bulk deletion must be prohibited for audit logs.');

        $this->assertFalse(VisitorTrackingResource::canDelete($dummyRecord), 'Single deletion must be prohibited for visitor tracking.');
        $this->assertFalse(VisitorTrackingResource::canDeleteAny(), 'Bulk deletion must be prohibited for visitor tracking.');
    }

    public function test_tier2_mutating_routes_are_inaccessible(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'mutate_super');

        // Creating routes should be 403 or 404
        $createHistory = $this->actingAs($superAdmin)->get('/admin/login-histories/create');
        $this->assertContains($createHistory->getStatusCode(), [403, 404]);

        $createVisitor = $this->actingAs($superAdmin)->get('/admin/visitor-trackings/create');
        $this->assertContains($createVisitor->getStatusCode(), [403, 404]);
    }

    // ==========================================
    // Tier 3: Privilege Escalation, Isolation & Navigation Group
    // ==========================================

    public function test_tier3_privilege_escalation_switching_from_superadmin_to_staff_immediately_revokes_access(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'switch_super');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'switch_staff');

        // Super Admin access succeeds
        $this->actingAs($superAdmin)->get('/admin/login-histories')->assertStatus(200);

        // Immediate switch to Staff
        $this->flushSession();
        $this->actingAs($staff)->get('/admin/login-histories')->assertStatus(403);
    }

    public function test_tier3_navigation_group_is_activity_and_logs(): void
    {
        $this->assertTrue(class_exists(LoginHistoryResource::class));
        $this->assertTrue(class_exists(VisitorTrackingResource::class));

        $this->assertEquals('Activity & Logs', LoginHistoryResource::getNavigationGroup());
        $this->assertEquals('Activity & Logs', VisitorTrackingResource::getNavigationGroup());
    }

    public function test_tier3_staff_and_admin_retain_standard_permissions_while_denied_audit_logs(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'matrix_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'matrix_staff');

        // Admin can access Site Settings, but denied audit logs
        $this->actingAs($admin)->get('/admin/manage-site-settings')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/login-histories')->assertStatus(403);
        $this->actingAs($admin)->get('/admin/visitor-trackings')->assertStatus(403);

        $this->flushSession();

        // Staff can access Destinations, but denied audit logs
        $this->actingAs($staff)->get('/admin/destinations')->assertStatus(200);
        $this->actingAs($staff)->get('/admin/login-histories')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/visitor-trackings')->assertStatus(403);
    }
}
