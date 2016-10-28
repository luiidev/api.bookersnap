<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

class EmitNotification extends Event
{
    use SerializesModels;

    private $data;
    private $event;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($event, $data)
    {
        $this->data  = $data;
        $this->event = $event;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    public function getData()
    {
        return $this->data;
    }

    public function getEvent()
    {
        return $this->event;
    }

}
