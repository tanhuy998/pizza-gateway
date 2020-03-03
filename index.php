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

        public function test(Request $res, $a , $b, $c) {
            echo $a, ' ' ,$b, '@', $c;
        }
    }

    $container = Container::GetInstance();

    $container->Bind(Request::class, Request::class, function(Container $_container) {
        //echo '<pre>', var_dump($_container), '</pre>';
        return new Request('PUT');
    });
    
   
    // function A(Request $con, $a, $b) {
    //     echo $a, ' ', $b;
    // }
    //echo SubRootDir();
    

    

    
    