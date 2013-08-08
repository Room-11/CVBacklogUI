<?php

if (is_array($questionsData)) {
    foreach ($questionsData as $question) {

?><tr class='<?= $question->getQuestionType() ?>'>
<td<?php

        echo (isset($question->questionData->closed_reason))
            ? " title='" . $question->getCloseReasonName() . "'>" . $question->getCloseReasonAcronymn()
            : '>-';

?></td>
<td title='Score: <?= $question->questionData->score ?> ( +<?= $question->questionData->up_vote_count ?> / -<?= $question->questionData->down_vote_count ?> )'>
<a href='<?= $question->questionData->link ?>'><?php

        echo '<span>' . $question->questionData->title . '</span>';

        if (isset($question->questionData->locked_date)) {
            echo "<i class='icon-locked' title='Locked'></i>\n";
        }

        if (isset($question->questionData->protected_date)) {
            echo "<i class='icon-protected' title='Protected'></i>\n";
        }

        if (isset($question->questionData->community_owned_date)) {
            echo "<i class='icon-wikied' title='Community wikied'></i>\n";
        }

        if (isset($question->questionData->accepted_answer_id)) {
            echo "<i class='icon-accepted' title='Has accepted answer'></i>\n";
        }

?></a></td>
<td><?= (0 == $question->questionData->close_vote_count) ? '-' : $question->questionData->close_vote_count ?></td>
<td><?= (0 == $question->questionData->delete_vote_count) ? '-' : $question->questionData->delete_vote_count ?></td>
<td><?= (0 == $question->questionData->reopen_vote_count) ? '-' : $question->questionData->reopen_vote_count ?></td>
</tr><?php

    }
}
