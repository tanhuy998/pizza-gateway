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


    $container = Container::GetInstance();
    
    header('Access-Control-Allow-Origin: *');

    // $request = Dependencies\HttpHandler\HttpHandler::Request();

    // $container->BindSingleton(Dependencies\Router\Router::class, Dependencies\Router\Router::class, 
    //         function () use($container) {
                
    //             return new Dependencies\Router\Router($container);
    //         })->name('router');

    // $container->BindSingleton(Dependencies\Http\Request::class, Dependencies\Http\Request::class,
    //         function () {
    //             return new Request('PUT');
    //         })->name('request');

    // $container->BindSingleton(Dependencies\Http\Respone::class, Dependencies\Http\Respone::class,
    //         function () use($container) {

    //             return new Dependencies\Http\Respone(200, $container);
    //         });

    // $router = $container->Get(Dependencies\Router\Router::class);

    // $router->Get('/test', function (IContainer $container) {

    //     return $container->Call(function ($a,Request $b, $c) {
    //         var_dump($a);
    //     }, ['c' => 'a', 1 ,'b' => new Request('POST')]);
    // });

    // $router->Get('/testcontroller/{id}', 'TestController::Index');

    // $respone = $router->Handle($request);

    // $respone->send();

    $func = function (string $a, $b, Dependency $c) {
        echo $a;
    };

    $reflect = new ReflectionFunction($func);

    $a = $container->PassArguments($reflect, ['a' => '1','b' => 2]);

    var_dump($a);



    
    