<?php
    namespace Dependencies\Http;

use Dependencies\Router\Route;
use Exception;

class Request {

        private $method;
        private $input;
        private $queries;
        private $cookie;

        private $route;

        public function __construct($_method = 'GET') {
            $http_methods = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'CONNECT', 'OPTIONs', 'TRACE', 'PATCH'];

            if (in_array($_method, $http_methods)) {
                $this->method = $_method;

                $this->Bind();
            }
        }
        

        
        private function Bind() {
            $this->inputs = $_POST;
            $this->cookies = $_COOKIE;
            $this->queries = $_GET;

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

        public function Cookie($name) {
            $value = $_COOKIE[$name];

            return $value ?? null;
        }

        public function __get($name) {
            $this->Input($name);
        }

        public function Input($name) {
            $content = $this->inputs[$name];

            return $content ?? null;
        }

        public function Query($name) {
            $content = $this->queries[$name];

            return $content ?? null;
        }

        public function All() {
            return $this->input;
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