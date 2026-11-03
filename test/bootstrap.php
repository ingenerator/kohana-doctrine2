<?php

use Composer\Autoload\ClassLoader;

// Set up temporary directories for the app
// These need to exist and be writable, though we do very little with them
(function () {
    $mkdir = function (string $dir): string {
        if ( ! mkdir($dir, recursive: TRUE)) {
            throw new RuntimeException('Could not create temporary directory in '.$dir);
        }

        return $dir;
    };

    $basedir = uniqid(sys_get_temp_dir().'/kohana-tests/');
    define('APPPATH', $mkdir($basedir.'/application').'/');
    $mkdir(APPPATH.'cache');
    define('MODPATH', $mkdir($basedir.'/modules').'/');
    define('DOCROOT', $mkdir($basedir.'/htdocs').'/');
})();

define('SYSPATH', __DIR__.'/../vendor/ingenerator/kohana-core/');
define('EXT', '.php');

error_reporting(E_ALL);

define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());

// Load the core Kohana class and extension
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

// Set default runtime environment (based on default Kohana install)
date_default_timezone_set('America/Chicago');
setlocale(LC_ALL, 'en_US.utf-8');
mb_substitute_character('none');

// Enable the Kohana auto-loader and enable it for unserialization
spl_autoload_register(['Kohana', 'auto_load']);
ini_set('unserialize_callback_func', 'spl_autoload_call');

// Configure and initialize Kohana
I18n::lang('en-us');
Kohana::init();
Kohana::$config->attach(new Config_File);

Kohana::modules([]);

Session::$default = 'array';

// Autoload mocks and test-support helpers that should not autoload in the main app
$mock_loader = new ClassLoader;
$mock_loader->addPsr4('test\\mock\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/mock/']);
$mock_loader->addPsr4('test\\integration\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/integration/']);
$mock_loader->addPsr4('test\\unit\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/unit/']);
$mock_loader->register();
