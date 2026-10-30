<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme seu email - BullClinic')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Bem-vindo ao BullClinic! Para ativar sua conta, por favor confirme seu endereço de email.')
            ->action('Confirmar Email', $verificationUrl)
            ->line('Este link expira em 60 minutos.')
            ->line('Se você não criou esta conta, ignore este email.')
            ->salutation('Atenciosamente, Equipe BullClinic');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

