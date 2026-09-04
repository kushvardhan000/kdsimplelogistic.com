<?php

// Directly load Composer's static autoloader without platform check
require_once __DIR__ . '/vendor/composer/ClassLoader.php';

$loader = new \Composer\Autoload\ClassLoader(__DIR__);
\Composer\Autoload\ComposerStaticInit::getInitializer($loader)($loader);
$loader->register(true);

$filesToLoad = \Composer\Autoload\ComposerStaticInit::$files;
foreach ($filesToLoad as $fileIdentifier => $file) {
    if (!isset($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
        $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
        require $file;
    }
}

// Bootstrap Laravel application for tests
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
