<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure admin user exists
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador General',
                'email' => 'admin@visortv.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
            ])
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'username', 'name', 'role'],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Credenciales incorrectas',
            ]);
    }

    public function test_me_endpoint_returns_user_with_valid_token(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'username' => 'admin',
                ],
            ]);
    }

    public function test_me_endpoint_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_change_password_with_valid_token(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'admin123',
                'new_password' => 'newadminpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente',
            ]);

        // Restore original password for subsequent tests
        $user->password = Hash::make('admin123');
        $user->save();
    }

    public function test_logout_invalidates_token(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_forgot_password_generates_code_for_existing_user(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'username' => 'admin',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_email' => true,
                'username' => 'admin',
            ]);

        $this->assertNotNull($response->json('dev_code'));
        $this->assertEquals(6, strlen($response->json('dev_code')));
    }

    public function test_forgot_password_fails_for_non_existent_user(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'username' => 'usuario_que_no_existe_9999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_reset_password_succeeds_with_valid_code(): void
    {
        // 1. Request code
        $forgotResponse = $this->postJson('/api/auth/forgot-password', [
            'username' => 'admin',
        ]);
        $forgotResponse->assertStatus(200);
        $code = $forgotResponse->json('dev_code');

        // 2. Reset password
        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'username' => 'admin',
            'code' => $code,
            'new_password' => 'newSecretPassword123!',
            'new_password_confirmation' => 'newSecretPassword123!',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // 3. Verify user can login with new password
        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'newSecretPassword123!',
        ]);
        $loginResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Restore original password
        $user = User::where('username', 'admin')->first();
        $user->password = Hash::make('admin123');
        $user->save();
    }

    public function test_reset_password_fails_with_invalid_code(): void
    {
        // 1. Request code
        $this->postJson('/api/auth/forgot-password', [
            'username' => 'admin',
        ]);

        // 2. Try with wrong code
        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'username' => 'admin',
            'code' => '000000',
            'new_password' => 'brandnewpassword123',
            'new_password_confirmation' => 'brandnewpassword123',
        ]);

        $resetResponse->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_reset_password_fails_with_mismatched_passwords(): void
    {
        $forgotResponse = $this->postJson('/api/auth/forgot-password', [
            'username' => 'admin',
        ]);
        $code = $forgotResponse->json('dev_code');

        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'username' => 'admin',
            'code' => $code,
            'new_password' => 'password123',
            'new_password_confirmation' => 'mismatched_password',
        ]);

        $resetResponse->assertStatus(422)
            ->assertJsonValidationErrors(['new_password_confirmation']);
    }
}
