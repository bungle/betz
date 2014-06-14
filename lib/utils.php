<?php
function login($username, $remember = false) {
    @session_regenerate_id(true);
    $_SESSION['logged-in-username'] = $username;
    if ($remember) remember($username);
}
function remember($username) {
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown User Agent';
    $key = \password\hash(uniqid(session_id(), true) . $agent);
    $expire = date_modify(date_create(), '+40 day');
    $cookie = urlencode(sprintf('%s:%s', $username, $key));
    setcookie ('logged-in-cookie', $cookie, date_timestamp_get($expire), '/', '', false, true);
    db\users\remember($username, $key, date_format($expire, DATE_SQLITE));
}
function logoff() {
    $_SESSION = array();
    $expire = date_timestamp_get(date_modify(date_create(), '-1 day'));
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', $expire, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    if (isset($_COOKIE['logged-in-cookie'])) {
        $cookie = urldecode($_COOKIE['logged-in-cookie']);
        $parts = explode(':', $cookie);
        if (count($cookie) == 2) {
            $username = $parts[0];
            $key = $parts[1];
            db\users\forget($username, $key);
        }
        setcookie('logged-in-cookie', '', $expire, '/', '', false, true);
    }
}
function authenticate() {
    define('STARTED', db\bets\started());
    if (isset($_SESSION['logged-in-username'])) {
        $username = $_SESSION['logged-in-username'];
        $user = db\users\authenticate($username);
        if ($user !== false) {
            define('AUTHENTICATED', true);
            define('ADMIN', $user['admin'] === 1);
            define('username', $user['username']);
            return;
        }
    }
    if (isset($_COOKIE['logged-in-cookie'])) {
        $cookie = urldecode($_COOKIE['logged-in-cookie']);
        $parts = explode(':', $cookie);
        if (count($parts) == 2) {
            $username = $parts[0];
            $key = $parts[1];
            $user = db\users\remembered($username, $key);
            if ($user !== false) {
                $_SESSION['logged-in-username'] = $username;
                define('AUTHENTICATED', true);
                define('ADMIN', $user['admin'] === 1);
                define('username', $user['username']);
                remember($username);
                return;
            }
        }
    }
    define('AUTHENTICATED', false);
    define('ADMIN', false);
    define('username', 'anonymous');
}
function desktop() {
    unset($_SESSION['mobile-on']);
    if (isset($_COOKIE['mobile-on-cookie'])) {
        $expire = date_timestamp_get(date_modify(date_create(), '-1 day'));
        setcookie('mobile-on-cookie', '', $expire, '/', '', false, true);
    }
}
function mobile() {
    $_SESSION['mobile-on'] = true;
    $expire = date_modify(date_create(), '+40 day');
    setcookie ('mobile-on-cookie', 'on', date_timestamp_get($expire), '/', '', false, true);
}
function is_mobile() {
    return (isset($_SESSION['mobile-on']) || isset($_COOKIE['mobile-on-cookie']));
}
function checkbox(&$value) {
    $value = isset($value);
    return true;
}
function preglace($pattern, $replacement) {
    return function($subject) use ($pattern, $replacement) {
        return preg_replace($pattern, $replacement, $subject);
    };
}
function portlets() {
    view::register('user', \db\bets\single(username));
    $fee = defined('ENTRY_FEE') ? ENTRY_FEE : 15;
    $pot = \db\users\paid() * ENTRY_FEE;
    if (defined('POT_ADJUSTMENT')) $pot += POT_ADJUSTMENT;
    view::register('pot', $pot);
    view::register('upcoming', \db\bets\games(username, 4));
    view::register('played', \db\games\played());
}
function notify($title, $text = null, $type = 'notice') {
    static $notices = array();
    $notices[] = array('title' => $title, 'text' => $text, 'type' => $type);
    view::register('notices', $notices);
}
function weekday($date, $length = null) {
    $weekdaynum = (int)date_format($date, 'w');
    switch ($weekdaynum) {
        case 0: $date = 'Sunnuntai'; break;
        case 1: $date = 'Maanantai'; break;
        case 2: $date = 'Tiistai'; break;
        case 3: $date = 'Keskiviikko'; break;
        case 4: $date = 'Torstai'; break;
        case 5: $date = 'Perjantai'; break;
        case 6: $date = 'Lauantai'; break;
    }
    if ($length == null) return $date;
    return substr($date, 0, $length);
}
function cache_store($key, $var, $ttl = 0) {
    if (!function_exists('apc_store')) return false;
    return apc_store($key, $var, $ttl);
}
function cache_fetch($key) {
    if (!function_exists('apc_fetch')) return false;
    return apc_fetch($key);
}
function cache_delete($key) {
    if (!function_exists('apc_delete')) return false;
    return apc_delete($key);
}
function smileys_array() {
    $smileys = array();
    $smileys[] = array('src' => 'grin.gif', 'title' => 'grin', 'keys' => array(':-)'));
    $smileys[] = array('src' => 'lol.gif', 'title' => 'lol', 'keys' => array(':D', ':-D', ':lol:'));
    $smileys[] = array('src' => 'cheese.gif', 'title' => 'cheese', 'keys' => array(':cheese:'));
    $smileys[] = array('src' => 'smile.gif', 'title' => 'smile', 'keys' => array(':)'));
    $smileys[] = array('src' => 'wink.gif', 'title' => 'wink', 'keys' => array(';-)', ';)'));
    $smileys[] = array('src' => 'smirk.gif', 'title' => 'smirk', 'keys' => array(':smirk:'));
    $smileys[] = array('src' => 'rolleyes.gif', 'title' => 'rolleyes', 'keys' => array(':roll:'));
    $smileys[] = array('src' => 'confused.gif', 'title' => 'confused', 'keys' => array(':-S'));
    $smileys[] = array('src' => 'surprise.gif', 'title' => 'surprised', 'keys' => array(':wow:'));
    $smileys[] = array('src' => 'bigsurprise.gif', 'title' => 'big surprise', 'keys' => array(':bug:'));
    $smileys[] = array('src' => 'tongue_laugh.gif', 'title' => 'tongue laugh', 'keys' => array(':-P'));
    $smileys[] = array('src' => 'tongue_rolleye.gif', 'title' => 'tongue rolleye', 'keys' => array('%-P', '%P'));
    $smileys[] = array('src' => 'tongue_wink.gif', 'title' => 'tongue wink', 'keys' => array(';-P'));
    $smileys[] = array('src' => 'rasberry.gif', 'title' => 'raspberry', 'keys' => array(':P'));
    $smileys[] = array('src' => 'blank.gif', 'title' => 'blank stare', 'keys' => array(':blank:', ':long:'));
    $smileys[] = array('src' => 'ohh.gif', 'title' => 'ohh', 'keys' => array(':ohh:'));
    $smileys[] = array('src' => 'grrr.gif', 'title' => 'grrr', 'keys' => array(':grrr:'));
    $smileys[] = array('src' => 'gulp.gif', 'title' => 'gulp', 'keys' => array(':gulp:'));
    $smileys[] = array('src' => 'ohoh.gif', 'title' => 'ohoh', 'keys' => array('8-/'));
    $smileys[] = array('src' => 'downer.gif', 'title' => 'downer', 'keys' => array(':down:'));
    $smileys[] = array('src' => 'embarrassed.gif', 'title' => 'red face', 'keys' => array(':red:'));
    $smileys[] = array('src' => 'sick.gif', 'title' => 'sick', 'keys' => array(':sick:'));
    $smileys[] = array('src' => 'shuteye.gif', 'title' => 'shut eye', 'keys' => array(':shut:'));
    $smileys[] = array('src' => 'hmm.gif', 'title' => 'hmmm', 'keys' => array(':-/'));
    $smileys[] = array('src' => 'mad.gif', 'title' => 'mad', 'keys' => array(':mad:', '>:('));
    $smileys[] = array('src' => 'angry.gif', 'title' => 'angry', 'keys' => array(':angry:', '>:-('));
    $smileys[] = array('src' => 'zip.gif', 'title' => 'zipper', 'keys' => array(':zip:'));
    $smileys[] = array('src' => 'kiss.gif', 'title' => 'kiss', 'keys' => array(':kiss:'));
    $smileys[] = array('src' => 'shock.gif', 'title' => 'shock', 'keys' => array(':ahhh:', ':shock:'));
    $smileys[] = array('src' => 'shade_smile.gif', 'title' => 'cool smile', 'keys' => array(':coolsmile:'));
    $smileys[] = array('src' => 'shade_smirk.gif', 'title' => 'cool smirk', 'keys' => array(':coolsmirk:'));
    $smileys[] = array('src' => 'shade_grin.gif', 'title' => 'cool grin', 'keys' => array(':coolgrin:'));
    $smileys[] = array('src' => 'shade_hmm.gif', 'title' => 'cool hmm', 'keys' => array(':coolhmm:'));
    $smileys[] = array('src' => 'shade_mad.gif', 'title' => 'cool mad', 'keys' => array(':coolmad:'));
    $smileys[] = array('src' => 'shade_cheese.gif', 'title' => 'cool mad', 'keys' => array(':coolcheese:'));
    $smileys[] = array('src' => 'vampire.gif', 'title' => 'vampire', 'keys' => array(':vampire:'));
    $smileys[] = array('src' => 'snake.gif', 'title' => 'snake', 'keys' => array(':snake:'));
    $smileys[] = array('src' => 'smoke.gif', 'title' => 'smoke', 'keys' => array(':smoke:'));
    $smileys[] = array('src' => 'finger.gif', 'title' => 'finger', 'keys' => array(':finger:'));
    $smileys[] = array('src' => 'rock.gif', 'title' => 'rock!', 'keys' => array(':rock:'));
    $smileys[] = array('src' => 'party.gif', 'title' => 'party', 'keys' => array(':party:'));
    $smileys[] = array('src' => 'facepalm.gif', 'title' => 'face palm', 'keys' => array(':facepalm:'));
    $smileys[] = array('src' => 'exclaim.gif', 'title' => 'exclaim', 'keys' => array(':exclaim:'));
    $smileys[] = array('src' => 'question.gif', 'title' => 'question', 'keys' => array(':question:'));
    $smileys[] = array('src' => 'hearth.png', 'title' => 'hearth', 'keys' => array('<3'));
    $smileys[] = array('src' => 'thumbs.gif', 'title' => 'thumb', 'keys' => array('(y)', '(Y)'));
    $smileys[] = array('src' => 'thumbs_down.gif', 'title' => 'thumbs down', 'keys' => array('(n)', '(N)'));
    $smileys[] = array('src' => 'fingerscrossed.png', 'title' => 'fingers crossed', 'keys' => array(':x:', ':X:'));
    if (defined('TOURNAMENT_TYPE') && TOURNAMENT_TYPE === 'hockey') {
        $smileys[] = array('src' => 'puck.gif', 'title' => 'puck', 'keys' => array(':puck:'));
    }
    if (defined('TOURNAMENT_TYPE') && TOURNAMENT_TYPE === 'soccer') {
        $smileys[] = array('src' => 'soccer.gif', 'title' => 'ball', 'keys' => array(':ball:'));
    }
    $smileys[] = array('src' => 'beer.gif', 'title' => 'beer', 'keys' => array('(b)', '(B)'));
    $smileys[] = array('src' => 'champagne.gif', 'title' => 'champagne', 'keys' => array(':c:', ':C:'));
    $smileys[] = array('src' => 'stars.gif', 'title' => 'stars', 'keys' => array(':*:'));
    $smileys[] = array('src' => 'rose.png', 'title' => 'rose', 'keys' => array(':rose:'));
    $smileys[] = array('src' => 'money.png', 'title' => 'money', 'keys' => array(':money:'));
    
    return $smileys;
}
function smileys($value) {
    $smileys = smileys_array();
    $template = '<img src="%s" class="smiley" title="%s" alt="%s" width="19" height="19">';
    foreach ($smileys as $smiley) {
        $replace = sprintf($template, url('~/img/smileys') . '/' . $smiley['src'], $smiley['keys'][0], $smiley['title']);
        $value = str_replace($smiley['keys'], $replace, $value);
    }
    return $value;
}
function links($value) {
    if($value == '' || !preg_match('/(http|www\.|@)/i', $value)) return $value;
    $lines = explode("\n", $value); $value = '';
    while (list($k, $l) = each($lines)) {
        $l = preg_replace("/([ \t]|^)www\./i", "\\1http://www.", $l);
        $l = preg_replace("/(http:\/\/[^ )\r\n!]+)/i", "<a target=\"_blank\" href=\"\\1\">\\1</a>", $l);
        $l = preg_replace("/(https:\/\/[^ )\r\n!]+)/i", "<a target=\"_blank\" href=\"\\1\">\\1</a>", $l);
        $l = preg_replace("/([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)+))/i", "<a href=\"mailto:\\1\">\\1</a>", $l);
        $value .= $l."\n";
    }
    return $value;
}
