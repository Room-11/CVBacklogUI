<?php

/* displays data for a source in UI */
$app->route('GET', '/backlog/$src', 'CvRing\Backlog\Presenter\BacklogPresenter::indexAction');
$app->route('GET', '/backlog', 'CvRing\Backlog\Presenter\BacklogPresenter::indexAction');

/* outputs raw debug dump for a source */
$app->route('GET', '/backlog/$src/debug/$qid', 'CvRing\Backlog\Presenter\BacklogPresenter::debugAction');
$app->route('GET', '/backlog/$src/debug', 'CvRing\Backlog\Presenter\BacklogPresenter::debugAction');

/* refreshes data table content for a source */
$app->route('GET', '/backlog/$src/ajax', 'CvRing\Backlog\Presenter\BacklogPresenter::ajaxRefreshAction');

/* refreshes cached data for a source, only allowed via CLI */
$app->route('GET', '/backlog/$src/cron', 'CvRing\Backlog\Presenter\BacklogPresenter::cacheRefreshAction');
