<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
echo 'SESSION_DOMAIN=' . var_export($app->make('config')->get('session.domain'), true) . "\n";
echo 'SESSION_SECURE=' . var_export($app->make('config')->get('session.secure'), true) . "\n";
echo 'APP_URL=' . var_export($app->make('config')->get('app.url'), true) . "\n";
echo 'SESSION_COOKIE=' . var_export($app->make('config')->get('session.cookie'), true) . "\n";
