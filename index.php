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

    $container = Container::GetInstance();

    $container->BindSingleton(Request::class, Request::class, function(Container $_container) {

        echo '<pre>',var_dump($_container), '</pre>';
        return new Request('POST');
    });
    

    $res = $container->Get(Container::class);
    
    //echo $res->do = 123;

    $test = $container->Make(A::class);
   
    $func = function() {

    };
    //echo SubRootDir();
    
    $a = $func();
    
?>

    
    