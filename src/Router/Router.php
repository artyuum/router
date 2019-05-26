<?php

namespace Artyum\Router;

use Artyum\Router\Exceptions\NoRoutesRegistered;
use Artyum\Router\Exceptions\NotFoundException;
use Artyum\Router\Exceptions\UnsupportHTTPMethodException;
use Artyum\Router\Exceptions\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Router
 * @package Artyum\Router
 */
class Router
{

    /**
     * @var array Should contain an array of HTTP methods supported by the router.
     */
    private $supportedMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS'
    ];

    /**
     * @var string Should contain the base path.
     */
    private $basePath;

    /**
     * @var RouteGroup Should contain the RouteGroup class instance.
     */
    private $group;

    /**
     * @var Route[] Should contain an array of all registered routes.
     */
    public $routes;

    /**
     * @var string|callable Should contain the handler to execute when no route has been matched.
     */
    private $notFoundHandler;

    /**
     * @var Route Should contain the route that matched the current request.
     */
    private $matchedRoute;

    /**
     * @var Request Should contain the Symfony Http Request class instance.
     */
    private $request;

    /**
     * @var Response Should contain the Symfony Http Response class instance.
     */
    private $response;

    /**
     * Router constructor.
     */
    public function __construct() {
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
    }

    /**
     * Checks if the given http method & route matche one of the registered routes.
     *
     * @param string $currentMethod
     * @param string $currentRoute
     * @return Route|null
     */
    private function findMatch(string $currentMethod, string $currentRoute): ?Route
    {
        // loops though all registered routes and search for a match
        foreach ($this->routes as $route) {
            // checks if the current HTTP method corresponds to the registered HTTP method for this route
            if (!in_array($currentMethod, $route->getMethod())) {
                continue;
            }

            $hasMatched = preg_match_all('#^' . $route->getPath() . '$#', $currentRoute, $matches, PREG_SET_ORDER);

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
     * Invokes the controller with arguments if any.
     *
     * @param callable|array $handler
     * @param mixed ...$additionalArguments
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function invoke($handler, ...$additionalArguments)
    {
        // if the first argument is not an array and is an anonymous function or a function name
        if (!is_array($handler) && is_callable($handler)) {
            return call_user_func($handler, $this->request, $this->response, $additionalArguments);
        }

        // if it's a class
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];

            return call_user_func([new $class(), $method], $this->request, $this->response, $additionalArguments);
        }

        throw new InvalidArgumentException();
    }

    /**
     * Gets the base path.
     *
     * @return string|null
     */
    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    /**
     * Sets the base path.
     *
     * @param string $path
     */
    public function setBasePath(string $path)
    {
        $this->basePath = '/' . $path . '/';
    }

    /**
     * Registers a "GET" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function get(string $path, $handler): Route
    {
        return $this->addRoute(['GET'], $path, $handler);
    }

    /**
     * Registers a "POST" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function post(string $path, $handler): Route
    {
        return $this->addRoute(['POST'], $path, $handler);
    }

    /**
     * Registers a "PUT" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function put(string $path, $handler): Route
    {
        return $this->addRoute(['PUT'], $path, $handler);
    }

    /**
     * Registers a "PATCH" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function patch(string $path, $handler): Route
    {
        return $this->addRoute(['PATCH'], $path, $handler);
    }

    /**
     * Registers a "DELETE" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function delete(string $path, $handler): Route
    {
        return $this->addRoute(['DELETE'], $path, $handler);
    }

    /**
     * Registers a "OPTIONS" route.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function options(string $path, $handler): Route
    {
        return $this->addRoute(['OPTIONS'], $path, $handler);
    }

    /**
     * Registers a route that matches any HTTP methods.
     *
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function any(string $path, $handler): Route
    {
        return $this->addRoute($this->supportedMethods, $path, $handler);
    }

    /**
     * Registers a route.
     *
     * @param array $method
     * @param string $path
     * @param $handler
     * @return Route
     * @throws UnsupportHTTPMethodException
     * @throws InvalidArgumentException
     */
    public function addRoute(array $method, string $path, $handler): Route
    {
        // converts the HTTP methods to uppercase and validates the HTTP method
        $method = array_map('strtoupper', $method);
        if (array_diff($method, $this->supportedMethods)) {
            throw new UnsupportHTTPMethodException();
        }

        // adds the base path to the route path if any
        $path = $this->basePath . $path;

        // creates a new route and store its informations
        $route = new Route($this->group);
        $route
            ->setPath($path)
            ->setMethod($method)
            ->setHandler($handler);

        // stores the newly created route into an array of Route
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Maps multiple HTTP methods to one route.
     *
     * @param string $path
     * @return RouteMapper
     */
    public function map(string $path): RouteMapper
    {
        $mapper = new RouteMapper($path, $this);

        return $mapper;
    }

    /**
     * Groups routes used to set prefix, middlewares or namespace.
     *
     * @param callable $handler
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
     * @return mixed
     * @throws NotFoundException
     * @throws NoRoutesRegistered
     * @throws InvalidArgumentException
     */
    public function dispatch()
    {
        $currentHTTPMethod  = $this->request->getMethod();
        $currentPath        = Helper::formatPath(($this->request->getPathInfo()));

        // if there are no routes registered, we throw an exception
        if (empty($this->routes)) {
            throw new NoRoutesRegistered();
        }

        // checks if we have a match
        $this->matchedRoute = $this->findMatch($currentHTTPMethod, $currentPath);

        // if we don't have a match we execute the not found handler if set, otherwise we throw an exception
        if ($this->matchedRoute === null) {
            if ($this->notFoundHandler) {
                return $this->invoke($this->notFoundHandler);
            } else {
                throw new NotFoundException();
            }
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
     *
     * @return array
     */
    public function getRegisteredRoutes(): ?array
    {
        return $this->routes;
    }

    /**
     * Gets the matched route.
     *
     * @return Route
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * Builds an URL from the route name.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function url(string $name, array $parameters = null): ?string
    {
        // loops through all registered routes to find a route matching this name
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route->getPath();
            }
        }

        // no route found
        return null;
    }

}
