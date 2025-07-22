<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration
     */
    public function test_user_can_register()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'Test Device',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => ['id', 'name', 'email'],
                        'token',
                        'expires_at'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }

    /**
     * Test user login
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => ['id', 'name', 'email'],
                        'token',
                        'expires_at'
                    ]
                ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ]);
    }

    /**
     * Test getting authenticated user info
     */
    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => ['id', 'name', 'email']
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email
                        ]
                    ]
                ]);
    }

    /**
     * Test logout
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout realizado com sucesso'
                ]);
    }

    /**
     * Test accessing protected route without token
     */
    public function test_protected_route_requires_authentication()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test token refresh
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $refreshData = [
            'device_name' => 'Test Device Refreshed',
        ];

        $response = $this->postJson('/api/auth/refresh', $refreshData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'token',
                        'expires_at'
                    ]
                ]);
    }

    /**
     * Test registration validation
     */
    public function test_registration_validation()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password', 'device_name']);
    }

    /**
     * Test login validation
     */
    public function test_login_validation()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    /**
     * Test inactive user cannot login
     */
    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => false
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Conta desativada. Entre em contato com o administrador.'
                ]);
    }
}
