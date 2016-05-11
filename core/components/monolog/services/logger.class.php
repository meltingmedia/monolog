<?php

require_once __DIR__ . '/../vendor/autoload.php';

class Logger
{
    /**
     * @var modX
     */
    public $modx;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * Pending logs awaiting to be committed/sent to the logger
     *
     * @var array
     */
    protected $logs = [];

    public function __construct(modX $modx, array $options = [])
    {
        $this->modx = $modx;
        $this->load();
    }

    /**
     * Commit all pending logs
     *
     * @return void
     */
    public function commit()
    {
        foreach ($this->logs as $entry) {
            $level = $this->getLevel($entry['level']);
            $entry = $this->cleanLog($entry);
            $this->logger->log($level, $entry['msg'], $entry);
        }
        $this->logs = [];
    }

    /**
     * @return \Monolog\Logger|\Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getDefaultLogPath()
    {
        return MODX_CORE_PATH . 'cache/logs/error.log';
    }

    protected function load()
    {
        // @TODO allow customization of channel name, handlers & processors
        $this->logger = new Monolog\Logger(
            'app',
            [
                new Monolog\Handler\StreamHandler($this->getDefaultLogPath(), 'info'),
            ],
            [
                new Monolog\Processor\UidProcessor(),
                new Monolog\Processor\WebProcessor(),
            ]
        );

        // Make the level as height as possible so Monolog could handle the logs
        $this->modx->setLogLevel(modX::LOG_LEVEL_DEBUG);
        // Register the log target so modX::log could write pending logs in our service
        $this->modx->setLogTarget([
            'target' => 'ARRAY_EXTENDED',
            'options' => [
                'var' => & $this->logs
            ],
        ]);
    }

    /**
     * Remove some data from default MODX log messages
     *
     * @param array $entry
     *
     * @return array
     */
    protected function cleanLog(array $entry)
    {
        unset($entry['content'], $entry['level']);

        $entry['file'] = trim(str_replace('@ ', '', $entry['file']));
        $entry['line'] = trim(str_replace(':', '', $entry['line']));
        $entry['def'] = trim(str_replace('in ', '', $entry['def']));

        return $entry;
    }

    /**
     * Convert MODX log level to valid ones for Monolog
     *
     * @param string $level
     *
     * @return string
     */
    protected function getLevel($level)
    {
        $level = strtoupper($level);
        switch ($level) {
            case 'WARN':
                $level = 'WARNING';
                break;
            case 'FATAL':
                $level = 'EMERGENCY';
                break;
        }

        return $level;
    }
}
