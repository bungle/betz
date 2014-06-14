<?php
get('/points', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/points.phtml');
    $view->title = 'Pistetilanne';
    $view->menu = 'points';
    $points = cache_fetch(TOURNAMENT_ID . ':points');
    if ($points === false) {
        $points = db\stats\points(isset($_GET['order']) ? $_GET['order'] : 'total');
        cache_store(TOURNAMENT_ID . ':points', $points);
    }
    $view->points = $points;
    $scorers = cache_fetch(TOURNAMENT_ID . ':scorers');
    if ($scorers === false) {
        $scorers = db\stats\scorers();
        cache_store(TOURNAMENT_ID . ':scorers', $scorers);
    }
    $view->scorers = $scorers;
    $view->online = db\users\visited(username, 'Pistetilanne');
    die($view);
});
get('/points/%p', function($username) {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $username = urldecode($username);
    $view = new view(DIR . '/views/points.user.phtml');
    $view->title = username === $username ? 'Omat pisteet' : "Pistetilanne ($username)";
    $view->title_games = username === $username ? 'Omat otteluveikkaukset' : "Otteluveikkaukset";
    $view->menu = 'points';
    $points = cache_fetch(TOURNAMENT_ID . ':points');
    if ($points === false) {
        $points = db\stats\points(isset($_GET['order']) ? $_GET['order'] : 'total');
        cache_store(TOURNAMENT_ID . ':points', $points);
    }
    $points = array_filter($points, function($point) use ($username) {
        return $point['username'] === $username;
    });
    $view->points = $points;
    $scorers = cache_fetch(TOURNAMENT_ID . ':scorers');
    if ($scorers === false) {
        $scorers = db\stats\scorers();
        cache_store(TOURNAMENT_ID . ':scorers', $scorers);
    }
    $view->scorers = $scorers;
    $view->pointsuser = $username;
    $view->games = db\stats\games($username);
    $view->online = db\users\visited(username, $view->title);
    die($view);
});
get('/stats', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/stats.phtml');
    $view->title = 'Tilastot';
    $view->menu = 'stats';
    $view->online = db\users\visited(username, 'Tilastot');
    die($view);
});

get('/stats/history', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    header("Content-type: text/json");
    $points = cache_fetch(TOURNAMENT_ID . ':points:history');
    if ($points === false) {
        $points = \db\stats\points_history();
        cache_store(TOURNAMENT_ID . ':points:history', $points);
    }    
    die(json_encode($points));
});