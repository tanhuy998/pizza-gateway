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
    use Dependencies\Http\HttpClient;

    if (ob_get_level() == 0) ob_start();
    ini_set("allow_url_fopen", true);
    ini_set('display_errors', 1);
    
    header('Access-Control-Allow-Origin: *');

    $request = Dependencies\HttpHandler\HttpHandler::Request();

    $app = new \Application\Application($request);
    
    $app->start();

    $router = $app->router;

    // $router->AllVerbs('/admin(/[^*.]*)*', function(Request $_request) {

    //     echo 1;
    // });
    $router->AllVerbs('/admin[^*.]*', function(Request $_request, Respone $_response) {
        
        $proxy = new HttpClient();

        $respone_data = $proxy->Forward($_request)
                        ->To('piz-api.herokuapp.com/public')
                        ->then(function ($res) {

                            //var_dump($res);
                        })->return();
        
        $headers = $respone_data['headers'];
        $body = $respone_data['body'];
        
        foreach($headers as $key => $value) {
            // echo $key.'<br>';
            
            if ($key === 'Transfer-Encoding') continue;
            if ($key === '') break;
            //var_dump($value);
            $_response->header($key, $value);
            //echo $value;
        }

        $_response->Render($body, Respone::RENDER_OVERIDE);
        
    });

    $router->Allverbs('/[^*.]*', function() {
        echo 1;
    }); 
    
    // $router->AllVerbs('/[^*.]*', function(Request $_request, Respone $_response) {

    //     $req_url = $_request->Uri();
    //     $url = '';
    //     $path_part = explode('/', $req_url);
        
    //     if ($path_part[1] === 'admin') {
    //         $url = 'piz-api.herokuapp.com/public'.$_request->Uri();
            
    //     }

        
    //     $headers = getallheaders();
    //     $ch = curl_init($url);
    //     $method = $_request->Method();
    //     $request_body = file_get_contents('php://input');
    //     //curl_setopt($ch, CURLOPT_URL, 'https://localhost/pizza/public/admin/category');
        
        
    //     $option = [
    //         CURLOPT_CUSTOMREQUEST => $method,
    //         CURLOPT_HEADER => TRUE,
    //         CURLOPT_RETURNTRANSFER => TRUE,
    //         CURLOPT_POSTFIELDS => $request_body,    
    //         CURLOPT_FOLLOWLOCATION => true,
    //     ];
        
    //     curl_setopt_array($ch, $option);
    //     $hd = [];
        
    //     foreach ($headers as $name => $value) {
    //         if ($name === 'Host') continue;

    //         if ($name === 'Content-Length') {
    //             $hd[] = $name.': '.strlen($request_body);
    //             continue;
    //         }
    //         $hd[] = $name.': '.$value;
    //     }

    //     $hd[] = 'Host: '.'piz-api.herokuapp.com';
        
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
    //     curl_setopt_array($ch, $option);

    //     $respone = curl_exec($ch);
        
    //     $output = $respone;

    //     // close curl resource to free up system resources
    //     //curl_close($ch);

    //     $headers = [];
    //     $output = rtrim($output);
    //     $data = explode("\n",$output);
    //     $headers['status'] = $data[0];
    //     array_shift($data);

    //     foreach($data as $part){

    // //some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
    //         $middle = explode(":",$part,2);

    // //Supress warning message if $middle[1] does not exist, Thanks to @crayons
    //         if ( !isset($middle[1]) ) { $middle[1] = null; }

    //         $headers[trim($middle[0])] = trim($middle[1]);
    //     }
        
    //     $headers_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //     $body = substr($respone, $headers_size);

    //     curl_close($ch);
        
    //     $_response->Render($body, Respone::RENDER_OVERIDE);
    //     // var_dump($body);
        
    //     foreach($headers as $key => $value) {
    //        // echo $key.'<br>';
            
    //         if ($key === 'Transfer-Encoding') continue;
    //         if ($key === '') break;
    //         $_response->header($key, $value);
    //     }
        
    // //echo 1;
    // });
    

    $respone = $router->Handle($request);

    //$respone->Header('Content-Type', 'application/json; charset=UTF-8');

    $respone->send();
    
    $app->Terminate();