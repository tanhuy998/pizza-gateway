<?php 
    namespace Application\Container;

    use Application\Container\DIContainer as DIContainer;
    use stdClass;
    use ReflectionClass;

    class Dependency {
        /**
         *  The class to build
         */
        private $class;

        /**
         *  the container that manage this dependency
         */
        private $container;

        /**
         *  the singleton state of this dependency 
         */
        private $singleton;

        /**
         *  @param string
         *  @param Application\Container\DIContainer
         */
        public function __construct($_class, DIContainer &$_container) {
            $this->container = $_container;
            $this->class = $_class;

            return $this;
        }

        /**
         *  Check if this dependency is singleton
         */
        public function IsSingleton() {
            return (isset($this->singleton));
        }

        /**
         *  deprecated method
         */
        public function GetInstance($_args = []) {

            if ($this->IsSingleton()) {

                

                return ;
            }

            return $this->Build();
            // $class = new ReflectionClass($this->class);

            // if (empty($_args)) {
            //     return $class->newInstance( );
            // }
            // else {
            //     return $class->newInstanceArgs($_args);
            // }
        }

        /**
         *  function that build an instance of this dependency
         *  @param array 
         */
        public function Build($_args = []) {
            $class = new ReflectionClass($this->class);

            if (empty($_args)) {
                return $class->newInstance( );
            }
            else {
                return $class->newInstanceArgs($_args);
            }
        }

        /**
         *  register this dependency as singleton
         */
        public function AsSingleton() {
            //$obj = $this->GetInstance();

            if (!$this->IsSingleton()) {
                $_address = $this->container->BindSingleton($this);

                $this->singleton = new stdClass();
                $this->SetSingletonAddress($_address);
            }
            return $this;
        }

        /**
         *  register alias name for this dependency to container
         *  @param string
         */
        public function Name(string $_name) {
            $this->container->BindName($_name, $this);

            return $this;
        }

        /**
         *  retrieve object address from the container for singleton
         *  @param int
         */
        private function SetSingletonAddress(int $_address) {
            $this->singleton->address = $_address;
        }

        /**
         *  return the singleton address for container
         */
        public function GetSingletonAddress() {
            return $this->singleton->address;
        }


        public function GetClass() {
            return $this->class;
        }
    }