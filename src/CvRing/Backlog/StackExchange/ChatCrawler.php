<?php

namespace CvRing\Backlog\StackExchange;

use Artax\Client;
use Artax\ClientException;
use CvRing\Backlog\ConfigFile;
use Monolog\Logger;

/**
 * Crawls chat transcript and compiles list of valid *-pls question links
 *
 * @author Kyra D. <kyra@existing.me>
 * @author Chris Wright <cvbacklogui@daverandom.com>
 */
class ChatCrawler
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
    public function getQuestionIds()
    {
        $this->logger->addInfo('start crawling chat transcript');
        $this->crawlTranscript();
        $this->logger->addInfo('end crawling chat transcript');

        return $this->qids;
    }

    private function crawlTranscript()
    {
        $maxItems = $this->config->getSourceMaxItems('chat');

        while ($maxItems >= count($this->qids)) {

            if (false === ($html = $this->fetchPageHtml())) {
                continue;
            }

            $startTime = microtime(true);
            $qids = $this->questionsExist($this->findQuestionsIds($html));
            $this->qids = array_unique(array_merge($this->qids, $qids));
            $sleep = 10 - (microtime(true) - $startTime);

            if (0 < $sleep) {
                sleep($sleep); /* 1 transcript request / 10 secs */
            }
        }

        $this->qids = array_slice($this->qids, 0, $maxItems);
    }

    /** @return bool|string */
    private function fetchPageHtml()
    {
        $query = http_build_query(
            [
                'page' => $this->page_number,
                'pagesize' => 100,
                'q' => 'tag:cv-pls tag:delv-pls tag:reopen-pls tag:review-pls tag:ro-pls tag:rov-pls tag:rv-pls',
                'room' => $this->config->getChatSourceRoomId(),
                'sort' => 'newest'
            ]
        );

        try {

            $request = "http://{$this->config->getChatSourceDomain()}/search?$query";
            $this->logger->addInfo("requesting: $request");
            $response = $this->client->request($request);

            if ((200 === $response->getStatus()) && ('' !== $response->getBody())) {
                $this->logger->addInfo('response body successfully fetched');
                $this->page_number++;
                return $response->getBody();
            }
        } catch (ClientException $e) {
            echo $e;
            $this->logger->addCritical('request failed: ' . $e);
        }

        return false;
    }

    /**
     * @param string $html
     * @return array
     */
    private function findQuestionsIds($html)
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $result = [];

        $findTags = '//div[@class = "messages"]' // chat container
            . '/div[@class = "message"]' // message container
            . '/div[@class = "content"]' // message content
            . '//span[@class = "ob-post-tag" and ' // chat tag
            . 'substring(., string-length(.) - 3) = "-pls"]'; // match tags ending in '-pls'

        $findQuestionId = "~^
                https?://{$this->config->getApiStackDomain()} # Stack Exchange site
                /q(?:uestions)?/                              # short or full path
                (?P<qid>\\d++)                                # question ID
                (?!/[^/]+/\\d+)                               # don't match if answer ID present
            ~xi";

        foreach ($xpath->query($findTags) as $tag) {

            $node = $tag->parentNode->parentNode->firstChild;
            $questionId = null;

            do {
                if (($node instanceof \DOMElement)
                    && ($node->tagName === 'a')
                    && (preg_match($findQuestionId, $node->getAttribute('href'), $matches))
                ) {
                    $questionId = $matches['qid'];
                    break;
                }

                if (($node instanceof \DOMText) && (preg_match($findQuestionId, $node->data, $matches))) {
                    $questionId = $matches['qid'];
                }
            } while (!is_null(($node = $node->nextSibling)));

            if (isset($questionId)) {
                $result[] = $questionId;
            }
        }

        return $result;
    }

    /**
     * @param array $qids
     * @return array
     */
    private function questionsExist(array $qids)
    {
        $query = http_build_query(
            [
                'filter' => $this->config->getFilter('check_qids'),
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

                $this->logger->addInfo('qid batch validation successful');
                $entries = json_decode($response->getBody())->items;

                foreach ($entries as $entry) {
                    $qids[] = $entry->question_id;
                }

                return $qids;
            }
        } catch (ClientException $e) {
            $this->logger->addCritical('request failed: ' . $e);
        }

        return false;
    }
}
