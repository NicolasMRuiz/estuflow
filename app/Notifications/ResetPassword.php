<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as FrameworkResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends FrameworkResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)->subject('Recuperá tu acceso a EstuFlow')
            ->greeting('Hola,')
            ->line('Recibimos una solicitud para restablecer tu contraseña de EstuFlow.')
            ->action('Elegir una nueva contraseña', $url)
            ->line('Este enlace vence en 60 minutos y solo puede usarse una vez.')
            ->line('Si no lo solicitaste, podés ignorar este correo.')
            ->salutation('El equipo de EstuFlow');
    }
}
