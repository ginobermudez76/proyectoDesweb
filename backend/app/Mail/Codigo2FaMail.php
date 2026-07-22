<?php

namespace App\Mail;

use App\Modules\Auth\Entities\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Codigo2FaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Usuario $usuario;
    public string $codigo;

    public function __construct(Usuario $usuario, string $codigo)
    {
        $this->usuario = $usuario;
        $this->codigo = $codigo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación de inicio de sesión (2FA)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo_2fa',
        );
    }
}
