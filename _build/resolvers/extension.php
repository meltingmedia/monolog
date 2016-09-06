<?php
/**
 * @see xPDOVehicle::resolve
 *
 * @var xPDOVehicle $this
 * @var xPDOTransport $transport
 * @var xPDOObject|mixed $object
 * @var array $options
 *
 * @var array $fileMeta
 * @var string $fileName
 * @var string $fileSource
 *
 * @var array $r
 * @var string $type (file/php), obviously php :)
 * @var string $body (json)
 * @var integer $preExistingMode
 */
if ($object->xpdo) {
    /** @var modX $modx */
    $modx = $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $path = $modx->getOption('monolog.core_path');
            if (empty($modelPath)) {
                $path = '[[++core_path]]components/monolog/';
            }
            if ($modx instanceof modX) {
                $modx->addExtensionPackage('monolog', $path, [
                    'serviceName' => 'logger',
                    'serviceClass' => 'Logger'
                ]);
            }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            if ($modx instanceof modX) {
                $modx->removeExtensionPackage('monolog');
            }
            break;
    }
}

return true;
