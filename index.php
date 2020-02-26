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
        return new Request('POST');
    });
    
   
    function A(Request $con, $a, $b) {
        echo $a, ' ', $b;
    }
    //echo SubRootDir();
    
    $arr = ['b' => 1, 2];
    
    $a = $container->CallFunction(new ReflectionFunction('A'), $arr);
    
    echo '<pre>', var_dump($a), '</pre>';
?>

    
    