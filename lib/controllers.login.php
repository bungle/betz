<?php
get('/', function() {
    if (AUTHENTICATED) {
        redirect('~/chat');
    }
    die(new view(DIR . '/views/login.phtml'));
});
post('/', function() {
    $form = new form($_POST);
    $form->username->filter('trim', length(2, 15), '/^[a-z0-9åäö_-]+$/ui', specialchars());
    $form->password->filter(length(6, 20));
    if ($form->validate() && db\users\login($form->username, $form->password)) {
        login($form->username->value, isset($_POST['remember']));
        redirect('~/');
    }
    $view = new view(DIR . '/views/login.phtml');
    $view->invalid = true;
    die($view);
});
post('/login/google', function() {
    $_SESSION['login-google'] = 'login';
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
get('/login/google', function() {
    if (isset($_SESSION['login-google'])) {
        $login = $_SESSION['login-google'];
        unset($_SESSION['login-google']);
        $form = new form($_GET);
        $form->openid_claimed_id->filter('url');
        $form->openid_op_endpoint->filter('url');
        $form->openid_ext1_value_email->filter('email');
        if ($form->validate() && \openid\check($form->openid_op_endpoint)) {
            $claim = $form->openid_claimed_id;
            $username = db\users\claimed($claim, $form->openid_ext1_value_email->value);
            if ($username !== false) {
                login($username, true);
                redirect('~/');
            }
            $_SESSION['google-claim'] = \password\hash($claim);
            $_SESSION['google-email'] = $form->openid_ext1_value_email->value;
            redirect('~/registration/google/confirm');
        } elseif ($login == 'registration') {
            redirect('~/registration');
        }
    }
    redirect('~/');
});
get('/logoff', function() {
    logoff();
    redirect('~/');
});
get('/unauthorized', function() {
    status(401);
    logoff();
    die(new view(DIR . '/views/unauthorized.phtml'));
});
route('/error', function() {
    status(500);
    if (defined('AUTHENTICATED') && AUTHENTICATED) {
        $view = new view(DIR . '/views/error.main.phtml');
        $view->title = 'Sivulla tapahtui virhe';
        $view->menu = 'error';
        die($view);
    }
    die(new view(DIR . '/views/error.login.phtml'));
});