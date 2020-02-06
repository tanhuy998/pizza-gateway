<?php
    putenv('BASE_PATH='.dirname(__DIR__));

    /**
     *  Base Path is the directory that the project is placed
     */
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

    /**
     *  Sub-root directory is the sub directory
     *  of $_SERVER['DOCUMENT_ROOT'] directory
     *  sub-root also known as the base path without the document root directory
     */
    function SubRootDir() {
        $root_dir = $_SERVER['DOCUMENT_ROOT'];
        $base_path = str_replace('\\','/',BasePath());
        
        $ret = str_replace($root_dir,'', $base_path);

        $first_char = substr($ret,0,1);
        $last_char = substr($ret, strlen($ret)-1, 1);

        $ret = $first_char === '/'? substr($ret, 1, strlen($ret)-1): $ret;
        $ret = $last_char === '/'? substr($ret, strlen($ret)-1, 1): $ret;

        return $ret;
    }

    

    

    