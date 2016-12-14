<?php

namespace App\Listeners;

use App\Events\sendMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mandrill;

class SendMailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  sendMailEvent  $event
     * @return void
     */
    public function handle(sendMailEvent $event)
    {
        $key = config("mandrill.api_key");
        $config = config("sendMail.".$event->getConfig());

        $message = array(
            'html'       => $event->getBody(),
            'subject'    => $config['subject'],
            'from_email' => $config['from_email'],
            'from_name'  => $config['from_name'],
            'to'         => array(
                $event->getTo()
            ),
        );

        $mandrill = new Mandrill($key);
        $mandrill->messages->send($message);
    }
}
