<?php

namespace CrossHub\Ports;

interface Logger
{
    /** @return bool Falhas de log nao devem interromper a integracao. */
    public function write($channel, $message);
}
