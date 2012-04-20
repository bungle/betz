<?php
get('/rules', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/rules.phtml');
    $view->title = 'Säännöt';
    $view->menu = 'rules';
    $view->online = db\users\visited(username, 'Säännöt');
    die($view);
});
get('/chat', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $last = 0;
    $messages = db\chat\latest(50, $last);
    $view = new view(DIR . '/views/chat.phtml');
    $view->title = 'Kisachat';
    $view->menu = 'chat';
    $view->smileys = smileys_array();
    $view->online = db\users\visited(username, 'Kisachat');
    $_SESSION['last-chat-message-id'] = $last;
    if (count($messages) > 0) {
        $chat = new view(DIR . '/views/chat.messages.phtml');
        $chat->messages = $messages;
        $view->chat = $chat;
    }
    die($view);
});
post('/chat', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $form = new form($_POST);
    $form->message->filter('trim', minlength(1), specialchars(), 'links', 'smileys');
    if ($form->validate()) {
        db\chat\post(username, $form->message->value);
        db\users\visited(username, 'Kisachat');
    } else {
        status(500);
    }
    die();
});
get('/chat/poll', function() {
    if (!AUTHENTICATED) return;
    $last = isset($_SESSION['last-chat-message-id']) ? $_SESSION['last-chat-message-id'] : 0;
    $messages = db\chat\poll($last);
    if (count($messages) === 0) {
        status(304);
        die;
    }
    $_SESSION['last-chat-message-id'] = $last;
    $view = new view(DIR . '/views/chat.messages.phtml');
    $view->messages = $messages;
    die($view);
});
get('/program', function() {
    if (!AUTHENTICATED) redirect('~/unauthorized');
    $view = new view(DIR . '/views/program.phtml');
    $view->title = 'Otteluohjelma';
    $view->menu = 'program';
    $view->online = db\users\visited(username, 'Otteluohjelma');
    die($view);
});