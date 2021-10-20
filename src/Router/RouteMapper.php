<?php

namespace Artyum\Router;

/**
 * Class RouteMapper.
 */
class RouteMapper
{
    /**
     * @var string should contain the uri to match for the routes
     */
    private $uri;

    /**
     * @var Router should contain the Router class instance
     */
    private $router;

    /**
     * RouteMapper constructor.
     */
    public function __construct(string $uri, Router $router)
    {
        $this->uri = $uri;
        $this->router = $router;
    }

    /**
     * Registers a "GET" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function get($handler): RouteMapper
    {
        $this->router->get($this->uri, $handler);

        return $this;
    }

    /**
     * Registers a "POST" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function post($handler): RouteMapper
    {
        $this->router->post($this->uri, $handler);

        return $this;
    }

    /**
     * Registers a "PUT" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function put($handler): RouteMapper
    {
        $this->router->put($this->uri, $handler);

        return $this;
    }

    /**
     * Registers a "PATCH" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function patch($handler): RouteMapper
    {
        $this->router->patch($this->uri, $handler);

        return $this;
    }

    /**
     * Registers a "DELETE" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function delete($handler): RouteMapper
    {
        $this->router->delete($this->uri, $handler);

        return $this;
    }

    /**
     * Registers a "OPTIONS" route.
     *
     * @param $handler
     *
     * @throws Exceptions\UnsupportHTTPMethodException
     * @throws Exceptions\InvalidArgumentException
     */
    public function options($handler): RouteMapper
    {
        $this->router->options($this->uri, $handler);

        return $this;
    }

    /**
     * Gets the last registered Route object.
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
