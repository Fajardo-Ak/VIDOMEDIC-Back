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
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'titulo' => '⏰ ¡Hora de tu medicamento!',
            'mensaje' => "Es hora de tomar: {$this->nombreMedicamento}",
            'hora_dosis' => $this->hora,
            'accion' => '/inicio' // A donde redirigir si le dan clic
        ];
    }
}