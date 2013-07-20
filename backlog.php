<?php

require_once __DIR__ . '/json_file_cache.php';

/**
* Some description which I can't think of
*
* @oauthor      Kyra D. <kyra@existing.me>
* @method       void __construct(string $dataSource)
* @method       void fetchChatQuestionIds(void)
* @method       void fetchApiQuestionIds(int $page)
* @method       void fetchQuestionData(void)
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
    /**
     * @var array
     */
    public $questionIds = [];

    /**
     * @var array
     */
    public $questionsData = [];

    /**
     * @var string
     */
    private $dataSource;

    /**
     * @var JsonFileCache
     */
    private $questionIdsCache;

    /**
     * @var JsonFileCache
     */
    private $questionDataCache;

    /**
    * Sets data source for current instance
    *
    * @access   public
     * @param    string  $dataSource
     * @param    string  $cacheDir
    */
    public function __construct($dataSource, $cacheDir) {
        $this->setDataSource($dataSource);

        $dataSourceDir           = $cacheDir . '/' . $this->dataSource;
        $this->questionIdsCache  = new JsonFileCache($dataSourceDir . '_backlog_ids.cache.json', 900);
        $this->questionDataCache = new JsonFileCache($dataSourceDir . '_backlog_data.cache.json', 120);
    }

    /**
    * Updates the chat ID cache file with most current question IDs
    *
    * @access   public
    */
    public function fetchChatQuestionIds() {
        if ($this->questionIdsCache->isExpired()) {
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
    * Updates the API ID cache file with most current question IDs
    *
    * @access   public
    * @param    int     $page
    */
    public function fetchApiQuestionIds($page = 1) {
        if ($this->questionIdsCache->isExpired()) {
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
            $this->questionIdsCache->write($this->questionIds);
        }
    }

    /**
    * Updates the data cache file with most current question data
    *
    * @access   public
    */
    public function fetchQuestionData() {
        if ($this->questionDataCache->isExpired()) {
            return;
        }

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

            $this->questionDataCache->write($this->questionsData = array_merge($this->questionsData, $apiData->items));
        }
    }

    /**
    * Gets question IDs for current data source
    *
    * @access   public
    */
    public function getQuestionIds() {
        $this->questionIds = $this->questionIdsCache->read();
    }

    /**
    * Gets dataset for current data source
    *
    * @access   public
    */
    public function getQuestionData() {
        $this->questionsData = $this->questionDataCache->read();
    }

    /**
    * Sets the data source
    *
    * @access   public
    * @param    string  $dataSource
    */
    public function setDataSource($dataSource) {
        $this->dataSource = (string) $dataSource;
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
