<?php

namespace Artyum\Router;

/**
 * Class RouteGroup
 * @package Artyum\Router
 */
class RouteGroup
{

    /**
     * @var string Should contain the prefix that will be added to the routes uri.
     */
    private $prefix;

    /**
     * @var array Should contain an array of routes middlewares.
     */
    private $middlewares;

    /**
     * RouteGroup constructor.
     * @param RouteGroup $group
     */
    public function __construct(RouteGroup $group = null)
    {
        if ($group) {
            $this->setPrefix($group->getPrefix());
            $this->setMiddlewares($group->getMiddlewares());
        }
    }

    /**
     * Gets the routes prefix.
     *
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Sets the routes prefix.
     *
     * @param string $prefix
     * @return RouteGroup
     */
    public function setPrefix(?string $prefix): RouteGroup
    {
        $this->prefix = '/' . $prefix . '/';

        return $this;
    }

    /**
     * Gets the routes middlewares.
     *
     * @return array
     */
    public function getMiddlewares(): ?array
    {
        return $this->middlewares;
    }

    /**
     * Sets the before routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function setBeforeMiddlewares(array $middlewares): RouteGroup
    {
        return $this->setMiddlewares([
            'before' => $middlewares
        ]);
    }

    /**
     * Sets the after routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function setAfterMiddlewares(array $middlewares): RouteGroup
    {
        return $this->setMiddlewares([
            'after' => $middlewares
        ]);
    }

    /**
     * Sets the routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function setMiddlewares(?array $middlewares): RouteGroup
    {
        if (!empty($this->middlewares)) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        } else {
            $this->middlewares = $middlewares;
        }

        return $this;
    }

}
