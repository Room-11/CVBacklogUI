<?php

/**
* Some description which I can't think of
*
* @author       Kyra D. <kyra@existing.me>
* @copyright    2013 © Kyra D.
* @method       void __construct(string $dataSource)
* @method       void fetchChatQuestionIds(void)
* @method       void fetchApiQuestionIds(int $page)
* @method       void fetchQuestionData(void)
* @method       void saveCacheFile(string $cacheFilePath, object $cacheFileContents)
* @method       void getQuestionIds(void)
* @method       void getQuestionData(void)
* @method       void setCacheDir(string $cacheDir)
* @method       void setDataSource(string $dataSource)
* @method       void setIdsCacheFilePath(void)
* @method       void setDataCacheFilePath(void)
* @method       void setCacheExpirations(array $expirationTimes)
* @method       void checkCachExpiration(string $cacheFilename, int $expirationTime)
* @method       int getQuestionsCount(void)
* @method       string getCloseReasonName(string $closeReason)
* @method       string getCloseReasonAcronymn(string $closeReason)
* @method       string getQuestionType(object $resultRow)
* @todo         Do it all better =o(
* @uses         -
*/
class Backlog
{

    public $questionsData = [];

    /**
    * Sets data source for current instance
    *
    * @access   public
    * @param    string  $dataSource
    */
    public function __construct($dataSource) {
        $this->setDataSource($dataSource);
    }

    /**
    * Updates the chat ID cache file with most current question IDs
    *
    * @access   public
    */
    public function fetchChatQuestionIds() {
        $this->checkCachExpiration($this->idsCacheFilename, $this->idsCacheExpiration);
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
        $this->saveCacheFile($this->idsCacheFilename, $this->questionIds);
    }

    /**
    * Updates the API ID cache file with most current question IDs
    *
    * @access   public
    * @param    int     $page
    */
    public function fetchApiQuestionIds($page = 1) {
        $this->checkCachExpiration($this->idsCacheFilename, $this->idsCacheExpiration);
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

        if ($apiData->has_more && $page !== 15) {
            $this->fetchApiQuestionIds(++$page);
        } else {
            $this->saveCacheFile($this->idsCacheFilename, $this->questionIds);
        }
    }

    /**
    * Updates the data cache file with most current question data
    *
    * @access   public
    */
    public function fetchQuestionData() {
        $this->checkCachExpiration($this->dataCacheFilename, $this->dataCacheExpiration);
        $this->getQuestionIds();
        foreach (array_chunk($this->questionIds, 100) as $questionsBatch) {

            $apiQuery = implode(';', $questionsBatch) . '?' . http_build_query([
                    'filter'   => '!0b1yGv(T6fJS-9tIpIsZ3LdBYJEbeFBSlD',
                    'key'      => 'pMxerkFG8E257Xblt5BUHA((',
                    'order'    => 'desc',
                    'pagesize' => 100,
                    'site'     => 'stackoverflow',
                    'sort'     => 'creation',
                ]
            );

            $apiRequest = 'https://api.stackexchange.com/2.1/questions/' . $apiQuery;

            $apiData = json_decode(file_get_contents('compress.zlib://' . $apiRequest, false,
                stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Accept-Encoding: gzip, deflate\r\n",
                    ],
                ]
            )));

            $this->questionsData = array_merge($this->questionsData, $apiData->items);
            $this->saveCacheFile($this->dataCacheFilename, $this->questionsData);
        }
    }

    /**
    * Saves a cache file
    *
    * @access   public
    * @param    string  $cacheFilePath
    * @param    object  $cacheFileContents
    */
    public function saveCacheFile($cacheFilePath, $cacheFileContents) {
        file_put_contents($cacheFilePath, json_encode($cacheFileContents), LOCK_EX);
        chmod($cacheFilePath, 0604);
    }

    /**
    * Gets question IDs for current data source
    *
    * @access   public
    */
    public function getQuestionIds() {
        $this->questionIds = json_decode(file_get_contents($this->idsCacheFilename));
    }

    /**
    * Gets dataset for current data source
    *
    * @access   public
    */
    public function getQuestionData() {
        $this->questionsData = json_decode(file_get_contents($this->dataCacheFilename));
    }

    /**
    * Sets the cache dir and generates cache paths
    *
    * @access   public
    * @param    string  $cacheDir
    */
    public function setCacheDir($cacheDir) {
        if (is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;
            $this->setIdsCacheFilePath();
            $this->setDataCacheFilePath();
        }
    }

    /**
    * Sets the data source
    *
    * @access   public
    * @param    string  $dataSource
    */
    public function setDataSource($dataSource) {
        $this->dataSource = $dataSource;
    }

    /**
    * Sets path for the question IDs cache file
    *
    * @access   public
    */
    public function setIdsCacheFilePath() {
        $this->idsCacheFilename = $this->cacheDir . '/'
            . $this->dataSource . '_backlog_ids.cache.json';
    }

    /**
    * Sets path for the questions data cache file
    *
    * @access   public
    */
    public function setDataCacheFilePath() {
        $this->dataCacheFilename = $this->cacheDir . '/'
            . $this->dataSource . '_backlog_data.cache.json';
    }

    /**
    * Sets the expiration in seconds for the cached files
    *
    * @access   public
    * @param    array   $expirationTimes  The expiration times for cache files
    */
    public function setCacheExpirations(Array $expirationTimes) {
        if (array_key_exists('ids', $expirationTimes)) {
            $this->idsCacheExpiration = $expirationTimes['ids'];
        }
        if (array_key_exists('data', $expirationTimes)) {
            $this->dataCacheExpiration = $expirationTimes['data'];
        }
    }

    /**
    * Checks if cache file is expired and if not then bail
    *
    * @access   public
    * @param    string  $cacheFilename
    * @param    int     $expirationTime
    */
    public function checkCachExpiration($cacheFilename, $expirationTime) {
        if (file_exists($cacheFilename) && $expirationTime > (time() - filemtime($cacheFilename))) {
            exit;
        }
    }

    /**
    * Returns the number of questions in the dataset
    *
    * @access   public
    * @return   int
    */
    public function getQuestionsCount() {
        if (!property_exists($this, 'questionsCount')) {
            $this->questionsCount = count($this->questionsData);
        }
        return $this->questionsCount;
    }

    /**
    * Get close reason name for close reason
    *
    * @access   public
    * @param    string  $closeReason
    * @return   string
    */
    public function getCloseReasonName($closeReason) {
        return [
            'dupe' => 'Duplicate',
            'ot'   => 'Off-Topic',
            'pob'  => 'Primarily Opinion Based',
            'tb'   => 'Too Broad',
            'uwya' => 'Unclear What You&#39;re Asking',
        ][$this->getCloseReasonAcronymn($closeReason)];
    }

    /**
    * Get close type acronymn for close reason
    *
    * @access   public
    * @param    string  $closeReason
    * @return   string
    */
    public function getCloseReasonAcronymn($closeReason) {
        switch ($closeReason) {
            case 'duplicate':
            case 'exact duplicate':
                return 'dupe';
                break;
            case 'off topic':
            case 'off-topic':
            case 'too localized':
                return 'ot';
                break;
            case 'not constructive':
            case 'primarily opinion-based':
                return 'pob';
                break;
            case 'too broad':
                return 'tb';
                break;
            case 'not a real question':
            case 'unclear what you&#39;re asking':
                return 'uwya';
                break;
        }
    }

    /**
    * Get the question type
    *
    * @access   public
    * @param    object  $resultRow
    * @return   string
    */
    public function getQuestionType(stdClass $resultRow) {
        $type = 'rv';
        if (isset($resultRow->closed_reason)) {
            $type = 'delv';
            if (0 < $resultRow->reopen_vote_count) {
                $type ='ro';
            } else if (!isset($resultRow->accepted_answer_id) && 1 > $resultRow->score && !isset($resultRow->locked_date) && 'dupe' !== $this->getCloseReasonAcronymn($resultRow->closed_reason)) {

                $type = 'adelv';
                $continue = true;

                if (isset($resultRow->last_edit_date) && (777600 <= (time() - $resultRow->last_edit_date))) {
                    $type = 'cv';
                    $continue = false;
                }

                if ($continue && isset($resultRow->answers)) {
                    foreach ($resultRow->answers as $answer) {
                        if (0 < $answer->score) {
                            $type = 'cv';
                            break;
                        }
                    }
                }
            }
        } else if (0 < $resultRow->close_vote_count) {
            $type = 'cv';
        }
        return $type;
    }

}
