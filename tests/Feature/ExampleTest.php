<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_returns_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Ocean Lombok Tour');
    }

    public function test_indonesian_landing_page_returns_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/id');
        $response->assertStatus(200);
        $response->assertSee('Layanan Tour & Transportasi Kuta Mandalika');
    }

    public function test_admin_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_switch_locale_redirects_correctly(): void
    {
        $response = $this->get('/lang/id');
        $response->assertRedirect('/id');
        $response->assertCookie('olt_locale', 'id');

        $responseEn = $this->get('/lang/en');
        $responseEn->assertRedirect('/');
        $responseEn->assertCookie('olt_locale', 'en');
    }

    public function test_authenticated_user_with_must_change_password_is_redirected_to_profile(): void
    {
        $this->seed();
        $user = \App\Models\User::first();
        $user->must_change_password = true;
        $user->save();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect(route('filament.admin.auth.profile'));

        $user->must_change_password = false;
        $user->save();

        $responseAllowed = $this->actingAs($user)->get('/admin');
        $responseAllowed->assertStatus(200);
    }

    public function test_role_based_access_controls(): void
    {
        $this->seed();

        $superAdmin = \App\Models\User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin_test',
            'password' => 'Password123!',
            'role' => \App\Models\User::ROLE_SUPERADMIN,
            'must_change_password' => false,
        ]);

        $admin = \App\Models\User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'password' => 'Password123!',
            'role' => \App\Models\User::ROLE_ADMIN,
            'must_change_password' => false,
        ]);

        $staff = \App\Models\User::create([
            'name' => 'Staff Test',
            'username' => 'staff_test',
            'password' => 'Password123!',
            'role' => \App\Models\User::ROLE_STAFF,
            'must_change_password' => false,
        ]);

        // Super Admin can access Users and Settings
        $this->actingAs($superAdmin)->get('/admin/users')->assertSuccessful();
        $this->actingAs($superAdmin)->get('/admin/manage-site-settings')->assertSuccessful();

        // Flush session between role switches so auth state is fresh
        $this->flushSession();

        // Admin cannot access Users, but can access Settings
        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/manage-site-settings')->assertSuccessful();

        $this->flushSession();

        // Staff cannot access Users and cannot access Site Settings
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->get('/admin/manage-site-settings')->assertForbidden();

        // Staff CAN access Destinations and Hero Slides
        $this->actingAs($staff)->get('/admin/destinations')->assertSuccessful();
        $this->actingAs($staff)->get('/admin/hero-slides')->assertSuccessful();
    }
}
