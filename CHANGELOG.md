### Unreleased

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