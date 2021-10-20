<?php

namespace Artyum\Router;

use Artyum\Router\Exceptions\InvalidArgumentException;
use Artyum\Router\Exceptions\NoRoutesRegistered;
use Artyum\Router\Exceptions\NotFoundException;
use Artyum\Router\Exceptions\UnsupportHTTPMethodException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Router.
 */
class Router
{
    /**
     * @var array should contain an array of HTTP methods supported by the router
     */
    private $supportedMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ];

    /**
     * @var string should contain the base uri
     */
    private $baseUri;

    /**
     * @var RouteGroup should contain the RouteGroup class instance
     */
    private $group;

    /**
     * @var Route[] should contain an array of all registered routes
     */
    public $routes;

    /**
     * @var string|callable should contain the handler to execute when no route has been matched
     */
    private $notFoundHandler;

    /**
     * @var Route should contain the route that matched the current request
     */
    private $matchedRoute;

    /**
     * @var Request should contain the Symfony Http Request class instance
     */
    private $request;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    /**
     * Checks if the given http method & route matche one of the registered routes.
     */
    private function findMatch(string $currentMethod, string $currentRoute): ?Route
    {
        // loops through all registered routes and search for a match
        foreach ($this->routes as $route) {
            // checks if the current HTTP method corresponds to the registered HTTP method for this route
            if (!in_array($currentMethod, $route->getMethods(), true)) {
                continue;
            }

            $hasMatched = preg_match_all('#^' . $route->getUri() . '$#', $currentRoute, $matches, PREG_SET_ORDER);

            // if we found a match
            if ($hasMatched) {
                // checks if we have parameters
                if (!empty($matches)) {
                    $parameters = [];

                    // takes the needed part
                    $matches = $matches[0];

                    // removes the full match to keep only the groups
                    unset($matches[0]);

                    // saves only the named parameters into $parameters[] array and excludes numeric indexes (non-named parameters)
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $parameters[$key] = $value;
                        }
                    }

                    // saves the route parameters into the Route object
                    $route->setParameters($parameters);
                }

                return $route;
            }
        }

        // we don't have a match
        return null;
    }

    /**
     * Invokes the handler.
     *
     * @param callable|array $handler
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    private function invoke($handler)
    {
        // if the first argument is not an array and is an anonymous function or a function name
        if (!is_array($handler) && is_callable($handler)) {
            return call_user_func($handler, $this->request);
        }

        // if it's a class
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];

            return call_user_func([new $class(), $method], $this->request);
        }

        throw new InvalidArgumentException();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Gets the base uri.
     */
    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    /**
     * Sets the base uri.
     */
    public function setBaseUri(string $uri)
    {
        $this->baseUri = '/' . $uri . '/';
    }

    /**
     * Registers a "GET" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function get(string $uri, $handler): Route
    {
        return $this->addRoute(['GET'], $uri, $handler);
    }

    /**
     * Registers a "POST" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function post(string $uri, $handler): Route
    {
        return $this->addRoute(['POST'], $uri, $handler);
    }

    /**
     * Registers a "PUT" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function put(string $uri, $handler): Route
    {
        return $this->addRoute(['PUT'], $uri, $handler);
    }

    /**
     * Registers a "PATCH" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function patch(string $uri, $handler): Route
    {
        return $this->addRoute(['PATCH'], $uri, $handler);
    }

    /**
     * Registers a "DELETE" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function delete(string $uri, $handler): Route
    {
        return $this->addRoute(['DELETE'], $uri, $handler);
    }

    /**
     * Registers a "OPTIONS" route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function options(string $uri, $handler): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $handler);
    }

    /**
     * Registers a route that matches any HTTP methods.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function any(string $uri, $handler): Route
    {
        return $this->addRoute($this->supportedMethods, $uri, $handler);
    }

    /**
     * Registers a route.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function addRoute(array $methods, string $uri, $handler): Route
    {
        // converts the HTTP methods to uppercase and validates the HTTP method
        $methods = array_map('strtoupper', $methods);

        // adds the base uri to the route uri if any
        $uri = $this->baseUri . $uri;

        // creates a new route and store its information
        $route = (new Route($this->group))
            ->setUri($uri)
            ->setMethods($methods)
            ->setHandler($handler)
        ;

        // stores the newly created route into an array of Route
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Maps multiple HTTP methods to one route.
     */
    public function map(string $uri): RouteMapper
    {
        return new RouteMapper($uri, $this);
    }

    /**
     * Groups routes used to set prefix, middlewares or namespace.
     */
    public function group(callable $handler)
    {
        // saves the current group
        $currentGroup = $this->group;

        // creates a new group and stores its instance
        $this->group = new RouteGroup($this->group);

        // executes the callable and passes the newly created group instance
        call_user_func($handler, $this->group);

        // goes back to the previous group
        $this->group = $currentGroup;
    }

    /**
     * Sets the handler to execute when no route has been matched.
     *
     * @param $handler
     *
     * @throws InvalidArgumentException
     */
    public function setNotFoundHandler($handler)
    {
        if (!is_string($handler) && !is_callable($handler)) {
            throw new InvalidArgumentException();
        }
        $this->notFoundHandler = $handler;
    }

    /**
     * Catches current route/method and runs the defined handler, otherwise returns the notFound() method.
     *
     * @throws NotFoundException
     * @throws NoRoutesRegistered
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function dispatch()
    {
        $currentHTTPMethod = $this->request->getMethod();
        $currentUri = Helper::formatUri(($this->request->getPathInfo()));

        // if there are no routes registered, we throw an exception
        if (empty($this->routes)) {
            throw new NoRoutesRegistered();
        }

        // checks if we have a match
        $this->matchedRoute = $this->findMatch($currentHTTPMethod, $currentUri);

        // if we don't have a match we execute the not found handler if set, otherwise we throw an exception
        if ($this->matchedRoute === null) {
            if ($this->notFoundHandler) {
                return $this->invoke($this->notFoundHandler);
            }
            throw new NotFoundException();
        }

        // we have a match so save the matched route parameters in the Request object
        $this->request->attributes->add($this->matchedRoute->getParameters());

        // invokes the matched route before middleware(s) if any
        if (!empty($this->matchedRoute->getMiddlewares()['before'])) {
            foreach ($this->matchedRoute->getMiddlewares()['before'] as $middleware) {
                if (is_callable($middleware)) {
                    $this->invoke($middleware);
                } else {
                    $this->invoke([$middleware, 'handle']);
                }
            }
        }

        // invokes the matched route handler
        $this->invoke($this->matchedRoute->getHandler());

        // invokes the matched route after middleware(s) if any
        if (!empty($this->matchedRoute->getMiddlewares()['after'])) {
            foreach ($this->matchedRoute->getMiddlewares()['after'] as $middleware) {
                if (is_callable($middleware)) {
                    $this->invoke($middleware);
                } else {
                    $this->invoke([$middleware, 'handle']);
                }
            }
        }
    }

    /**
     * Gets all registered routes.
     */
    public function getRegisteredRoutes(): ?array
    {
        return $this->routes;
    }

    /**
     * Gets the matched route.
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * Builds an URL from the route name.
     */
    public function url(string $name, array $parameters = null): ?string
    {
        // loops through all registered routes to find a route matching this name
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route->getUri();
            }
        }

        // no route found
        return null;
    }
}
