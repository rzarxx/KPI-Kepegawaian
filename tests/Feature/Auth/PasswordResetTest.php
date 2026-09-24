<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_request_does_not_reveal_unknown_or_inactive_accounts(): void
    {
        Notification::fake();
        $inactive = User::factory()->create(['is_active' => false]);

        $unknown = $this->post('/forgot-password', ['email' => 'tidak.ada@example.test']);
        $inactiveResponse = $this->post('/forgot-password', ['email' => $inactive->email]);

        $unknown->assertSessionHas('status', 'Jika email cocok dengan akun aktif, tautan atur ulang kata sandi telah dikirim.');
        $inactiveResponse->assertSessionHas('status', 'Jika email cocok dengan akun aktif, tautan atur ulang kata sandi telah dikirim.');
        Notification::assertNothingSent();
    }

    public function test_reset_email_uses_indonesian_branded_content(): void
    {
        $user = User::factory()->make(['name' => 'Ayu Pengguna', 'email' => 'ayu@example.test']);
        $notification = new ResetPasswordNotification('token-contoh');
        $message = $notification->toMail($user);
        $html = view($message->view['html'], $message->viewData)->render();
        $text = view($message->view['text'], $message->viewData)->render();

        $this->assertSame('Atur Ulang Kata Sandi - KPI Kepegawaian', $message->subject);
        $this->assertStringContainsString('KPI KEPEGAWAIAN', $html);
        $this->assertStringContainsString('Buat Kata Sandi Baru', $html);
        $this->assertStringContainsString('Ayu Pengguna', $html);
        $this->assertStringContainsString('Jika Anda tidak meminta perubahan kata sandi', $text);
    }

    public function test_inactive_account_cannot_use_a_previously_issued_reset_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $user->update(['is_active' => false]);

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'PasswordBaru123!',
                'password_confirmation' => 'PasswordBaru123!',
            ])->assertSessionHasErrors([
                'email' => 'Tautan atur ulang kata sandi tidak valid atau akun sudah tidak aktif.',
            ]);

            return true;
        });
    }
}
