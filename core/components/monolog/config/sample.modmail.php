<?php
/**
 * A sample configuration file to send logs using MODX modMail
 *
 * @var \Logger $this
 *
 * @see \Logger::getConfig()
 */

return [
    'handlers' => [
        // Let's add an extra handler to send emails on error, using modMail service
        'modmail' => [
            'class' => 'Melting\MODX\Logger\Handler\modMail',
            'level' => 'error',
            'modx' => $this->modx,
        ],
        // This is a special handler to "buffer" messages so we only receive a single email in case of many error messages
        'buffer' => [
            'class' => 'Monolog\Handler\BufferHandler',
            'level' => 'error',
            'handler' => 'modmail',
        ],
    ],

    'loggers' => [
        'modx' => [
            // Now we add our extra buffer handler to the default logger so if any $modx->log(modX::LOG_LEVEL_ERROR, ...) is called, we will receive an email
            'handlers' => ['buffer'],
        ],
    ],
];
