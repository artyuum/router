# router

<p align="center">
    <img src="https://raw.githubusercontent.com/artyuum/router/master/hello-world.png" alt="hello world" data-canonical-src="https://gyazo.com/eb5c5741b6a9a16c692170a41a49c858.png" width="600">
</p>

**Note: This router is not fully functional yet and is under development. A stable version will be released soon.**

### Features

* RESTFul router.
* Shipped with [Symfony Http Foundation](https://symfony.com/components/HttpFoundation).
* Supports named route parameters & placeholders.
* Supports route groups (and nested groups).
* Supports named routes (with reverse routing).
* Supports before & after route middlewares.
* Supports before & after global middlewares.
* Supports routes prefixes.
* Supports route mapping.
* Supports caching/compiled routes.
* Supports custom handler for 404.

### Installation

```
composer require artyuum/router:dev-master
```

### Documentation

* [Registering a route](#Registering-a-route)
* [Route parameters](#Route-parameters)
* [Named routes](#Named-routes)
* [Route groups](#Route-groups)
  * [Route uri prefixing](#Route-uri-prefixing)
  * [Route name prefixing](#Route-name-prefixing)
  * [Route middlewares](#Route-middlewares)
* [Route mapping](#Route-mapping)
* [Route not found](#Route-not-found)

#### Registering a route

The router contains few methods for the most common HTTP methods that will help you to easily register a route:

```php
$router->get(string $uri, $handler);
$router->post(string $uri, $handler);
$router->put(string $uri, $handler);
$router->patch(string $uri, $handler);
$router->delete(string $uri, $handler);
$router->options(string $uri, $handler);
```

These are just wrappers around the following method:

```php
$router->addRoute(array $methods, string $uri, $handler);
```

Using the `addRoute()` method, you can register a route that matches more than one HTTP method:  

```php
$router->addRoute(['GET', 'POST'], string $uri, $handler);
```

The router doesn't limit your application to the most common HTTP methods. Indeed, you can also register a route that matches a custom HTTP method:

```php
$router->addRoute(['BLAH'], string $uri, $handler);
```

The `$handler` argument must be either a callable or an array (if it's a class):

```php
// using an anonymous function
$router->get('/', function(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response) {
    echo 'hello world';
});

// using a function name
$router->get('/', 'myFunction');

// using an array (if it's a class)
$router->get('/', ['HomepageController::class', 'index']);
```

Once a request matches one of the registered routes, the router will execute the handler and passes two arguments in the following order :

1. `Symfony\Component\HttpFoundation\Request $request`

2. `Symfony\Component\HttpFoundation\Response $response`

These arguments are part of the [Symfony HTTP Foundation](https://symfony.com/components/HttpFoundation) component and will help you to get more informations about the request and easily build and send a response to the client. Feel free to [read the docs](https://symfony.com/components/HttpFoundation) to get more informations about its usage.

#### Route parameters

Route parameters can be set using placeholders or pure regexes ([PCRE](https://courses.cs.washington.edu/courses//cse154/14sp/cheat-sheets/php-regex-cheat-sheet.pdf)) as follow:

```php
$router->get('/profile/{username}', $handler); // will be internally converted to /profile/?<username>(\w+)
```

When using pure regex, it is important to [give a name to the group](https://www.regular-expressions.info/named.html) (like above). Otherwise, the router won't be able get the parameters from the URL and it also won't be able to properly build an URL to a route using the `url()` method. 

By default, placeholders will be converted to required parameters that will match any word of at least one letter, number or _. You can change this behavior by using the `where()` method, as follow:

```php
$router->get('/profile/{id}', $handler)->where([
    'id' => '[0-9]+' // can also be written as "\d+"
]); // will match /profile/<one or n digit(s) from 0 to 9>
```

The `where(array $placeholders)` method takes an associative array as argument where the `$key` is the name of the placeholder, and the `$value` is a regex.

You can also set a placeholders as optional by appending a "?" sign after the placeholder's name, as follow:

```php
$router->get('/profile/{username?}', $handler); // will match /profile OR /profile/<any word of at least one letter, number or _>
```

### Named routes

Naming a route allows you to easily find a registered Route object by name.
With the returned object, you will be able to access all methods of this object (e.g. `getPath()`, `getName()`, `getMiddlewares()`, `getHandler()`, etc...) 

**Example :**

```php
// registers a route named "homepage"
$router->get('/', $handler)->setName('homepage'); 

// registers a route named "user.delete"
$router->get('/users/{id}/delete', $handler)->setName('user.delete'); 

// gets the Route instance of the "homepage" route
$route = $route->getRoute('homepage');

// builds an url to a route
$url = $router->url('homepage'); // /home

// builds an url to a route with parameters
$url = $router->url('user.delete', ['id' => 1]); // /users/1/delete
```

### Route groups

You can group routes using the `group()` method. This gives you the ability to set a prefixes or middlewares for all the routes inside the group.

#### Route uri prefixing

**Example:**

```php
// homepage
$router->get('/', $handler); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setPathPrefix('/admin');
    $router->get('/', $handler); // will match "/admin"
});
```

#### Route name prefixing

**Example:**

```php
// homepage
$router->get('/', $handler); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setNamePrefix('admin.');
    $router->get(string $uri, $handler)->setName('homepage'); // name will be "admin.homepage" 
});
```

#### Route middlewares

**Example:**

```php
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->addMiddlewares([
        'before' => ['RateLimitMiddleware::class', 'AuthMiddleware::class'],
        'after' => ['LoggingMiddleware::class']
    ]);

    $router->get(string $uri, $handler);
    $router->post(string $uri, $handler);
});
```

If a request matches one of the registered routes, the router will do the following process in this order:

1. In first, the `handle()` method from the `RateLimitMiddleware` class will be executed.
2. In second, the `handle()` method from the `AuthMiddleware` class will be executed.
3. In third, the matched route controller will be executed.
4. And finally, the `handle()` method from the `LoggingMiddleware` class will be executed. 

The router will automatically executes the `handle()` method from the middleware class so you don't need to specify it.

You can also add before/after route middlewares to a group as follow:

```php
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setBeforeMiddlewares(['RateLimitMiddleware::class', 'AuthMiddleware::class']);
    $group->setAfterMiddlewares(['LoggingMiddleware::class']);
});
```

These are simply wrappers around the `addMiddlewares()` method.

### Route mapping

You can map a single uri to multiple HTTP methods with differents handler, as follow:

```php
$router->map('/users/{id}')
    ->put($handler)
    ->patch($handler)
    ->delete($handler);
```

Using the `withAttributes()` method, you will be able to get access to the Route object in order to set additional attributes to the route:

```php
$router->map('/users/{id}')
    ->put($handler)->withAttributes(function(\Artyum\Router\Route $route) {
        $route
            ->setName('user.replace')
            ->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
     })
    ->patch($handler)->withAttributes(function(\Artyum\Router\Route $route) {
        $route
            ->setName('user.update')
            ->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
    })
    ->delete($handler)->withAttributes(function(\Artyum\Router\Route $route) {
        $route
            ->setName('user.delete')
            ->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
    });
```

It's also possible to add mapped routes to a group, that way you can set the middlewares for all routes that is inside the group and name prefix too:

```php
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
    $group->setNamePrefix('user.');

    $router->map('/users/{id}')
        ->put($handler)->withAttributes(function(\Artyum\Router\Route $route) {
            $route->setName('replace');
         })
        ->patch($handler)->withAttributes(function(\Artyum\Router\Route $route) {
            $route->setName('update');
        })
        ->delete($handler)->withAttributes(function(\Artyum\Router\Route $route) {
            $route->setName('delete');
        });
});
```

### Route not found

By default, when the request doesn't match any registered routes, the `dispatch()` method will throw a "NotFoundException". You can change this behavior by registering a handler. The registered handler will be executed with the same arguments as controllers or middlewares.

```php
setNotFoundHandler($handler);
```

**Example:**

```php
// sends a 404 status code if no routes match the current request
$router->setNotFoundHandler(function(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response) {
    $response
        ->setStatusCode(404)
        ->send();
});
```

## Contributing

If you'd like to contribute, please fork the repository and make changes as you'd like. Pull requests are warmly welcome.
