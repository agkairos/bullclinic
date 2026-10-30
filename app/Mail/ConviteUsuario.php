<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConviteUsuario extends Mailable
{
    use Queueable, SerializesModels;

    public string $linkConvite;
    public string $nomeClinica;

    public function __construct(public User $usuario, string $nomeClinica)
    {
        $this->linkConvite = url('/convite/' . $usuario->invite_token);
        $this->nomeClinica = $nomeClinica;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Você foi convidado para o {$this->nomeClinica}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.convite-usuario',
        );
    }
}
