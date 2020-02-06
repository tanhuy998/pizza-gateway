<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\DIContainer as Container;
use Dependencies\Http\Request;
use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    
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

    $router->get('test/{i}', 'abc:jjnm');
    $request = new Request();

    $var = $router->handle($request);
    var_dump($var);
    //echo SubRootDir();
?>

<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <form action="/mvc/test?a=123" method="post">
            <input type="text" value="" name="text">
            <input type="submit" value="1" name="sub">
        </form>
    </body>
</html>
    
    