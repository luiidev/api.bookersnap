<?php

namespace App\Helpers;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class NotificationServerHelper
{
    private $_client;

    public function __construct()
    {
        $url_socket    = config('settings.SOCKET_NOTIFICATION_URL');
        $this->_client = new Client(new Version1X($url_socket));
    }
    public function emit(string $emit, array $data)
    {
        $this->_client->initialize();
        $this->_client->emit($emit, $data);
        $this->_client->close();
    }
}
