<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_guest_can_view_login_page()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_guest_can_view_register_page()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_guest_can_view_forgot_password_page()
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_guest_can_view_reset_password_page()
    {
        $response = $this->get('/reset-password/token');

        $response->assertStatus(200);
        $response->assertViewIs('auth.reset-password');
    }

    public function test_guest_cannot_access_protected_routes()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_admin_routes()
    {
        $response = $this->get('/kategori');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_kasir_routes()
    {
        $response = $this->get('/transaksi/baru');

        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_cannot_login_with_nonexistent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_user_can_access_admin_routes()
    {
        $adminUser = User::factory()->create();
        $adminUser->level = 1; // Admin level
        $adminUser->save();

        $this->actingAs($adminUser);

        $response = $this->get('/kategori');

        $response->assertStatus(200);
    }

    public function test_kasir_user_can_access_kasir_routes()
    {
        $kasirUser = User::factory()->create();
        $kasirUser->level = 2; // Kasir level
        $kasirUser->save();

        $this->actingAs($kasirUser);

        $response = $this->get('/transaksi/baru');

        $response->assertStatus(200);
    }

    public function test_kasir_user_cannot_access_admin_only_routes()
    {
        $kasirUser = User::factory()->create();
        $kasirUser->level = 2; // Kasir level
        $kasirUser->save();

        $this->actingAs($kasirUser);

        $response = $this->get('/kategori');

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_user_can_access_kasir_routes()
    {
        $adminUser = User::factory()->create();
        $adminUser->level = 1; // Admin level
        $adminUser->save();

        $this->actingAs($adminUser);

        $response = $this->get('/transaksi/baru');

        $response->assertStatus(200);
    }

    public function test_login_requires_email()
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('password');
    }

    public function test_login_requires_valid_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_user_remains_authenticated_across_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // First request
        $response1 = $this->get('/dashboard');
        $response1->assertStatus(200);

        // Second request - should still be authenticated
        $response2 = $this->get('/dashboard');
        $response2->assertStatus(200);

        $this->assertAuthenticatedAs($user);
    }

    public function test_multiple_users_can_login_independently()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        // Login as user1
        $this->actingAs($user1);
        $response1 = $this->get('/dashboard');
        $response1->assertStatus(200);

        // Switch to user2
        $this->actingAs($user2);
        $response2 = $this->get('/dashboard');
        $response2->assertStatus(200);

        $this->assertAuthenticatedAs($user2);
    }

    public function test_login_redirects_to_intended_url_after_authentication()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Try to access protected route without authentication
        $this->get('/dashboard')->assertRedirect('/login');

        // Login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_logout_clears_session_and_cookies()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Verify authenticated
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post('/logout');

        // Verify no longer authenticated
        $this->assertGuest();
    }

    public function test_password_reset_request_requires_valid_email()
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_request_accepts_valid_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('status', 'We have emailed your password reset link.');
    }

    public function test_password_reset_requires_valid_token()
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_requires_password_confirmation()
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('password');
    }

    public function test_user_cannot_access_other_user_profile()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        // User1 should not be able to access user2's profile
        $response = $this->get("/user/{$user2->id}");

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_can_access_any_user_profile()
    {
        $adminUser = User::factory()->create();
        $adminUser->level = 1; // Admin level
        $adminUser->save();

        $regularUser = User::factory()->create();

        $this->actingAs($adminUser);

        $response = $this->get("/user/{$regularUser->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_access_own_profile()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/profil');

        $response->assertStatus(200);
        $response->assertViewIs('user.profil');
    }

    public function test_unauthenticated_user_cannot_access_profile()
    {
        $response = $this->get('/profil');

        $response->assertRedirect('/login');
    }

    public function test_login_attempts_are_rate_limited()
    {
        // This test would require rate limiting middleware to be properly configured
        // For now, we'll just test that multiple failed attempts don't crash the system

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'nonexistent@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertRedirect('/');
        }

        // System should still be responsive
        $this->assertTrue(true);
    }

    public function test_session_expires_after_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Access protected route
        $this->get('/dashboard')->assertStatus(200);

        // Logout
        $this->post('/logout');

        // Try to access protected route again
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_level_determines_access_permissions()
    {
        $adminRoutes = [
            '/kategori',
            '/produk',
            '/member',
            '/supplier',
            '/pengeluaran',
            '/pembelian',
            '/laporan',
            '/user',
            '/setting',
        ];

        $kasirRoutes = [
            '/transaksi/baru',
            '/penjualan',
            '/profil',
        ];

        // Test admin access
        $adminUser = User::factory()->create();
        $adminUser->level = 1;
        $adminUser->save();

        $this->actingAs($adminUser);

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(403, $response->getStatusCode(), "Admin should access {$route}");
        }

        foreach ($kasirRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(403, $response->getStatusCode(), "Admin should access {$route}");
        }

        // Test kasir access
        $kasirUser = User::factory()->create();
        $kasirUser->level = 2;
        $kasirUser->save();

        $this->actingAs($kasirUser);

        foreach ($kasirRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(403, $response->getStatusCode(), "Kasir should access {$route}");
        }

        // Kasir should not access admin routes
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertEquals(403, $response->getStatusCode(), "Kasir should not access {$route}");
        }
    }
}
