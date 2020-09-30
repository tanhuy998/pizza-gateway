<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\Dependency;
    use Application\Container\DIContainer as Container;
    use Application\Container\DIContainer;
    use Application\Container\IContainer;
    use Dependencies\Event\EventArgs;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone;
    use Dependencies\Router\Router as Router;
    use Dependencies\Router\Route as Route;
    use Dependencies\HttpHandler\HttpHandler as HttpHandler;
    use Dependencies\Notification\EventClient as EventClient;
    use Dependencies\Parsing\URLParser;

    if (ob_get_level() == 0) ob_start();
    ini_set("allow_url_fopen", true);
    
    header('Access-Control-Allow-Origin: *');

    $request = Dependencies\HttpHandler\HttpHandler::Request();

    $app = new \Application\Application($request);

    $app->start();

    $router = $app->router;

    $router->AllVerbs('/admin/[^*.]*', function(Request $_request) {

        echo 1;
    });

    $router->AllVerbs('/[^*.]*', function(Request $_request) {

        $url = $_request->FullUrl();
        
        $path_part = explode('/', $url);

        if ($path_part[1] === 'admin') {

        }

        $headers = getallheaders();
        $ch = curl_init('localhost/pizza/public/admin/category');
        $method = $_request->Method();
        $request_body = file_get_contents('php://input');
        var_dump($request_body);
        //curl_setopt($ch, CURLOPT_URL, 'https://localhost/pizza/public/admin/category');

        $option = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS => $request_body,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        
        //curl_setopt_array($ch, $option);
        $hd = [];

        foreach ($headers as $name => $value) {
        
            $hd[] = $name.': '.$value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
        curl_setopt_array($ch, $option);

        $respone = curl_exec($ch);
        
        

        curl_close($ch);
    });


    $respone = $router->Handle($request);

    $respone->Header('Content-Type', 'application/json');

    $respone->send();
    
    $app->Terminate();

    


    
