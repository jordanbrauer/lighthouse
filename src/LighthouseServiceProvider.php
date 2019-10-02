<?php

namespace Nuwave\Lighthouse;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Validation\Validator;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Schema\NodeRegistry;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Console\QueryCommand;
use Nuwave\Lighthouse\Console\UnionCommand;
use Nuwave\Lighthouse\Console\ScalarCommand;
use Illuminate\Contracts\Container\Container;
use Nuwave\Lighthouse\Console\MutationCommand;
use Nuwave\Lighthouse\Schema\ResolverProvider;
use Nuwave\Lighthouse\Console\IdeHelperCommand;
use Nuwave\Lighthouse\Console\InterfaceCommand;
use Nuwave\Lighthouse\Execution\ContextFactory;
use Nuwave\Lighthouse\Execution\GraphQLRequest;
use Nuwave\Lighthouse\Execution\SingleResponse;
use Nuwave\Lighthouse\Execution\Utils\GlobalId;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Console\ClearCacheCommand;
use Nuwave\Lighthouse\Console\PrintSchemaCommand;
use Nuwave\Lighthouse\Execution\GraphQLValidator;
use Laravel\Lumen\Application as LumenApplication;
use Nuwave\Lighthouse\Console\SubscriptionCommand;
use Nuwave\Lighthouse\Execution\LighthouseRequest;
use Nuwave\Lighthouse\Schema\Source\SchemaStitcher;
use Nuwave\Lighthouse\Console\ValidateSchemaCommand;
use Nuwave\Lighthouse\Execution\MultipartFormRequest;
use Illuminate\Validation\Factory as ValidationFactory;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Schema\Factories\DirectiveFactory;
use Nuwave\Lighthouse\Support\Contracts\CreatesResponse;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Support\Contracts\ProvidesResolver;
use Nuwave\Lighthouse\Support\Contracts\CanStreamResponse;
use Illuminate\Foundation\Application as LaravelApplication;
use Nuwave\Lighthouse\Support\Http\Responses\ResponseStream;
use Nuwave\Lighthouse\Support\Compatibility\MiddlewareAdapter;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nuwave\Lighthouse\Support\Compatibility\LumenMiddlewareAdapter;
use Nuwave\Lighthouse\Support\Compatibility\LaravelMiddlewareAdapter;
use Nuwave\Lighthouse\Support\Contracts\GlobalId as GlobalIdContract;
use Nuwave\Lighthouse\Support\Contracts\ProvidesSubscriptionResolver;

class LighthouseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->package('nuwave/lighthouse', 'lighthouse', __DIR__.'/../');
        \Route::group([], function () {
            require __DIR__.'/Support/Http/routes.php';
        });
        \Validator::resolver(function ($translator, array $data, array $rules, array $messages, array $customAttributes): Validator {
            // This determines whether we are resolving a GraphQL field
            return Arr::has($customAttributes, ['root', 'context', 'resolveInfo'])
                ? new GraphQLValidator($translator, $data, $rules, $messages, $customAttributes)
                : new Validator($translator, $data, $rules, $messages, $customAttributes);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'lighthouse');
        $this->app->singleton(GraphQL::class);
        $this->app->singleton(DirectiveFactory::class);
        $this->app->singleton(NodeRegistry::class);
        $this->app->singleton(TypeRegistry::class);
        $this->app->singleton(CreatesContext::class, ContextFactory::class);
        $this->app->singleton(CanStreamResponse::class, ResponseStream::class);
        $this->app->bind(CreatesResponse::class, SingleResponse::class);
        $this->app->bind(GlobalIdContract::class, GlobalId::class);
        $this->app->singleton(GraphQLRequest::class, function ($app): GraphQLRequest {
            /** @var \Illuminate\Http\Request $request */
            $request = $app->make('request');

            return Str::startsWith($request->header('Content-Type'), 'multipart/form-data')
                ? new MultipartFormRequest($request)
                : new LighthouseRequest($request);
        });
        $this->app->singleton(SchemaSourceProvider::class, function (): SchemaStitcher {
            // NOTE: read from consumer config first!
            return new SchemaStitcher(config('lighthouse::schema.register', config('lighthouse.schema.register')));
        });
        $this->app->bind(ProvidesResolver::class, ResolverProvider::class);
        $this->app->bind(ProvidesSubscriptionResolver::class, function (): ProvidesSubscriptionResolver {
            return new class implements ProvidesSubscriptionResolver {
                public function provideSubscriptionResolver(FieldValue $fieldValue): Closure
                {
                    throw new Exception('Add the SubscriptionServiceProvider to your config/app.php to enable subscriptions.');

                    return function () {};
                }
            };
        });
        $this->app->singleton(MiddlewareAdapter::class, function (Container $app): MiddlewareAdapter {
            // prefer using fully-qualified class names here when referring to Laravel-only or Lumen-only classes
            if ($app instanceof LaravelApplication) {
                return new LaravelMiddlewareAdapter($app->get(Router::class));
            } elseif ($app instanceof LumenApplication) {
                return new LumenMiddlewareAdapter($app);
            }

            throw new Exception('Could not correctly determine Laravel framework flavor, got '.get_class($app).'.');
        });

        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         ClearCacheCommand::class,
        //         IdeHelperCommand::class,
        //         InterfaceCommand::class,
        //         MutationCommand::class,
        //         PrintSchemaCommand::class,
        //         QueryCommand::class,
        //         ScalarCommand::class,
        //         SubscriptionCommand::class,
        //         UnionCommand::class,
        //         ValidateSchemaCommand::class,
        //     ]);
        // }
    }

    /**
     * Polyfill/stub method from Laravel 5/6 service providers
     *
     * @param string $source The source file path to the base (package provided) config
     * @param string $name The name of the config key to get and set merged config from/to
     * @return void
     */
    private function mergeConfigFrom(string $source, string $name): void
    {
        $config = $this->app['config']->get($name, []);

        $this->app['config']->set($name, array_merge(require $source, $config));
    }
}
