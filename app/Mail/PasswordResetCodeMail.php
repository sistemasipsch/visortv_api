<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $code;

    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Recuperación de Contraseña — Visor TV Sistemas',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    protected function buildHtml(): string
    {
        $appName = config('app.name', 'Visor TV Sistemas');
        $code = htmlspecialchars($this->code, ENT_QUOTES, 'UTF-8');
        $userName = htmlspecialchars($this->user->name, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #0049ea 0%, #0284c7 100%); text-align: center;">
                <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">📺 {$appName}</h1>
                <p style="color: #e0f2fe; font-size: 13px; margin: 6px 0 0 0;">Plataforma de Señalización Digital & Carteleras TV</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 32px;">
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 12px 0;">Recuperación de Contraseña</h2>
                <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 20px 0;">
                    Hola <strong>{$userName}</strong>, hemos recibido una solicitud para restablecer la contraseña de acceso a tu cuenta.
                </p>
                <div style="background-color: #f1f5f9; border: 1px dashed #0049ea; border-radius: 12px; padding: 18px; text-align: center; margin: 0 0 24px 0;">
                    <p style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 1px; margin: 0 0 8px 0;">Tu Código de Verificación</p>
                    <span style="font-family: monospace; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #0049ea; display: inline-block;">{$code}</span>
                    <p style="font-size: 12px; color: #64748b; margin: 8px 0 0 0;">Válido durante los próximos <strong>15 minutos</strong></p>
                </div>
                <p style="font-size: 13px; line-height: 1.5; color: #64748b; margin: 0 0 16px 0;">
                    Ingresa este código en la pantalla de inicio de sesión de Visor TV para asignar tu nueva contraseña. Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.
                </p>
                <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 24px 0 16px 0;">
                <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 0;">
                    Mensaje generado automáticamente por el sistema de seguridad de {$appName}.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
