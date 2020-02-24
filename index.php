<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\DIContainer as Container;
use Application\Container\DIContainer;
use Dependencies\Http\Request as Request;
    use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    use Dependencies\HttpHandler\HttpHandler as HttpHandler;

    class A {
        public $res;

        public function __construct(Request $res)
        {
            $this->res = $res;
        }
    }

    $con = new Container();

    $container = new DIContainer();

    $container->BindSingleton(Request::class, Request::class, function() {
        return new Request('POST');
    });

    $res = $container->Get(Request::class);
    
    echo $res->do = 123;

    $test = $container->Make(A::class);
   
    $func = function() {

    };
    //echo SubRootDir();
    
    $a = $func();
    echo '<pre>',var_dump($test), '</pre>';
?>

    
    