<?php
get('/', function() {
    if (is_writable(DIR . '/data')) {
        $view = new view(DIR . '/views/install.phtml');
        $view->form = new form;
        die($view);
    } else {
        $view = new view(DIR . '/views/install.chmod.phtml');
        die($view);
    }
});

post('/install', function() {
    $form = new form($_POST);
    $form->username->filter('trim', length(2, 15), '/^[a-z0-9åäö_-]+$/ui', specialchars());
    $form->password1->filter(length(6, 20), equal($form->password2->value));
    $form->password2->filter(length(6, 20), equal($form->password1->value));
    $form->email->filter('trim', 'email', specialchars());
    $view = new view(DIR . '/views/install.phtml');
    if ($form->validate()) {
        db\install\schema();
        db\users\register($form->username->value, \password\hash($form->password1->value), $form->email->value, true);
        login($form->username->value, true);
        redirect('~/');
    }
    $view->form = $form;
    die($view);
});
post('/install/google', function() {
    $_SESSION['login-google'] = 'install';
    \openid\auth('https://www.google.com/accounts/o8/id', array(
        'openid.return_to' => url('~/login/google', true),
        'openid.ns.ui' => 'http://specs.openid.net/extensions/ui/1.0',
        'openid.ui.icon' => 'true',
        'openid.ns.ax' => 'http://openid.net/srv/ax/1.0',
        'openid.ax.mode' => 'fetch_request',
        'openid.ax.required' => 'firstname,email',
        'openid.ax.type.email' => 'http://axschema.org/contact/email',
        'openid.ax.type.firstname' => 'http://axschema.org/namePerson/first'
    ));
});

get('/login/google', function() {
    $form = new form($_GET);
    $form->openid_claimed_id->filter('url');
    $form->openid_op_endpoint->filter('url');
    $form->openid_ext1_value_email->filter('email');
    $form->openid_ext1_value_firstname->filter('trim', preglace('/[^a-z0-9åäö_-]/ui', ''));
    if ($form->validate() && \openid\check($form->openid_op_endpoint)) {
        $claim = $form->openid_claimed_id;
        $_SESSION['google-claim'] = \password\hash($claim);
        $_SESSION['google-fname'] = $form->openid_ext1_value_firstname->value;
        $_SESSION['google-email'] = $form->openid_ext1_value_email->value;
        redirect('~/install/google/confirm');
    }
    redirect('~/');
});
get('/install/google/confirm', function() {
    if (isset($_SESSION['google-claim']) && isset($_SESSION['google-fname']) && isset($_SESSION['google-email'])) {
        $form = new form($_GET);
        $form->username = $_SESSION['google-fname'];
        $form->email = $_SESSION['google-email'];
        unset($_SESSION['google-fname']);
        $view = new view(DIR . '/views/install.google.phtml');
        $view->form = $form;
        die($view);
    }
    redirect('~/');
});
post('/install/google/confirm', function() {
    if (!isset($_SESSION['google-claim']) || !isset($_SESSION['google-email'])) redirect('~/');
    $claim = $_SESSION['google-claim'];
    $email = $_SESSION['google-email'];
    $form = new form($_POST);
    $form->username->filter('trim', '/^[a-z0-9åäö_-]+$/ui', specialchars());
    $form->email($email);
    $view = new view(DIR . '/views/install.google.phtml');
    $view->form = $form;
    if ($form->validate()) {
        db\install\schema();
        db\users\claim($form->username->value, $claim, $form->email->value, true);
        unset($_SESSION['google-claim'], $_SESSION['google-email']);
        login($form->username->value, true);
        redirect('~/');
    }
    die($view);
});