<?php
function get($path, $func) {
    return $_SERVER['REQUEST_METHOD'] === 'GET' ? route($path, $func) : false;
}
function post($path, $func) {
    return $_SERVER['REQUEST_METHOD'] === 'POST' ? route($path, $func) : false;
}
function put($path, $func) {
    return $_SERVER['REQUEST_METHOD'] === 'PUT' || (isset($_POST['_method']) && $_POST['_method'] === 'PUT') ? route($path, $func) : false;
}
function delete($path, $func) {
    return $_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') ? route($path, $func) : false;
}
function route($path, $func) {
	static $url = null;
	if ($url == null) {
		$url = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH);
		$url = trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(substr($url, 0, strrpos($url, '/')))), '/');
	}
	$path = trim($path, '/');
	$scnf = str_replace('%p', '%[^/]', $path);
	$prnf = str_replace('%p', '%s', $path);
	$args = sscanf($url, $scnf);
	if (substr_count($prnf, '%') !== count($args)) return false;
	$path = vsprintf($prnf, $args);
	if ($path !== $url) return false;
	$args = array_map(function($value) { return is_string($value) ? urldecode($value) : $value;	}, $args);
	return call($func, $args);
}
function call($func, array $args = array()) {
	if (is_string($func)) {
		if (file_exists($func)) return require $func;
		if (iconv_strpos($func, '->') !== false) {
			list($clazz, $method) = explode('->', $func, 2);
			$func = array(new $clazz, $method);
		}
	}
	return call_user_func_array($func, $args);
}
function status($code) {
    switch ($code) {
        // Informational
        case 100: $msg = 'Continue'; break;
        case 101: $msg = 'Switching Protocols'; break;
        // Successful
        case 200: $msg = 'OK'; break;
        case 201: $msg = 'Created'; break;
        case 202: $msg = 'Accepted'; break;
        case 203: $msg = 'Non-Authoritative Information'; break;
        case 204: $msg = 'No Content'; break;
        case 205: $msg = 'Reset Content'; break;
        case 206: $msg = 'Partial Content'; break;
        // Redirection
        case 300: $msg = 'Multiple Choices'; break;
        case 301: $msg = 'Moved Permanently'; break;
        case 302: $msg = 'Found'; break;
        case 303: $msg = 'See Other'; break;
        case 304: $msg = 'Not Modified'; break;
        case 305: $msg = 'Use Proxy'; break;
        case 306: $msg = '(Unused)'; break;
        case 307: $msg = 'Temporary Redirect'; break;
        // Client Error
        case 400: $msg = 'Bad Request'; break;
        case 401: $msg = 'Unauthorized'; break;
        case 402: $msg = 'Payment Required'; break;
        case 403: $msg = 'Forbidden'; break;
        case 404: $msg = 'Not Found'; break;
        case 405: $msg = 'Method Not Allowed'; break;
        case 406: $msg = 'Not Acceptable'; break;
        case 407: $msg = 'Proxy Authentication Required'; break;
        case 408: $msg = 'Request Timeout'; break;
        case 409: $msg = 'Conflict'; break;
        case 410: $msg = 'Gone'; break;
        case 411: $msg = 'Length Required'; break;
        case 412: $msg = 'Precondition Failed'; break;
        case 413: $msg = 'Request Entity Too Large'; break;
        case 414: $msg = 'Request-URI Too Long'; break;
        case 415: $msg = 'Unsupported Media Type'; break;
        case 416: $msg = 'Requested Range Not Satisfiable'; break;
        case 417: $msg = 'Expectation Failed'; break;
        // Server Error
        case 500: $msg = 'Internal Server Error'; break;
        case 501: $msg = 'Not Implemented'; break;
        case 502: $msg = 'Bad Gateway'; break;
        case 503: $msg = 'Service Unavailable'; break;
        case 504: $msg = 'Gateway Timeout'; break;
        case 505: $msg = 'HTTP Version Not Supported'; break;
        default: return;
    }
    $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
    header("$protocol $code $msg");
}
function url($url = null, $abs = false) {
    if ($url == null) {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $abs = true;
    }
    if (parse_url($url, PHP_URL_SCHEME) !== null) return $url;
    static $base = null, $path = null, $root = null;
    if ($base == null) {
        $base = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH);
        $base = substr($base, 0, strrpos($base, '/'));
    }
    if ($path == null) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = substr($path, 0, strrpos($path, '/'));
    }
    if (!$abs) return strpos($url, '~/') === 0 ? $base . '/' . substr($url, 2) : $url;
    if ($root == null) {
        $root = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'];
        if (($root[4] === 's' && $port !== '443') || $port !== '80') $root .= ":$port";
    }
    if (strpos($url, '~/') === 0) return $root . $base . '/' . substr($url, 2);
    return strpos($url, '/') === 0 ? $root . $url : $root . $path . '/' . $url;
}
function redirect($url, $code = 301, $die = true) {
    header('Location: ' . url($url, true), true, $code);
    if ($die) die;
}
function flash($name, $value, $hops = 1) {
    $_SESSION[$name] = $value;
    if (!isset($_SESSION['web.php:flash']))
        $_SESSION['web.php:flash'] = array($name => $hops);
    else
        $_SESSION['web.php:flash'][$name] = $hops;
}
function sendfile($path, $name = null, $mime = null, $die = true) {
    if ($mime == null) {
        $fnfo = finfo_open(FILEINFO_MIME_TYPE);
        $fmim = finfo_file($fnfo, $path);
        finfo_close($fnfo);
        $mime = $fmim === false ? 'application/octet-stream' : $fmim;
    }
    if ($name == null) $name = basename($path);
    header("Content-Type: $mime");
    header("Content-Disposition: attachment; filename=\"$name\"");
    if (defined('XSENDFILE_HEADER')) {
        header(XSENDFILE_HEADER . ': ' . $path);
    } else {
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($path);
    }
    if ($die) die;
}
// View
class view {
    static $globals = array();
	function __construct($file, $layout = null) {
		$this->file = $file;
		if ($layout != null) $this->layout = $layout;
	}
    static function register($name, $value) {
        self::$globals[$name] = $value;
    }        
    function __toString() {
        extract(self::$globals);
        extract((array)$this);
		start:
		ob_start();
		require $file;
		if (!isset($layout)) return ob_get_clean();
		$view = ob_get_clean();
		$file = $layout;
		unset($layout);
		goto start;
    }
}
function block(&$block = false) {
    if ($block === false) return ob_end_clean();
    ob_start(function($buffer) use (&$block) { $block = $buffer; });
}
function partial($file, $args = null) {
    ob_start();
    if ($args !== null) extract($args);
    include $file;
    return ob_get_clean();
}
// Filters
function filter(&$value, array $filters) {
	foreach ($filters as $filter) {
		$valid = true;
		switch ($filter) {
			case 'bool':  $valid = false !== filter_var($value, FILTER_VALIDATE_BOOLEAN); break;
			case 'int':   $valid = false !== filter_var($value, FILTER_VALIDATE_INT); break;
			case 'float': $valid = false !== filter_var($value, FILTER_VALIDATE_FLOAT); break;
			case 'ip':    $valid = false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6); break;
			case 'ipv4':  $valid = false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4); break;
			case 'ipv6':  $valid = false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6); break;
			case 'email': $valid = false !== filter_var($value, FILTER_VALIDATE_EMAIL); break;
			case 'url':   $valid = false !== filter_var($value, FILTER_VALIDATE_URL); break;
			default:
				if (is_callable($filter) || is_string($filter)) {
					if (is_string($filter) && strpos($filter, '/') === 0) {
						$valid = preg_match($filter, $value);
					} else {
						$filtered = call($filter, array($value));
						if ($filtered !== null) {
							if (is_bool($filtered)) {
								$valid = $filtered;
							} else {
								$value = $filtered;
							}
						}
					}
				} else {
					trigger_error(sprintf('Invalid filter: %s', $filter), E_USER_WARNING);
				}
		}
		if (!$valid) return false;
	}
	return true;
}
function not($filter) {
    if (is_callable($filter)) return function($value) use ($filter) {
        $value = $filter($value);
        if ($value !== null && is_bool($value)) return !$value;
        return;
    };
    return is_bool($filter) ? !$filter : null;
}
function equal($exact, $strict = true) {
    return function($value) use ($exact, $strict) { return $strict ? $value === $exact : $value == $exact; };
}
function length($min, $max = null, $charset = 'UTF-8') {
    return function($value) use ($min, $max, $charset) {
        $len = iconv_strlen($value, $charset);
        return $len >= $min && $len <= ($max == null ? $min : $max);
    };
}
function minlength($min, $charset = 'UTF-8') {
    return function($value) use ($min, $charset) {
        return iconv_strlen($value, $charset) >= $min;
    };
}
function maxlength($max, $charset = 'UTF-8') {
    return function($value) use ($max, $charset) {
        return iconv_strlen($value, $charset) <= $max;
    };
}
function between($min, $max) {
    return function($value) use ($min, $max) {
        return $value >= $min && $value <= $max;
    };
}
function minvalue($min) {
    return function($value) use ($min) {
        return $value >= $min;
    };
}
function maxvalue($max) {
    return function($value) use ($max) {
        return $value <= $max;
    };
}
function choice() {
    $choices = func_get_args();
    return function($value) use ($choices) {
        return in_array($value, $choices);
    };
}
function specialchars($quote = ENT_NOQUOTES, $charset = 'UTF-8', $double = true) {
    return function($value) use ($quote, $charset, $double) {
        return htmlspecialchars($value, $quote, $charset, $double);
    };
}
function slug($title, $delimiter = '-') {
    $title = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
    $title = preg_replace('#[^a-z0-9/_|+\s-]#i', '', $title);
    $title = strtolower(trim($title, '/_|+ -'));
    $title = preg_replace('#[/_|+\s-]+#', $delimiter, $title);
    return $title;
}
// Form
class form {
    function __construct($args = null) {
        if ($args == null) return;
        foreach ($args as $name => $value) $this->$name = $value;
    }
    function __get($name) {
        if (!isset($this->$name)) $this->$name = new field;
        return $this->$name;
    }
    function __set($name, $field) {
        $this->$name = ($field instanceof field) ? $field : new field($field);
    }
    function __call($name, $args) {
        $field = $this->$name;
        return $field($args[0]);
    }
    function validate() {
        foreach($this as $field) if (!$field->valid) return false;
        return true;
    }
}
class field {
    public $original, $value, $valid;
    function __construct($value = null) {
        $this->original = $value;
        $this->value = $value;
        $this->valid = true;
    }
    function filter() {
        return $this->valid = filter($this->value, func_get_args());
    }
    function __invoke($value) {
        $this->value = $value;
        return $this;
    }
    function __toString() {
        return strval($this->value);
    }
}
// Shutdown Function
register_shutdown_function(function() {
    if (!defined('SID') || !isset($_SESSION['web.php:flash'])) return;
    $flash =& $_SESSION['web.php:flash'];
    foreach($flash as $key => $hops) {
        if ($hops === 0)  unset($_SESSION[$key], $flash[$key]);
        else $flash[$key]--;
    }
    if (count($flash) === 0) unset($flash);
});