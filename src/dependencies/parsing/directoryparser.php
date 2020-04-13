<?php
    namespace Dependencies\Parsing;

    class DirectoryParser {

        public function __construct() {
            
        }

        public function IsRelative(string $_dir) {
            $regex = '/^(\/[\w\.\[\]\(\)\$\#\@\!\-\_\+\=\&\^\%\'\;\,\~\`]+)+$/';

            return preg_match($regex, $_dir);
        }

        public function Isdirectory(string $_dir) {
            $regex = '/^([a-zA-Z]:)?(\/[\w\.\[\]\(\)\$\#\@\!\-\_\+\=\&\^\%\'\;\,\~\`]+)+$/';

            //$regex = PHP_OS === 'WINNT' ? "/^([a-zA-Z]:)?$regex" : "/^$regex";
            $_dir = str_replace('\\', '/', $_dir);
            
            return preg_match($regex, $_dir);
        }

        // public function IsAbsolute(string $_dir) {

        //     $basePath = $this->basePath;
        //     $basePath = preg_replace('/\\/', '/', $basePath);

        //     $regex = "^$basePath((\/|\\)[\w\.\[\]\(\)\$\#\@\!\-\_\+\=\&\^\%\'\;\,\~\`]+)+$";

        //     return preg_match("/$regex/", $_dir);
        // }
    }