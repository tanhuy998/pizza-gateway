<?php
    include __DIR__.'/src/init.php';
    include __DIR__.'/src/autoload/autoload.php';

    use Application\Container\DIContainer as Container;
    
    $con = new Container();

    $con->BindClass(Dependencies\Http\HttpHandler::class);

    $obj = $con->GetClassInstance(\Dependencies\Http\HttpHandler::class);

    

