<?php

namespace App\Helpers;

class MailMandrillHelper
{
    private $_mandrill;

    public function __construct(string $private_key)
    {
        $this->_mandrill = new \Mandrill($private_key);
    }

    public function sendEmail(array $params, string $template)
    {
        try {
            $messageData = $this->prepareDataSend($params, $template);
            $result      = $this->_mandrill->messages->send($messageData['message'], $messageData['async'], $messageData['ip_pool'], $messageData['send_at']);
        } catch (Mandrill_Error $e) {
            echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            throw $e;
        }
    }

    public function prepareDataSend(array $messageData, string $template)
    {
        $message = array(
            'html'       => view($template, $messageData)->render(),
            'subject'    => $messageData['subject'],
            'from_email' => $messageData['from_email'],
            'from_name'  => $messageData['from_name'],
            'to'         => array(
                array(
                    'email' => $messageData['to_email'],
                    'name'  => $messageData['to_name'],
                    'type'  => 'to',
                ),
            ),
        );

        $messageData['message'] = $message;
        $messageData['async']   = false;
        $messageData['ip_pool'] = null;
        $messageData['send_at'] = null;

        return $messageData;
    }

}
