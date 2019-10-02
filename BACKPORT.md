# Laravel 4.2 Compatibility Backport Changes

This document details a bullet list of changes made to various parts of the code to get the package working in Laravel 4.2.

## Misc.

These changes do not really pertain to code itself, but are changes to the repository no-less.

* removed **all** laravel `>=5.5.*`/`6` dependencies
* deleted various meta data (dot) files in the root of the repository
* removed generated documentation

## Service Provider

The `src/LighthouseServiceProvider.php` file need some minor modification to properly register the services and configurations.

* replace `publishes` method calls to `package` method call
* use route facade to register routes instead of `loadRoutesFrom` method
    - also remove check for lumen framework when doing this
* use validation facade to set resolver for GraphQL validation, instead of validation factory DI
* change config calls to call consumer (published) config file before trying package's
