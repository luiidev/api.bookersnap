<?php

namespace App\Services\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Exception;

class HttpRequestHelper
{
    private $method;
    private $url;
    private $header;
    private $data;

    private $isResponseOk = false;
    private $error;
    private $errorResponse;
    private $statusCode;

    private $response;

    function __construct (string $method = null, string $url = null, $data = null, array $headers = null)
    {
        $this->setDefaultHeaders();
        $this->setMethod($method);
        $this->setUrl($url);
        $this->setHeader($headers);
        $this->setData($data);
    }

    public static function make(string $method = null, string $url = null, $data = null, array $headers = null)
    {
        return new static($method, $url, $data, $headers);
    }

    public function send()
    {
        try {
            $client = new Client();

            $response = $client->request(
                $this->method, $this->url, [
                    'headers' => $this->header,
                    'body' => $this->data
                ]
            );

            $this->isResponseOk =$response->getReasonPhrase();
            $this->statusCode = $response->getStatusCode();
            $this->response =$response->getBody();
        } catch (RequestException $e) {
            $this->isResponseOk = false;
            if ($e->hasResponse()) {
                $this->statusCode = $e->getResponse()->getStatusCode();
                $this->errorResponse = $e->getResponse()->getBody();
            }
            $this->error = strtok($e->getMessage(), "\n");
        } catch (Exception $e) {
            $this->isResponseOk = false;
            $this->error = $e->getMessage();
        }

        return $this;
    }

    public function setMethod(string $method = null)
    {
        $this->method = $method;
        return $this;
    }

    public function setUrl(string $url = null)
    {
        $this->url = filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
        return $this;
    }

    public function setHeader(array $headers = null)
    {
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $this->header[$key] = $value;
            }
        }
        return $this;
    }

    private function setDefaultHeaders()
    {
        $this->header = ['content-type' => 'application/json'];

        $request = request();

        $this->header['user-agent'] = $request->server('HTTP_USER_AGENT');

        $api_token = $request->session()->get('api-token');

        if (!is_null($api_token)) {
            $this->header['Authorization'] = 'Bearer ' . $api_token;
        }
    }

    public function setData($data = null)
    {
        $this->data = is_null($data) ? null : json_encode($data);
        return $this;
    }

    public function getResponse()
    {
        return (String) $this->response;
    }

    public function getArrayResponse()
    {
        return json_decode($this->response, true);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function isOk()
    {
        return $this->isResponseOk == "OK";
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrorResponse()
    {
        return $this->errorResponse;
    }
}