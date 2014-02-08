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
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

$config = new ConfigFile(__DIR__ . '/../config/backlog.ini');

$logger = new Logger('backlog_' . $appEnv);

/* keep a log for each day and rotate weekly */
$logger->pushHandler(
    new RotatingFileHandler(__DIR__ . '/../logs/backlog_' . $appEnv . '.log', 7, $logLevel)
);

$logger->pushProcessor(new IntrospectionProcessor);
$logger->pushProcessor(new MemoryPeakUsageProcessor);
$logger->pushProcessor(new MemoryUsageProcessor);
$logger->pushProcessor(new UidProcessor);
$logger->pushProcessor(new WebProcessor);

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
$httpClient->setOption('userAgentString', 'CVBacklogUI/2.0.0 (https://github.com/Room-11/CVBacklogUI)');

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
