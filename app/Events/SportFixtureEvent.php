<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SportFixtureEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    protected $fixture;

    protected $data;

    protected $action;

    /*** Create a new event instance.
     */
    public function __construct($fixture, $data, $action)
    {
        $this->fixture = $fixture;
        $this->data = $data;
        $this->action = $action;
    }

    /*** Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sport-fixture'),
        ];
    }
}
