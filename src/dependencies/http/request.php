<?php
    namespace Dependencies\Http;

use Dependencies\Router\Route;
use Exception;

    class Request {
        const REQUEST_FINE = true;
        const REQUEST_STOP = 0;

        protected static $http_methods = [
            'GET',
            'POST', 
            'HEAD', 
            'PUT', 
            'DELETE',
            'PATCH'
        ];

        private $method;
        private $inputs;
        private $queries;
        private $cookies;
        private $files;

        private $route;

        private $error;
        private $middlewaresMessage;

        public function __construct($_method = 'GET') {

            if (in_array($_method, self::$http_methods)) {
                $this->method = $_method;

                $this->BindGlobal();
            }

        }
        
        // public function Status() {
        //     if (isset($this->error)) {
        //         $error = $this->error;
        //         return $this->middlewaresMessage[$error];
        //     }

        //     return self::REQUEST_FINE;
        // }
        
        private function BindGlobal() {
            $this->inputs = $_POST;
            $this->cookies = $_COOKIE;
            $this->queries = $_GET;
            $this->files = $_FILES;
            //$this->server = $_SERVER;
        }

        public function Url() {
            return $_SERVER['REQUEST_SCHEME'].'://'.gethostname().'/'.$this->Path();
        }

        public function FullUrl() {
            return $_SERVER['REQUEST_SCHEME'].'://'.gethostname().$_SERVER['REQUEST_URI'];
        }

        public function Path() {
            $path = explode('?', $_SERVER['REQUEST_URI'])[0];

            $last_char = substr($path, strlen($path) - 1, 1);
            $first_char = substr($path, 0, 1);

            if ($last_char == '/') {
                $path = substr($path, 0, strlen($path) -1);
            }

            if ($first_char == '/') {
                $path = substr($path, 1, strlen($path) -1);
            }

            return $path;
        }

        public function FullPath() {
            $path = $_SERVER['REQUEST_URI'];

            $last_char = substr($path, strlen($path) - 1, 1);
            $first_char = substr($path, 0, 1);

            if ($last_char == '/') {
                $path = substr($path, 0, strlen($path) -1);
            }

            if ($first_char == '/') {
                $path = substr($path, 1, strlen($path) -1);
            }

            return $path;
        }

        public function Method() {
            return $this->method ?? false;

        }

        public function Cookie($_name) {
            return array_key_exists($_name, $this->cookies) 
                    ? $this->cookies[$_name] : null;
        }

        public function __get($name) {
            $this->Input($name);
        }

        public function Input(string $_name) {

            return array_key_exists($_name, $this->inputs) 
                    ? $this->inputs[$_name] : null;
        }

        public function File(string $_name) {

            return array_key_exists($_name, $this->files) 
                    ? $this->files[$_name] : null;
        }

        public function Query($_name) {
            return array_key_exists($_name, $this->queries) 
                    ? $this->queries[$_name] : null;
        }

        public function All() {
            return $this->inputs;
        }

        public function Description() {

            return $this->Method().' '.$this->Path();
        }

        public function SetRoute(Route $_route) {
            if (!isset($this->route)) {
                $this->route = $_route;
            }
        }

        public function Route() {
            return $this->route;
        }
    }