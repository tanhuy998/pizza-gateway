<?php 
    namespace Dependencies\Router;

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

            if (is_callable($_action)) {
                $this->action = $_action;
            }

            if (is_string($_action)) {
                $arr = explode(':', $_action);

                $this->action = count($arr) == 2? $arr: null;

                if (!$this->action) {
                    throw new Exception();
                }
            }

            throw new Exception();
        }

        public function Handle() {
            
        }

        public function Action() {
            return $this->action;
        }

        public function SetAction($_action) {
            $this->action = $_action;
        }

        public function Method($_method) {
            
        }

        public function Name(string $_name) {
            $this->name = $_name;

            $this->router->BindName($_name, $this);
        }

        public function Middleware(...$_chain) {

            if (empty($_chain)) {
                return $this->middlewareChain;
            }

            foreach ($_chain as $abstract) {
                $this->middlewareChain[] = $abstract;
            }
        }
    }