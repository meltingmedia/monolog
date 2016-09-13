<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Melting\MODX\Logger\Service;

/**
 * A log service to help ship logs to Monolog
 */
class Logger extends Service implements \Psr\Log\LoggerInterface
{
    public function emergency($message, array $context = [])
    {
        $this->getLogger()->emergency($message, $context);
    }
    public function alert($message, array $context = [])
    {
        $this->getLogger()->alert($message, $context);
    }
    public function critical($message, array $context = [])
    {
        $this->getLogger()->critical($message, $context);
    }
    public function error($message, array $context = [])
    {
        $this->getLogger()->error($message, $context);
    }
    public function warning($message, array $context = [])
    {
        $this->getLogger()->warning($message, $context);
    }
    public function notice($message, array $context = [])
    {
        $this->getLogger()->notice($message, $context);
    }
    public function info($message, array $context = [])
    {
        $this->getLogger()->info($message, $context);
    }
    public function debug($message, array $context = [])
    {
        $this->getLogger()->debug($message, $context);
    }
    public function log($level, $message, array $context = [])
    {
        $this->getLogger()->log($level, $message, $context);
    }
}
