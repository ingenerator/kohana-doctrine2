kohana-doctrine2 - integrates [Doctrine 2 ORM](http://www.doctrine-project.org/projects/orm.html) with Kohana
=============================================================================================================

[![Build Status](https://travis-ci.org/ingenerator/kohana-doctrine2.png?branch=0.2.x)](https://travis-ci.org/ingenerator/kohana-doctrine2)

kohana-doctrine2 adds the Doctrine2 ORM library to a Kohana project, aiming to make the most of the Kohana framework
(including the cascading file system and autoloader) while maintaining clean application and module code. This module is
opinionated, and does not seek to make every part of Doctrine2 available or configurable.

In particular:

* Only PHP annotation entity mapping is supported

## Installing the basic library

This package has the `kohana-module` type which means by default composer will install it in
`{PATH_TO_COMPOSER_JSON}/modules/ingenerator/kohana-doctrine2`. The use of an explicit 
`modules` directory is now deprecated in Kohana - we recommend all composer packages should
be installed in `/vendor`.

You can do this by adding the following to your composer.json:

```json
    "extra": {
        "installer-paths": {
            "vendor/{$vendor}/{$name}": ["type:kohana-module"]
        }
    }
```

You can obviously specify any path that suits you, and/or specify a custom path for just 
this module. See the [composer/installers documentation](https://github.com/composer/installers#custom-install-paths)
for more info.

Then run `composer require ingenerator/kohana-doctrine2:dev-master` to add the package to your composer.json and 
install it, together with doctrine2 and its dependencies. You should see them all appear in your configured
vendor directories.

Finally, you need to enable the module in your `bootstrap.php`:

```php

Kohana::modules([
  //... existing modules
  'kohana-doctrine2' => BASEDIR.'vendor/ingenerator/kohana-doctrine2' 
  // or whatever the path to the module is. Note BASEDIR is not a stock kohana constant.
]);  
```

You should ensure your .gitignore file excludes directories containing Composer packages.

## Configuration

We load the Doctrine connection configuration from the [database](config/database.php) config group so that you can
use the same configuration for both Doctrine and the core Kohana database module if you require. Currently, the module
only supports a Mysql backend (which will use the pdo_mysql driver in Doctrine).

Doctrine-specific configuration is stored in the [doctrine](config/doctrine.php) config group - for things like the
path to the composer vendors folder, the proxy class namespace and directory.

Additionally, a few settings are configured based on the `Kohana::$environment` setting:

| Configuration            | Kohana::DEVELOPMENT | Other    |
|--------------------------|---------------------|----------|
| autoGenerateProxyClasses | TRUE                | FALSE    |
| metaDataCache            | ArrayCache          | ApcCache |
| queryCache               | ArrayCache          | ApcCache |

## Usage

Define your model classes (by default, these should begin Model_) and place them within your module or application
classes folder just as you would do with any Kohana class. You can use Kohana-style transparent extension as required,
this module implements a custom entity class loader to find only the active Kohana classes when parsing for annotations.
Your model classes do not have to extend or implement any particular base class or interface. Annotate them with
Doctrine2 docblock annotations as required.

To load and interact with your entities in your application:

```php
$factory = new Doctrine_EMFactory;
$manager = $factory->entity_manager();

$user = $manager->find('Model_User', 1);
$user->set_email('foo@foo.com');
$manager->persist($user);
$manager->flush();
```

## Using the command line tools

Doctrine ships with a number of command line tools. Using them with your Kohana application takes a couple of extra
config steps, in particular because as standard Kohana assumes that if executing in CLI it is to execute a Minion Task.

### Separate your index.php and application bootstrap

We recommend moving much of your current index.php file to the application/bootstrap.php so that all index.php does is
execute the request. You will need to update the path definitions, so you could end up with something like this:

```php
// application/bootstrap.php
define('APPPATH', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('MODPATH', realpath(__DIR__.'/../modules').DIRECTORY_SEPARATOR);
define('SYSPATH', realpath(__DIR__.'/../system').DIRECTORY_SEPARATOR);

define('DOCROOT', realpath(__DIR__.'/../htdocs').DIRECTORY_SEPARATOR);
// It is recommended to move your index.php to a web or htdocs directory under the main project base directory for security.
// If that's not possible then DOCROOT above should be defined as __DIR__.'/../' for a stock Kohana directory structure

/**
 * The default extension of resource files. If you change this, all resources
 * must be renamed to use the new extension.
 *
 * @link http://kohanaframework.org/guide/about.install#ext
 */
define('EXT', '.php');

/**
 * Set the PHP error reporting level. If you set this in php.ini, you remove this.
 * @link http://www.php.net/manual/errorfunc.configuration#ini.error-reporting
 *
 * When developing your application, it is highly recommended to enable notices
 * and strict warnings. Enable them by using: E_ALL | E_STRICT
 *
 * In a production environment, it is safe to ignore notices and strict warnings.
 * Disable them by using: E_ALL ^ E_NOTICE
 *
 * When using a legacy application with PHP >= 5.3, it is recommended to disable
 * deprecated notices. Disable with: E_ALL & ~E_DEPRECATED
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Define the start time of the application, used for profiling.
 */
if ( ! defined('KOHANA_START_TIME'))
{
	define('KOHANA_START_TIME', microtime(TRUE));
}

/**
 * Define the memory usage at the start of the application, used for profiling.
 */
if ( ! defined('KOHANA_START_MEMORY'))
{
	define('KOHANA_START_MEMORY', memory_get_usage());
}

// -- Environment setup --------------------------------------------------------

// Standard bootstrap continues from here
```

```php

<?php
// index.php

// Bootstrap the application
require realpath(__DIR__.'/../application/bootstrap.php');

if (PHP_SAPI == 'cli') // Try and load minion
{
	class_exists('Minion_Task') OR die('Please enable the Minion module for CLI support.');
	set_exception_handler(array('Minion_Exception', 'handler'));

	Minion_Task::factory(Minion_CLI::options())->execute();
}
else
{
	/**
	 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
	 * If no source is specified, the URI will be automatically detected.
	 */
	echo Request::factory(TRUE, array(), FALSE)
	     ->execute()
	     ->send_headers(TRUE)
	     ->body();
}
```

Note that this is recommended but not strictly required - you could just add the path constants to the top of your
cli-config.php file and use the standard bootstrap - but this will involve duplicating your index.php.

### Create a cli-config.php file

Doctrine needs to load a cli-config.php file from the current working directory in order to get an entity manager and
database connection. If you've rearranged your bootstrap as above then place this in your project root folder - it can
be very simple:

```php
require(__DIR__.'/application/bootstrap.php');
$emfactory = new Doctrine_EMFactory;
$em = $emfactory->entity_manager();
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));
```

You will now be able to run the doctrine command line tools (vendor/bin/doctrine) from your application base directory.
Note that they will not be able to run with any other working directory.

```shell
# In other words, you have to do this:
cd /path/to/my/project && vendor/bin/doctrine

# And not this
/path/to/my/project/vendor/bin/doctrine
```

## License

Copyright (c) 2013, inGenerator Ltd
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided
that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and
  the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions
  and the following disclaimer in the documentation and/or other materials provided with the distribution.
* Neither the name of inGenerator Ltd nor the names of its contributors may be used to endorse or
  promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS
BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
