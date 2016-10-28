<?php

namespace App\Listeners;

use App\Events\EmitNotification;
use App\Helpers\NotificationServerHelper;

class EmitNotificationListener
{

    private $_NotificationServeHelper;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(NotificationServerHelper $NotificationServerHelper)
    {
        $this->_NotificationServeHelper = $NotificationServerHelper;
    }

    /**
     * Handle the event.
     *
     * @param  EmitNotification  $event
     * @return void
     */
    public function handle(EmitNotification $event)
    {
        $data  = $this->prepareData($event->getData());
        $event = $event->getEvent();

        $this->_NotificationServeHelper->emit($event, $data);
    }

    private function prepareData($data)
    {
        $data['room'] = 'microsites' . $data['microsite_id'];
        return $data;
    }
}
