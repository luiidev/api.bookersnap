<?php

namespace App\Helpers;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class NotificationServerHelper
{
    private $_client;

    public function __construct()
    {
        $this->_client = new Client(new Version1X('http://127.0.0.1:1337'));
    }
    public function emit(string $emit, array $data)
    {
        $this->_client->initialize();
        $this->_client->emit($emit, $data);
        $this->_client->close();
    }
}
