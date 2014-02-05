<?php

namespace CvRing\Backlog;

/**
 * Gathers additional metadata about a question.
 *
 * @author Kyra D. <kyra@existing.me>
 * {@internal Don't trust API to sanitize data, do it ourselves }}
 * @todo optimize/refactor the question types rules
 */
class QuestionItem
{
    /** @var int */
    private $accepted_answer_id;

    /** @var int */
    private $answer_count;

    /** @var int */
    private $bounty_closes_date;

    /** @var int */
    private $close_vote_count;

    /** @var int */
    private $closed_date;

    /** @var string */
    private $closed_reason;

    /** @var array */
    private $comments;

    /** @var int */
    private $community_owned_date;

    /** @var int */
    private $creation_date;

    /** @var int */
    private $delete_vote_count;

    /** @var int */
    private $down_vote_count;

    /** @var int */
    private $is_answered;

    /** @var int */
    private $last_edit_date;

    /** @var string */
    private $link;

    /** @var int */
    private $locked_date;

    /** @var string */
    private $migrated_from;

    /** @var string */
    private $migrated_to;

    /** @var \stdClass */
    private $owner;

    /** @var int */
    private $protected_date;

    /** @var int */
    private $reopen_vote_count;

    /** @var int */
    private $score;

    /** @var string */
    private $title;

    /** @var int */
    private $up_vote_count;

    /** @var int */
    private $view_count;

    /**
     * @param \stdClass $questionData
     * @throws \UnexpectedValueException
     */
    public function __construct(\stdClass $questionData)
    {
        foreach ($questionData as $property => $value) {
            if (!property_exists($this, $property)) {
                throw new \UnexpectedValueException("unknown question property '$property'");
            }
            $this->$property = $value;
        }
    }

    /** @return bool */
    public function isAccepted()
    {
        return isset($this->accepted_answer_id);
    }

    /** @return bool */
    public function isBountied()
    {
        return isset($this->bounty_closes_date) && (time() >= $this->bounty_closes_date);
    }

    /** @return bool */
    public function isWikied()
    {
        return isset($this->community_owned_date);
    }

    /** @return bool */
    public function isLocked()
    {
        return isset($this->locked_date);
    }

    /** @return bool */
    public function isProtected()
    {
        return isset($this->protected_date);
    }

    /** @return int */
    public function getCloseVoteCount()
    {
        return (int)$this->close_vote_count;
    }

    /** @return bool|string */
    public function getCloseReasonAcronym()
    {
        switch ($this->closed_reason) {
            case 'duplicate':
            case 'exact duplicate':
                return 'dupe';
            case 'off topic':
            case 'off-topic':
            case 'too localized':
                return 'ot';
            case 'not constructive':
            case 'primarily opinion-based':
                return 'pob';
            case 'too broad':
                return 'tb';
            case 'not a real question':
            case "unclear what you're asking":
                return 'uwya';
        }

        return false;
    }

    /** @return string */
    public function getCloseReasonName()
    {
        static $reasons = [
            'dupe' => 'Duplicate',
            'ot'   => 'Off-Topic',
            'pob'  => 'Primarily Opinion Based',
            'tb'   => 'Too Broad',
            'uwya' => "Unclear What You're Asking"
        ];

        return $reasons[$this->getCloseReasonAcronym()];
    }

    /** @return int */
    public function getDeleteVoteCount()
    {
        return (int)$this->delete_vote_count;
    }

    /** @return int */
    public function getDownVoteCount()
    {
        return (int)$this->down_vote_count;
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getLink()
    {
        if (!preg_match('~^https?://stackoverflow.com/questions/\d+/[a-z\d-]+$~', $this->link)) {
            throw new \UnexpectedValueException("invalid link format '$this->link'");
        }

        return $this->link;
    }

    /** @return string */
    public function getQuestionType()
    {
        if ($this->isCloseQuestion()) {
            return 'cv';
        }

        if ($this->isAutoDeleteQuestion()) {
            return 'adelv';
        }

        if ($this->isDeleteQuestion()) {
            return 'delv';
        }

        /** {@internal Close voted questions with a bounty can't be
         * further closed nor deleted, so mark them as review }}
         */
        if ($this->isReviewQuestion() || $this->isBountied()) {
            return 'rv';
        }

        if ($this->isReopenQuestion()) {
            return 'ro';
        }

        return 'none';
    }

    /** @return int */
    public function getReOpenVoteCount()
    {
        return (int)$this->reopen_vote_count;
    }

    /** @return int */
    public function getScore()
    {
        return (int)$this->score;
    }

    /** @return string */
    public function getUnsafeTitle()
    {
        return $this->title;
    }

    /** @return int */
    public function getUpVoteCount()
    {
        return (int)$this->up_vote_count;
    }

    /**
     * {@internal Auto-deletion rules: http://meta.stackoverflow.com/a/92006/204512 }}
     * @return bool
     */
    private function isAutoDeleteQuestion()
    {
        $questionAge = (time() - $this->creation_date);

        if (2592000 < $questionAge
            && isset($this->migrated_to)) {
            return true;
        }

        if (2592000 < $questionAge
            && 0 > $this->score
            && 0 === $this->answer_count
            && !isset($this->locked_date)) {
            return true;
        }

        if (31536000 < $questionAge
            && (0 === $this->score
                || (1 <= $this->score && isset($this->owner) && 'does_not_exist' === $this->owner->user_type))
            && 0 ===$this->answer_count
            && !isset($this->locked_date)
            && isset($this->comments) && 1 >= count($this->comments)
            && $this->view_count <= (($questionAge / 86400) * 1.5)) {
            return true;
        }

        if (!$this->is_answered
            && 0 === $this->reopen_vote_count
            && 0 >= $this->score
            && (isset($this->closed_reason) && 'dupe' !== $this->getCloseReasonAcronym())
            && !isset($this->locked_date)
            && (isset($this->closed_date) && 777600 < (time() - $this->closed_date))
            && (isset($this->last_edit_date) && 777600 > (time() - $this->last_edit_date))) {
            return true;
        }

        return false;
    }

    /** @return bool */
    private function isCloseQuestion()
    {
        return (!isset($this->closed_date)
            && 0 < $this->close_vote_count
            && !$this->isBountied());
    }

    /**
     * {@internal Deletion rules: http://meta.stackoverflow.com/a/5222/204512 }}
     * @return bool
     */
    private function isDeleteQuestion()
    {
        return (isset($this->closed_date)
            && ((172800 < (time() - $this->closed_date) && 0 === $this->reopen_vote_count)
                || -3 >= $this->score));
    }

    /** @return bool */
    private function isReopenQuestion()
    {
        return (isset($this->closed_date)
            && 0 < $this->reopen_vote_count);
    }

    /** @return bool */
    private function isReviewQuestion()
    {
        return ((!isset($this->closed_date)
            && 0 === $this->close_vote_count
            && 0 === $this->delete_vote_count
            && 0 === $this->reopen_vote_count
            && 1 < $this->down_vote_count)
            || isset($this->migrated_from));
    }
}
