<?php

namespace CrossHub\Tests;

final class RecordingHttp implements \CrossHub\Ports\HttpClient
{
    public $requests = array();
    public $responses = array();

    public function send(\CrossHub\Http\Request $request)
    {
        $this->requests[] = array(
            'method' => $request->method(),
            'url' => $request->url(),
            'body' => $request->body(),
            'headers' => $request->headers(),
            'verify_peer' => (bool) $request->verifyPeer(),
        );
        if (!$this->responses) {
            throw new \RuntimeException('Requisicao inesperada no teste.');
        }
        return array_shift($this->responses);
    }
}
