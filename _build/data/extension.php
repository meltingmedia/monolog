<?php
/**
 * @var modX $modx
 */
$extensions = [];

/** @var modExtensionPackage $ext */
$ext = $modx->newObject('modExtensionPackage');
$ext->fromArray([
    'namespace' => 'monolog',
    'name' => 'monolog',
    //'path' => '[[++core_path]]components/monolog/',
    'service_class' => 'Logger',
    'service_name' => 'logger',
], '', true, true);

$extensions[] = $ext;

return $extensions;
