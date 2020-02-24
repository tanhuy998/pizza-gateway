<?php 
    namespace Application\Container;

    use Application\Container\DIContainer as DIContainer;
use Closure;
use stdClass;
    use ReflectionClass;

    class Dependency {
        /**
         *  The class to build
         */
        private $class;

        private $name;

        /**
         *  the container that manage this dependency
         */
        private $container;

        /**
         *  the singleton state of this dependency 
         */
        private $singleton;

        private $default;

        private $address;

        /**
         *  @param string
         *  @param Application\Container\DIContainer
         */
        public function __construct($_class, DIContainer &$_container, Closure $_default = null) {
            $this->container = $_container;
            $this->class = $_class;
            $this->name = null;
            $this->singleton = false;
            $this->default = $_default;

            return $this;
        }

        /**
         *  Check if this dependency is singleton
         */
        public function IsSingleton() {
            return $this->singleton;
        }

        public function HasDefault() {
            return (!is_null($this->default));
        }

        public function GetDefaultGenerator() {
            return $this->default;
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
         *  @param object reference to object to bind as singleton
         */
        public function AsSingleton(&$_object = null) {
            //$obj = $this->GetInstance();

            if (!$this->IsSingleton()) {

                $this->singleton = true;
            }

            
            return $this;
        }

        // public function HasSingleton() {
        //     if ($this->IsSingleton()) {
        //         return !($this->singleton instanceof bool);
        //     }

        //     return false;
        // }

        /**
         *  register alias name for this dependency to container
         *  @param string
         */
        public function Name(string $_name) {

            $this->name = $_name;

            $this->container->BindEvent();

            return $this;
        }

        public function GetName() {
            return $this->name;
        }

        /**
         *  retrieve object address from the container for singleton
         *  @param int
         */
        public function SetSingletonAddress(int $_address) {
            $this->singleton = $_address;
        }

        /**
         *  return the singleton address for container
         */
        public function GetSingletonAddress() {
            return $this->singleton;
        }


        public function GetClass() {
            return $this->class;
        }
    }