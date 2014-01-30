<?php

namespace CvRing\Backlog;

/**
 * Fetches and serves backlog data
 *
 * @author Kyra D. <kyra@existing.me>
 */
class BacklogCore
{
    /** @var \CvRing\Backlog\FileCache */
    private $cache;

    /** @param FileCache $cache */
    public function __construct(FileCache $cache)
    {
        $this->cache = $cache;
    }

    public function setQuestionsData()
    {
        //$this->questionsData = array_map(
        //    function ($questionData) {
        //        return new QuestionItem($questionData);
        //    },
        //    $this->questionDataCache->read()
        //);
    }
}
