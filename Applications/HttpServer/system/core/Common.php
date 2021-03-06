<?php
/**
 * Created by PhpStorm.
 * User: tmtbe
 * Date: 16-6-23
 * Time: 下午4:48
 */
if (!function_exists('config_item')) {
    /**
     * Returns the specified config item
     *
     * @param    string
     * @return    mixed
     */
    function config_item($item)
    {
        static $_config;

        if (empty($_config))
        {
            // references cannot be directly assigned to static variables, so we use an array
            $_config[0] =& get_config();
        }

        return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
    }
}

if ( ! function_exists('get_config'))
{
    /**
     * Loads the main config.php file
     *
     * This function lets us grab the config file even if the Config class
     * hasn't been instantiated yet
     *
     * @param    array
     * @return    array
     */
    function &get_config(Array $replace = array())
    {
        static $config;

        if (empty($config))
        {
            $file_path = APPPATH.'config/config.php';
            $found = FALSE;
            if (file_exists($file_path)) {
                $found = TRUE;
                require($file_path);
            }

            // Is the config file in the environment folder?
            if (file_exists($file_path = APPPATH . 'config/config.php')) {
                require($file_path);
            } elseif (!$found) {
                echo 'The configuration file does not exist.';
            }

            // Does the $config array exist in the file?
            if (!isset($config) OR !is_array($config)) {
                echo 'Your config file does not appear to be formatted correctly.';
            }
        }

        // Are any values being dynamically added or replaced?
        foreach ($replace as $key => $val)
        {
            $config[$key] = $val;
        }

        return $config;
    }
}
if ( ! function_exists('load_class'))
{
    /**
     * Class registry
     *
     * This function acts as a singleton. If the requested class does not
     * exist it is instantiated and set to a static variable. If it has
     * previously been instantiated the variable is returned.
     *
     * @param    string    the class name being requested
     * @param    string    the directory where the class should be found
     * @param    string    an optional argument to pass to the class constructor
     * @return    object
     */
    function &load_class($class, $directory = 'libraries', $param = NULL)
    {
        static $_classes = array();

        // Does the class exist? If so, we're done...
        if (isset($_classes[$class]))
        {
            return $_classes[$class];
        }

        $name = FALSE;

        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        foreach (array(APPPATH, BASEPATH) as $path)
        {
            if (file_exists($path.$directory.'/'.$class.'.php'))
            {
                $name = 'CI_' . $class;

                if (class_exists($name, FALSE) === FALSE)
                {
                    require_once($path . $directory . '/' . $class . '.php');
                }

                break;
            }
        }

        // Is the request a class extension? If so we load it too
        if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
        {
            $name = config_item('subclass_prefix').$class;

            if (class_exists($name, FALSE) === FALSE)
            {
                require_once(APPPATH . $directory . '/' . $name . '.php');
            }
        }

        // Did we find the class?
        if ($name === FALSE)
        {
            // Note: We use exit() rather than show_error() in order to avoid a
            // self-referencing loop with the Exceptions class
            echo 'Unable to locate the specified class: ' . $class . '.php';
        }

        // Keep track of what we just loaded
        is_loaded($class);

        $_classes[$class] = isset($param)
            ? new $name($param)
            : new $name();
        return $_classes[$class];
    }
}

// --------------------------------------------------------------------

if ( ! function_exists('is_loaded'))
{
    /**
     * Keeps track of which libraries have been loaded. This function is
     * called by the load_class() function above
     *
     * @param    string
     * @return    array
     */
    function &is_loaded($class = '')
    {
        static $_is_loaded = array();

        if ($class !== '')
        {
            $_is_loaded[strtolower($class)] = $class;
        }

        return $_is_loaded;
    }
}
if ( ! function_exists('log_message'))
{
    /**
     * Error Logging Interface
     *
     * We use this as a simple mechanism to access the logging
     * class and send messages to be logged.
     *
     * @param    string    the error level: 'error', 'debug' or 'info'
     * @param    string    the error message
     * @return    void
     */
    function log_message($level, $message)
    {
        static $_log;

        if ($_log === NULL)
        {
            // references cannot be directly assigned to static variables, so we use an array
            $_log =& load_class('Log', 'core');
        }

        $_log->write_log($level, $message);
    }
}
if ( ! function_exists('is_really_writable'))
{
    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param    string
     * @return    bool
     */
    function is_really_writable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') OR !ini_get('safe_mode')))
        {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file))
        {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE)
            {
                return FALSE;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        } elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
        {
            return FALSE;
        }

        fclose($fp);
        return TRUE;
    }
}
if ( ! function_exists('is_php'))
{
    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param    string
     * @return    bool    TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string)$version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

if ( ! function_exists('workerman_request_headers'))
{
    function workerman_request_headers()
    {
        return $GLOBALS['header'];
    }
}

if (!function_exists('get_mimes')) {
    /**
     * Returns the MIME types array from config/mimes.php
     *
     * @return    array
     */
    function &get_mimes()
    {
        static $_mimes;

        if (empty($_mimes))
        {
            if (file_exists(APPPATH . 'config/mimes.php'))
            {
                $_mimes = include(APPPATH . 'config/mimes.php');
            } elseif (file_exists(APPPATH . 'config/mimes.php')) {
                $_mimes = include(APPPATH . 'config/mimes.php');
            } else {
                $_mimes = array();
            }
        }

        return $_mimes;
    }
}

if ( ! function_exists('set_status_header'))
{
    /**
     * Set HTTP Status Header
     *
     * @param    int    the status code
     * @param    string
     * @return    void
     */
    function set_status_header($code = 200, $text = '')
    {
        if (empty($code) OR !is_numeric($code))
        {
            show_error('Status codes must be numeric', 500);
        }

        if (empty($text))
        {
            is_int($code) OR $code = (int)$code;
            $stati = array(
                100 => 'Continue',
                101 => 'Switching Protocols',

                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',

                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',

                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',
                422 => 'Unprocessable Entity',

                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported'
            );

            if (isset($stati[$code])) {
                $text = $stati[$code];
            } else {
                show_error('No status text available. Please check your status code number or supply your own message text.', 500);
            }
        }

        $server_protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        wokerman_header($server_protocol . ' ' . $code . ' ' . $text, TRUE, $code);
    }
}
if ( ! function_exists('wokerman_header'))
{
    function wokerman_header($string, $replace = true, $http_response_code = null)
    {
        \Workerman\Protocols\Http::header($string,$replace,$http_response_code);
    }
}
if ( ! function_exists('workerman_setcookie'))
{
    function workerman_setcookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        \Workerman\Protocols\Http::setcookie($name,$value,$expire,$path,$domain,$secure,$httponly);
    }
}
if ( ! function_exists('show_error'))
{
    /**
     * Error Handler
     *
     * This function lets us invoke the exception class and
     * display errors using the standard error template located
     * in application/views/errors/error_general.php
     * This function will send the error page directly to the
     * browser and exit.
     *
     * @param    string
     * @param    int
     * @param    string
     * @return    void
     */
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        $status_code = abs($status_code);
        if ($status_code < 100) {
            $exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
            if ($exit_status > 125) // 125 is EXIT__AUTO_MAX
            {
                $exit_status = 1; // EXIT_ERROR
            }

            $status_code = 500;
        } else {
            $exit_status = 1; // EXIT_ERROR
        }
        print_r([$heading, $message, 'error_general', $status_code]);
    }
}
if (!function_exists('is_https'))
{
    /**
     * Is HTTPS?
     *
     * Determines if the application is accessed via an encrypted
     * (HTTPS) connection.
     *
     * @return    bool
     */
    function is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return TRUE;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return TRUE;
        }

        return FALSE;
    }
}
function &get_instance()
{
    return \Server\HttpServer::get_instance();
}