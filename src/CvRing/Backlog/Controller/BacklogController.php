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

        return $this->twig->render(
            'base_body.html.twig',
            [
                'source_data' => $this->backlog->getSourceData($src)
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

        if (file_exists("{$this->config->getCachePath()}/{$src}_data.json")) {
            return $this->twig->render(
                'tbody.html.twig',
                [
                    'source_data' => $this->backlog->getSourceData($src)
                ]
            );
        }

        $this->logger->addCritical("cache file for '$src' did not exist");

        return (new Response)
            ->setStatus(404)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(
                json_encode(
                    [
                        'code' => 404,
                        'data' => [
                            'file_name' => "{$src}_data.json",
                            'retry_period' => $this->config->getSourceCacheTtl($src, 'data')
                        ],
                        'message' => "cache file does not exist",
                        'status' => 'error'
                    ],
                    JSON_PRETTY_PRINT
                )
            );
    }

    /**
     * @param $src
     * @return Response|int
     */
    public function cacheRefreshAction($src)
    {
        if ('cli' !== PHP_SAPI) {

            $this->logger->addCritical('Non-CLI cache update attempted');

            return (new Response)
                ->setStatus(403)
                ->setHeader('Content-Type', 'text/plain; charset=utf-8')
                ->setBody('Cache update only permitted via CLI');
        }

        $this->assertAllowedSrcValues($src);

        return $this->backlog->updateCache($src);
    }

    /**
     * @param $src
     * @param null $qid
     * @return Response
     */
    public function debugAction($src, $qid = null)
    {
        $this->assertAllowedSrcValues($src);

        $sourceData = $this->backlog->getSourceData($src);
        $questions = ($qid && isset($sourceData->questions[$qid]))
            ? $sourceData->questions[$qid]
            : $sourceData->questions;

        return (new Response)
            ->setHeader('Content-Type', 'text/plain; charset=utf-8')
            ->setBody(
                $this->twig->render(
                    'debug.text.twig',
                    [
                        'data_src_name' => $sourceData->src_name,
                        'dump_questions' => $questions,
                        'print_questions' => print_r($questions, true),
                        'route_src_name' => $src,
                        'serialize_questions' => serialize($questions),
                        'timestamp' => $sourceData->timestamp
                    ]
                )
            );
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
