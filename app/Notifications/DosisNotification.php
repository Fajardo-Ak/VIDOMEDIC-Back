<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class DosisNotification extends Notification
{
    use Queueable;

    public $nombreMedicamento;
    public $hora;

    public function __construct($nombreMedicamento, $hora)
    {
        $this->nombreMedicamento = $nombreMedicamento;
        $this->hora = $hora;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('⏰ ¡Hora de tu medicamento!')
            ->icon('/logoazul.png') // Asegúrate que este archivo exista en public/ del FRONTEND
            ->body("Es hora de tomar: {$this->nombreMedicamento} ({$this->hora})")
            ->action('Ver Agenda', 'ver_agenda')
            ->data(['url' => '/inicio']); // Al hacer clic, te lleva a inicio
    }
}