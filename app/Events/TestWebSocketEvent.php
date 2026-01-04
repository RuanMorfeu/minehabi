<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestWebSocketEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $data;

    /*** Create a new event instance.
     */
    public function __construct()
    {
        $this->data = 'Hello World';
    }

    /*** Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('testing'),
        ];
    }

    /***
     * Definir um nome para o evento
     * @return string
     */
    public function broadcastAs()
    {
        return 'MyWebSocket';
    }
}
