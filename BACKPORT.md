# Laravel 4.2 Compatibility Backport Changes

This document details a bullet list of changes made to various parts of the code to get the package working in Laravel 4.2.

## Misc.

These changes do not really pertain to code itself, but are changes to the repository no-less.

* removed **all** laravel `>=5.5.*`/`6` dependencies
* deleted various meta data (dot) files in the root of the repository
* removed generated documentation

## Config File(s)

The configuration file needed some modifications in order to not throw errors, due to helper functions not yet existing during the bootstrap process.

* remove `env` helper function calls and simply assign the default/fallback value.

## Service Provider

The `LighthouseServiceProvider` file need some minor modification to properly register the services and configurations.

* replace `publishes` method calls to `package` method call
* use route facade to register routes instead of `loadRoutesFrom` method
    - also remove check for lumen framework when doing this
* use validation facade to set resolver for GraphQL validation, instead of validation factory DI
* change config calls to call consumer (published) config file before trying package's

## Lighthouse Request

This file was pretty good out of the gate, except that 

* swap call to request instance method `hasAny` to multiple calls on `has`
    - `hasAny` is simply `has` but accepts variadic set of keys to check

## Lighthouse Response (Single)

* replace `response` helper function call with `Response::json` facade macro
    - implementing the `response()` helper function to work properly ~is~ was just not worth the time

## GraphQL Entrypoint

This file needed some heavy modification to prep the schema and execute the query without barfing errors and exceptions everywhere.

* remove/comment out event dispatcher DI and usage of the instance (**this should be replaced with Larvel 4.2 events**)
    - also commented out whatever the fuck `BuildExtensionsResponse` event and related code does...
* change config calls to call consumer (published) config file before trying package's

## AST (Abstract Syntax Tree) Builder

* more commenting out of event dispatcher calls
* switch `Arr::prepend` call to helper function `array_prepend` to allow for Dealsix polyfilling the function easily
