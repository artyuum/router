<?php

namespace Artyum\Router;

use Artyum\Router\Exceptions\WrongArgumentTypeException;

/**
 * Class Route
 * @package Artyum\Router
 */
class Route
{

    /**
     * @var string Should contain the name of the route.
     */
    private $name;

    /**
     * @var string Should contain the route path.
     */
    private $path;

    /**
     * @var array Should contain an array of HTTP methods supported by this route.
     */
    private $method;

    /**
     * @var string|callable Should contain the route handler.
     */
    private $handler;

    /**
     * @var array Should contain an array of route middlewares.
     */
    private $middlewares;

    /**
     * Route constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function setName(string $name): Route
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Route
     */
    public function setPath(string $path): Route
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return array
     */
    public function getMethod(): ?array
    {
        return $this->method;
    }

    /**
     * @param array $method
     * @return Route
     */
    public function setMethod(array $method): Route
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string|callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param mixed $handler
     * @return Route
     * @throws WrongArgumentTypeException
     */
    public function setHandler($handler): Route
    {
        // if a wrong argument type is passed
        if (!is_callable($handler) && !is_array($handler)) {
            throw new WrongArgumentTypeException();
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): ?array
    {
        return $this->middlewares;
    }

    /**
     * @param array $middlewares
     * @return Route
     */
    public function setBeforeMiddlewares(array $middlewares): Route
    {
        if (!empty($this->middlewares['before'])) {
            $this->middlewares = array_merge($this->middlewares['before'], $middlewares);
        } else {
            $this->middlewares['before'] = $middlewares;
        }

        return $this;
    }

    /**
     * @param array $middlewares
     * @return Route
     */
    public function setAfterMiddlewares(array $middlewares): Route
    {
        if (!empty($this->middlewares['after'])) {
            $this->middlewares = array_merge($this->middlewares['after'], $middlewares);
        } else {
            $this->middlewares['after'] = $middlewares;
        }

        return $this;
    }

    /**
     * @param array $middlewares
     * @return Route
     */
    public function setMiddlewares(array $middlewares): Route
    {
        if (!empty($this->middlewares)) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        } else {
            $this->middlewares = $middlewares;
        }

        return $this;
    }

}
