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
    private $from;

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($to, $body, $config, $from = null)
    {
        $this->to = $this->setTo($to);
        $this->body = $this->setBody($body);
        $this->config = $config;
        $this->from = $this->setFrom($from);
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

    private function setBody($body)
    {
        if ( is_string($body) ) {
            return $body;
        } elseif ( is_array($body) ) {
            return  view($body["template"], $body["data"])->render();
        } else {
            return "";
        }
    }

    private function setFrom($from)
    {
        if ( is_array($from)  ) {
            return $from;
        } else {
            return [];
        }
    }

    private function setTo($to)
    {
        if ( is_array($to)  ) {
            return $to;
        } else if ( is_string($to) ){
            return array(
                "email" => $to,
                "type" => "to"
            );
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

    public function getFrom()
    {
        return $this->from;
    }
}
