<?php

namespace CrossHub\Http;

/** Valor de transporte; nao conhece cURL, credenciais ou regras dos CRMs. */
final class Request
{
    private $method;
    private $url;
    private $body;
    private $headers;
    private $verifyPeer;

    public function __construct($method, $url, $body = null, array $headers = array(), $verifyPeer = true)
    {
        $this->method = $method;
        $this->url = $url;
        $this->body = $body;
        $this->headers = $headers;
        $this->verifyPeer = $verifyPeer;
    }

    public function method() { return $this->method; }
    public function url() { return $this->url; }
    public function body() { return $this->body; }
    public function headers() { return $this->headers; }
    public function verifyPeer() { return $this->verifyPeer; }
}
