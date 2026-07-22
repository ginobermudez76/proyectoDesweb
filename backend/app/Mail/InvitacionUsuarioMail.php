<?php

namespace App\Mail;

use App\Modules\Auth\Entities\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class InvitacionUsuarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public Usuario $usuario;
    public string $nombreRol;
    public string $urlActivacion;
    public string $fechaExpiracion;
    public string $tiempoRestante;
    public bool $esRecordatorio;

    public function __construct(Usuario $usuario, string $nombreRol, bool $esRecordatorio = false)
    {
        $this->usuario = $usuario;
        $this->nombreRol = $nombreRol;
        $this->esRecordatorio = $esRecordatorio;

        $baseUrl = config('app.url', 'https://eldomoniodedesarrollo.dev');
        if (empty($baseUrl) || str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
            $baseUrl = 'https://eldomoniodedesarrollo.dev';
        }
        $this->urlActivacion = rtrim($baseUrl, '/') . '/activar-invitacion.html?token=' . $usuario->token_invitacion;

        /** @var Carbon $expiracion */
        $expiracion = $usuario->fecha_expiracion_invitacion ?? now()->addDays(7);
        $this->fechaExpiracion = $expiracion->format('d/m/Y H:i');
        $this->tiempoRestante = $expiracion->diffForHumans(now(), ['parts' => 2, 'syntax' => Carbon::DIFF_RELATIVE_TO_NOW]);
    }

    public function envelope(): Envelope
    {
        $subject = $this->esRecordatorio
            ? 'Recordatorio: Invitación al Sistema de Incidencias'
            : 'Invitación de acceso al Sistema de Incidencias';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion',
        );
    }
}
