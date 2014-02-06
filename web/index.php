<?php

use Monolog\Logger;

require_once '../vendor/autoload.php';

/* set PHP config defaults */
ini_set('date.timezone', 'UTC'); /* required to set a default timezone, blame Derick Rethans */
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
ini_set('error_reporting', E_ALL);
ini_set('html_errors', 0);
ini_set('log_errors', 1);

/* get application environment */
$appEnv = (isset($_SERVER['APP_ENV']) && in_array($_SERVER['APP_ENV'], ['dev', 'prod']))
    ? $_SERVER['APP_ENV']
    : (('127.0.0.1' === $_SERVER['REMOTE_ADDR'])
        ? 'dev'
        : 'prod');

/* default production options */
ini_set('display_errors', 0);
$debug = false;
$logLevel = Logger::INFO;

/* development options */
if ('dev' === $appEnv) {
    ini_set('display_errors', 1);
    $debug = true;
    $logLevel = Logger::DEBUG;
}

require_once '../src/app.php';
