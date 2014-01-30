<?php

namespace CvRing\Backlog\Controller;

use Arya\Response;
use CvRing\Backlog\BacklogCore;
use CvRing\Backlog\ConfigFile;
use Monolog\Logger;
use Twig_Environment;

/**
 * Class BacklogController
 * @package CvRing\BacklogUi\Controller
 */
class BacklogController
{
    /** @var \CvRing\Backlog\BacklogCore */
    private $backlog;

    /** @var \CvRing\Backlog\ConfigFile */
    private $config;

    /** @var \Monolog\Logger */
    private $logger;

    /** @var \Twig_Environment */
    private $twig;

    /**
     * @param BacklogCore $backlog
     * @param ConfigFile $config
     * @param Logger $logger
     * @param Twig_Environment $twig
     */
    public function __construct(BacklogCore $backlog, ConfigFile $config, Logger $logger, Twig_Environment $twig)
    {
        $this->backlog = $backlog;
        $this->config = $config;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    /**
     * @param string $src
     * @return string
     */
    public function indexAction($src = 'api')
    {
        $this->assertAllowedSrcValues($src);

        // ... run backlog code to get current cached data for source

        return $this->twig->render(
            'base_body.html.twig',
            [
                'source_data' => false, /* nothing to return yet */
                'src' => $src
            ]
        );
    }

    /**
     * @param $src
     * @return Response
     */
    public function ajaxRefreshAction($src)
    {
        $this->assertAllowedSrcValues($src);

        $cacheFile = "{$this->config->getCachePath()}/{$src}_data.json";
        $response = (new Response)->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (file_exists($cacheFile)) {
            $this->logger->addCritical("cache file for '$src' did not exist");
            return $response->setBody(
                function () use ($cacheFile) {
                    readfile($cacheFile);
                }
            );
        }

        $error = [
            'code' => 404,
            'data' => [
                'file' => "{$src}_data.json"
            ],
            'message' => "cache file does not exist, retry in {$this->config->getSourceCacheTtl($src, 'data')} secs",
            'status' => 'error'
        ];

        return $response
            ->setBody(json_encode($error, JSON_PRETTY_PRINT))
            ->setStatus(404);
    }

    /**
     * @param $src
     * @return Response|int
     */
    public function cacheRefreshAction($src)
    {
        if ('cli' !== PHP_SAPI) {
            $this->logger->addCritical('Non-CLI cache update attempted.');
            return (new Response)
                ->setBody('Cache update only permitted via CLI')
                ->setHeader('Content-Type', 'text/plain; charset=utf-8')
                ->setStatus(403);
        }

        $this->assertAllowedSrcValues($src);

        // ... update data cache where update code should return a status code

        //return $status;
    }

    /**
     * @param $src
     * @return string
     */
    public function debugAction($src)
    {
        $this->assertAllowedSrcValues($src);

        // ... get vars that would help to debug

        $template = $this->twig->render(
            'debug.text.twig',
            [
                // ... pass debug vars to debug template
            ]
        );

        return (new Response)
            ->setBody($template)
            ->setHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * @param $value
     * @throws \InvalidArgumentException
     */
    private function assertAllowedSrcValues($value)
    {
        if (!in_array($value, ['api', 'chat'])) {
            throw new \InvalidArgumentException("Invalid source argument of '$value' passed");
        }
    }
}
