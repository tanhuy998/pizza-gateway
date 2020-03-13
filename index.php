<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\DIContainer as Container;
use Application\Container\DIContainer;
use Dependencies\Http\Request as Request;
use Dependencies\Http\Respone;
use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    use Dependencies\HttpHandler\HttpHandler as HttpHandler;

    // class A {
    //     public $res;

        function test(Request $res, $a, int $b, $c)
        {
           var_dump($c);
        }

    //     public function test(Request $res, $a , $b, $c) {
    //         echo $a, ' ' ,$b, '@', $c;
    //     }
    // }

    $container = Container::GetInstance();
    //$container->call('test', [ 'res' => new Request('PUT'),  1, 'b' => 2]);
    // $container->Bind(Request::class, Request::class, function(Container $_container) {
    //     //echo '<pre>', var_dump($_container), '</pre>';
    //     return new Request('PUT');
    // }); 
    
    header('Access-Control-Allow-Origin: *');

    $request = Dependencies\HttpHandler\HttpHandler::Request();

    $container->BindSingleton(Dependencies\Router\Router::class, Dependencies\Router\Router::class, 
            function () use($container) {
                
                return new Dependencies\Router\Router($container);
            })->name('router');

    $container->BindSingleton(Dependencies\Http\Request::class, Dependencies\Http\Request::class,
            function () use ($request) {
                return $request;
            })->name('request');

    $container->BindSingleton(Dependencies\Http\Respone::class, Dependencies\Http\Respone::class,
            function () use($container) {

                return new Dependencies\Http\Respone(200, $container);
            });

    $router = $container->Get(Dependencies\Router\Router::class);

    $router->Get('test/{id}', function (int $id) {
        echo $id;
    });

    $respone = $router->Handle($request);

    $respone->send();
    //echo $_SERVER['REQUEST_METHOD'];
    // $res->Cookie('name', '2');
    // $res->Header('Content-Type', 'application/json');
    // $res->Render('abc');
    // $res->Render('123', Respone::RENDER_OVERIDE);
    // $res->Send();



    
    