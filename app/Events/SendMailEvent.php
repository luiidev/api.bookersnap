<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendMailEvent extends Event
{
    private $to;
    private $body;
    private $config;

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($to, $body, $config)
    {
        $this->to = $to;
        $this->body = $this->render($body);
        $this->config = $config;
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

    public function render($body)
    {
        if ( is_string($body) ) {
            return $body;
        } elseif ( is_array($body) ) {
            $html = view($body["template"], $body["data"])->render();
        } else {
            return "";
        }
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
