<?php

namespace App\Listeners;

use App\Events\sendMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mandrill;
use Mandrill_Error;
use Log;

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
        try {
                $key = config("mandrill.api_key");
                $config = config("sendMail.".$event->getConfig());

                if ($config === null) {
                    $config = config("sendMail.default");
                } 

                $from = $event->getFrom();

                $message = array(
                    'html'       => $event->getBody(),
                    'subject'    => @$from["subject"] ? $from["subject"] : $config['subject'],
                    'from_email' =>  @$from["from_email"] ? $from["from_email"] : $config['from_email'],
                    'from_name'  =>  @$from["from_name"] ? $from["from_name"] : $config['from_name'],
                    'to'         => array(
                        $event->getTo()
                    ),
                );

                $mandrill = new Mandrill($key);
                $mandrill->messages->send($message);
        } catch(Mandrill_Error $e) {
            Log::info('A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage());
            Log::info('Datos de destinatario:: ' . json_encode($event->getTo()));
        }
    }
}
