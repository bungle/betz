<?php
get('/bets/games', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/bets.games.phtml');
    $view->title = 'Otteluveikkaus';
    $view->menu = 'bets/games';
    $view->games = db\bets\games(username);
    $view->online = db\users\visited(username, 'Otteluveikkaus');
    die($view);
});
get('/bets/games/%d', function($id) {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/bets.game.phtml');
    $game = \db\games\find($id);
    $game['started'] = \db\games\started($id);
    $view->game = $game;
    $view->bets = \db\games\bets($id);;
    $view->title = "{$game["home"]} - {$game["road"]}";
    $view->menu = 'bets/game';
    $view->online = db\users\visited(username, "{$game["home_abbr"]} - {$game["road_abbr"]}");
    die($view);
});
post('/bets/games/%d', function($game) {
    if (!AUTHENTICATED) return status(401);
    $form = new form($_POST);
    $form->score->filter(choice('1', 'X', '2'));
    if ($form->validate() && !db\games\started($game)) {
        if (db\bets\game($game, username, $form->score->value) === false) {
            status(500);
        }
    } else {
        status(500);
    }
    die();
});
get('/bets/teams', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/bets.teams.phtml');
    $view->teams = db\teams\all();
    $view->title = 'Kolmen kärki &trade;';
    $view->menu = 'bets/teams';
    $view->start = db\games\start();
    $view->online = db\users\visited(username, 'Kolmen kärki &trade;');
    $view->hide_teams = true;
    die($view);
});
post('/bets/teams/%d', function($position) {
    if (!AUTHENTICATED) return status(401);
    $form = new form($_POST);
    $form->team->filter('\db\teams\exists');
    $form->position($position)->filter(choice('1', '2', '3'), 'intval');
    if ($form->validate()) {
        if (!STARTED) {
            switch ($form->position->value) {
                case 1: db\bets\winner(username, $form->team->value); break;
                case 2: db\bets\second(username, $form->team->value); break;
                case 3: db\bets\third(username, $form->team->value); break;
            }
            cache_delete(TOURNAMENT_ID . ':points:total');
            cache_delete(TOURNAMENT_ID . ':points:game');
            cache_delete(TOURNAMENT_ID . ':points:scorer');
            cache_delete(TOURNAMENT_ID . ':points:team');
            cache_delete(TOURNAMENT_ID . ':points:history');
        } else {
            status(500);
        }
    } else {
        status(500);
    }
    die();
});
if (defined('ENABLE_SCORER') && ENABLE_SCORER) {
    get('/bets/scorer', function() {
        if (!AUTHENTICATED) redirect('~/unauthorized');
        $view = new view(DIR . '/views/bets.scorer.phtml');
        if (isset($_SESSION['saved'])) {
            $view->saved = true;
        }
        $view->title = 'Maalikuninkuus';
        $view->form = new form();
        $view->menu = 'bets/scorer';
        $view->start = db\games\start();
        $view->online = db\users\visited(username, 'Maalikuninkuus');
        die($view);
    });
    post('/bets/scorer', function() {
        if (!AUTHENTICATED) redirect('~/unauthorized');
        $form = new form($_POST);
        $form->scorer->filter('trim', specialchars(), minlength(3));
        $view = new view(DIR . '/views/bets.scorer.phtml');
        $view->start = db\games\start();
        if ($form->validate()) {
            if (!STARTED) {
                db\bets\scorer(username, $form->scorer);
                cache_delete(TOURNAMENT_ID . ':points:total');
                cache_delete(TOURNAMENT_ID . ':points:game');
                cache_delete(TOURNAMENT_ID . ':points:scorer');
                cache_delete(TOURNAMENT_ID . ':points:team');
                cache_delete(TOURNAMENT_ID . ':points:history');
                db\users\visited(username, 'Maalikuninkuus');
                flash('saved', true);
                redirect('~/bets/scorer');
            } else {
                $view->title = 'Maalikuninkuus';
                $view->menu = 'bets/scorer';
                $view->form = $form;
                $view->closed = true;
                die($view);
            }
        } 
        $view->title = 'Maalikuninkuus';
        $view->menu = 'bets/scorer';
        $view->form = $form;
        $view->error = true;
        die($view);
    });
}