<?php

namespace Tests\Feature;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarAndThemeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_user_avatar_string(): void
    {
        $user = User::factory()->create([
            'username' => 'admin_test1',
            'role' => 'admin',
            'avatar' => null,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/users/{$user->id}", [
                'avatar' => 'avatar-dev',
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertEquals('avatar-dev', $user->avatar);
    }

    public function test_can_upload_avatar_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'username' => 'admin_test2',
            'role' => 'admin',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post("/api/users/{$user->id}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('/api/media/stream/avatar_', $user->avatar);
    }

    public function test_can_assign_theme_to_sede(): void
    {
        $user = User::factory()->create([
            'username' => 'admin_test3',
            'role' => 'admin',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $sede = Sede::create([
            'name' => 'Sede Cyber',
            'slug' => 'sede-cyber',
            'theme' => null,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/sedes/{$sede->id}", [
                'theme' => 'cyberdeck',
            ]);

        $response->assertStatus(200);
        $sede->refresh();
        $this->assertEquals('cyberdeck', $sede->theme);

        // Check playlist includes theme
        $playlistRes = $this->getJson("/api/playlist?slug={$sede->slug}");
        $playlistRes->assertStatus(200);
        $this->assertEquals('cyberdeck', $playlistRes->json('sede.theme'));
    }

    public function test_user_can_persist_theme_in_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'ashly_theme_test',
            'role' => 'admin',
            'theme' => 'dark-blue',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        // 1. Update theme via updateProfile
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/update-profile', [
                'theme' => 'ny-graffiti',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('ny-graffiti', $response->json('user.theme'));

        $user->refresh();
        $this->assertEquals('ny-graffiti', $user->theme);

        // 2. Fetch /api/auth/me to verify persistence on any device
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200);
        $this->assertEquals('ny-graffiti', $meResponse->json('user.theme'));
    }
}
