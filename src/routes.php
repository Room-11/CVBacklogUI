<?php

/* displays data for a source in UI */
$app->route('GET', '/backlog/$src', 'CvRing\Backlog\Controller\BacklogController::indexAction');
$app->route('GET', '/backlog', 'CvRing\Backlog\Controller\BacklogController::indexAction');

/* outputs raw debug dump for a source */
$app->route('GET', '/backlog/$src/debug', 'CvRing\Backlog\Controller\BacklogController::debugAction');

/* refreshes data table content for a source */
$app->route('GET', '/backlog/$src/ajax', 'CvRing\Backlog\Controller\BacklogController::ajaxRefreshAction');

/* refreshes cached data for a source, only allowed via CLI */
$app->route('GET', '/backlog/$src/cron', 'CvRing\Backlog\Controller\BacklogController::cacheRefreshAction');
