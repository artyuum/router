<?php

namespace Artyum\Router;

/**
 * Class RouteGroup
 * @package Artyum\Router
 */
class RouteGroup
{

    /**
     * @var string Should contain the name prefix that will be added to the routes name.
     */
    private $namePrefix;

    /**
     * @var string Should contain the uri prefix that will be added to the routes uri.
     */
    private $uriPrefix;

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
        // if it's a nested group, we get the parent group attributes
        if ($group) {
            $this->setNamePrefix($group->getNamePrefix());
            $this->setUriPrefix($group->getUriPrefix());
            $this->addMiddlewares($group->getMiddlewares());
        }
    }

    /**
     * Gets the routes name prefix.
     *
     * @return string
     */
    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    /**
     * Sets the routes name prefix.
     *
     * @param string $namePrefix
     * @return RouteGroup
     */
    public function setNamePrefix(string $namePrefix): RouteGroup
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    /**
     * Gets the routes uri prefix.
     *
     * @return string
     */
    public function getUriPrefix(): ?string
    {
        return $this->uriPrefix;
    }

    /**
     * Sets the routes uri prefix.
     *
     * @param string $uriPrefix
     * @return RouteGroup
     */
    public function setUriPrefix(?string $uriPrefix): RouteGroup
    {
        $this->uriPrefix = '/' . $uriPrefix . '/';

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
     * Sets the routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function addMiddlewares(?array $middlewares): RouteGroup
    {
        if (!empty($this->middlewares)) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        } else {
            $this->middlewares = $middlewares;
        }

        return $this;
    }

    /**
     * Sets the before routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function addBeforeMiddlewares(array $middlewares): RouteGroup
    {
        return $this->addMiddlewares([
            'before' => $middlewares
        ]);
    }

    /**
     * Sets the after routes middlewares.
     *
     * @param array $middlewares
     * @return RouteGroup
     */
    public function addAfterMiddlewares(array $middlewares): RouteGroup
    {
        return $this->addMiddlewares([
            'after' => $middlewares
        ]);
    }

}
