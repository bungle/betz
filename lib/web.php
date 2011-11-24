<?php
namespace {
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
        $path = @vsprintf($prnf, $args);
        if ($path !== $url) return false;
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
    function url($url, $abs = false) {
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
        function __construct($view, $layout = null) {
            $this->view = $view;
            $this->layout = $layout;
        }
        static function register($name, $value) {
            self::$globals[$name] = $value;
        }        
        function __toString() {
            extract(self::$globals);
            extract((array)$this);
            ob_start();
            require $view;
            if ($layout == null) return ob_get_clean();
            $view = ob_get_clean();
            ob_start();
            require $layout;
            return ob_get_clean();
        }
    }
    function block(&$block = false) {
        if ($block === false) return ob_end_clean();
        ob_start(function($buffer) use (&$block) { $block = $buffer; });
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
    // Flickr
    // TODO: POSTing to Flickr not implemented.
    function flickr($args) {
        $endpoint = isset($args['endpoint']) ? $args['endpoint'] : 'http://api.flickr.com/services/rest/';
        $secret = isset($args['api_secret']) ? $args['api_secret'] : null;
        unset($args['endpoint'], $args['api_secret']);
        if ($secret != null) {
            ksort($args);
            $api_sig = $secret;
            foreach($args as $k => $v) $api_sig .= $k . $v;
            $api_sig = md5($api_sig);
            $args['api_sig'] = $api_sig;
        }
        $url = $endpoint . '?' . http_build_query($args);
        if (substr($endpoint, -15) === '/services/auth/') return $url;
        $response = file_get_contents($url);
        return isset($args['format']) && $args['format'] === 'php_serial' ? unserialize($response) : $response;
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
}
// Logging
namespace log {
    function debug($message) { append($message, LOG_DEBUG); }
    function info($message) { append($message, LOG_INFO); }
    function warn($message) { append($message, LOG_WARNING); }
    function error($message) { append($message, LOG_ERR); }
    function write($message, $level) { append($message, $level); }
    function level($level) {
        if ($level > LOG_INFO) return 'DEBUG';
        if ($level > LOG_WARNING) return 'INFO';
        return $level > LOG_ERR ? 'WARNING' : 'ERROR';
    }
    function append($message, $level) {
        defined('LOG_PATH')  or define('LOG_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/data');
        defined('LOG_LEVEL') or define('LOG_LEVEL', LOG_DEBUG);
        defined('LOG_FILE')  or define('LOG_FILE', 'Y-m-d.\l\o\g');
        if (LOG_LEVEL < $level) return;
        static $messages = null;
        if ($messages == null) {
            register_shutdown_function(function() use (&$messages) {
                file_put_contents(rtrim(LOG_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date_create()->format(LOG_FILE), $messages, FILE_APPEND | LOCK_EX);
            });
        }
        $trace = debug_backtrace();
        list($usec, $sec) = explode(' ', microtime());
        $messages .= sprintf('%s %7s %-20s %s', date('Y-m-d H:i:s.', $sec) . substr($usec, 2, 3) , level($level), basename($trace[1]['file']) . ':' . $trace[1]['line'], trim($message) . PHP_EOL);
    }
}
// Password (PHP >= 5.3 version of http://www.openwall.com/phpass/)
namespace password {
    function hash($password, $iterations = 8) {
        $random = random();
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        if ($iterations < 4 || $iterations > 31) $iterations = 8;
        $salt = '$2a$';
        $salt .= chr(ord('0') + $iterations / 10);
        $salt .= chr(ord('0') + $iterations % 10);
        $salt .= '$';
        $i = 0;
        calc:
        $c1 = ord($random[$i++]);
        $salt .= $itoa64[$c1 >> 2];
        $c1 = ($c1 & 0x03) << 4;
        if ($i >= 16) {
            $salt .= $itoa64[$c1];
            $hash = crypt($password, $salt);
            return strlen($hash) == 60 ? $hash : '*';
        }
        $c2 = ord($random[$i++]);
        $c1 |= $c2 >> 4;
        $salt .= $itoa64[$c1];
        $c1 = ($c2 & 0x0f) << 2;
        $c2 = ord($random[$i++]);
        $c1 |= $c2 >> 6;
        $salt .= $itoa64[$c1];
        $salt .= $itoa64[$c2 & 0x3f];
        goto calc;
    }
    function check($password, $hash) {
        return crypt($password, $hash) == $hash;
    }
		function random() {
			if (function_exists('mcrypt_create_iv')) return mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
			$output = '';
			if (is_readable('/dev/urandom') && ($fh = @fopen('/dev/urandom', 'rb'))) {
				$output = fread($fh, 16);
				fclose($fh);
			}
			if (strlen($output) < 16) {
				$output = '';
				$state = microtime();
				for ($i = 0; $i < 16; $i += 16) {
					$state = md5(microtime() . $state);
					$output .= pack('H*', md5($state));
				}
				$output = substr($output, 0, 16);
			}
			return $output;
		}
}
namespace openid {
    function auth($url, array $params = array()) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array('Accept: application/xrds+xml')
        ));
        $oid = curl_exec($ch);
        curl_close($ch);
        $url = simplexml_load_string($oid)->XRD->Service->URI;
        $needed = array(
            'openid.mode' => 'checkid_setup',
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select'
        );
        $params = array_merge($params, $needed);
        $qs = parse_url($url, PHP_URL_QUERY);
        $url .= isset($qs) ? '&' : '?';
        $url .= http_build_query($params);
        redirect($url);
    }
    function check($url) {
        $data = str_replace('openid.mode=id_res', 'openid.mode=check_authentication', $_SERVER['QUERY_STRING']);
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $data
        ));
        $oid = curl_exec($ch);
        curl_close($ch);
        return strpos($oid, 'is_valid:true') === 0;
    }
}