<?php
/**
 * A plugin to handle logging to Monolog
 *
 * @var modX $modx
 * @var array $scriptProperties
 *
 * @events OnManagerPageAfterRender OnWebPageComplete
 */

$path = $modx->getOption('monolog.core_path', null, $modx->getOption('core_path') . 'components/monolog/');
/** @var Logger $service */
$service = $modx->getService('logger', 'model.Logger', $path);

$service->commit();

return '';