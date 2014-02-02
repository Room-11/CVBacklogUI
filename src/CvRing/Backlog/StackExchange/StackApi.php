<?php

namespace CvRing\Backlog\StackExchange;

use Artax\Client;
use Artax\ClientException;
use CvRing\Backlog\ConfigFile;
use Monolog\Logger;

/**
 * Handles requests to the Stack Exchange API
 *
 * @author Kyra D. <kyra@existing.me>
 */
class StackApi
{
    /** @var \Artax\Client */
    private $client;

    /** @var \CvRing\Backlog\ConfigFile */
    private $config;

    /** @var \Monolog\Logger */
    private $logger;

    /** @var int */
    private $page_number = 1;

    /** @var array */
    private $qids = [];

    /** @var array */
    private $qid_data = [];

    /**
     * @param Client $client
     * @param ConfigFile $config
     * @param Logger $logger
     */
    public function __construct(Client $client, ConfigFile $config, Logger $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    /** @return array */
    public function getApiQuestionIds()
    {
        $maxItems = $this->config->getSourceMaxItems('api');

        while ($maxItems >= count($this->qids)) {
            $this->fetchApiQuestionIds();
        }

        return array_slice($this->qids, 0, $maxItems);
    }

    /**
     * @return array
     */
    public function getQuestionData()
    {
        $qids = $this->getApiQuestionIds();

        foreach (array_chunk($qids, 100) as $batch) {
            $startTime = microtime(true);
            $this->qid_data = array_merge($this->qid_data, $this->fetchQuestionData($batch));
            $sleep = 2 - (microtime(true) - $startTime);

            if (0 < $sleep) {
                sleep($sleep); /* 1 API request / 2 secs */
            }
        }

        return $this->qid_data;
    }

    private function fetchApiQuestionIds()
    {
        $query = http_build_query(
            [
                'filter'   => $this->config->getFilter('get_qids'),
                'key'      => $this->config->getApiRequestKey(),
                'order'    => 'desc',
                'page'     => $this->page_number,
                'pagesize' => 100,
                'site'     => $this->config->getApiStackDomain(),
                'sort'     => 'creation',
                'tagged'   => $this->config->getApiSourceTopicTags(),
            ]
        );

        try {

            $request = 'https://api.stackexchange.com/2.1/search/advanced?' . $query;
            $this->logger->addInfo("requesting: $request");
            $response = $this->client->request($request);

            $entries = json_decode($response->getBody());

            if (!isset($entries->error_id)) {
                foreach ($entries->items as $entry) {
                    if (isset($entry->closed_date)
                        || 0 < $entry->close_vote_count
                        || 0 < $entry->delete_vote_count
                        || 0 < $entry->reopen_vote_count
                        || 1 < $entry->down_vote_count
                    ) {
                        $this->qids[] = $entry->question_id;
                    }
                }

                $this->page_number = ++$entries->page;

                if (isset($entries->backoff)) {
                    sleep($entries->backoff);
                }


            } else {
                echo $entries->error_message;
            }
        } catch (ClientException $e) {
            $this->logger->addCritical('request failed: ' . $e);
        }
    }

    /**
     * @param array $qids
     * @return array
     */
    private function fetchQuestionData(array $qids)
    {
        $query = http_build_query(
            [
                'filter' => $this->config->getFilter('get_data'),
                'key' => $this->config->getApiRequestKey(),
                'pagesize' => 100,
                'site' => $this->config->getApiStackDomain()
            ]
        );

        try {

            $request = 'https://api.stackexchange.com/2.1/questions/' . implode(';', $qids) . '?' . $query;
            $this->logger->addInfo("requesting: $request");
            $response = $this->client->request($request);

            if ((200 === $response->getStatus()) && ('' !== $response->getBody())) {
                $this->logger->addInfo('batch question data successful');
                return json_decode($response->getBody())->items;
            }

        } catch (ClientException $e) {
            $this->logger->addCritical('request failed: ' . $e);
        }

        return [];
    }
}
