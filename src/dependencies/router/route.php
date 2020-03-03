<?php 
    namespace Dependencies\Router;

use Closure;
use Dependencies\Router\Router as Router;
use Exception;
use ReflectionFunction;
use ReflectionType;

class Route {

        private $router;

        private $action;

        private $path;

        private $name;

        private $method;

        private $middlewareChain;


        public function __construct(Router &$_router, $_path, $_action = null) {
            $this->path = $_path;

            $this->middlewareChain = [];

            $this->router = $_router;

            $this->ValidateAction($_action);

            $this->action = $_action;
        }

        public function Action() {
            return $this->action;
        }


        private function ValidateAction($_action) {
            if ($_action instanceof Closure) {
                return;
            }

            if (is_string($_action)) {
                $arr = explode('::' ,$_action);

                if (count($arr) === 2) {
                    return;
                }

                throw new Exception();
            }

            throw new Exception();
        }

        public function Method() {
            return $this->method;
        }

        public function Name(string $_name) {
            if (!isset($this->name)) {
                $this->name = $_name;

                $this->router->RouteRegisterEvent();
            }
        }

        public function GetName() {
            return $this->name;
        }

        public function Middleware(...$_chain) {

            if (empty($_chain)) {
                return $this->middlewareChain;
            }

            foreach ($_chain as $abstract) {
                $this->middlewareChain[] = $abstract;
            }
        }

        public function GetUriPattern() {
            return $this->path;
        }
    }