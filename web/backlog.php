<?php


// so paths work nice with CLI
chdir(__DIR__);


require '../application/classes/FileCache.php';
require '../application/classes/QuestionItem.php';
require '../application/classes//Backlog.php';


// compress output
ob_start('ob_gzhandler');


// are we requesting chatroom data source
$chatRoomSource = (isset($_GET['chatroom']) || (isset($argv[1]) && 'chatroom' === $argv[1]));


// set data source options
$backlog = ($chatRoomSource)
    ? new Backlog('chat', './assets/cache', ['ids' => 900, 'data' => 120,])
    : new Backlog('api', './assets/cache', ['ids' => 300, 'data' => 120,]);


// only allow CLI to perform cache update
if ('cli' === php_sapi_name()) {
    if ($chatRoomSource) {
        $backlog->fetchChatQuestionIds();
    } else {
        $backlog->fetchApiQuestionIds();
    }
    $backlog->updateCacheWithQuestionData();
    $backlog->setQuestionsData();
    exit;
}


// get the TBODY data
$backlog->getTbodyData();


// dump cached data and object
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $backlog->debugDump('var_dump' === $_GET['debug']);
    exit;
}


// Content Security Policy (http://www.w3.org/TR/CSP/)
$cspPolicy = "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
    . "object-src 'none'; "
    . "style-src 'self' https://fonts.googleapis.com; "
    . "img-src 'self'; "
    . "media-src 'none'; "
    . "frame-src 'none'; "
    . "font-src 'self' https://themes.googleusercontent.com; "
    . "connect-src 'self'";

header('Content-Security-Policy: ' . $cspPolicy);
header('Cache Control: max-age=3600, must-revalidate, private');
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noarchive, noodp, nofollow, noindex');


?>
<!DOCTYPE html>
<html lang='en' xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta charset='utf-8' />
<title>cv-pls Backlog (beta)</title>
<meta content='A fancy smancy cv-pls backlog interface' name='description' />
<meta content='noimageindex, noodp, noarchive, nofollow, noindex' name='robots' />
<meta content='width=device-width' name='viewport' />
<link href='https://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css' />
<link href='/assets/less/main.less' rel='stylesheet' />
<link href='/assets/img/favicon.png' rel='icon' type='image/png' />
<link href='/assets/img/apple-touch-icon.png' rel='apple-touch-icon-precomposed image_src' />
</head>
<?php flush(); ?>
<body>
<div id='data-sources'>
<strong>Backlog Source</strong>
<small><?php

echo ($chatRoomSource)
    ? "<a href='/backlog' title='Pull from the Stack Exchange API'>API</a> <small>/</small> Chatroom"
    : "API <small>/</small> <a href='?chatroom' title='Pull from the PHP chatroom transcript'>Chatroom</a>";

?></small>
</div>
<h1 id='page-top'><a href='/backlog'><img alt='[cv-ring] logo' src='/assets/img/favicon.png'>[cv-pls] Backlog <small>1.1-beta</small></a></h1>
<form class='form-inline' id='options-form'>
<fieldset>
<legend>Options</legend>
<strong>Hide</strong>
<label class='checkbox cv'><input id='check-cv' type='checkbox' />close</label>
<label class='checkbox delv'><input id='check-delv' type='checkbox' />delete</label>
<label class='checkbox ro'><input id='check-ro' type='checkbox' />re-open</label>
<label class='checkbox rv'><input id='check-rv' type='checkbox' />review</label>
<label class='checkbox adelv'><input id='check-adelv' type='checkbox' />auto-deleting</label><br />
<label class='checkbox'><input checked='checked' id='check-tabs' type='checkbox' />open in tabs</label>
<!--<span>refresh every
<select id='refresh-interval'>
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
</span>-->
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
<div id='sticky-container'>
<table class='table table-bordered table-condensed' id='data-table-head'><thead><tr>
<th><a class='icon-arrow-up goto-top' href='#page-top'>Top</a></th>
<th><small>
<strong>Viewing</strong> <span id='questions-count'>0</span> / <?php

echo (isset($backlog->tbodyData->count))
    ? $backlog->tbodyData->count
    : 0;

?><strong>|</strong> <span id='questions-timestamp'><?php

$timestamp = (isset($backlog->tbodyData->timestamp))
    ? $backlog->tbodyData->timestamp
    : time();

$updated = new DateTime();
$updated->setTimestamp($timestamp);
echo $updated->format(DateTime::W3C);

?></span>
</small></th>
<th colspan='3'>Votes</th>
</tr><tr>
<th><a href='https://github.com/cv-pls/site/blob/cv-pls.com/cv-pls_chat_docs.md#close-vote-acronyms' target='_blank'>Reason</a></th>
<th>Title</th>
<th>Close</th>
<th>Delete</th>
<th>Open</th>
</tr></thead></table>
</div>
<table class='scroll table table-bordered table-condensed table-hover' id='data-table'>
<tbody id='data-table-body'><?php

echo (empty($backlog->tbodyData->content))
    ? "<tr class='error-message'><td>Cache file(s) currently unavailable</td></tr>\n"
    : $backlog->tbodyData->content;

?>
</tbody></table>
<div id='footer'>
<small>API data provided by the <a href='https://stackexchange.com/' target='_blank'>Stack Exchange Network</a>.
Contribute to the <a href='https://github.com/PHP-Chat/CVBacklogUI' target='_blank'>CVBacklogUI</a> Github project.<br />
Made by and for the Stack Overflow <a href='http://chat.stackoverflow.com/rooms/11/php' target='_blank'>PHP chatroom</a>.</small>
</div>
<script src='/assets/jscc/main.jscc'></script>
</body>
</html>
