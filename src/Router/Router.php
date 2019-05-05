<?php

namespace Artyum\Router;

use Artyum\Router\Exceptions\DelimiterNotFoundException;
use Artyum\Router\Exceptions\UnsupportHTTPMethodException;
use Artyum\Router\Exceptions\WrongArgumentTypeException;

/**
 * Class Router
 *
 * This is the Router class that is used to route the incoming requests to the proper handler (controller).
 */
class Router
{

    /**
     * @var array Should contain an array of allowed HTTP methods.
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
     * @var string Should contain the namespace to use for all handlers.
     */
    private $handlersNamespace;

    /**
     * @var string Should contain the namespace to use for all middlewares.
     */
    private $middlewaresNamespace;

    /**
     * @var string Should contain the last registered route(s) prefix.
     */
    private $prefix;

    /**
     * @var string|array Should contain the last registered route(s) middleware(s).
     */
    private $middlewares;

    /**
     * @var array Should contain an array of all registered routes.
     */
    private $routes;

    /**
     * @var string|callable Should contain the handler to execute when no route has been matched.
     */
    private $notFoundHandler;

    /**
     * @var array Should contain an array parameters (from the matched route).
     */
    private $routeParameters;

    /**
     * Router constructor.
     */
    public function __construct() {}

    /**
     * Removes unneeded slashes.
     *
     * @param string $path
     * @return string
     */
    private function formatRoute(string $path)
    {
        $path = preg_replace('#/+#','/', $path); // removes extra slashes
        $path = rtrim($path, '/'); // removes the trailing slash

        return $path;
    }

    /**
     * Checks if the given route matches one of the registered routes.
     *
     * @param string $currentMethod
     * @param string $currentRoute
     * @return array|false
     */
    private function findMatch(string $currentMethod, string $currentRoute)
    {
        /**
         * "The HEAD method is identical to GET except that the server MUST NOT return a message-body in the response."
         * @link https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
         */
        if ($currentMethod == 'HEAD') {
            ob_start(); // prevents the output from being sent to the client (but keeping sending the headers)
            $currentMethod = 'GET'; // sets the method to "GET" in order to fallback to a matching "GET" route
        }

        // loops though all registered routes and then executes the defined handler if there is a match
        foreach ($this->routes as $route) {
            // checks if the current HTTP method corresponds to the registered HTTP method for this route
            if (is_array($route['method'])) {
                if (!in_array($currentMethod, $route['method'])) {
                    continue;
                }
            } else {
                if ($route['method'] !== $currentMethod) {
                    continue;
                }
            }

            // if we have a match
            if (preg_match_all('#^' . $route['path'] . '$#', $currentRoute, $matches, PREG_OFFSET_CAPTURE)) {

                // we only take the needed array part
                $matches = array_slice($matches, 1);

                // extracts the matched URL parameters (and only the parameters)
                $routeParameters = array_map(function ($match, $index) use ($matches) {
                    // we have a following parameter, so we take the substring from the current parameter position until the next one's position (thanks to PREG_OFFSET_CAPTURE)
                    if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    } else { // we have no following parameters, so we return the whole lot
                        return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                    }
                }, $matches, array_keys($matches));

                // stores the route parameters
                $this->routeParameters = $routeParameters;

                return $route;
            }
        }

        // we does not have a match
        return false;
    }

    /**
     * Invokes the controller with arguments if any.
     *
     * @param $handler
     * @return mixed
     */
    private function invoke($handler)
    {
        // if it's a callable
        if (is_callable($handler)) {
            return call_user_func($handler);
        }

        // otherwise, if it's a string
        $handler    = explode('@', $handler);
        $class      = $handler[0]; // e.g. HomepageController
        $method     = $handler[1]; // e.g index

        // if arguments are set, we pass them to the handler
        return call_user_func([new $class(), $method]);
    }

    /**
     * Handles not found routes.
     *
     * @return bool
     */
    private function notFound(): bool
    {
        // checks if the "not found" handler is set and executes it
        if ($this->notFoundHandler) {
            return $this->invoke($this->notFoundHandler);
        }

        // the "not found" handler is not set, we send the default response (404)
        return http_response_code(404);
    }

    /**
     * Sets the base path.
     *
     * @param string $path
     */
    public function setBasePath(string $path)
    {
        $this->basePath = $path;
    }

    /**
     * Sets the namespace for all handlers.
     *
     * @param string $namespace
     */
    public function setHandlersNamespace(string $namespace)
    {
        $this->handlersNamespace = $namespace;
    }

    /**
     * Sets the namespace for all middlewares.
     *
     * @param string $namespace
     */
    public function setMiddlewaresNamespace(string $namespace)
    {
        $this->middlewaresNamespace = $namespace;
    }

    /**
     * Sets the handler to execute when no route has been matched.
     *
     * @param $handler
     * @throws WrongArgumentTypeException
     */
    public function setNotFound($handler)
    {
        if (!is_string($handler) && !is_callable($handler)) {
            throw new WrongArgumentTypeException('$handler argument must be a callable.');
        }
        if (is_string($handler)) {
            $this->notFoundHandler = $this->handlersNamespace . '\\' . $handler;
        }
    }

    /**
     * Groups routes used to set prefix, middlewares or namespace.
     *
     * @param array $options
     * @param callable $handler
     */
    public function group(array $options, callable $handler)
    {
        // saves the current routes prefix & middlewares
        $currentPrefix = $this->prefix;
        $currentMiddlewares = $this->middlewares;

        // sets the new routes prefix
        $this->prefix .= '/' . $options['prefix'] . '/';

        // sets the before middleware namespace if any
        if (!empty($options['middlewares']['before'])) {
            foreach ($options['middlewares']['before'] as &$middleware) {
                if (!is_callable($middleware)) {
                    switch (true) {
                        case $options['middlewares']['namespace']:
                            $middleware = $options['middlewares']['namespace'] . '\\' . $middleware;
                            break;
                        case $this->middlewaresNamespace:
                            $middleware = $this->middlewaresNamespace . '\\' . $middleware;
                            break;
                    }
                }
            }
        }

        // sets the after middleware namespace if any
        if (!empty($options['middlewares']['after'])) {
            foreach ($options['middlewares']['after'] as &$middleware) {
                if (!is_callable($middleware)) {
                    switch (true) {
                        case $options['middlewares']['namespace']:
                            $middleware = $options['middlewares']['namespace'] . '\\' . $middleware;
                            break;
                        case $this->middlewaresNamespace:
                            $middleware = $this->middlewaresNamespace . '\\' . $middleware;
                            break;
                    }
                }
            }
        }

        // saves the before middleware
        if (!empty($options['middlewares']['before'])) {
            if (!empty($this->middlewares['before'])) {
                $this->middlewares['before'] = array_merge($this->middlewares['before'], $options['middlewares']['before']);
            } else {
                $this->middlewares['before'] = $options['middlewares']['before'];
            }
        }

        // saves the after middleware
        if (!empty($options['middlewares']['after'])) {
            if (!empty($this->middlewares['after'])) {
                $this->middlewares['after'] = array_merge($this->middlewares['after'], $options['middlewares']['after']);
            } else {
                $this->middlewares['after'] = $options['middlewares']['after'];
            }
        }

        // executes the callable
        call_user_func($handler);

        // rollback back to the previous route prefix & middlewares
        $this->prefix = $currentPrefix;
        $this->middlewares = $currentMiddlewares;
    }

    /**
     * Registers a "GET" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function get(string $path, $handler, string $name = null)
    {
        $this->addRoute(['GET'], $path, $handler, $name);
    }

    /**
     * Registers a "POST" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function post(string $path, $handler, string $name = null)
    {
        $this->addRoute(['POST'], $path, $handler, $name);
    }

    /**
     * Registers a "PUT" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function put(string $path, $handler, string $name = null)
    {
        $this->addRoute(['PUT'], $path, $handler, $name);
    }

    /**
     * Registers a "PATCH" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function patch(string $path, $handler, string $name = null)
    {
        $this->addRoute(['PATCH'], $path, $handler, $name);
    }

    /**
     * Registers a "DELETE" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function delete(string $path, $handler, string $name = null)
    {
        $this->addRoute(['DELETE'], $path, $handler, $name);
    }

    /**
     * Registers a "OPTIONS" route.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function options(string $path, $handler, string $name = null)
    {
        $this->addRoute(['OPTIONS'], $path, $handler, $name);
    }

    /**
     * Registers a route that matches any HTTP methods.
     *
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function any(string $path, $handler, string $name = null)
    {
        $this->addRoute($this->supportedMethods, $path, $handler, $name);
    }

    /**
     * Registers a route.
     *
     * @param array $method
     * @param string $path
     * @param $handler
     * @param string $name
     * @throws DelimiterNotFoundException
     * @throws UnsupportHTTPMethodException
     * @throws WrongArgumentTypeException
     */
    public function addRoute(array $method, string $path, $handler, string $name = null)
    {
        // checks if the passed $controller variable is either a callable or a string
        if (!is_callable($handler) && !is_string($handler)) {
            throw new WrongArgumentTypeException('The $controller argument must be a callable or a string');
        }

        // checks if string contains "@" delimiter
        if (is_string($handler) && !strpos($handler, '@')) {
            throw new DelimiterNotFoundException('Unable to find the "@" delimiter in the controller argument.');
        }

        // converts the HTTP method(s) to uppercase and validates the HTTP method
        $method = array_map('strtoupper', $method);
        if (array_diff($method, $this->supportedMethods)) {
            throw new UnsupportHTTPMethodException('Unsupported HTTP method(s): ' . implode(', ', $method));
        }

        // adds the prefix to the route path if any
        if ($this->prefix) {
            $path = $this->prefix . $path;
        }

        // adds the base path to the route path (if set)
        if ($this->basePath) {
            $path = '/' . $this->basePath . '/' . $path;
        }

        // formats the route path
        $path = $this->formatRoute($path);

        // sets the namespace handler if any
        if ($this->handlersNamespace && !is_callable($handler)) {
            $handler = $this->handlersNamespace . '\\' . $handler;
        }

        // stores the route informations into an array
        $this->routes[] = [
            'method'        => $method,
            'path'          => $path,
            'middlewares'   => $this->middlewares,
            'handler'       => $handler,
            'name'          => $name
        ];
    }

    /**
     * Catches current route/method and runs the defined handler, otherwise returns the notFound() method.
     *
     * @param null $middlwares
     * @return mixed|string
     */
    public function run($middlwares = null)
    {
        $currentMethod  = $_SERVER['REQUEST_METHOD'];
        $currentRoute   = $this->getCurrentRoute();

        // invokes the before application middleware(s) if any
        if (isset($middlwares['before'])) {
            $this->invoke($middlwares['before']);
        }

        // checks if we have a match
        $matchedRoute = $this->findMatch($currentMethod, $currentRoute);

        // we did not have a match so we execute the notFound() method
        if (!$matchedRoute) {
            return $this->notFound();
        }

        // invokes the matched route before middleware(s) if any
        if (isset($matchedRoute['middlewares']['before'])) {
            foreach ($matchedRoute['middlewares']['before'] as $middleware) {
                $this->invoke($middleware);
            }
        }

        // invokes the matched route handler
        $this->invoke($matchedRoute['handler']);

        // invokes the matched route after middleware(s) if any
        if (isset($matchedRoute['middlewares']['after'])) {
            foreach ($matchedRoute['middlewares']['after'] as $middleware) {
                $this->invoke($middleware);
            }
        }

        // invokes the after application middleware(s) if any
        if (isset($middlwares['after'])) {
            $this->invoke($middlwares['after']);
        }
    }

    /**
     * Gets all registered routes.
     *
     * @return array
     */
    public function getAllRoutes()
    {
        return $this->routes;
    }

    /**
     * The current route (without query string)
     *
     * @return string
     */
    public function getCurrentRoute()
    {
        $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $route = $this->formatRoute($route);

        return $route;
    }

    /**
     * Gets the matched route parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->routeParameters;
    }

}
