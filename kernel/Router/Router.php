<?php

namespace App\Kernel\Router;

use App\Kernel\Auth\AuthInterface;
use App\Kernel\Controller\Controller;
use App\Kernel\Database\DatabaseInterface;
use App\Kernel\Http\RedirectInterface;
use App\Kernel\Http\RequestInterface;
use App\Kernel\Middleware\AbstractMiddleware;
use App\Kernel\Session\SessionInterface;
use App\Kernel\Storage\StorageInterface;
use App\Kernel\View\ViewInterface;
use JetBrains\PhpStorm\NoReturn;

class Router implements RouterInterface
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(
        private readonly ViewInterface $view,
        private readonly RequestInterface $request,
        private readonly RedirectInterface $redirect,
        private readonly SessionInterface $session,
        private readonly DatabaseInterface $database,
        private readonly AuthInterface $auth,
        private readonly StorageInterface $storage
    ) {
        $this->initRoutes();
    }

    public function dispatch(string $uri, string $method): void
    {
        $route = $this->findRoute($uri, $method);

        if (! $route) {
            $this->notFound();
        }

        if ($route->hasMiddlewares()) {
            foreach ($route->getMiddlewares() as $middleware) {
                /** @var AbstractMiddleware $middleware */
                $middleware = new $middleware($this->request, $this->auth, $this->redirect);

                $middleware->handle();
            }
        }

        if (is_array($route->getAction())) {
            [$controller, $action] = $route->getAction();
            /** @var Controller $controller */
            $controller = new $controller();

            call_user_func([$controller, 'setView'], $this->view);
            call_user_func([$controller, 'setRequest'], $this->request);
            call_user_func([$controller, 'setRedirect'], $this->redirect);
            call_user_func([$controller, 'setSession'], $this->session);
            call_user_func([$controller, 'setDatabase'], $this->database);
            call_user_func([$controller, 'setAuth'], $this->auth);
            call_user_func([$controller, 'setStorage'], $this->storage);

            call_user_func([$controller, $action]);
        } else {
            call_user_func($route->getAction());
        }
    }

    #[NoReturn]
    private function notFound(): void
    {
        echo '404 | Not Found';
        exit();
    }

    private function findRoute(string $uri, string $method): Route|false
    {
        if (! isset($this->routes[$method][$uri])) {
            return false;
        }

        return $this->routes[$method][$uri];
    }

    private function initRoutes(): void
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            $this->routes[$route->getMethod()][$route->getUri()] = $route;
        }

    }

    /**
     * @return Route[]
     */
    private function getRoutes(): array
    {
        return require_once APP_PATH.'/config/routes.php';
    }
}
