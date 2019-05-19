<?php

namespace Artyum\Router;

use phpDocumentor\Reflection\Types\Callable_;

/**
 * Class RouteMapper
 * @package Artyum\Router
 */
class RouteMapper
{

    /**
     * @var string Should contain the path to match for the routes.
     */
    private $path;

    /**
     * @var Router Should contain the Router class instance.
     */
    private $router;

    /**
     * @var Route Should contain the last register Route instance.
     */
    private $lastRegisteredRoute;

    /**
     * RouteMapper constructor.
     * @param string $path
     * @param Router $router
     */
    public function __construct(string $path, Router $router)
    {
        $this->path = $path;
        $this->router = $router;
    }

    public function get(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->get($this->path, $handler);

        return $this;
    }

    public function post(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->post($this->path, $handler);

        return $this;
    }

    public function put(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->put($this->path, $handler);

        return $this;
    }

    public function patch(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->patch($this->path, $handler);

        return $this;
    }

    public function delete(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->delete($this->path, $handler);

        return $this;
    }

    public function options(array $handler): RouteMapper
    {
        $this->lastRegisteredRoute = $this->router->options($this->path, $handler);

        return $this;
    }

    public function withAttributes(Callable $callback): RouteMapper
    {
        call_user_func($callback, $this->lastRegisteredRoute);

        return $this;
    }

}
