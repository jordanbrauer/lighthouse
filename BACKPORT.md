#### Service Provider

* replace `publishes` method calls to `package` method call
* use route facade to register routes instead of `loadRoutesFrom` method
    - also remove check for lumen framework when doing this
* use validation facade to set resolver for GraphQL validation, instead of validation factory DI
* change config calls to call consumer (published) config file before trying package's
