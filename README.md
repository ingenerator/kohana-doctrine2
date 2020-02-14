kohana-doctrine2 - integrates [Doctrine 2 ORM](http://www.doctrine-project.org/projects/orm.html) with Kohana
=============================================================================================================

[![Build Status](https://travis-ci.org/ingenerator/kohana-doctrine2.png?branch=0.2.x)](https://travis-ci.org/ingenerator/kohana-doctrine2)

**[!!] This package has been significantly rewritten for the 0.2 series**

kohana-doctrine2 provides a wrapper with opinionated configuration for the Doctrine2 ORM in the way we like to use it.
The package does not attempt to make every part of Doctrine2 available or configurable, though can usually be extended
or configured to tweak behaviour.

In particular:

* Only PHP annotation entity mapping (with the SimpleAnnotationReader) is supported
* By default, you must specify an explicit list of entity classes rather than just paths to where there might be some
* By default, we just expose two cache instances - one for "compiler stuff" like class metadata and parsed DQL->SQL 
  queries, and one for "data stuff" like query results. The compiler cache defaults to an ArrayCache in dev and an 
  ApcuCache in other environments. The data cache is always an ArrayCache unless you specify something else.

## Installation

Run `composer require ingenerator/kohana-doctrine2`. Note this is no longer a kohana module so you don't need to 
register it in the bootstrap, so long as you register Composer's autoloader.

## Configuring database connection

We read database information from the Kohana::$config 'database' group, in the same structure as the legacy core Kohana
database module. Currently we only support a MySQL backend with a pdo_mysql driver in Doctrine.

Using the default Kohana config readers, your app should provide a `config/database.php` like this:

```php
<?php
// application/config/database.php
return [
    'default' => [
        // 'type' => 'MySQL' (this is the default AND we'll throw an exception if you use anything else)
        'connection' => [
            'hostname' => 'localhost',
            'database' => 'mydatabase',
            'username' => 'me',
            'password' => 'sesame',
        ],
        // 'charset'         => 'utf8' (this is the default if not specified)
        // 'timeout_seconds' => 5 (this is the default if not specified)
    ]
];
```

Sometimes - e.g. for unit tests or running Doctrine build tooling - you might not have a database server actually 
available. If you configure `'hostname' => NULL` we will use a `NullPDO` driver to allow Doctrine to bootstrap itself
without failing on a database connection error. That connection is sufficent to run things like `orm:generate-proxies` 
or `orm:validate-schema --skip-sync`. If your code does anything that attempts to actually make PDO calls we'll throw
an exception.

## Configuring entities and options

You **must** provide an explicit list of the entities that should be defined. This, and any other config, goes in 
`config/doctrine.php`:

```php
<?php
// application/config/doctrine.php
return [
    'entity_classes' => [
        \My\Amazing\Entity::class,
        \My\Other\Entity::class,
        \Third\Party\Entity::class,
    ],
    'orm' => [
        // 'auto_gen_proxies' => FALSE, (defaults to TRUE in development, FALSE otherwise)
        // 'proxy_dir         => APPPATH.'/DoctrineEntityProxy',
        // 'proxy_namespace'  => 'DoctrineEntityProxy',
        'custom_types' => [
            // Any custom column types that should be registered when Doctrine is loaded
            'money' => \My\Money\Type::class,
        ]        
    ]
];
```

## Configuring your Dependency Container

We provide bindings for the ingenerator/kohana-dependencies DI container. If you're also using our kohana-extras package
then your dependency config would generally look like this:

```php
<?php
// application/config/dependencies.php
return [
    '_include' => [
        \Ingenerator\KohanaDoctrine\Dependency\DoctrineFactory::definitions(),
    ],
    // Any of your own definitions and overrides
];
```

This will expose the entity manager as `doctrine.entity_manager` and a raw PDO connection as `doctrine.pdo_connection`, 
as well as various internal helpers - see the DoctrineFactory::definitions() method to explore the services that are 
defined.

You can also automatically bind event subscribers when the entity_manager is created:

```php
<?php
// application/config/dependencies.php
return [
    '_include' => [
        \Ingenerator\KohanaDoctrine\Dependency\DoctrineFactory::definitions(),
        \Ingenerator\KohanaDoctrine\Dependency\DoctrineFactory::subscriberDefinitions([
            \My\Subscriber::class => ['arguments' => ['%some.service.it.needs%']],
            \My\Other\Subscriber::class => ['arguments' => ['@some.config@']],
        ]),
    ],
    // Any of your own definitions and overrides
];
```

See DoctrineFactory::subscriberDefinitions for more details.

## Usage

Define entity classes with php annotations in the usual Doctrine way. They don't have to extend any particular base 
class. You can put these anywhere so long as they can be autoloaded. 

All entity classes must be listed in the `entity_classes` array in `config/doctrine.php` otherwise they will
not be detected for schema validation and database diff generation.

## Using the command line tools

Doctrine ships with a number of command line tools. To use them you'll need to provision a cli-config.php file.

It's expected that you've separated out your application bootstrap from your index.php and any minion / task runner
entry points so that application/bootstrap.php does all the setup including path definitions etc.

```php
<?php
// cli-config.php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require(__DIR__.'/application/bootstrap.php');

return ConsoleRunner::createHelperSet(\Dependencies::instance()->get('doctrine.entity_manager'));
``` 

## License

Copyright (c) 2013-2018, inGenerator Ltd
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
