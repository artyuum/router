<?php

namespace Artyum\Router;

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
     * @var array Should contain an array of arguments that will be passed to the handler when invoking.
     */
    private $handlerArguments;

    /**
     * @var Route Should contain the route that matched the current request.
     */
    private $matchedRoute;

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
    private function formatPath(string $path)
    {
        $path = preg_replace('/\s+/','/', $path); // removes whitespaces
        $path = preg_replace('#/+#','/', $path); // removes extra slashes
        $path = rtrim($path, '/'); // removes the trailing slash

        // if the path becomes empty after trimming, we add one slash
        if (empty($path)) {
            $path = '/';
        }

        return $path;
    }

    /**
     * Checks if the given route matches one of the registered routes.
     *
     * @param string $currentMethod
     * @param string $currentRoute
     * @return Route|null
     */
    private function findMatch(string $currentMethod, string $currentRoute): ?Route
    {
        /**
         * "The HEAD method is identical to GET except that the server MUST NOT return a message-body in the response."
         * @link https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
         */
        if ($currentMethod == 'HEAD') {
            ob_start(); // prevents the output from being sent to the client (but keeping sending the headers)
            $currentMethod = 'GET'; // sets the method to "GET" in order to fallback to a matching "GET" route
        }

        // loops though all registered routes and search for a match
        foreach ($this->routes as $route) {
            // checks if the current HTTP method corresponds to the registered HTTP method for this route
            if (!in_array($currentMethod, $route->getMethod())) {
                continue;
            }

            // if we have a match
            if (preg_match_all('#^' . $route->getPath() . '$#', $currentRoute, $matches, PREG_OFFSET_CAPTURE)) {

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
                //$this->routeParameters = $routeParameters;

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
     * @return mixed
     */
    private function invoke($handler)
    {
        // if the first argument is not an array and is an anonymous function or a function name
        if (!is_array($handler) && is_callable($handler)) {
            if (!empty($this->handlerArguments)) {
                return call_user_func($handler, ...$this->handlerArguments); // executes the handler with arguments
            } else {
                return call_user_func($handler); // executes the handler without arguments
            }
        }

        // if it's a class
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];

            if (!empty($this->handlerArguments)) {
                return call_user_func([new $class(), $method], ...$this->handlerArguments); // executes the method with arguments
            } else {
                return call_user_func([new $class(), $method]); // executes the method without arguments
            }
        }
    }

    /**
     * Executes the not found handler.
     */
    private function fireNotFound()
    {
        // if the "not found" handler is set we execute it
        if ($this->notFoundHandler) {
            return $this->invoke($this->notFoundHandler);
        }

        // otherwise we send the default response (404)
        return http_response_code(404);
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
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
     * @throws WrongArgumentTypeException
     */
    public function addRoute(array $method, string $path, $handler): Route
    {
        // converts the HTTP methods to uppercase and validates the HTTP method
        $method = array_map('strtoupper', $method);
        if (array_diff($method, $this->supportedMethods)) {
            throw new UnsupportHTTPMethodException();
        }

        // adds the prefix to the route path
        if ($this->group && $this->group->getPrefix()) {
            $path = $this->group->getPrefix() . $path;
        }

        // adds the base path to the route path
        if ($this->basePath) {
            $path = $this->basePath . $path;
        }

        // formats the route path
        $path = $this->formatPath($path);

        // creates a new route and store its informations
        $route = new Route();
        $route
            ->setPath($path)
            ->setMethod($method)
            ->setHandler($handler);

        // adds the route middlewares to the route in any
        if ($this->group && $this->group->getMiddlewares()) {
            $route->setMiddlewares($this->group->getMiddlewares());
        }

        // stores the newly created route into an array of routes
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
     * Gets the not found handler.
     *
     * @return mixed
     */
    public function getNotFoundHandler()
    {
        return $this->notFoundHandler;
    }

    /**
     * Sets the handler to execute when no route has been matched.
     *
     * @param $handler
     * @throws WrongArgumentTypeException
     */
    public function setNotFoundHandler($handler)
    {
        if (!is_string($handler) && !is_callable($handler)) {
            throw new WrongArgumentTypeException();
        }
        $this->notFoundHandler = $handler;
    }

    /**
     * Sets the arguments that will be passed to the handlers when invoking.
     *
     * @param mixed ...$arguments
     */
    public function setHandlerArguments(...$arguments)
    {
        $this->handlerArguments = $arguments;
    }

    /**
     * Catches current route/method and runs the defined handler, otherwise returns the notFound() method.
     *
     * @return mixed
     */
    public function dispatch()
    {
        $currentMethod  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
        $currentRoute   = $this->formatPath($_SERVER['REQUEST_URI']); // todo: create getRequest() method

        // if there are no routes registered or we are not able to get the needed informations from the request we stop here
        if (empty($this->routes) || $currentRoute === null) {
            return $this->fireNotFound();
        }

        // checks if we have a match
        $this->matchedRoute = $this->findMatch($currentMethod, $currentRoute);

        // if we don't have a match, we execute the notFound() method
        if ($this->matchedRoute === null) {
            return $this->fireNotFound();
        }

        // invokes the matched route before middleware(s) if any
        if (!empty($this->matchedRoute->getMiddlewares()['before'])) {
            foreach ($this->matchedRoute->getMiddlewares()['before'] as $middleware) {
                $this->invoke($middleware);
            }
        }

        // invokes the matched route handler
        $this->invoke($this->matchedRoute->getHandler());

        // invokes the matched route after middleware(s) if any
        if (!empty($this->matchedRoute->getMiddlewares()['after'])) {
            foreach ($this->matchedRoute->getMiddlewares()['after'] as $middleware) {
                $this->invoke($middleware);
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
     * @return array
     */
    public function getMatchedRoute(): ?array
    {
        /*$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // gets only the route and not query string
        $route = $this->formatPath($route);*/

        $parameters = null;

        if ($this->matchedRoute) {
            return [
                $this->matchedRoute,
                $parameters
            ];
        }

        return null;

        /*
         * todo: Should return an array containing the matched route, the route parameters and the route name if any
         */
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
