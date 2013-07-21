<?php

foreach ($questionsData as $question) {

?><tr class='<?php echo $question->getQuestionType(); ?>'>
<td<?php

    if (isset($question->questionData->closed_reason)) {
        echo " title='" . $question->getCloseReasonName() . "'>" . $question->getCloseReasonAcronymn();
    } else {
        echo '>-';
    }

?></td>
<td><a href='<?php echo $question->questionData->link; ?>'><?php

    // has to be a better way to truncate. CSS text-overflow??
    $shortTitle = html_entity_decode($question->questionData->title, ENT_QUOTES);
    echo (90 < strlen($shortTitle))
        ? htmlentities(substr($shortTitle, 0, 90), ENT_QUOTES) . '...'
        : $question->questionData->title;

?></a><?php

    if (isset($question->questionData->locked_date)) {
        echo "<i class='icon-locked' title='Question is locked'></i>\n";
    }

    if (isset($question->questionData->protected_date)) {
        echo "<i class='icon-protected' title='Question is protected'></i>\n";
    }

    if (isset($question->questionData->community_owned_date)) {
        echo "<i class='icon-wikied' title='Question is community wikied'></i>\n";
    }

    if (isset($question->questionData->accepted_answer_id)) {
        echo "<i class='icon-accepted' title='Question has an accepted answer'></i>\n";
    }

?></td>
<td><?php echo (0 == $question->questionData->close_vote_count) ? '-' : $question->questionData->close_vote_count; ?></td>
<td><?php echo (0 == $question->questionData->delete_vote_count) ? '-' : $question->questionData->delete_vote_count; ?></td>
<td><?php echo (0 == $question->questionData->reopen_vote_count) ? '-' : $question->questionData->reopen_vote_count; ?></td>
</tr><?php

}