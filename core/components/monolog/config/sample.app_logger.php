<?php
/**
 * @var \Logger $this
 *
 * @see \Logger::getDefaultConfig
 */
// Require our project autoloader (if needed) to support third party handlers/processors/formatters...
require_once __DIR__ . '/../vendor/autoload.php';

// Return a valid cascade configuration
return [
    'handlers' => [
        // this is a default log handler, we just want to add some extra processors
        'core' => [
            // Add some extra information in the regular modx error log
            'processors' => ['uid', 'web'],
        ],
        // this creates a new handler dedicated to our application logic
        'app' => [
            'class' => 'Monolog\Handler\StreamHandler',
            'level' => 'debug',
            'stream' => MODX_CORE_PATH . 'cache/logs/app.log',
            'processors' => ['uid', 'web', 'modx'],
        ],
    ],

    'loggers' => [
        // this is the default logger, available using Logger::getLogger() or Logger::getLogger('modx')
        'modx' => [
            'handlers' => [],
        ],
        // our custom application logger we can now retrieve using Logger::getLogger('app')
        'app' => [
            'handlers' => ['app'],
        ],
    ],
];
