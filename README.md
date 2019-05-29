# router

<p align="center">
    <img src="https://raw.githubusercontent.com/artyuum/router/master/hello-world.png" alt="hello world" data-canonical-src="https://gyazo.com/eb5c5741b6a9a16c692170a41a49c858.png" width="600">
</p>

**Note: This router is not fully functional yet and is under development. A stable version will be released soon.**

### Features
* RESTFul router.
* Shipped with Symfony Http Foundation.
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
* [Supported HTTP Methods](#HTTP-methods)
* [Route parameters](#Route-parameters)
* [Named routes](#Named-routes)
* [Route groups](#Route-groups)
    * [Route path prefixing](#Route-path-prefixing)
    * [Route name prefixing](#Route-name-prefixing)
    * [Route middlewares](#Route-middlewares)
* [Route mapping](#Route-mapping)
* [Route not found](#Route-not-found)
 
#### HTTP methods
The router supports the following HTTP methods:
```php
$router->get(string $path, $handler);
$router->post(string $path, $handler);
$router->put(string $path, $handler);
$router->patch(string $path, $handler);
$router->delete(string $path, $handler);
$router->options(string $path, $handler);

// the methods above are just wrappers around the `addRoute()` method
$router->addRoute(array $methods, string $path, $handler);
```

You can also register a route that matches more than one HTTP method:  
```php
$router->addRoute(['GET', 'POST'], '/', 'myController');
```

The `$handler` argument must be either a callable or array:
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

#### Route parameters
Route parameters can be set using placeholders or pure regexes ([PCRE](https://courses.cs.washington.edu/courses//cse154/14sp/cheat-sheets/php-regex-cheat-sheet.pdf)) as follow:
```php
$router->get('/profile/{username}', $handler); // will be converted to /profile/?<username>(\w+)
```
When using pure regex, it is important to [give a name to the group](https://www.regular-expressions.info/named.html) (like above). Otherwise, the router won't be able get the parameters from the URL and it also won't be able to properly build an URL to a route using the `url()` method. 

By default, placeholders will be converted to required parameters that will match any word of at least one letter, number or _. You can change this behavior by using the `where()` method, as follow:
```php
$router->get('/profile/{id}', $handler)->where([
    'id' => '[0-9]+' // can also be written as "\d+"
]); // will match /profile/<one or n digit(s) from 0 to 9>
```
The `where(array $placeholders)` method takes an associative array where the `$key` is the name of the placeholder, and the `$value` is a regex.

You can also set a placeholders as optional by appending a "?" sign after the placeholder's name, as follow:
```php
$router->get('/profile/{username?}', $handler); // will match /profile OR /profile/<any word of at least one letter, number or _>
```

### Named routes
Naming a route allows you to easily find a registered Route object by name.
With the returned object, you will be able to access all methods of this object (e.g. `getPath()`, `getName()`, `getMiddlewares()`, `getHandler()`, etc...) 

**Example :**

```php
$router->get('/', 'HomepageController@index')->setName('homepage'); // registers a route named "homepage"
$router->get('/users/{id}/delete', 'UserController@delete')->setName('user.delete'); // registers a route named "user.delete"

// gets the Route instance of the "homepage" route
$route = $route->getRoute('homepage');

// builds an url to a route
$url = $router->url('homepage'); // /home

// builds an url to a route with parameters
$url = $router->url('user.delete', ['id' => 1]); // /users/1/delete
```

### Route groups
You can group routes using the `group()` method. This gives you the ability to set a prefixes or middlewares for all the routes inside the group.

#### Route path prefixing
**Example:**

```php
// homepage
$router->get('/', 'HomepageController@index'); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setPathPrefix('/admin');
    $router->get('/', 'AdminController@index'); // will match "/admin"
});
```

#### Route name prefixing
**Example:**

```php
// homepage
$router->get('/', 'HomepageController@index'); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setNamePrefix('admin.');
    $router->get('/', 'AdminController@index')->setName('homepage'); // name will be "admin.homepage" 
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
    
    $router->get('/edit/{id}', 'AdminController@wallpapers'); // will match "/user/edit/<number>"
    $router->post('/add/{id}', 'AdminController@wallpaper'); // will match "/user/add/<number>"
});
```
If a request matches one of the registered routes, the router will do the following process in this order:
1. In first, the `handle()` method from the `RateLimitMiddleware` class will be executed.
2. In second, the `handle()` method from the `AuthMiddleware` class will be executed.
3. In third, the matched route controller will be executed.
4. And finally, the `handle()` method from the `LoggingMiddleware` class will be executed. 

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
You can map a single path to multiple HTTP methods with differents handler, as follow:
```php
$router->map('/users/:num')
    ->put($handler)
    ->patch($handler)
    ->delete($handler);
```

Using the `withAttributes()` method, you will be able to get access to the Route object in order to set additional attributes to the route:
```php
$router->map('/users/:num')
    ->put([UserController::class], 'replace')->withAttributes(function(\Artyum\Router\Route $route) {
        $route
            ->setName('user.replace')
            ->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
     })
    ->patch([UserController::class], 'update')->withAttributes(function(\Artyum\Router\Route $route) {
        $route
            ->setName('user.update')
            ->setBeforeMiddlewares(['RateLimitMiddleware::class', 'RolesMiddleware::class']);
    })
    ->delete([UserController::class], 'delete')->withAttributes(function(\Artyum\Router\Route $route) {
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

    $router->map('/users/:num')
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
By default, when the request doesn't match any registered routes, the `dispatch()` method will throw a "NotFoundException". You can change this behavior by registering an handler. The registered handler will be executed with the same arguments as controllers or middlewares. That way, you will be able to easily access the request and response object.
```php
setNotFoundHandler(callable $handler);
```

**Example:**
```php
// using a function name
$router->setNotFoundHandler($handler);

// using an anonymous function
$router->setNotFoundHandler(function(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response) {
    // code goes here
});

// using a class
$router->setNotFoundHandler([ErrorController::class, 'notFound']);
```

## Contributing
If you'd like to contribute, please fork the repository and make changes as you'd like. Pull requests are warmly welcome.
