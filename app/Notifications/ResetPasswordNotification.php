<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expiresIn = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $data = [
            'name' => $notifiable->name,
            'resetUrl' => $url,
            'expiresIn' => $expiresIn,
        ];

        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi - KPI Kepegawaian')
            ->view('mail.auth.reset-password', $data)
            ->text('mail.auth.reset-password-text', $data);
    }
}
