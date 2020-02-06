<?php 
    namespace Dependencies\Router;

    use Dependencies\Router\Router as Router;

    class Route {

        private $router;

        private $path;

        private $action;

        private $method;


        public function __construct(Router &$_router, $_path, $_action = null) {
            $this->path = $_path;

            $this->action = $_action;

            $this->router = $_router;
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
            $this->router->BindName($_name, $this);
        }
    }