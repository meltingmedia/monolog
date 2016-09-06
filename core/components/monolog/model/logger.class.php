<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cascade\Cascade;

/**
 * A log service to help ship logs to Monolog
 */
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
     * Commit all MODX generated pending logs
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
     * Get a logger instance
     *
     * @param string $name - The logger instance name
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
     * Get MODX default log file path
     *
     * @return string
     */
    public function getDefaultLogPath()
    {
        return MODX_CORE_PATH . 'cache/logs/error.log';
    }

    /**
     * Handle logging fatal errors & logs inside connectors
     *
     * @return void
     */
    public function shutdown()
    {
        if (!empty($this->logs)) {
            // Workaround to handle situations where we were not able to commit pending logs
            $this->commit();
        }
        /**
         * @see xPDO::_log
         */
        //$message = debug_backtrace();
        $message = error_get_last();
        if (!$message) {
            return;
        }
        if ($message['type'] === E_ERROR || $message['type'] === E_USER_ERROR) {
            $this->getLogger()->emergency($message['message'], $message);
        }
    }

    /**
     * Load our loggers configuration
     */
    protected function load()
    {
        // First make sure we could use the logger in case of a fatal error, so we do not miss the notification
        register_shutdown_function([$this, 'shutdown']);
        // Get the originally configured MODX log level
        $this->setOriginalLogLevel();

        // Check if we have a user defined configuration file
        $file = $this->modx->getOption('monolog.config_path');
        if ($file && file_exists($file)) {
            $config = require_once $file;
            // @TODO make it optional to merge the config ?
            $config = array_merge_recursive($this->getDefaultConfig(), $config);
        } else {
            // Fallback to the default configuration
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
     * Get a default configuration, which "mimic" the default MODX log behavior
     *
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
        if (!empty($entry['def'])) {
            $entry['def'] = trim(str_replace('in ', '', $entry['def']));
        } else {
            unset($entry['def']);
        }

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
     * Get the PSR/Monolog error level name from MODX level (integer)
     *
     * @param int $int - The MODX log level (0-4)
     *
     * @return string
     */
    protected function getLevelNameFromInteger($int)
    {
        switch ($int) {
            case modX::LOG_LEVEL_FATAL:
                return 'EMERGENCY';
            case modX::LOG_LEVEL_ERROR:
                return 'ERROR';
            case modX::LOG_LEVEL_WARN:
                return 'WARNING';
            case modX::LOG_LEVEL_INFO:
                return 'INFO';
            case modX::LOG_LEVEL_DEBUG:
                return 'DEBUG';
        }

        // Should never happen, but just in case, let's fall back to error
        return 'ERROR';
    }
}
