<?php

/**
 * Charcoal - A PHP Framework
 */

/** Built on top of Slim */
use \Charcoal\App\App;
use \Charcoal\App\AppConfig;
use \Charcoal\App\AppContainer;

/** If we're not using PHP 5.6+, explicitly set the default character set. */
if ( PHP_VERSION_ID < 50600 ) {
    ini_set('default_charset', 'UTF-8');
}

/** For the time being, let's track and show all issues. */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

/**
 * If you are using PHP's built-in server, return FALSE
 * for existing files on the filesystem.
 */
if ( PHP_SAPI === 'cli-server' ) {
    $file = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

    if (is_file($file)) {
        return false;
    }
}

/** Register The Composer Autoloader */
require dirname(__DIR__) . '/vendor/autoload.php';

/** Import the application's settings */
$config = new AppConfig();
$config->addFile(dirname(__DIR__) . '/config/config.php');

/** Build a DI container */
$container = new AppContainer([
    'settings' => [
        'displayErrorDetails' => $config['dev_mode']
    ],
    'config' => $config
]);

/** Instantiate a Charcoal~Slim application */
$app = App::instance($container);

// Set up dependencies
require __DIR__.'/../config/dependencies.php';
// Register middleware
require __DIR__.'/../config/middlewares.php';

/** Start new or resume existing session */
if (!session_id()) {
    session_start();
}

/** Run The Application */
$app->run();
