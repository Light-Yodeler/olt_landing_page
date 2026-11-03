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
use PHPUnit\Framework\ExpectationFailedException;
use Tests\TestCase;

class AdminPanelSecurityTest extends TestCase
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
            'name' => ucfirst($role) . ' Security User',
            'username' => $username,
            'password' => Hash::make('SuperSecurePass123!'),
            'role' => $role,
            'must_change_password' => false,
        ]);
    }

    protected function createSampleLoginHistory(array $attributes = []): LoginHistory
    {
        return LoginHistory::create(array_merge([
            'user_id' => null,
            'attempted_identifier' => 'adversarial_identifier',
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => '198.51.100.99',
            'user_agent' => 'AdversarialSecurityAgent/1.0',
            'login_at' => now(),
        ], $attributes));
    }

    protected function createSampleVisitorTracking(array $attributes = []): VisitorTracking
    {
        return VisitorTracking::create(array_merge([
            'visitor_hash' => hash('sha256', 'adversarial_visitor_seed'),
            'ip_address' => '203.0.113.199',
            'user_agent' => 'AdversarialVisitorAgent/1.0',
            'visit_date' => now()->toDateString(),
            'visit_count' => 1,
            'first_visited_at' => now(),
            'last_visited_at' => now(),
        ], $attributes));
    }

    // 1. Direct Livewire Component Calls (Admin, Staff, Guest -> 403 Forbidden)

    public function test_admin_cannot_mount_login_histories_livewire_component(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_admin_lh');

        Livewire::actingAs($admin)
            ->test(ListLoginHistories::class)
            ->assertForbidden();
    }

    public function test_staff_cannot_mount_login_histories_livewire_component(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_staff_lh');

        Livewire::actingAs($staff)
            ->test(ListLoginHistories::class)
            ->assertForbidden();
    }

    public function test_admin_cannot_mount_visitor_trackings_livewire_component(): void
    {
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_admin_vt');

        Livewire::actingAs($admin)
            ->test(ListVisitorTrackings::class)
            ->assertForbidden();
    }

    public function test_staff_cannot_mount_visitor_trackings_livewire_component(): void
    {
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_staff_vt');

        Livewire::actingAs($staff)
            ->test(ListVisitorTrackings::class)
            ->assertForbidden();
    }

    public function test_unauthenticated_guest_cannot_mount_audit_livewire_components(): void
    {
        auth()->logout();

        Livewire::test(ListLoginHistories::class)
            ->assertForbidden();

        Livewire::test(ListVisitorTrackings::class)
            ->assertForbidden();
    }

    public function test_direct_livewire_update_post_to_login_histories_as_admin_yields_403(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_snap_super_lh');
        $snapshot = Livewire::actingAs($superAdmin)->test(ListLoginHistories::class)->snapshot;

        $this->flushSession();
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_post_admin_lh');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $response = $this->actingAs($admin)->postJson('/livewire/update', [
            'components' => [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_direct_livewire_update_post_to_login_histories_as_staff_yields_403(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_snap_super_lh2');
        $snapshot = Livewire::actingAs($superAdmin)->test(ListLoginHistories::class)->snapshot;

        $this->flushSession();
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_post_staff_lh');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $response = $this->actingAs($staff)->postJson('/livewire/update', [
            'components' => [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_direct_livewire_update_post_to_visitor_trackings_as_admin_yields_403(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_snap_super_vt');
        $snapshot = Livewire::actingAs($superAdmin)->test(ListVisitorTrackings::class)->snapshot;

        $this->flushSession();
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_post_admin_vt');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $response = $this->actingAs($admin)->postJson('/livewire/update', [
            'components' => [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_direct_livewire_update_post_to_visitor_trackings_as_staff_yields_403(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_snap_super_vt2');
        $snapshot = Livewire::actingAs($superAdmin)->test(ListVisitorTrackings::class)->snapshot;

        $this->flushSession();
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_post_staff_vt');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $response = $this->actingAs($staff)->postJson('/livewire/update', [
            'components' => [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    // 2. Invocation of Mutating Actions (Create, Edit, Delete, Bulk Delete)

    public function test_cannot_invoke_create_action_on_login_histories(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_create_lh');

        $component = Livewire::actingAs($superAdmin)
            ->test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertActionDoesNotExist('create');

        $this->expectException(ExpectationFailedException::class);
        $component->callAction('create');
    }

    public function test_cannot_invoke_row_level_edit_or_delete_on_login_histories(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_row_lh');
        $record = $this->createSampleLoginHistory();

        $component = Livewire::actingAs($superAdmin)
            ->test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');

        try {
            $component->callTableAction('delete', $record);
            $this->fail('Calling unmounted table action "delete" should have thrown an exception.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('delete', $e->getMessage());
        }

        try {
            $component->callTableAction('edit', $record);
            $this->fail('Calling unmounted table action "edit" should have thrown an exception.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('edit', $e->getMessage());
        }

        $this->assertNotNull($record->fresh(), 'Record must not be deleted from database.');
    }

    public function test_cannot_invoke_bulk_delete_action_on_login_histories(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_bulk_lh');
        $record = $this->createSampleLoginHistory();

        $component = Livewire::actingAs($superAdmin)
            ->test(ListLoginHistories::class)
            ->assertSuccessful()
            ->assertTableBulkActionDoesNotExist('delete');

        $this->expectException(ExpectationFailedException::class);
        $component->callTableBulkAction('delete', [$record->getKey()]);

        $this->assertNotNull($record->fresh(), 'Record must survive bulk delete attempt.');
    }

    public function test_cannot_invoke_create_action_on_visitor_trackings(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_create_vt');

        $component = Livewire::actingAs($superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertActionDoesNotExist('create');

        $this->expectException(ExpectationFailedException::class);
        $component->callAction('create');
    }

    public function test_cannot_invoke_row_level_edit_or_delete_on_visitor_trackings(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_row_vt');
        $record = $this->createSampleVisitorTracking();

        $component = Livewire::actingAs($superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');

        try {
            $component->callTableAction('delete', $record);
            $this->fail('Calling unmounted table action "delete" should have thrown an exception.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('delete', $e->getMessage());
        }

        try {
            $component->callTableAction('edit', $record);
            $this->fail('Calling unmounted table action "edit" should have thrown an exception.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('edit', $e->getMessage());
        }

        $this->assertNotNull($record->fresh(), 'Visitor record must not be deleted.');
    }

    public function test_cannot_invoke_bulk_delete_action_on_visitor_trackings(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_bulk_vt');
        $record = $this->createSampleVisitorTracking();

        $component = Livewire::actingAs($superAdmin)
            ->test(ListVisitorTrackings::class)
            ->assertSuccessful()
            ->assertTableBulkActionDoesNotExist('delete');

        $this->expectException(ExpectationFailedException::class);
        $component->callTableBulkAction('delete', [$record->getKey()]);

        $this->assertNotNull($record->fresh(), 'Visitor record must survive bulk delete attempt.');
    }

    public function test_unsupported_http_methods_return_method_not_allowed(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_super_verbs');

        $routes = [
            '/admin/login-histories',
            '/admin/visitor-trackings',
        ];

        foreach ($routes as $route) {
            $postResponse = $this->actingAs($superAdmin)->post($route);
            $this->assertEquals(405, $postResponse->getStatusCode(), "POST to {$route} must return 405.");

            $putResponse = $this->actingAs($superAdmin)->put($route);
            $this->assertEquals(405, $putResponse->getStatusCode(), "PUT to {$route} must return 405.");

            $deleteResponse = $this->actingAs($superAdmin)->delete($route);
            $this->assertEquals(405, $deleteResponse->getStatusCode(), "DELETE to {$route} must return 405.");
        }
    }

    // 3. Role Tampering Mid-Session (Immediate 403 on Privilege Downgrade)

    public function test_role_tampering_downgrade_to_staff_immediately_yields_403(): void
    {
        $user = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_tamper_staff');

        // Step 1: Initial Super Admin access succeeds
        $firstResponse = $this->actingAs($user)->get('/admin/login-histories');
        $firstResponse->assertStatus(200);

        $firstVisitorResponse = $this->get('/admin/visitor-trackings');
        $firstVisitorResponse->assertStatus(200);

        // Step 2: Role downgraded in database mid-session
        $user->update(['role' => User::ROLE_STAFF]);
        $user->refresh();

        // Step 3: Immediate subsequent requests must return 403 Forbidden
        $subsequentLhResponse = $this->get('/admin/login-histories');
        $subsequentLhResponse->assertStatus(403);

        $subsequentVtResponse = $this->get('/admin/visitor-trackings');
        $subsequentVtResponse->assertStatus(403);

        // Step 4: Direct Livewire mount must also immediately yield 403 Forbidden
        Livewire::actingAs($user)
            ->test(ListLoginHistories::class)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(ListVisitorTrackings::class)
            ->assertForbidden();
    }

    public function test_role_tampering_downgrade_to_admin_immediately_yields_403(): void
    {
        $user = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_tamper_admin');

        // Step 1: Initial Super Admin access succeeds
        $this->actingAs($user)->get('/admin/login-histories')->assertStatus(200);
        $this->get('/admin/visitor-trackings')->assertStatus(200);

        // Step 2: Role downgraded to admin mid-session
        $user->update(['role' => User::ROLE_ADMIN]);
        $user->refresh();

        // Step 3: Immediate subsequent requests must return 403 Forbidden
        $this->get('/admin/login-histories')->assertStatus(403);
        $this->get('/admin/visitor-trackings')->assertStatus(403);

        Livewire::actingAs($user)
            ->test(ListLoginHistories::class)
            ->assertForbidden();
    }

    public function test_role_tampering_immediately_hides_sidebar_navigation_links(): void
    {
        $user = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_tamper_nav');

        // Step 1: Super Admin sees sidebar navigation items
        $initialNav = $this->actingAs($user)->get('/admin');
        $initialNav->assertStatus(200);
        $initialNav->assertSee('/admin/login-histories');
        $initialNav->assertSee('/admin/visitor-trackings');

        // Step 2: Role downgraded to staff mid-session
        $user->update(['role' => User::ROLE_STAFF]);
        $user->refresh();

        // Step 3: Sidebar immediately omits the audit links
        $downgradedNav = $this->get('/admin');
        $downgradedNav->assertStatus(200);
        $downgradedNav->assertDontSee('/admin/login-histories');
        $downgradedNav->assertDontSee('/admin/visitor-trackings');
    }

    // 4. Zero Data Leakage to Non-Superadmin Users

    public function test_zero_data_leakage_of_sensitive_login_history_to_admin_and_staff(): void
    {
        $sensitiveRecord = $this->createSampleLoginHistory([
            'attempted_identifier' => 'classified_ceo_login_identifier',
            'ip_address' => '198.51.100.99',
            'user_agent' => 'SuperSecretInternalCorporateBrowser/99.0',
        ]);

        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_leak_admin_lh');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_leak_staff_lh');

        // Admin probe on audit route
        $adminLhResponse = $this->actingAs($admin)->get('/admin/login-histories');
        $adminLhResponse->assertStatus(403);
        $adminLhResponse->assertDontSee('classified_ceo_login_identifier');
        $adminLhResponse->assertDontSee('198.51.100.99');
        $adminLhResponse->assertDontSee('SuperSecretInternalCorporateBrowser');

        // Admin probe on dashboard
        $adminDashResponse = $this->actingAs($admin)->get('/admin');
        $adminDashResponse->assertStatus(200);
        $adminDashResponse->assertDontSee('classified_ceo_login_identifier');
        $adminDashResponse->assertDontSee('198.51.100.99');

        $this->flushSession();

        // Staff probe on audit route
        $staffLhResponse = $this->actingAs($staff)->get('/admin/login-histories');
        $staffLhResponse->assertStatus(403);
        $staffLhResponse->assertDontSee('classified_ceo_login_identifier');
        $staffLhResponse->assertDontSee('198.51.100.99');
        $staffLhResponse->assertDontSee('SuperSecretInternalCorporateBrowser');

        // Staff probe on dashboard
        $staffDashResponse = $this->actingAs($staff)->get('/admin');
        $staffDashResponse->assertStatus(200);
        $staffDashResponse->assertDontSee('classified_ceo_login_identifier');
        $staffDashResponse->assertDontSee('198.51.100.99');
    }

    public function test_zero_data_leakage_of_visitor_tracking_to_admin_and_staff(): void
    {
        $sensitiveHash = hash('sha256', 'classified_presidential_suite_visit');
        $sensitiveVisitor = $this->createSampleVisitorTracking([
            'visitor_hash' => $sensitiveHash,
            'ip_address' => '203.0.113.199',
            'user_agent' => 'ConfidentialDiplomaticBrowser/4.0',
        ]);

        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_leak_admin_vt');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_leak_staff_vt');

        // Admin probe on visitor route
        $adminVtResponse = $this->actingAs($admin)->get('/admin/visitor-trackings');
        $adminVtResponse->assertStatus(403);
        $adminVtResponse->assertDontSee($sensitiveHash);
        $adminVtResponse->assertDontSee('203.0.113.199');
        $adminVtResponse->assertDontSee('ConfidentialDiplomaticBrowser');

        // Admin probe on dashboard
        $adminDashResponse = $this->actingAs($admin)->get('/admin');
        $adminDashResponse->assertStatus(200);
        $adminDashResponse->assertDontSee($sensitiveHash);
        $adminDashResponse->assertDontSee('203.0.113.199');

        $this->flushSession();

        // Staff probe on visitor route
        $staffVtResponse = $this->actingAs($staff)->get('/admin/visitor-trackings');
        $staffVtResponse->assertStatus(403);
        $staffVtResponse->assertDontSee($sensitiveHash);
        $staffVtResponse->assertDontSee('203.0.113.199');
        $staffVtResponse->assertDontSee('ConfidentialDiplomaticBrowser');

        // Staff probe on dashboard
        $staffDashResponse = $this->actingAs($staff)->get('/admin');
        $staffDashResponse->assertStatus(200);
        $staffDashResponse->assertDontSee($sensitiveHash);
        $staffDashResponse->assertDontSee('203.0.113.199');
    }

    public function test_crafted_query_parameters_do_not_bypass_authorization_or_leak_records(): void
    {
        $this->createSampleLoginHistory(['attempted_identifier' => 'probe_search_target']);
        $this->createSampleVisitorTracking(['visitor_hash' => hash('sha256', 'probe_hash_target')]);

        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_forged_admin');
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_forged_staff');

        $forgedQueries = [
            '/admin/login-histories?tableSearch=probe_search_target',
            '/admin/login-histories?tableFilters[status][value]=success',
            '/admin/login-histories?tableSortColumn=login_at&tableSortDirection=desc',
            '/admin/visitor-trackings?tableSearch=probe_hash_target',
            '/admin/visitor-trackings?tableFilters[visitor_type][value]=returning',
        ];

        foreach ($forgedQueries as $query) {
            $this->flushSession();
            $adminRes = $this->actingAs($admin)->get($query);
            $this->assertEquals(403, $adminRes->getStatusCode(), "Admin must receive 403 on {$query}.");
            $adminRes->assertDontSee('probe_search_target');
            $adminRes->assertDontSee('probe_hash_target');

            $this->flushSession();
            $staffRes = $this->actingAs($staff)->get($query);
            $this->assertEquals(403, $staffRes->getStatusCode(), "Staff must receive 403 on {$query}.");
            $staffRes->assertDontSee('probe_search_target');
            $staffRes->assertDontSee('probe_hash_target');
        }
    }

    public function test_static_resource_contracts_strictly_deny_non_superadmin_under_all_contexts(): void
    {
        $dummyRecord = new class extends Model {};

        // 1. Guest Context
        auth()->logout();
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertFalse(VisitorTrackingResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::shouldRegisterNavigation());

        // 2. Admin Context
        $admin = $this->createUserWithRole(User::ROLE_ADMIN, 'sec_contract_admin');
        $this->actingAs($admin);
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertFalse(VisitorTrackingResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::shouldRegisterNavigation());

        // 3. Staff Context
        $staff = $this->createUserWithRole(User::ROLE_STAFF, 'sec_contract_staff');
        $this->actingAs($staff);
        $this->assertFalse(LoginHistoryResource::canAccess());
        $this->assertFalse(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertFalse(VisitorTrackingResource::canAccess());
        $this->assertFalse(VisitorTrackingResource::shouldRegisterNavigation());

        // 4. Super Admin Context - Read-only immutability contract
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'sec_contract_super');
        $this->actingAs($superAdmin);
        $this->assertTrue(LoginHistoryResource::canAccess());
        $this->assertTrue(LoginHistoryResource::shouldRegisterNavigation());
        $this->assertTrue(VisitorTrackingResource::canAccess());
        $this->assertTrue(VisitorTrackingResource::shouldRegisterNavigation());

        $this->assertFalse(LoginHistoryResource::canCreate());
        $this->assertFalse(LoginHistoryResource::canEdit($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDelete($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canDeleteAny());
        $this->assertFalse(LoginHistoryResource::canForceDelete($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canForceDeleteAny());
        $this->assertFalse(LoginHistoryResource::canReplicate($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canRestore($dummyRecord));
        $this->assertFalse(LoginHistoryResource::canRestoreAny());

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

    public function test_user_resource_bulk_delete_prevents_authenticated_superadmin_self_deletion(): void
    {
        $superAdmin = $this->createUserWithRole(User::ROLE_SUPERADMIN, 'super_bulk_guard');
        $otherUser = $this->createUserWithRole(User::ROLE_STAFF, 'staff_bulk_target');

        $this->actingAs($superAdmin);

        \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableBulkAction('delete', [$superAdmin, $otherUser]);

        // Superadmin must survive the bulk delete attempt
        $this->assertNotNull($superAdmin->fresh(), 'Authenticated superadmin must not be deleted by bulk action.');
        // Other selected user should be deleted
        $this->assertNull($otherUser->fresh(), 'Other selected non-self user should be deleted.');
    }

    public function test_image_optimizer_service_sanitizes_svg_content(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $svgPayload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(document.domain)</script><circle cx="50" cy="50" r="40"/><a href="javascript:alert(2)">Link</a></svg>';
        $tempPath = tempnam(sys_get_temp_dir(), 'svg_test');
        file_put_contents($tempPath, $svgPayload);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'malicious.svg',
            'image/svg+xml',
            null,
            true
        );

        $storedPath = \App\Services\ImageOptimizerService::optimizeAndStore($uploadedFile, 'site');

        $savedContent = \Illuminate\Support\Facades\Storage::disk('public')->get($storedPath);

        $this->assertStringNotContainsString('<script', $savedContent);
        $this->assertStringNotContainsString('alert(document.domain)', $savedContent);
        $this->assertStringNotContainsString('onload=', $savedContent);
        $this->assertStringNotContainsString('javascript:', $savedContent);
        $this->assertStringContainsString('<circle', $savedContent);

        @unlink($tempPath);
    }
}
