<?php

namespace App\Notifications;

use App\Models\Paquete;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevoPaqueteNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Paquete $paquete) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $apartamento = $this->paquete->apartamento;
        $nombreResidente = $this->paquete->residente?->nombre ?? $notifiable->name;

        return (new MailMessage)
            ->subject('Nuevo paquete recibido - Valle de San Remo')
            ->greeting("Hola {$nombreResidente}.")
            ->line('Has recibido un nuevo paquete en la portería del Conjunto Valle de San Remo.')
            ->line("Guía: {$this->paquete->guia}")
            ->line("Empresa: {$this->paquete->empresa}")
            ->line('Apartamento: '.($apartamento ? "{$apartamento->torre} - Apto. {$apartamento->numero}" : 'Sin apartamento asignado'))
            ->line("Estado: {$this->paquete->estado}")
            ->line('Fecha de recepción: '.$this->paquete->created_at->format('d/m/Y H:i'))
            ->when(
                filled($this->paquete->detalles),
                fn (MailMessage $mail) => $mail->line("Observación: {$this->paquete->detalles}"),
            )
            ->action('Consultar mis paquetes', route('mis-paquetes'))
            ->line('Puedes ingresar al sistema para consultar tus paquetes.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
