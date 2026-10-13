<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as FrameworkVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends FrameworkVerifyEmail implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)->subject('Verificá tu email en EstuFlow')
            ->greeting('Hola,')
            ->line('Confirmá tu dirección para comenzar a usar tu espacio de EstuFlow.')
            ->action('Verificar mi email', $url)
            ->line('El enlace vence en 60 minutos. Abrilo con tu cuenta de EstuFlow iniciada.')
            ->line('Si no creaste esta cuenta, podés ignorar este correo.')
            ->salutation('El equipo de EstuFlow');
    }
}
