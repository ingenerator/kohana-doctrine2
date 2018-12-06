<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2017 inGenerator Ltd
 */

$_SERVER['KOHANA_ENV'] = 'DEVELOPMENT';
require_once(__DIR__.'/../koharness_bootstrap.php');

// Hacky workaround to show a simple text exception on fatal errors
// Otherwise Kohana's shutdown function catches it and shows a huge HTML trace that's horrible to follow
file_put_contents(
    APPPATH.'views/text-error.php',
    '<?php echo "\n\nUnhandled error: ".\Kohana_Exception::text($e)."\n";'
);
Kohana_Exception::$error_view = 'text-error';

\Session::$default = 'array';

// Autoload mocks and test-support helpers that should not autoload in the main app
$mock_loader = new \Composer\Autoload\ClassLoader;
$mock_loader->addPsr4('test\\mock\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/mock/']);
$mock_loader->addPsr4('test\\integration\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/integration/']);
$mock_loader->addPsr4('test\\unit\\Ingenerator\\KohanaDoctrine\\', [__DIR__.'/unit/']);
$mock_loader->register();
