<?php

namespace Artyum\Router;

use Artyum\Router\Exceptions\InvalidArgumentException;

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
     * @var string Should contain the route uri.
     */
    private $uri;

    /**
     * @var array Should contain an array of HTTP methods supported by this route.
     */
    private $methods;

    /**
     * @var string|callable Should contain the route handler.
     */
    private $handler;

    /**
     * @var array Should contain an array of route middlewares.
     */
    private $middlewares;

    /**
     * @var array Should contain an array of parameters that will be populated when this route matches the current request and contains parameters.
     */
    private $parameters;

    /**
     * Route constructor.
     * @param RouteGroup $group
     */
    public function __construct(?RouteGroup $group)
    {
        // if the group is inside a group, we add the group attributes to the route
        if ($group) {
            $this->setName($group->getNamePrefix());
            $this->setUri($group->getUriPrefix());
            $this->addMiddlewares($group->getMiddlewares());
            $this->addMiddlewares($group->getMiddlewares());
        }
    }

    /**
     * Gets the route name.
     *
     * @return mixed
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the route name.
     *
     * @param string $name
     * @return Route
     */
    public function setName(?string $name): Route
    {
        $this->name = $this->name . $name;

        return $this;
    }

    /**
     * Gets the route uri.
     *
     * @return string
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Sets the route uri.
     *
     * @param string $uri
     * @return Route
     */
    public function setUri(?string $uri): Route
    {
        $this->uri = Helper::formatUri($this->uri . '/' . $uri);

        return $this;
    }

    /**
     * Gets the route method.
     *
     * @return array
     */
    public function getMethods(): ?array
    {
        return $this->methods;
    }

    /**
     * Sets the route method.
     *
     * @param array $methods
     * @return Route
     */
    public function setMethods(array $methods): Route
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Gets the route handler.
     *
     * @return string|callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Sets the route handler.
     *
     * @param mixed $handler
     * @return Route
     * @throws InvalidArgumentException
     */
    public function setHandler($handler): Route
    {
        // if a wrong argument type is passed
        if (!is_callable($handler) && !is_array($handler)) {
            throw new InvalidArgumentException();
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * Gets the route middlewares.
     *
     * @return array
     */
    public function getMiddlewares(): ?array
    {
        return $this->middlewares;
    }

    /**
     * Sets the route middlewares.
     *
     * @param array $middlewares
     * @return Route
     */
    public function addMiddlewares(?array $middlewares): Route
    {
        if (!empty($this->middlewares)) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        } else {
            $this->middlewares = $middlewares;
        }

        return $this;
    }

    /**
     * Sets the before route middlewares.
     *
     * @param array $middlewares
     * @return Route
     */
    public function addBeforeMiddlewares(array $middlewares): Route
    {
        if (!empty($this->middlewares['before'])) {
            $this->middlewares = array_merge($this->middlewares['before'], $middlewares);
        } else {
            $this->middlewares['before'] = $middlewares;
        }

        return $this;
    }

    /**
     * Sets the after route middlewares.
     *
     * @param array $middlewares
     * @return Route
     */
    public function addAfterMiddlewares(array $middlewares): Route
    {
        if (!empty($this->middlewares['after'])) {
            $this->middlewares = array_merge($this->middlewares['after'], $middlewares);
        } else {
            $this->middlewares['after'] = $middlewares;
        }

        return $this;
    }

    /**
     * Gets the route parameters.
     *
     * @return array
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * Sets the route parameters.
     *
     * @param array $parameters
     * @return Route
     */
    public function setParameters(?array $parameters): Route
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Adds contraints on placeholders.
     *
     * @param array $placeholders
     * @return Route
     */
    public function where(array $placeholders): Route
    {
        $search = [];
        $replace = [];

        // loops through all parameters and stores their name & type
        foreach ($placeholders as $name => $type) {

            // if the placeholder is marked as optional
            if (strpos($this->getUri(), '{' . $name . '?}') !== false) {
                $search[]   = '{' . $name . '?}';
                $replace[]  = '(?<' . $name . '>' . $type . ')?';
            } else {
                $search[]   = '{' . $name . '}';
                $replace[]  = '(?<' . $name . '>' . $type . ')';
            }
        }

        return $this->setUri(
            str_replace($search, $replace, $this->getUri())
        );
    }

}
