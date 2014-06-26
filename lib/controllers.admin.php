<?php
get('/admin', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.phtml');
    $view->title = 'Ylläpito';
    $view->menu = 'admin';
    die($view);
});
/*
get('/admin/news', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.news.phtml');
    $view->title = 'Uutiset';
    $view->menu = 'admin/news';
    die($view);
});
post('/admin/news', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $form = new form($_POST);
    $form->slug($form->title->value)->filter('slug');
    $form->content->filter('trim', minlength(1), 'links', 'smileys');
    $form->level->filter('intval');
    if (!isset($form->id->value)) $form->id->value = null; 
    if($form->validate()) {
        db\news\add($form->id->value, $form->title->value, $form->content->value, $form->level->value, username, $form->slug->value);
        redirect('~/');
    }
});
get('/admin/news/%d', function($id) {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.news.phtml');
    $view->title = 'Uutinen';
    $view->menu = 'admin/news';
    $view->news = db\news\edit($id);
    die($view);
});
*/
get('/admin/teams', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.teams.phtml');
    $view->title = 'Joukkueet';
    $view->menu = 'admin/news';
    $view->teams = db\teams\all();
    die($view);
});
post('/admin/teams', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $form = new form($_POST);
    if ($form->validate()) {
        db\teams\add($form->name->value, $form->abbr->value);
        redirect('~/admin/teams');
    }
});
post('/admin/teams/%p', function($team) {
    if (!ADMIN) redirect('~/unauthorized');
    $team = urldecode($team);
    $form = new form($_POST);
    $form->ranking->filter('int', 'intval');
    if ($form->validate()) {
        db\teams\ranking($team, $form->ranking->value);
        cache_delete(TOURNAMENT_ID . ':points:total');
        cache_delete(TOURNAMENT_ID . ':points:game');
        cache_delete(TOURNAMENT_ID . ':points:scorer');
        cache_delete(TOURNAMENT_ID . ':points:team');
        cache_delete(TOURNAMENT_ID . ':points:history');
    } else {
        status(500);
    }
    die();
});
get('/admin/games', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.games.phtml');
    $view->title = 'Ottelut';
    $view->menu = 'admin/games';
    $view->form = new form;
    $view->teams = db\teams\all(false);
    $view->games = db\games\all();
    die($view);
});
post('/admin/games', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $form = new form($_POST);
    $form->date->filter('/^20[1-9]\d-[01]\d-[0-3]\d$/');
    $form->time->filter('/^[0-2]\d:[0-5]\d:[0-5]\d$/');
    $form->home->filter('db\teams\exists', not(equal($form->road->value)));
    $form->road->filter('db\teams\exists', not(equal($form->home->value)));
    $form->draw = new field(isset($_POST['draw']));
    if ($form->validate()) {
        $time = sprintf('%sT%s', $form->date->value, $form->time->value);
        db\games\add($time, $form->home->value, $form->road->value, $form->draw->value);
        redirect('~/admin/games');
    }
    $view = new view(DIR . '/views/admin.games.phtml');
    $view->title = 'Ottelut';
    $view->menu = 'admin/games';
    $view->form = $form;
    $view->error = true;
    $view->teams = db\teams\all();
    $view->games = db\games\all();
    die($view);
});
post('/admin/games/%d', function($id) {
    if (!ADMIN) redirect('~/unauthorized');
    $form = new form($_POST);
    $form->home_goals->filter('int', 'intval');
    $form->home_goals_total->filter('int', 'intval');
    $form->road_goals->filter('int', 'intval');
    $form->road_goals_total->filter('int', 'intval');
    if ($form->validate()) {
        db\games\score($id, $form->home_goals->value, $form->road_goals->value, $form->home_goals_total->value, $form->road_goals_total->value);
        cache_delete(TOURNAMENT_ID . ':points:total');
        cache_delete(TOURNAMENT_ID . ':points:game');
        cache_delete(TOURNAMENT_ID . ':points:scorer');
        cache_delete(TOURNAMENT_ID . ':points:team');
        cache_delete(TOURNAMENT_ID . ':points:history');
    } else {
        status(500);
    }
    die();
});
if (defined('ENABLE_SCORER') && ENABLE_SCORER) {
    get('/admin/scorers', function() {
        if (!ADMIN) redirect('~/unauthorized');
        $view = new view(DIR . '/views/admin.scorers.phtml');
        $view->title = 'Maalintekijät';
        $view->menu = 'admin/scorers';
        $view->teams = db\teams\all();
        $view->scorers = db\scorers\all();
        $view->userscorers = db\scorers\users();
        die($view);
    });
    post('/admin/scorers', function() {
        if (!ADMIN) redirect('~/unauthorized');
        $form = new form($_POST);
        $form->scorer->filter('trim', specialchars(), minlength(3));
        $form->team->filter('db\teams\exists');
        $form->goals->filter('int', 'intval');
        if ($form->validate()) {
            $changes = db\scorers\add($form->scorer->value, $form->team->value, $form->goals->value);
            if ($changes > 0) {
                cache_delete(TOURNAMENT_ID . ':points:total');
                cache_delete(TOURNAMENT_ID . ':points:game');
                cache_delete(TOURNAMENT_ID . ':points:scorer');
                cache_delete(TOURNAMENT_ID . ':points:team');
                cache_delete(TOURNAMENT_ID . ':points:history');
                cache_delete(TOURNAMENT_ID . ':scorers');
                redirect('~/admin/scorers');
            }
        }
        $view = new view(DIR . '/views/admin.scorers.phtml');
        $view->error = true;
        $view->title = 'Maalintekijät';
        $view->menu = 'admin/scorers';
        $view->teams = db\teams\all();
        $view->scorers = db\scorers\all();
        $view->userscorers = db\scorers\users();
        die($view);
    });
    post('/admin/scorers/map', function() {
        if (!ADMIN) redirect('~/unauthorized');
        $form = new form($_POST);
        $form->scorer->filter('trim', specialchars(), minlength(3));
        $form->betted->filter('trim', specialchars(), minlength(3));
        if ($form->validate()) {
            db\scorers\map($form->scorer->value, $form->betted->value);
            cache_delete(TOURNAMENT_ID . ':points:total');
            cache_delete(TOURNAMENT_ID . ':points:game');
            cache_delete(TOURNAMENT_ID . ':points:scorer');
            cache_delete(TOURNAMENT_ID . ':points:team');
            cache_delete(TOURNAMENT_ID . ':points:history');
            cache_delete(TOURNAMENT_ID . ':scorers');
        } else {
            status(500);
        }
        die();
    });
    post('/admin/scorers/%p', function($scorer) {
        if (!ADMIN) redirect('~/unauthorized');
        $scorer = urldecode($scorer);
        $form = new form($_POST);
        $form->goals->filter('int', 'intval');
        if ($form->validate()) {
            db\scorers\goals($scorer, $form->goals->value);
            cache_delete(TOURNAMENT_ID . ':points:total');
            cache_delete(TOURNAMENT_ID . ':points:game');
            cache_delete(TOURNAMENT_ID . ':points:scorer');
            cache_delete(TOURNAMENT_ID . ':points:team');
            cache_delete(TOURNAMENT_ID . ':points:history');
            cache_delete(TOURNAMENT_ID . ':scorers');
        } else {
            status(500);
        }
        die();
    });
}
get('/admin/users', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.users.phtml');
    $view->title = 'Käyttäjät';
    $view->menu = 'admin/users';
    $view->users = db\users\all();
    die($view);
});
post('/admin/users', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $form = new form($_POST);
    $form->active = new field(isset($_POST['active']));
    $form->paid = new field(isset($_POST['paid']));
    $form->admin = new field(isset($_POST['admin']));
    if ($form->validate()) {
        db\users\update($form->user, $form->active->value || $form->paid->value || username == $form->user, $form->paid->value, $form->admin->value || username == $form->user);
        cache_delete(TOURNAMENT_ID . ':points:total');
        cache_delete(TOURNAMENT_ID . ':points:game');
        cache_delete(TOURNAMENT_ID . ':points:scorer');
        cache_delete(TOURNAMENT_ID . ':points:team');
        cache_delete(TOURNAMENT_ID . ':points:history');
    } else {
        status(500);
    }
    die();
});
get('/admin/email', function() {
    if (!ADMIN) redirect('~/unauthorized');
    $view = new view(DIR . '/views/admin.email.phtml');
    $view->title = 'Sähköpostiosoitteet';
    $view->menu = 'admin/email';
    $view->team_emails = db\users\teams_not_betted();
    $view->game_emails = db\users\games_not_betted();
    $view->emails = db\users\emails();
    die($view);
});