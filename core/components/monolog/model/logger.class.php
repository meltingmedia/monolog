<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cascade\Cascade;

class Logger
{
    /**
     * @var modX
     */
    public $modx;
    /**
     * Pending logs awaiting to be committed/sent to the logger
     *
     * @var array
     */
    protected $logs = [];
    protected $originalLevel = '';

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
            $this->getLogger()->log($level, $entry['msg'], $entry);
        }
        $this->logs = [];
    }

    /**
     * @param string $name
     *
     * @return \Monolog\Logger|\Psr\Log\LoggerInterface
     */
    public function getLogger($name = 'modx')
    {
        return Cascade::getLogger($name);
    }

    /**
     * Get the originally configured MODX log level, formatted to be usable with Monolog
     *
     * @return string
     */
    public function getOriginalLogLevel()
    {
        return $this->originalLevel;
    }

    /**
     * @return string
     */
    public function getDefaultLogPath()
    {
        return MODX_CORE_PATH . 'cache/logs/error.log';
    }

    public function logFatal()
    {
        /**
         * @see xPDO::_log
         */
        //$message = debug_backtrace();
        $message = error_get_last();
        if (!$message) {
            return;
        }
        $this->getLogger()->info('Fatal!', $message);
        if ($message['type'] === E_ERROR) {
            //echo print_r($message, true);
            $this->getLogger()->emergency($message['message'], $message);
        }
    }

    protected function load()
    {
        register_shutdown_function([$this, 'logFatal']);
        $this->setOriginalLogLevel();

        $file = $this->modx->getOption('monolog.config_path');
        if ($file && file_exists($file)) {
            $config = require_once $file;
            // @TODO make it optional to merge the config ?
            $config = array_merge_recursive($this->getDefaultConfig(), $config);
        } else {
            $config = $this->getDefaultConfig();
        }
        Cascade::fileConfig($config);

        // Make the level as high as possible so Monolog could handle the logs
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
     * Store the configured MODX log level
     */
    protected function setOriginalLogLevel()
    {
        $this->originalLevel = $this->getLevelNameFromInteger($this->modx->getLogLevel());
    }

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
//            'disable_existing_loggers' => false,
//            'formatters' => [
//
//            ],

            'processors' => [
                'psr' => [
                    'class' => 'Monolog\Processor\PsrLogMessageProcessor',
                ],
                'introspection' => [
                    'class' => 'Monolog\Processor\IntrospectionProcessor',
                ],
                'web' => [
                    'class' => 'Monolog\Processor\WebProcessor',
                ],
                'memory_usage' => [
                    'class' => 'Monolog\Processor\MemoryUsageProcessor',
                ],
                'memory_peak' => [
                    'class' => 'Monolog\Processor\MemoryPeakUsageProcessor',
                ],
                'process_id' => [
                    'class' => 'Monolog\Processor\ProcessIdProcessor',
                ],
                'uid' => [
                    'class' => 'Monolog\Processor\UidProcessor',
                ],
                'git' => [
                    'class' => 'Monolog\Processor\GitProcessor',
                ],
//                'tags' => [
//                    'class' => 'Monolog\Processor\TagProcessor',
//                    'tags' => ['demo', 'test']
//                ],
                'modx' => [
                    'class' => 'Melting\MODX\Logger\Processor\MODXInfo',
                    'modx' => $this->modx,
                ],
            ],

            'handlers' => [
                'core' => [
                    'class' => 'Monolog\Handler\StreamHandler',
                    'level' => $this->getOriginalLogLevel(),
                    'stream' => $this->getDefaultLogPath(),
                    //'processors' => ['uid', 'web'],
                ]
            ],

            'loggers' => [
                'modx' => [
                    'handlers' => ['core'],
                ]
            ],
        ];
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

        $entry['msg'] = trim($entry['msg']);
        $entry['file'] = trim(str_replace('@', '', $entry['file']));
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

    /**
     * @param int $int
     *
     * @return string
     */
    protected function getLevelNameFromInteger($int)
    {
        switch ($int) {
            case MODx::LOG_LEVEL_FATAL:
                return 'EMERGENCY';
            case MODx::LOG_LEVEL_ERROR:
                return 'ERROR';
            case MODx::LOG_LEVEL_WARN:
                return 'WARNING';
            case MODx::LOG_LEVEL_INFO:
                return 'INFO';
            case MODx::LOG_LEVEL_DEBUG:
                return 'DEBUG';
        }

        return 'ERROR';
    }
}
