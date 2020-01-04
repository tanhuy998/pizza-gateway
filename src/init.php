<?php
    putenv('BASE_PATH='.dirname(__DIR__));

    function BasePath() {
        return getenv('BASE_PATH');
    }

    function UseSession() {
        session_start();
    }

    if (file_exists(BasePath().'/.env')) {
        $env = file(getenv('BASE_PATH').'/.env');

        foreach ($env as $var) {
            putenv($var);
        }

        unset($env);
    }

    
    

    

    