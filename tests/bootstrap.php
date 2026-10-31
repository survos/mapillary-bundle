<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$autoload = is_file($root.'/vendor/autoload.php') ? $root.'/vendor/autoload.php' : $root.'/../../vendor/autoload.php';
$loader = require $autoload;
$loader->addPsr4('Survos\\MapillaryBundle\\', $root.'/src/');
if (is_dir($root.'/../../lib/mapillary/src')) {
    $loader->addPsr4('Survos\\Mapillary\\', $root.'/../../lib/mapillary/src/');
}
