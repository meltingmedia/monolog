## Monolog for MODX Revolution

> A [Monolog](https://github.com/Seldaek/monolog) integration for MODX Revolution 2.5+, using [Monolog Cascade](https://github.com/theorchard/monolog-cascade) to handle loggers configuration

MODX Revolution comes with some logging management, sending logs to a single file (in `core/cache/logs/error.log`).

However, for critical errors, you might want to get notified as soon as possible, and not read the logs every day.
Using Monolog and its [default handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) as well as [third party handlers](https://github.com/Seldaek/monolog/wiki/Third-Party-Packages#handlers) helps you manage your logs the way you need!


### Requirements

* MODX Revolution 2.5+
* PHP 5.5.9+


### Installation

* Install from package manager


### Usage

There is a default "modx" logger that logs everything sent through `$modx->log()` the same way you are used to. You have nothing to do and can keep browsing the log at its regular place.
You can check the default configuration in `Logger::getDefaultConfig`.

Things get interesting if you want to use your own loggers to split your application logs.
Create a PHP file with a valid cascade configuration array (see cascade [configuration](https://github.com/theorchard/monolog-cascade#configuring-your-loggers) for available parameters) and set the full path to your configuration file in `monolog.config_path` setting.

Here is a sample configuration file

```
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

```

You should then be able to manipulate those loggers

```
// Since the logger service is automatically loaded, you just need to retrieve it
$logger = $modx->getService('logger'); 
// Grab the default logger
$logger->getLogger('modx')->info('Your log message in the regular error log');
// Then your custom one
$logger->getLogger('app')->info('Log message for your custom logger & its handlers');
```

If you project needs different configuration per context, you just need to create a context setting `monolog.config_path` with the full path to the desired configuration.


### To know

Due to how logs are handled, timestamps for logs sent using `$modx->log` won't be accurate, since the logs are "queued" into an array, and "committed" once PHP stops its execution (using [register_shutdown_function](http://php.net/manual/en/function.register-shutdown-function.php)).
