<?php
ini_set('max_execution_time', 0);
/**
 * This makes our life easier when dealing with paths.
 * Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}
// Set display errors to one
ini_set('display_errors', 1);
// Report all errors
error_reporting(E_ALL);

// Initialize Request Microtime
if (! defined('REQUEST_MICROTIME')) {
    define('REQUEST_MICROTIME', microtime(true));
}

// Initialize Base Dir
if (! defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . "/../"));
}
// Initialize DS - Directory Separator
if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Define the application enviornment
if (! defined('APPLICATION_ENV')) {
    $envApplicationEnv = getenv("APPLICATION_ENV");
    define("APPLICATION_ENV", $envApplicationEnv ? $envApplicationEnv : "production");
}

// Setup autoloading
require_once (dirname(__FILE__) . '/../init_autoloader.php');
require_once (dirname(__FILE__) . '/../vendor/chrisboulton/php-resque/lib/Resque.php');
$application = Zend\Mvc\Application::init(require 'config/application.config.php');
$GLOBALS['application'] = $application;