<?php 
    namespace Application\Container;

    use Application\Container\DIContainer as DIContainer;
    use stdClass;
    use ReflectionClass;

    class Dependency {
        private $class;
        private $container;
        private $singleton;

        public function __construct($_class, DIContainer &$_container) {
            $this->container = $_container;
            $this->class = $_class;

            return $this;
        }

        public function IsSingleton() {
            return (isset($this->singleton));
        }

        public function GetInstance($_args = []) {

            if (!$this->IsSingleton()) {
                return $this->NewInstance();
            }
            // $class = new ReflectionClass($this->class);

            // if (empty($_args)) {
            //     return $class->newInstance( );
            // }
            // else {
            //     return $class->newInstanceArgs($_args);
            // }
        }

        private function NewInstance($_args = []) {
            $class = new ReflectionClass($this->class);

            if (empty($_args)) {
                return $class->newInstance( );
            }
            else {
                return $class->newInstanceArgs($_args);
            }
        }

        public function AsSingleton() {
            //$obj = $this->GetInstance();

            if (!$this->IsSingleton()) {
                $_address = $this->container->BindSingleton($this);

                $this->singleton = new stdClass();
                $this->SetSingletonAddress($_address);
            }
            return $this;
        }

        private function SetSingletonAddress(string $_address) {
            $this->singleton->address = $_address;
        }

        public function GetSingletonAddress() {
            return $this->singleton->address;
        }

        public function GetClass() {
            return $this->class;
        }
    }