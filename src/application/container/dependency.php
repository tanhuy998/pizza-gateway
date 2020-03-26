<?php 
    namespace Application\Container;

    use Application\Container\DIContainer as DIContainer;
use Closure;
use stdClass;
    use ReflectionClass;

    class Dependency {
        /**
         *  The concreate class
         * 
         *  @var string
         */
        private $concrete;

        /**
         *  The alias name bound in container 
         *     
         *  @var string
         */
        private $name;

        /**
         *  the container that manage this dependency
         * 
         *  @var Application\Container\DIContainer
         */
        private $container;

        /**
         *  the singleton state of this dependency 
         * 
         *  @var bool
         */
        private $singleton;

        /**
         *  The default generator
         * 
         *  @var Closure
         */
        private $default;

        /**
         *  The address of the registered singleton object 
         * 
         *  @var int
         */
        private $address;

        /**
         *  @param string $_concrete
         *  @param Application\Container\DIContainer
         *  @param Closure $_default
         */
        public function __construct(string $_concrete, DIContainer &$_container, Closure $_default = null) {
            $this->container = $_container;
            $this->concrete = $_concrete;
            $this->name = null;
            $this->singleton = false;
            $this->default = $_default;

            $this->address = null;
            return $this;
        }

        /**
         *  Check if this dependency is singleton
         */
        public function IsSingleton(): bool {
            return $this->singleton;
        }

        /**
         *  Check if this dependency is registered with a default generator
         */
        public function HasDefault(): bool {
            return (!is_null($this->default));
        }

        /**
         *  return the default generator
         */
        public function GetDefaultGenerator() {
            return $this->default;
        }

        /**
         *  register this dependency as singleton
         *  @param object reference to object to bind as singleton
         */
        public function AsSingleton() {
            //$obj = $this->GetInstance();

            if (!$this->IsSingleton()) {

                $this->singleton = true;
            }

            
            return $this;
        }

        /**
         *  register alias name for this dependency to container
         *  @param string
         */
        public function Name(string $_name) {

            $this->name = $_name;

            $this->container->BindEvent();

            return $this;
        }

        /**
         *  return the refitered alias name
         */
        public function GetName() {
            return $this->name;
        }

        /**
         *  retrieve object address from the container for singleton
         *  @param int
         */
        public function SetSingletonAddress(int $_address) {
            $this->address = $_address;
        }

        /**
         *  return the singleton address for container
         */
        public function GetSingletonAddress() {
            return $this->address;
        }

        /**
         *  Return the concrete class
         */
        public function GetConcrete() {
            return $this->concrete;
        }
    }