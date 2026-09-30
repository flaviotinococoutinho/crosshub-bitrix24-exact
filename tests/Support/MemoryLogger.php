<?php

namespace CrossHub\Tests;

final class MemoryLogger implements \CrossHub\Ports\Logger
{
    public $entries = array();

    public function write($channel, $message)
    {
        $this->entries[] = array('channel' => $channel, 'message' => $message);
        return true;
    }
}
