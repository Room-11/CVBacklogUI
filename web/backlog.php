<?php


require '../application/classes/FileCache.php';
require '../application/classes/QuestionItem.php';
require '../application/classes//Backlog.php';


// compress output
ob_start('ob_gzhandler');


// just caching some vars
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$chatRoomSource = (isset($_GET['chatroom']));


// set data source options
if ($chatRoomSource) {
    $backlog = new Backlog('chat', './assets/cache', ['ids' => 900, 'data' => 120,]);
} else {
    $backlog = new Backlog('api', './assets/cache', ['ids' => 300, 'data' => 120,]);
}


// only have cron perform a cache update
if ('cv-pls' === $userAgent) {
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


if (false !== strpos($userAgent, 'MSIE')) {
    header('MSThemeCompatible: no');           // disable theming
    header('X-Content-Type-Options: nosniff'); // prevent MIME sniffing
    header('X-XSS-Protection: 1; mode=block'); // prevent XSS attacks
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
<h1>[cv-pls] Backlog <small>beta</small></h1>
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
<table class='scroll table table-bordered table-condensed table-hover' id='data-table'>
<thead><tr>
<th class='stats' colspan='2'>
<small><strong>Displaying</strong> <span id='questions-count'>0</span> / <?php

echo (empty($backlog->tbodyData->count)) ? 0 : $backlog->tbodyData->count;

?></small>
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

echo (empty($backlog->tbodyData->content))
    ? "<tr><td colspan='5' class='error-message'>Cache file(s) currently unavailable</td></tr>\n"
    : $backlog->tbodyData->content;

?>
</tbody></table>
<div id='footer'>
<small>API data provided by the <a href='https://stackexchange.com/' target='_blank'>Stack Exchange Network</a>.
Official Github <a href='https://github.com/PHP-Chat/CVBacklogUI' target='_blank'>CVBacklogUI</a> project.<br />
Made by and for the Stack Overflow <a href='http://chat.stackoverflow.com/rooms/11/php' target='_blank'>PHP chatroom</a>.</small></div>
<script src='/assets/jscc/main.jscc'></script>
</body>
</html>
