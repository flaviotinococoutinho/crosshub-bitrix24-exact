<?php

namespace CrossHub\Logging;

/** Retencao limitada: arquivo atual + N copias, todos com limite de bytes. */
final class Rotation
{
    private $maxBytes;
    private $backups;

    public function __construct($maxBytes, $backups)
    {
        if ($maxBytes < 256 || $backups < 0) {
            throw new \InvalidArgumentException('Limites de rotacao invalidos.');
        }
        $this->maxBytes = $maxBytes;
        $this->backups = $backups;
    }

    public function fit($message)
    {
        if (strlen($message) <= $this->maxBytes) {
            return $message;
        }
        $marker = "\n[registro truncado pelo limite de log]\n";
        return mb_strcut($message, 0, $this->maxBytes - strlen($marker), 'UTF-8') . $marker;
    }

    public function prepare($path, $incomingBytes)
    {
        $this->prune($path);
        clearstatcache(true, $path);
        if (!is_file($path) || filesize($path) + $incomingBytes <= $this->maxBytes) {
            return;
        }
        if ($this->backups === 0) {
            $this->remove($path);
            return;
        }
        $this->shift($path);
    }

    private function shift($path)
    {
        $this->remove($path . '.' . $this->backups);
        for ($number = $this->backups - 1; $number >= 1; $number--) {
            $this->move($path . '.' . $number, $path . '.' . ($number + 1));
        }
        $this->move($path, $path . '.1');
    }

    private function prune($path)
    {
        foreach (glob($path . '.*') as $candidate) {
            $suffix = substr($candidate, strlen($path) + 1);
            if (ctype_digit($suffix) && (int) $suffix > $this->backups) {
                $this->remove($candidate);
            }
        }
    }

    private function move($source, $destination)
    {
        if (is_file($source) && !@rename($source, $destination)) {
            throw new \RuntimeException('Falha na rotacao de logs.');
        }
    }

    private function remove($path)
    {
        if (is_file($path) && !@unlink($path)) {
            throw new \RuntimeException('Falha na retencao de logs.');
        }
    }
}
