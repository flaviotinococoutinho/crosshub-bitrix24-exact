<?php

namespace CrossHub\Application;

use CrossHub\Adapters\Http\CurlHttpClient;
use CrossHub\Adapters\Logging\RotatingFileLogger;
use CrossHub\Adapters\Logging\StderrLogger;
use CrossHub\Integration\Bitrix;
use CrossHub\Integration\Exact;
use CrossHub\Logging\RedactingLogger;
use CrossHub\Logging\Rotation;

final class Factory
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function webhooks()
    {
        $http = new CurlHttpClient($this->settings['http_timeout']);
        $logger = $this->logger();
        $bitrix = new Bitrix($http, $logger, $this->settings);
        $exact = new Exact($http, $this->settings);
        return array(
            'bitrix' => new BitrixWebhook($bitrix, $exact, $logger, $this->settings['input_mappings']),
            'exact' => new ExactWebhook($bitrix, $logger),
        );
    }

    private function logger()
    {
        $secrets = array_merge(
            array($this->settings['bitrix_hook']),
            array_values($this->settings['exact_tokens'])
        );
        return new RedactingLogger($this->destination(), $secrets);
    }

    private function destination()
    {
        if ($this->settings['log_driver'] === 'stderr') {
            return new StderrLogger();
        }
        if ($this->settings['log_driver'] !== 'file') {
            throw new \RuntimeException('LOG_DRIVER deve ser file ou stderr.');
        }
        return new RotatingFileLogger(
            $this->settings['log_directory'],
            new Rotation($this->settings['log_max_bytes'], $this->settings['log_backup_count'])
        );
    }
}
