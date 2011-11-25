<?php
error_reporting(-1);

setlocale(LC_ALL, 'fi_FI.utf8');
date_default_timezone_set('Europe/Helsinki');

define('START_TIME', microtime(true));
define('TOURNAMENT_ID', 'betz');
define('TOURNAMENT_NAME', 'UCL 2012');
define('HOST', 'betz.fchaps.com');
define('DATABASE', __DIR__ . '/data/' . TOURNAMENT_ID . '.sq3');
define('AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
define('DIR', __DIR__);
define('DATE_SQLITE', 'Y-m-d\TH:i:s');
define('GOOGLE_ANALYTICS_ID', 'UAXXXXXXXX1');

set_exception_handler(function(Exception $ex) {
    @log\error(sprintf('%s [%s:%s]', $ex->getMessage(), $ex->getFile(), $ex->getLine()));
    while (ob_get_level() > 0) @ob_end_clean();
    status(500);
    if (defined('AUTHENTICATED') && AUTHENTICATED) {
        $view = new view(DIR . '/views/error.main.phtml');
        $view->title = 'Sivulla tapahtui virhe';
        $view->menu = 'error';
        die($view);
    }
    die(new view(DIR . '/views/error.login.phtml'));
});

set_error_handler(function($code, $message, $file, $line, $context) {
		die(sprintf('%s [%s:%s]', $message, $file, $line));
    @log\error(sprintf('%s [%s:%s]', $message, $file, $line));
    while (ob_get_level() > 0) @ob_end_clean();
    status(500);
    if (defined('AUTHENTICATED') && AUTHENTICATED) {
        $view = new view(DIR . '/views/error.main.phtml');
        $view->title = 'Sivulla tapahtui virhe';
        $view->menu = 'error';
        die($view);
    }
    die(new view(DIR . '/views/error.login.phtml'));
}, -1);

session_start();

require './lib/utils.php';
require './lib/web.php';

if (!file_exists(DATABASE)) {
	require './lib/db.install.php';
	require './lib/db.users.php';
	require './lib/controllers.install.php';
} else {
	require './lib/db.php';
	authenticate();
	if (!AJAX) portlets();
	require './lib/controllers.login.php';
	if (!STARTED) {
			require './lib/controllers.registration.php';
	}
	if (AUTHENTICATED) {
			if (\db\bets\notbetted(username)) notify('Otteluita on veikkaamatta', 'Muista veikata otteluita ennen niiden alkua.');
			require './lib/controllers.main.php';
			require './lib/controllers.bets.php';
			require './lib/controllers.stats.php';
	}
	if (ADMIN) {
			require './lib/controllers.admin.php';
	}
}

status(404);

if (defined('AUTHENTICATED') && AUTHENTICATED) {
    $view = new view('./views/404.main.phtml');
    $view->title = 'Sivua ei löytynyt';
    $view->menu = '404';
    die($view);
}

die(new view('./views/404.login.phtml'));
