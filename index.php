<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

use Application\Container\Dependency;
use Application\Container\DIContainer as Container;
    use Application\Container\DIContainer;
    use Application\Container\IContainer;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone;
    use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    use Dependencies\HttpHandler\HttpHandler as HttpHandler;

    if (ob_get_level() == 0) ob_start();
    
    $container = Container::GetInstance();
    
    header('Access-Control-Allow-Origin: *');

    $request = Dependencies\HttpHandler\HttpHandler::Request();

    $container->BindSingleton(Dependencies\Router\Router::class, Dependencies\Router\Router::class, 
            function () use($container) {
                
                return new Dependencies\Router\Router($container);
            })->name('router');

    $container->BindSingleton(Dependencies\Http\Request::class, Dependencies\Http\Request::class,
            function () use($request){
                return $request;
            })->name('request');

    $container->BindSingleton(Dependencies\Http\Respone::class, Dependencies\Http\Respone::class);

    $router = $container->Get(Dependencies\Router\Router::class);

    $router->Get('/c', function (Router $router) {

        //$req = $container->bind(Respone::class, Respone::class);
    
        echo 'c';
    });

    $router->Get('/', function(Router $router) {
        return 'home';
    })->name('home');

    $router->Put('/test', function (Router $router) {
        
    });

    $respone = $router->Handle($request);

    $respone->send();
    exit;

    // $func = function (string $a, $b, Request $c) {
    //     return $c;
    // };

    // $reflect = new ReflectionFunction($func);

    // $a = $container->call($func, ['a' => 'hello']);

    

    
    