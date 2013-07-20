<?php

include './backlog.php';


// compress output
ob_start('ob_gzhandler');


// just caching some vars
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$chatRoomSource = (isset($_GET['chatroom']));


// set data source options
if ($chatRoomSource) {
    $backlog = new Backlog('chat', './assets/cache');
} else {
    $backlog = new Backlog('api', './assets/cache');
}


// only have cron perform a cache update
if ('cv-pls' === $userAgent) {
    if ($chatRoomSource) {
        $backlog->setCacheExpirations(['ids' => 900, 'data' => 120,]);
        $backlog->fetchChatQuestionIds();
    } else {
        $backlog->setCacheExpirations(['ids' => 300, 'data' => 120,]);
        $backlog->fetchApiQuestionIds();
    }
    $backlog->fetchQuestionData();
}


// get the cached dataset
$backlog->getQuestionData();


// allow dumping of dataset
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    var_dump($backlog);
    exit;
}


// set some headers, some are meant for possible future needs
header('Cache Control: max-age=3600, must-revalidate, private'); // cache settings
//header('Content-Type: application/xhtml+xml; charset=utf-8');  // XHTML5 (buggy)
header('Content-Type: text/html; charset=utf-8');                // document Mime
header('X-Frame-Options: DENY');                                 // prevent click-jacking attacks
header('X-Robots-Tag: noarchive, noodp, nofollow, index');       // search engine restrictions

// Determine If Internet Explorer
if (false !== strpos($userAgent, 'MSIE'))
{
    header('MSThemeCompatible: no');           // disable theming
    header('X-Content-Type-Options: nosniff'); // prevent MIME sniffing
    header('X-XSS-Protection: 1; mode=block'); // prevent XSS Attacks
}

// Content Security Policy (http://www.w3.org/TR/CSP/)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; object-src 'none'; style-src 'self' https://fonts.googleapis.com; img-src 'self'; media-src 'none'; frame-src 'none'; font-src 'self' https://themes.googleusercontent.com; connect-src 'self'");


?>
<!DOCTYPE html>
<html lang='en' xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta charset='utf-8' />
<title>cv-pls Backlog beta (un-official)</title>
<meta content='A fancy smancy cv-pls backlog interface' name='description' />
<meta content='noimageindex, noodp, noarchive, nofollow, index' name='robots' />
<meta content='width=device-width' name='viewport' />
<!--[if IE]>
<meta content='#ffffff' name='msapplication-TileColor' />
<meta content='/assets/img/windows-metro-icon.png' name='msapplication-TileImage' />
<meta content='cv-pls' name='application-name' />
<link href='/assets/img/favicon.ico' rel='shortcut icon' />
<![endif]-->
<link href='https://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css' />
<link href='/assets/less/main.less' rel='stylesheet' />
<link href='/assets/img/favicon.png' rel='icon' type='image/png' />
<link href='/assets/img/apple-touch-icon.png' rel='apple-touch-icon-precomposed image_src' />
<!--[if lt IE 9]>
<script src='//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6.2/html5shiv.js'></script>
<![endif]-->
</head>
<?php flush(); ?>
<body>
<div id='data-sources'>
<strong>Backlog Source</strong><br />
<small>
<?php

echo ($chatRoomSource)
    ? "<a href='/backlog' title='Pull from the Stack Exchange API'>API</a> <small>/</small> Chatroom"
    : "API <small>/</small> <a href='?chatroom' title='Pull from the PHP chatroom transcript'>Chatroom</a>";

?>
</small>
</div>
<h1>[cv-pls] Backlog <small>beta <em>(un-official)</em></small></h1>
<form class='form-inline' id='options-form'>
<fieldset>
<legend>Options</legend>
<strong>Hide</strong>
<label class='checkbox cv'><input id='check-cv' type='checkbox' />close</label>
<label class='checkbox delv'><input id='check-delv' type='checkbox' />delete</label>
<label class='checkbox ro'><input id='check-ro' type='checkbox' />re-open</label>
<label class='checkbox rv'><input id='check-rv' type='checkbox' />review</label>
<label class='checkbox adelv'><input id='check-adelv' type='checkbox' />auto-deleting</label><br />
<label class='checkbox'><input id='check-scroll' type='checkbox' />scrolled list</label>
<label class='checkbox'><input id='check-tabs' type='checkbox' />open in tabs</label>
<span class='disabled'>refresh every
<select disabled='disabled' id='refresh-interval'>
<option value='0' selected='selected'>0</option>
<option value='2'>2</option>
<option value='4'>4</option>
<option value='6'>6</option>
<option value='8'>8</option>
<option value='10'>10</option>
<option value='12'>12</option>
<option value='14'>14</option>
<option value='16'>16</option>
</select> mins
</span>
<button class='btn btn-link btn-small' id='reset-options' type='button'>[ reset options ]</button>
</fieldset>
</form>
<form id='legend'>
<fieldset>
<legend>Legend</legend>
<span class='icon-locked' title='Question is locked'>locked</span>
<span class='icon-protected' title='Question is protected'>protected</span>
<span class='icon-wikied' title='Question is community wikied'>wikied</span>
<span class='icon-accepted' title='Question has an accepted answer'>accepted</span>
</fieldset>
</form>
<table class='scroll table table-bordered table-condensed table-hover table-striped' id='data-table'>
<thead><tr>
<th class='stats' colspan='2'>
<small><strong>Displaying</strong> <span id='questions-count'>0</span> / <?php echo $backlog->getQuestionsCount(); ?></small>
</th>
<th class='c1'>Votes</th>
</tr><tr>
<th class='c4 border'>
<div class='go-top'><div>
<a class='icon-up' href='#menu' title='Go to top'></a><br />
<small>G<br />O</small><br />
<a class='icon-down' href='#footer' title='Go to bottom'></a></div></div>
<a href='https://github.com/cv-pls/site/blob/cv-pls.com/cv-pls_chat_docs.md#close-vote-acronyms' target='_blank'>Reason</a></th>
<th class='c5'>Title</th><th class='c3'>Close</th><th class='c3'>Delete</th><th class='c2'>Open</th>
</tr></thead>
<tbody id='data-table-body'><?php

foreach ($backlog->questionsData as $resultRow) {

?><tr class='<?php echo $backlog->getQuestionType($resultRow); ?>'>
<td class='c4'<?php

    if (isset($resultRow->closed_reason)) {
        echo " title='" . $backlog->getCloseReasonName($resultRow->closed_reason) . "'>"
            . $backlog->getCloseReasonAcronymn($resultRow->closed_reason);
    } else {
        echo '>-';
    }

?></td>
<td class='title'><a href='<?php echo $resultRow->link; ?>'><?php

    // has to be a better way to truncate. CSS text-overflow??
    $shortTitle = html_entity_decode($resultRow->title, ENT_QUOTES);
    echo (90 < strlen($shortTitle))
        ? htmlentities(substr($shortTitle, 0, 90), ENT_QUOTES) . '...'
        : $resultRow->title;

?></a><?php

    if (isset($resultRow->locked_date)) {
        echo "<i class='icon-locked' title='Question is locked'></i>\n";
    }

    if (isset($resultRow->protected_date)) {
        echo "<i class='icon-protected' title='Question is protected'></i>\n";
    }

    if (isset($resultRow->community_owned_date)) {
        echo "<i class='icon-wikied' title='Question is community wikied'></i>\n";
    }

    if (isset($resultRow->accepted_answer_id)) {
        echo "<i class='icon-accepted' title='Question has an accepted answer'></i>\n";
    }

?></td>
<td class='c3'><?php echo (0 == $resultRow->close_vote_count) ? '-' : $resultRow->close_vote_count; ?></td>
<td class='c3'><?php echo (0 == $resultRow->delete_vote_count) ? '-' : $resultRow->delete_vote_count; ?></td>
<td class='c3'><?php echo (0 == $resultRow->reopen_vote_count) ? '-' : $resultRow->reopen_vote_count; ?></td>
</tr><?php

}

if (0 >= $backlog->getQuestionsCount()) {

?>
<tr colspan='5'><td class='failure'>Backlog data request failed. Try again in a few minutes.</td></tr>
<?php

}

?>
</tbody></table>
<div id='footer'>
<small>API data provided by the <a href='https://stackexchange.com/' target='_blank'>Stack Exchange Network</a>.
Official Github <a href='https://github.com/PHP-Chat/CVBacklogUI' target='_blank'>CVBacklogUI</a> project.<br />
Made by and for the Stack Overflow <a href='http://chat.stackoverflow.com/rooms/11/php' target='_blank'>PHP chatroom</a>.</small></div>
<script src='/assets/jscc/main.jscc'></script>
</body>
</html>
