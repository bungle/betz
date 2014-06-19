<?php
/*
get('/mobile', function() {
    mobile();
    redirect('~/');
});
*/
get('/registration', function() {
    $view = new view(DIR . '/views/registration.phtml');
    if (isset($_SESSION['google-not-registered'])) $view->google_not_registered = true;
    $view->form = new form;
    die($view);
});
post('/registration', function() {
    $form = new form($_POST);
    $form->username->filter('trim', length(2, 15), '/^[a-z0-9åäö_-]+$/ui', specialchars());
    $form->password1->filter(length(6, 20), equal($form->password2->value));
    $form->password2->filter(length(6, 20), equal($form->password1->value));
    $form->email->filter('trim', 'email', specialchars());
    $view = new view(DIR . '/views/registration.phtml');
    if ($form->validate()) {
        $changes = db\users\register($form->username->value, \password\hash($form->password1->value), $form->email->value);
        if ($changes === 0) {
            if (db\users\username_taken($form->username->value)) $view->username_taken = true;
            if (db\users\email_taken($form->email->value)) $view->email_taken = true;
        } else {
            cache_delete(TOURNAMENT_ID . ':points:total');
            cache_delete(TOURNAMENT_ID . ':points:game');
            cache_delete(TOURNAMENT_ID . ':points:scorer');
            cache_delete(TOURNAMENT_ID . ':points:team');
            cache_delete(TOURNAMENT_ID . ':points:history');
            login($form->username->value, true);
            redirect('~/');
        }
    }
    $view->form = $form;
    die($view);
});
post('/registration/google', function() {
    $_SESSION['login-google'] = 'registration';
    \openid\auth('https://www.google.com/accounts/o8/id', array(
        'openid.return_to' => url('~/login/google', true),
        'openid.ns.ui' => 'http://specs.openid.net/extensions/ui/1.0',
        'openid.ui.icon' => 'true',
        'openid.ns.ax' => 'http://openid.net/srv/ax/1.0',
        'openid.ax.mode' => 'fetch_request',
        'openid.ax.required' => 'email',
        'openid.ax.type.email' => 'http://axschema.org/contact/email'
    ));
});
get('/registration/google/confirm', function() {
    if (isset($_SESSION['google-claim']) && isset($_SESSION['google-email'])) {
        $form = new form($_GET);
        $form->email = $_SESSION['google-email'];
        $view = new view(DIR . '/views/registration.google.phtml');
        $view->form = $form;
        if (db\users\email_taken($form->email)) $view->email_taken = true;
        die($view);
    }
    redirect('~/');
});
post('/registration/google/confirm', function() {
    if (!isset($_SESSION['google-claim']) || !isset($_SESSION['google-email'])) redirect('~/');
    $claim = $_SESSION['google-claim'];
    $email = $_SESSION['google-email'];
    $form = new form($_POST);
    $form->username->filter('trim', '/^[a-z0-9åäö_-]+$/ui', specialchars());
    $form->email($email);
    $view = new view(DIR . '/views/registration.google.phtml');
    $view->form = $form;
    if ($form->validate()) {
        $changes = db\users\claim($form->username->value, $claim, $form->email->value);
        if ($changes === 0) {
            if (db\users\username_taken($form->username->value)) $view->username_taken = true;
            if (db\users\email_taken($form->email->value)) $view->email_taken = true;
        } else {
            unset($_SESSION['google-claim'], $_SESSION['google-email']);
            cache_delete(TOURNAMENT_ID . ':points:total');
            cache_delete(TOURNAMENT_ID . ':points:game');
            cache_delete(TOURNAMENT_ID . ':points:scorer');
            cache_delete(TOURNAMENT_ID . ':points:team');
            cache_delete(TOURNAMENT_ID . ':points:history');
            login($form->username->value, true);
            redirect('~/');
        }
    }
    die($view);
});