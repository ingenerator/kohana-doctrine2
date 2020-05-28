### Unreleased

## v1.1.1 (2020-05-28)

* Restrict doctrine/common and doctrine/persistence versions - the latest minor release of doctrine/orm allows a 
  breaking release in these packages but we directly use classes and interfaces that have now been renamed.

## v1.1.0 (2020-02-14)

* Add support for configuring database connection timeout, with 5 second default

## v1.0.0 (2019-04-03)

* Drop php5 support
* Run php-cs-fixer native_function_invocation fix to potentially improve performance

## v0.2.0 (2018-12-06)

* Move default entity proxy namespace / path to APPPATH/DoctrineEntityProxy - not in the classes path at all
* Switch to using autoloader directly to autoload doctrine annotations instead of having to find and register the
  annotations entrypoint file.
* Add new mechanism for injecting event subscribers
* Add new factories and kohana-dependencies service definitions for all services. Switch to using 
  factories with inbuilt default config so that this package does not have to be registered as a 
  kohana module in its own right.
* Add new ConnectionConfigProvider to parse Kohana database.php config and return doctrine array, including with
  a NullPDO if there's no hostname.
* Add new NullPDO that can be passed around to classes that need a PDO injected but don't need to do anything
  with it - e.g. to allow setup of the Doctrine build tools in an environment that can't actually speak to a DB
* Switch package to generic library type rather than kohana module (so no need to override install path)
* Add new ExplicitClasslistAnnotationDriver for listing pre-configured entity class types
* Remove old Doctrine_EMFactory - will be replaced with new dependency factories and DI container definitions
* Remove factory support for anything other than the simple annotation reader - you'll be able to inject own reader in
  DI container.
* Remove factory support for the use_underscore_naming option - you'll be able to inject your own naming strategies in
  the DI container.
* Remove the KohanaAnnotationDriver - we no longer support automagically loading entities from the CFS
* Require ingenerator fork of kohana, use phpunit direct instead of kohana/unittest, update config of koharness and
  test paths.

## v0.1.1 (2018-12-04)

* Fix travis build config, drop support for PHP < 5.5

## v0.1.0 (2018-12-04)

* Release the old dev-master branch as a 0.1 release
