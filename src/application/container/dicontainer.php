<?php
    namespace Application\Container;

    use Application\Container\IContainer as IContainer;
    use Application\Container\Exception as Exception;
    use Application\Container\Dependency as Dependency;
    use ReflectionClass;

/**
     * DIContiner class define a container that stores dependencies
     * for the Dependencies Injector 
     */
    class DIContainer implements Icontainer{

        /**
         *  Property $classList stores the registered classes for the container
         */
        private $classList;

        /**
         *  Property $interfaceList stores the registered interfaces that is bound to specific classes
         */
        private $interfaceList;

        /**
         * Property $objectPool stores classes/interfaces that is registered as singleton 
         */
        private $objectPool;

        public function __construct() {
            $this->classList = [];
            $this->interfaceList = [];
            $this->objectPool = [];
        }

        /**
         * function that add specific class to the the container's class list
         * @param string $_class 
         * @return void
         */
        public function BindClass($_class) {
            $this->classList[$_class] = new Dependency($_class, $this);
        }

        /**
         * fucntion that bind an interface to a class 
         * then record to container's interfaces list
         * @param string $_interface
         * @param string $_class
         * @return void
         */
        public function BindInterface($_interface, $_class) {
            $this->interfaceList[$_interface] = new Dependency($_class, $this);
        }

        /**
         * function that bind a dependency to a singleton object in object pool
         * @param Dependency $_dependency
         * @return int the address of the allocated object
         */
        public function BindSingleton(Dependency $_dependency) {

            if (!$_dependency->IsSingleton()) {
                $object = $_dependency->GetInstance();

                $this->objectPool[] = $object;
    
                $object_address = count($this->objectPool) - 1;
    
                return $object_address;
            }
            
        }

        private function GetObjectByAddress($_address) {
            $object = $this->objectPool[$_address];

            if(isset($object)) {
                return $object;
            }
            
            return false;
        }
        
        /**
         * @param string $_class
         * @return object 
         */
        public function GetClassInstance($_class) {
            $dependency = $this->classList[$_class];

            if (isset($dependency)) {
                if ($dependency->IsSingleton()) {
                    $address = $dependency->GetSingletonAddress();
                    $object = $this->GetObjectByAddress($address);

                    return $object;
                }

                return $dependency->GetInstance();
            }
            
            throw new Exception\ClassNotBoundException($_class);
        }

        public function GetInterfaceInstance($_interface) {
            $dependency = $this->interfaceList[$_interface];

            if (isset($dependency)) {
                if ($dependency->IsSingleton()) {
                    $address = $dependency->GetSingletonAddress();
                    $object = $this->GetObjectByAddress($address);

                    return $object;
                }

                return $dependency->GetInstance();
            }

            throw new Exception\InterfaceNotBoundException($_interface);
        }

        public function GetInstance(string $_name) {
            
        }
    }