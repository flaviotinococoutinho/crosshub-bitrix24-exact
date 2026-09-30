<?php

namespace CrossHub\Logging;

use CrossHub\Ports\Logger;

final class RedactingLogger implements Logger
{
    private $logger;
    private $secrets;

    public function __construct(Logger $logger, array $secrets)
    {
        $this->logger = $logger;
        $this->secrets = array_filter($secrets, 'strlen');
        usort($this->secrets, function ($left, $right) { return strlen($right) - strlen($left); });
    }

    public function write($channel, $message)
    {
        foreach ($this->secrets as $secret) {
            $message = str_replace(array($secret, rawurlencode($secret), str_replace('/', '\\/', $secret)), '[REDACTED]', $message);
        }
        $message = preg_replace('~(/rest/[0-9]+/)[a-zA-Z0-9_-]+~', '$1[REDACTED]', $message);
        return $this->logger->write($channel, $message);
    }
}
