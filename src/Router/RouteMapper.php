<?php

namespace Artyum\Router;

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
     * RouteMapper constructor.
     * @param string $path
     * @param Router $router
     */
    public function __construct(string $path, Router $router)
    {
        $this->path = $path;
        $this->router = $router;
    }

    /**
     * Registers a "GET" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function get($handler): RouteMapper
    {
        $this->router->get($this->path, $handler);

        return $this;
    }

    /**
     * Registers a "POST" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function post($handler): RouteMapper
    {
        $this->router->post($this->path, $handler);

        return $this;
    }

    /**
     * Registers a "PUT" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function put($handler): RouteMapper
    {
        $this->router->put($this->path, $handler);

        return $this;
    }

    /**
     * Registers a "PATCH" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function patch($handler): RouteMapper
    {
        $this->router->patch($this->path, $handler);

        return $this;
    }

    /**
     * Registers a "DELETE" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function delete($handler): RouteMapper
    {
        $this->router->delete($this->path, $handler);

        return $this;
    }

    /**
     * Registers a "OPTIONS" route.
     *
     * @param $handler
     * @return RouteMapper
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function options($handler): RouteMapper
    {
        $this->router->options($this->path, $handler);

        return $this;
    }

    /**
     * Gets the last registered Route object.
     *
     * @param callable $callback
     * @return RouteMapper
     */
    public function addAttributes(callable $callback): RouteMapper
    {
        $registeredRoute = $this->router->getRegisteredRoutes();

        // gets the last element from the routes array
        $lastRegisteredRoute = end($registeredRoute);

        call_user_func($callback, $lastRegisteredRoute);

        return $this;
    }

}
