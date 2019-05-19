# router
Yet another PHP request router.  
Some parts of this router like methods name or functionalities are inspired from other existing routers.

### Documentation
* [HTTP Methods](#)
* [Route parameters](#)
* [Route naming](#)
* [Route grouping](#)
    * [Route prefix](#)
    * [Route middlewares](#)
* [Route mapping](#)

#### HTTP methods
The router supports the following HTTP methods:
```php
$router->get(string $path, $handler);
$router->post(string $path, $handler);
$router->put(string $path, $handler);
$router->patch(string $path, $handler);
$router->delete(string $path, $handler);
$router->options(string $path, $handler);
```

The methods above are just wrappers around the following method:
```php
$router->addRoute(array $method, string $path, $handler);
```

The `$handler` argument must be either a callback, a string or array:
```php
// using a callback
$router->get('/', function() {
    echo 'hello world';
});

// using a string (if it's a function)
$router->get('/', 'myFunction');

// using an array (if it's a class)
$router->get('/', ['HomepageController::class', 'index']);
```

#### Route parameters
You can pass required route parameters to the route using these availables patters:
- `\d+` = any digits (0-9).
- `\w+` = any word characters (a-z 0-9 _).
- `[a-z0-9_-]+` = any word characters (a-z 0-9 _) and the dash (-)
- `.*` = any character (including /), zero or more
- `[^/]+` = any character but /

**Example :**
```php
// will match /ressources/<number>
$router->get('/movie/:num', 'HomepageController@find');

// will match /ressources/<number>/something
$router->get('/movie/:num/:alpha', 'HomepageController@find');
```

*Note: credit goes to [bramus/router](https://github.com/bramus/router) for the regex part of [solis/router]().*

### Route naming
Naming a route allows you to easily find a registered Route object by name.
With the returned object, you will be able to access all methods of this object (e.g. `getPath()`, `getName()`, `getMiddlewares()`, `getHandler()`, etc...) 

**Example :**

```php
// registers a route named "homepage"
$router->get('/', 'HomepageController@index')->setName('homepage');

// gets the Route instance of the "homepage" route
$route = $route->getRoute('homepage');

// builds an url to this route
$url = $router->url('homepage'); // /home

$router->redirect('/homepage', $code = 301|302); // will redirect the user to the "homepage" route with one of the following codes : 301, 302.
```

### Route grouping
You can group routes using the `group()` method. This gives you the ability to set a prefix or middlewares for all the routes inside the group.

#### Route prefix
**Example:**

```php
// homepage
$router->get('/', 'HomepageController@index'); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setPrefix('/admin');
    $router->get('/', 'AdminController@index'); // will match "/admin"
});
```

The `group()` method can also be nested, as follow:
```php
// homepage
$router->get('/', 'HomepageController@index'); // will match "/"

// admin
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setPrefix('/admin');
    $router->get('/', 'AdminController@index'); // will match "/admin"
    
    $router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
    {
        $group->setPrefix('/users');
    
        $router->get('/', 'UserController::class', 'index'); // will match "/admin/users"
        $router->post('/add/', 'UserController::class', 'create'); // will match "/admin/users/add/"
        $router->put('/:num/edit', 'UserController::class', 'edit'); // will match "/admin/users/<number>/edit"
    });
});
```

#### Route middlewares
**Example:**
```php
$router->group(function(\Artyum\Router\RouteGroup $group) use ($router)
{
    $group->setMiddlewares([
        'before' => ['RateLimitMiddleware::class', 'AuthMiddleware::class'],
        'after' => ['LoggingMiddleware::class']
    ]);
    
    $router->get('/edit/:num', 'AdminController@wallpapers'); // will match "/user/edit/<number>"
    $router->post('/add/:num', 'AdminController@wallpaper'); // will match "/user/add/<number>"
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

These are simply wrappers around the `setMiddlewares()` method.

#### Route mapping
You can map a single path to multiple HTTP methods with differents handler, as follow:
```php
$router->map('/users/:num')
    ->put([UserController::class], 'replace')
    ->patch([UserController::class], 'update')
    ->delete([UserController::class], 'delete');
```

Using the `withAttributes()` method, you will be able to get access to the Route object in order to set additional attributes to the route:
```php
$router->map('/users/:num')
    ->put([UserController::class], 'replace')->withAttributes(function(\Artyum\Router\Route $route) {
        $route->setName('user.replace');
     })
    ->patch([UserController::class], 'update')->withAttributes(function(\Artyum\Router\Route $route) {
        $route->setName('user.update');
    })
    ->delete([UserController::class], 'delete')->withAttributes(function(\Artyum\Router\Route $route) {
        $route->setName('user.delete');
    });
``` 

## Contributing
If you'd like to contribute, please fork the repository and make changes as you'd like. Pull requests are warmly welcome.
