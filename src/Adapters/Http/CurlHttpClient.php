<?php

namespace CrossHub\Adapters\Http;

use CrossHub\Http\Request;
use CrossHub\Ports\HttpClient;

final class CurlHttpClient implements HttpClient
{
    private $timeout;

    public function __construct($timeout = 0)
    {
        $this->timeout = $timeout;
    }

    public function send(Request $request)
    {
        $curl = curl_init();
        curl_setopt_array($curl, $this->options($request));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function options(Request $request)
    {
        $options = array(
            CURLOPT_URL => $request->url(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => $request->verifyPeer(),
            CURLOPT_TIMEOUT => $this->timeout,
        );
        if ($request->headers()) {
            $options[CURLOPT_HTTPHEADER] = $request->headers();
        }
        if ($request->method() === 'POST') {
            $options[CURLOPT_POST] = true;
        }
        if ($request->method() !== 'GET' && $request->method() !== 'POST') {
            $options[CURLOPT_CUSTOMREQUEST] = $request->method();
        }
        if ($request->body() !== null) {
            $options[CURLOPT_POSTFIELDS] = $request->body();
        }
        return $options;
    }
}
