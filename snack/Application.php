<?php

declare(strict_types=1);

namespace Snack;

use Bramus\Router\Router as BramusRouterLib;
use League\Plates\Engine;
use Snack\Config\Repository;
use Snack\Container\Container;
use Snack\Database\Connection;
use Snack\Database\ConnectionFactory;
use Snack\Database\ConnectionInterface;
use Snack\Database\Model;
use Snack\Http\Request;
use Snack\Routing\BramusRouter;
use Snack\Routing\RouterInterface;
use Snack\View\Extension\VueExtension;
use Snack\View\PlatesView;
use Snack\View\ViewInterface;

final class Application extends Container
{
    public function boot(array $config): self
    {
        $this->instance(Repository::class, new Repository($config));

        $this->registerDatabase();
        $this->registerView();
        $this->registerHttp();
        $this->registerRouter();
        $this->loadRoutes();

        return $this;
    }

    public function run(): void
    {
        $this->get(RouterInterface::class)->dispatch()->send();
    }

    private function registerHttp(): void
    {
        // Request's constructor is private (built only via fromGlobals()),
        // so it needs an explicit binding — autowiring can't call it.
        $this->singleton(Request::class, static fn (): Request => Request::fromGlobals());
    }

    private function registerDatabase(): void
    {
        $this->singleton(ConnectionInterface::class, function (self $app): Connection {
            $config = $app->get(Repository::class);

            return ConnectionFactory::make($config->get('database', []));
        });

        // The Active Record layer resolves its connection statically (see
        // Model's docblock for why); wire it up as soon as the real
        // connection binding above has been resolved for the first time.
        Model::setConnection($this->get(ConnectionInterface::class));
    }

    private function registerView(): void
    {
        $this->singleton(Engine::class, function (self $app): Engine {
            $config = $app->get(Repository::class);

            $engine = new Engine($config->get('view.templates_path'));
            $engine->loadExtension(new VueExtension((string) $config->get('view.public_path')));

            return $engine;
        });

        $this->singleton(ViewInterface::class, PlatesView::class);
    }

    private function registerRouter(): void
    {
        $this->singleton(BramusRouterLib::class);
        $this->singleton(
            RouterInterface::class,
            fn (self $app): RouterInterface => new BramusRouter($app->get(BramusRouterLib::class), $app)
        );
    }

    private function loadRoutes(): void
    {
        $router = $this->get(RouterInterface::class);

        $config = $this->get(Repository::class);

        foreach ($config->get('routes', []) as $file) {
            (static function (string $__file, RouterInterface $router) : void {
                require $__file;
            })($file, $router);
        }
    }
}
