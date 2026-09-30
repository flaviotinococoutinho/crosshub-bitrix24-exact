<?php

namespace CrossHub\Adapters\Logging;

use CrossHub\Logging\Rotation;
use CrossHub\Ports\Logger;

final class RotatingFileLogger implements Logger
{
    private $directory;
    private $rotation;

    public function __construct($directory, Rotation $rotation)
    {
        $this->directory = rtrim($directory, '/');
        $this->rotation = $rotation;
    }

    public function write($channel, $message)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $channel) || !$this->ensureDirectory()) {
            return $this->failed();
        }
        $path = $this->directory . '/' . $channel . '.log';
        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false) {
            return $this->failed();
        }
        @chmod($path . '.lock', 0600);
        $result = $this->underLock($lock, $path, $message);
        fclose($lock);
        return $result;
    }

    private function underLock($lock, $path, $message)
    {
        if (!flock($lock, LOCK_EX)) {
            return $this->failed();
        }
        try {
            return $this->append($path, $this->rotation->fit($message));
        } catch (\Exception $error) {
            return $this->failed();
        } finally {
            flock($lock, LOCK_UN);
        }
    }

    private function append($path, $message)
    {
        $this->rotation->prepare($path, strlen($message));
        $bytes = @file_put_contents($path, $message, FILE_APPEND);
        @chmod($path, 0600);
        if ($bytes !== strlen($message)) {
            return $this->failed();
        }
        return true;
    }

    private function ensureDirectory()
    {
        return is_dir($this->directory) || @mkdir($this->directory, 0700, true) || is_dir($this->directory);
    }

    private function failed()
    {
        error_log('CrossHub: nao foi possivel gravar o log operacional.');
        return false;
    }
}
