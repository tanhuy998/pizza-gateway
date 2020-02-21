<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\DIContainer as Container;
    use Dependencies\Http\Request;
    use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    use Dependencies\HttpHandler\HttpHandler as HttpHandler;
    $con = new Container();

    //$con->BindClass(Dependencies\Http\HttpHandler::class);

    // $obj = $con->GetClassInstance(\Dependencies\Http\HttpHandler::class);

    // $handler = new Dependencies\Http\HttpHandler('3');

    // $con->BindInterface('http', Dependencies\Http\Request::class)->name('h');

    // $request = $con->Get('h', Container::INSTANTIATE);

    // echo $request->method();

    $router = new Router();
    $pattern = '/asdasd/b/xc/';
    //$router->StandardizePattern($pattern);

    $router->put('test', 'abc:jjnm');
    $http = new HttpHandler();
    $request = $http->Request();

    $var = $router->handle($request);
    var_dump($var);
    //echo SubRootDir();

    
?>

    
    