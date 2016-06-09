## Monolog for MODX Revolution

> A [Monolog](https://github.com/Seldaek/monolog) integration for MODX Revolution 2.5+, using [Monolog Cascade](https://github.com/theorchard/monolog-cascade) to handle loggers configuration

MODX Revolution comes with some logging management, sending logs to a single file (in `core/cache/logs/error.log`).

However, for critical errors, you might want to get notified as soon as possible, and not read the logs every day.
Using Monolog and its [default handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) as well as [third party handlers](https://github.com/Seldaek/monolog/wiki/Third-Party-Packages#handlers) helps you manager your logs easily!


### Requirements

* MODX Revolution 2.5+
* PHP 5.4+


### Installation

* Install from package manager


### Usage

* Default "modx" logger (everything that get sent through `$modx->log()`
* Use your own loggers to split your application logs


[configuration](https://github.com/theorchard/monolog-cascade)

```
$path = $modx->getOption('monolog.core_path', null, $modx->getOption('core_path') . 'components/monolog/');
$logger = $modx->getService('logger', 'model.Logger', $path); 
$logger->getLogger('modx')->info('Your log message');
$logger->getLogger('custom')->info('Log message for your custom logger & its handlers');
```
