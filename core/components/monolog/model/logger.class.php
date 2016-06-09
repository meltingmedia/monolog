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
     * @return string
     */
    public function getDefaultLogPath()
    {
        return MODX_CORE_PATH . 'cache/logs/error.log';
    }

    protected function load()
    {
        $file = $this->modx->getOption('monolog.config_path');
        if ($file && file_exists($file)) {
            $config = require_once $file;
            $config = array_merge_recursive($this->getDefaultConfig(), $config);
        } else {
            $config = $this->getDefaultConfig();
        }
        // @see https://github.com/theorchard/monolog-cascade/issues/51 in case we want to merge the default config with a custom one
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

    protected function getDefaultConfig()
    {
        return [
//            'disable_existing_loggers' => false,
//            'formatters' => [
//
//            ],

            'processors' => [
                'uid' => [
                    'class' => 'Monolog\Processor\UidProcessor',
                ],
                'web' => [
                    'class' => 'Monolog\Processor\WebProcessor',
                ],
            ],

            'handlers' => [
                'core' => [
                    'class' => 'Monolog\Handler\StreamHandler',
                    'level' => 'info',
                    'stream' => $this->getDefaultLogPath(),
                    'processors' => ['uid', 'web'],
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
}
