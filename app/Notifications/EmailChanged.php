<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChanged extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Cambiaste tu email de EstuFlow')->greeting('Hola,')->line('Tu cuenta cambió de email después de confirmar ambas direcciones. Cerramos las otras sesiones.')->line('Si no hiciste este cambio, revisá la seguridad de tu cuenta.')->salutation('El equipo de EstuFlow');
    }
}
