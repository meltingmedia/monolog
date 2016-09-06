<?php
/**
 * Package build script
 */
$tstart = microtime(true);
set_time_limit(0);

$root = dirname(__DIR__) . '/';

// define package
define('PKG_NAME', 'Monolog');
define('PKG_NAME_LOWER', strtolower(PKG_NAME));
$version = explode('-', trim(file_get_contents($root . 'VERSION')));
define('PKG_VERSION', $version[0]);
define('PKG_RELEASE', $version[1]);

// define sources
$sources = [
    'root' => $root,
    'build' => $root . '_build/',
    'build_target' => $root . '_build/_packages/',
    'resolvers' => $root . '_build/resolvers/',
    'validators' => $root . '_build/validators/',
    'data' => $root . '_build/data/',

    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER,
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER,
];
unset($root);

// override with your own defines here (see build.config.sample.php)
require_once $sources['build'] . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once $sources['build'] . '/includes/functions.php';

$modx= new modX();
$modx->initialize('mgr');
if (!XPDO_CLI_MODE) {
    echo '<pre>';
}
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
if (isset($sources['build_target']) && !empty($sources['build_target'])) {
    $exists = true;
    if (!file_exists($sources['build_target'])) {
        $exists = mkdir($sources['build_target'], 0777, true);
    }
    if ($exists) {
        $builder->directory = $sources['build_target'];
    }
}
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER, false, true, '{core_path}components/'.PKG_NAME_LOWER.'/');
$modx->log(modX::LOG_LEVEL_INFO, 'Created Transport Package and Namespace.');

$extensions = include_once $sources['data'] . 'extension.php';
$attributes = [
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
];
foreach ($extensions as $extension) {
    $vehicle = $builder->createVehicle($extension, $attributes);
    $builder->putVehicle($vehicle);
}

// create the plugin object
//$plugin= $modx->newObject('modPlugin');
//$plugin->set('id', 1);
//$plugin->set('name', PKG_NAME);
//$plugin->set('description', 'cmpLauncher allows you to display a link or redirect a user to a particular CMP.');
//$plugin->set('plugincode', getSnippetContent($sources['plugins'] . '/plugin.cmplauncher.php'));
//$plugin->set('category', 0);
//
//// add plugin events
//$events = include $sources['data'].'transport.plugin.events.php';
//if (is_array($events) && !empty($events)) {
//    $plugin->addMany($events);
//    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in '.count($events).' Plugin Events.');
//    flush();
//} else {
//    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find plugin events!');
//}
//unset($events);
//
//// load plugin properties
//$properties = include $sources['build'].'properties/properties.cmpLauncher.php';
//if (is_array($properties)) {
//    $modx->log(xPDO::LOG_LEVEL_INFO, 'Set '.count($properties).' plugin properties.');
//    flush();
//    $plugin->setProperties($properties);
//} else {
//    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not set plugin properties.');
//}

$attributes = [
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'PluginEvents' => [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
        ],
    ],
];
$vehicle = $builder->createVehicle($plugin, $attributes);

$vehicle->resolve('file', [
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
]);
$builder->putVehicle($vehicle);

// now pack in the license file, readme and setup options
$builder->setPackageAttributes([
    'license' => file_get_contents($sources['root'] . 'LICENSE'),
    'readme' => file_get_contents($sources['root'] . 'README.md'),
    'changelog' => file_get_contents($sources['root'] . 'CHANGELOG.md'),

    'requires' => [
        'php' => '>=5.4',
        //'modx' => '>=2.5',
    ],
]);
$modx->log(modX::LOG_LEVEL_INFO, 'Added package attributes and setup options.');

// zip up package
$modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
$builder->pack();

$tend = microtime(true);
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);
$modx->log(modX::LOG_LEVEL_INFO, "\n\nPackage Built. \nExecution time: {$totalTime}\n");
if (!XPDO_CLI_MODE) {
    echo '</pre>';
}

exit();
