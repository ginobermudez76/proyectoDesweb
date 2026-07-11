<?php

namespace App\Modules\Incidencias\Events;

use App\Modules\Incidencias\Entities\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoMensaje implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;


    public function __construct(Mensaje $mensaje)
    {
        $this->mensaje = $mensaje;
    }


    public function broadcastOn(): array
    {
        return [
            new Channel('incidencia.' . $this->mensaje->incidencia_id)
        ];
    }

 
    public function broadcastAs(): string
    {
        return 'NotificacionMensaje';
    }
}