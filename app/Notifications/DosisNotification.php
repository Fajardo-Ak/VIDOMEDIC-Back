<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
//use NotificationChannels\WebPush\WebPushMessage;
//use NotificationChannels\WebPush\WebPushChannel;

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

    public function toArray($notifiable)
    {
        return (new WebPushMessage)
            ->title('⏰ ¡Hora de tu medicamento!')
            // Asegurate que el logo exista en tu frontend o usa uno público
            ->icon('/logoazul.png') 
            ->body("Es hora de tomar: {$this->nombreMedicamento} ({$this->hora})")
            ->action('Ver Agenda', 'ver_agenda')
            ->data(['url' => '/inicio']);
    }
}