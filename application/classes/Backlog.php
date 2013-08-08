<?php

/**
 * Fetches and serves backlog data
 *
 * @author  Kyra D. <kyra@existing.me>
 * @method  void __construct(string $dataSource)
 * @todo    -
 * @uses    -
 */
class Backlog
{
    public  $questionIds = [];
    public  $questionsData = [];
    public  $tbodyData;

    private $dataSource;
    private $questionIdsCache;
    private $questionDataCache;
    private $tbodyHtmlCache;

    public function __construct($dataSource, $cacheDir, array $expirationTimes)
    {
        $this->setDataSource($dataSource);

        $dataSourceDir = $cacheDir . '/' . $this->dataSource;

        $this->questionIdsCache  = new FileCache($dataSourceDir . '_backlog_ids.cache.json', $expirationTimes['ids']);
        $this->questionDataCache = new FileCache($dataSourceDir . '_backlog_data.cache.json', $expirationTimes['data']);
        $this->tbodyHtmlCache    = new FileCache($dataSourceDir . '_tbody.cache.json', $expirationTimes['ids']);
    }

    /**
     * @return  bool
     */
    public function fetchChatQuestionIds()
    {
        if (!$this->questionIdsCache->isExpired()) {
            return;
        }

        $jsonFile = 'http://cvbacklog.gordon-oheim.biz/';

        $questions = json_decode(file_get_contents($jsonFile, false,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Accept: application/json\r\n",
                ]
            ]
        )));

        foreach ($questions as $question) {
            $this->questionIds[] = $question->question_id;
        }

        $this->questionIdsCache->write($this->questionIds);
    }

    /**
     * @return  bool
     */
    public function fetchApiQuestionIds($page = 1)
    {
        if (!$this->questionIdsCache->isExpired()) {
            return;
        }

        $apiQuery = http_build_query([
                'filter'   => '!wQ0g-ul-W8LDT0w',
                'key'      => 'pMxerkFG8E257Xblt5BUHA((',
                'order'    => 'desc',
                'pagesize' => 100,
                'site'     => 'stackoverflow',
                'sort'     => 'creation',
                'tagged'   => 'php',
                'page'     => $page,
            ]
        );

        $apiRequest = 'https://api.stackexchange.com/2.1/search/advanced?' . $apiQuery;

        sleep(1); // throttle down
        
        $apiData = json_decode(file_get_contents('compress.zlib://' . $apiRequest, false,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Accept-Encoding: gzip, deflate\r\n",
                ],
            ]
        )));

        foreach ($apiData->items as $entry) {
            if (isset($entry->closed_date) || 0 < $entry->close_vote_count || 1 < $entry->down_vote_count) {
                 $this->questionIds[] = $entry->question_id;
            }
        }

        ($apiData->has_more && $page !== 10)
            ? $this->fetchApiQuestionIds(++$page)
            : $this->questionIdsCache->write($this->questionIds);
    }

    /**
     * @return  bool
     */
    public function updateCacheWithQuestionData()
    {
        if (!$this->questionDataCache->isExpired()) {
            return;
        }

        $this->getQuestionIds();

        foreach (array_chunk($this->questionIds, 100) as $questionsBatch) {

            $apiQuery = implode(';', $questionsBatch) . '?' . http_build_query([
                    'filter'   => '!.QoEavc1uFd(zfKW0kN88b0XK9TMQGPuKhq3j2tZxi',
                    'key'      => 'pMxerkFG8E257Xblt5BUHA((',
                    'order'    => 'desc',
                    'pagesize' => 100,
                    'site'     => 'stackoverflow',
                    'sort'     => 'creation',
                ]
            );

            $apiRequest = 'https://api.stackexchange.com/2.1/questions/' . $apiQuery;

            sleep(1); // throttle down
            
            $apiData = json_decode(file_get_contents('compress.zlib://' . $apiRequest, false,
                stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Accept-Encoding: gzip, deflate\r\n",
                    ],
                ]
            )));

            $this->questionDataCache->write($this->questionsData = array_merge($this->questionsData, $apiData->items));
        }
    }

    public function getQuestionIds()
    {
        $this->questionIds = $this->questionIdsCache->read();
    }

    public function setQuestionsData()
    {
        $this->questionsData = array_map(
            function($questionData) {
                return new QuestionItem($questionData);
            },
            $this->questionDataCache->read()
        );

        $this->tbodyHtmlCache->write([
            'timestamp' => time(),
            'count'     => count($this->questionsData),
            'content'   => $this->renderView('tbody.php', ['questionsData' => $this->questionsData], true),
        ]);
    }

    public function getTbodyData()
    {
        $this->tbodyData = ($this->tbodyHtmlCache->isFresh())
            ? $this->tbodyHtmlCache->read()
            : '';
    }

    public function setDataSource($dataSource) {
        $this->dataSource = (string) $dataSource;
    }

    /**
     * @return bool|string
     */
    public function renderView($view, array $viewVars = [], $returnOutput = false)
    {
        $viewPath = '../application/views/' . $view;

        if (file_exists($viewPath)) {
            foreach ($viewVars as $varName => $varValue) {
                $$varName = $varValue;
            }

            ob_start();
            require_once $viewPath;
            $viewOutput = ob_get_contents();
            ob_end_clean();

            if ($returnOutput) {
                return $viewOutput;
            } else {
                echo $viewOutput;
            }
        }
        return false;
    }

    public function debugDump($varDump = false)
    {
        if ($varDump) {
            var_dump($this);

            if ($this->questionIdsCache->isFresh()) {
                var_dump($this->questionIdsCache->read());
            }

            if ($this->questionDataCache->isFresh()) {
                var_dump($this->questionDataCache->read());
            }
        } else {
            print_r($this);

            if ($this->questionIdsCache->isFresh()) {
                print_r($this->questionIdsCache->read());
            }

            if ($this->questionDataCache->isFresh()) {
                print_r($this->questionDataCache->read());
            }
        }
    }
}
