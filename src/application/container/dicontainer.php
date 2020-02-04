<?php
    namespace Application\Container;

    use Application\Container\IContainer as IContainer;
    use Application\Container\Exception as Exception;
    use Application\Container\Dependency as Dependency;
use Exception as GlobalException;
use ReflectionObject;

/**
     * DIContiner class define a container that stores dependencies
     * for the Dependencies Injector 
     */
    class DIContainer implements Icontainer{
        const INSTANTIATE = 1;

        /**
         *  all dependencies which is bound with classes
         */
        private $dependenciesList;

        /**
         *  all classes that is mapped to dependencies list
         */
        private $classList;

        /**
         *  all interfaces that is mapped to dependencies list
         */
        private $interfaceList;

        /**
         *  
         */
        private $nameList;

        /**
         * Property $objectPool stores classes/interfaces that is registered as singleton 
         */
        private $objectPool;

        public function __construct() {
            $this->nameList = [];
            $this->dependenciesList = [];
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

            if ($this->ClassIsBound($_class)) throw new Exception\ClassExistsException($_class);

            if (!isset($this->classList[$_class])) {

                $this->dependenciesList[] = new Dependency($_class, $this);

                $index = count($this->dependenciesList) - 1;

                $this->classList[$_class] = $this->dependenciesList[$index];

                return $this->classList[$_class];
            }
        }

        /**
         * fucntion that bind an interface to a class 
         * then record to container's interfaces list
         * @param string $_interface
         * @param string $_class
         * @return void
         */
        public function BindInterface($_interface, $_class) {

            if ($this->ClassIsBound($_class)) throw new Exception\ClassExistsException($_class);

            if (!isset($this->interfaceList[$_interface])) {

                $this->dependenciesList[] = new Dependency($_class, $this);

                $index = count($this->dependenciesList) - 1;

                $this->interfaceList[$_interface] = $this->dependenciesList[$index];

                return $this->interfaceList[$_interface];
            }
            
        }

        /**
         * function that binds a name with a dependency of a class
         * @param string $_name
         * @param Application\Container\Dependency
         */
        public function BindName(string $_name, Dependency &$_dependency) {

            if (!isset($this->nameList[$_name])) {
                $this->nameList[$_name] = $_dependency;

                return true;
            }

            throw new Exception\AliasNameExistsException($_name);
        }

        /**
         * Function to check a class is bound before
         * @param string $_class 
         * @return bool
         */
        private function ClassIsBound(string $_class): bool {

            $search_result = array_filter($this->dependenciesList, 
            function($var) use($_class) {

                if ($var->GetClass() == $_class) {
                    return $var;
                }

            });

            if (count($search_result) > 0) {
                return true;
            }

            return false;
        }

        /**
         * function that bind a dependency to a singleton object in object pool
         * @param Application\container\Dependency 
         * @param object
         * @return int the address of the allocated object
         */
        public function BindSingleton(Dependency $_dependency, &$_object = null) {

            if (!$_dependency->IsSingleton()) {

                $object = $_object ?? $_dependency->Build();

                $object_reflector = new ReflectionObject($object);

                if ($_dependency->GetClass() != $object_reflector->getName()) {
                    throw new Exception\ObjectTypeNotMatchException($_dependency->GetClass(), $object_reflector->getName());
                }

                $this->objectPool[] = $object;
    
                $object_address = count($this->objectPool) - 1;
    
                return $object_address;
            }
            
        }

        /**
         *  Get object in object pool by address
         *  @param int $_address;
         *  @return object
         */
        private function GetObjectByAddress(int $_address) {
            $object = $this->objectPool[$_address];

            if(isset($object)) {
                return $object;
            }
            
            return false;
        }

        /**
         *  
         *  @param Application\Container\Dependency
         *  @return object
         */
        private function ResolveDependency(Dependency $_dependency) {
            if ($_dependency->IsSingleton()) {

                $address = $_dependency->GetSingletonAddress();
                $object = $this->GetObjectByAddress($address);
                
                return $object;
            }

            return $_dependency->Build();
        }

        /**
         *  Get a class bound dependency
         *  @param string $_class
         *  @param int 
         *  @return object 
         */
        public function GetClass(string $_class, $_instantiate = self::INSTANTIATE) {
            $dependency = $this->classList[$_class];

            if (isset($dependency)) {

                if ($_instantiate === 1) {
                    return $this->ResolveDependency($dependency);
                }
                
                return $dependency;
            }
            
            throw new Exception\ClassNotBoundException($_class);
        }

        

        /** 
         *  Get a interface bound dependency
         *  @param string $_interface
         *  @param int
         *  @return object 
         */
        public function GetInterface(string $_interface, $_instantiate = self::INSTANTIATE) {
            $dependency = $this->interfaceList[$_interface];

            if (isset($dependency)) {
                if ($_instantiate === 1) {
                    return $this->ResolveDependency($dependency);
                }
                
                return $dependency;
            }

            throw new Exception\InterfaceNotBoundException($_interface);
        }

        /** 
         *  Get a named dependency
         *  @param string $_name
         *  @param int 
         *  @return object
         */
        public function Get(string $_name, $_instantiate = self::INSTANTIATE) {
            $dependency = $this->nameList[$_name];

            if (isset($dependency)) {
                if ($_instantiate === 1) {
                    return $this->ResolveDependency($dependency);
                }
                
                return $dependency;
            }

            throw new Exception\AliasNameNotRegisteredException($_name);
        }
    }