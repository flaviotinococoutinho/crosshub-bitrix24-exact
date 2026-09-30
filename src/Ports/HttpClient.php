<?php

namespace CrossHub\Ports;

interface HttpClient
{
    /** @return string|false Corpo original da resposta, inclusive erros HTTP. */
    public function send(\CrossHub\Http\Request $request);
}
