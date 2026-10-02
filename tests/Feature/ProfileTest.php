<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Profil berhasil diperbarui.')
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'auditable_id' => $user->id,
            'action' => 'profile.updated',
        ]);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_delete_route_is_not_available(): void
    {
        // Self-service account deletion is disabled.
        // User deactivation is handled exclusively by admins via UserManagementController.
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertStatus(405); // Method Not Allowed — route does not exist

        // User must still exist in the database
        $this->assertNotNull($user->fresh());
    }

    public function test_flash_message_is_shared_with_inertia_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['success' => 'Perubahan berhasil disimpan.'])
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Perubahan berhasil disimpan.')
                ->where('flash.error', null));
    }
}
