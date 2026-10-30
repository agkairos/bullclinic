<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NovoClienteRegistrado extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $clinicaNome,
        public string $planoNome
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Novo Cliente Registrado - BullClinic')
            ->greeting('Novo Cadastro!')
            ->line('Um novo cliente se registrou no BullClinic:')
            ->line('**Nome:** ' . $this->user->name)
            ->line('**Email:** ' . $this->user->email)
            ->line('**Clínica:** ' . $this->clinicaNome)
            ->line('**Plano:** ' . $this->planoNome)
            ->line('**Data:** ' . now()->format('d/m/Y H:i'))
            ->salutation('Sistema BullClinic');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

