<?php

use Artax\Client;
use Arya\Application;
use Auryn\Provider;
use CvRing\Backlog\BacklogCore;
use CvRing\Backlog\ConfigFile;
use CvRing\Backlog\FileCache;
use CvRing\Backlog\StackExchange\ChatCrawler;
use CvRing\Backlog\StackExchange\StackApi;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

$config = new ConfigFile(__DIR__ . '/../config/backlog.ini');

$logger = new Logger('backlog_' . $appEnv);

/* keep a log for each day and rotate weekly */
$logger->pushHandler(
    new RotatingFileHandler(__DIR__ . '/../logs/backlog_' . $appEnv . '.log', 7, $logLevel)
);

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(__DIR__ . '/../src/CvRing/Backlog/templates'),
    [
        'autoescape' => false, /* we escape manually */
        'cache' => __DIR__ . '/../cache/twig',
        'debug' => $debug,
        'strict_variables' => $debug
    ]
);

$twig->addExtension(new Twig_Extension_Optimizer());

$twig->addGlobal('APP_ENV', $appEnv);
$twig->addGlobal('config', $config);

$twig->addFunction(
    new Twig_SimpleFunction(
        'asset',
        function ($asset) {
            return '/' . ltrim($asset, '/');
        }
    )
);

$twig->addFunction(
    new Twig_SimpleFunction(
        'dump',
        function ($value) {
            ob_start();
            var_dump($value);
            return ob_get_clean();
        }
    )
);

$httpClient = new Client;
$httpClient->setOption('connectTimeout', 30);

$stackApi = new StackApi($httpClient, $config, $logger);

$chatCrawler = new ChatCrawler($httpClient, $config, $logger, $stackApi);

$cache = new FileCache($config);

$backlog = new BacklogCore($config, $chatCrawler, $cache, $logger, $stackApi);

/* inject dependencies */
$app = new Application(
    (new Provider)
        ->share($backlog)
        ->share($config)
        ->share($logger)
        ->share($twig)
);

require __DIR__ . '/../src/routes.php';

$app->run();
