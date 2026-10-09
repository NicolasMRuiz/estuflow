<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Cambiaste tu contraseña de EstuFlow')
            ->greeting('Hola,')
            ->line('Tu contraseña cambió y cerramos las sesiones anteriores de tu cuenta.')
            ->line('Si no hiciste este cambio, recuperá el acceso cuanto antes.')
            ->action('Recuperar mi acceso', route('password.request'))
            ->salutation('El equipo de EstuFlow');
    }
}
