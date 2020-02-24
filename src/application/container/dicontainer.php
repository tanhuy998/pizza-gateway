<?php
    namespace Application\Container;

    use Application\Container\IContainer as IContainer;
    use Application\Container\Exception as Exception;
    use Application\Container\Dependency as Dependency;
    use Application\Container\Exception\ClassExistsException;
    use Closure;
    use Exception as GlobalException;
    use ReflectionClass;
    use ReflectionFunction;
    use ReflectionFunctionAbstract;
    use ReflectionMethod;
    use ReflectionObject;
    use ReflectionParameter;

    /**
     * DIContiner class define a container that stores dependencies
     * for the Dependencies Injector 
     */
    class DIContainer implements Icontainer{
        const BIND_SINGLETON = 1;
        const BIND_NORMAL = 2;

        /**
         *  all dependencies which is bound with classes
         */
        private $dependenciesList;

        /**
         *  all classes that is mapped to dependencies list
         */
        private $bindMap;

        /**
         *  all interfaces that is mapped to dependencies list
         */
        private $interfaceList;

        /**
         *  
         */
        private $alias;

        /**
         * Property $objectPool stores classes/interfaces that is registered as singleton 
         */
        private $objectPool;

        private $bindStack;

        public function __construct() {
            $this->bindStack = [];
            $this->bindMap = [];
            $this->dependenciesList = [];
            $this->alias = [];
            $this->interfaceList = [];
            $this->objectPool = [];
        }

        /**
         * function that add specific class to the the container's class list
         * @param string $_class 
         * @return void
         */
        public function Bind($_abstract, $_class, Closure $_default = null) {

            if ($this->IsBound($_abstract)) throw new Exception\ClassExistsException($_class);

            $this->ValidateBinding($_abstract, $_class);
            //
            
            $dependency = null;

            if (is_null($_default)) {
                $dependency = new Dependency($_class, $this);
            }
            else {
                $default = $this->ResolveBindingDefault($_class, $_default);
                $default = Closure::fromCallable($default);
                $dependency = new Dependency($_class, $this, $default);
            }

            $this->bindMap[$_abstract] = $dependency;
            $this->bindStack[] = $dependency;

            return $dependency;
        }

        private function ValidateBinding(string $_abstract, string $_class) {

            if ($_abstract === $_class) return;

            $class = new ReflectionClass($_class);
            $abstract = new ReflectionClass($_abstract);

            if (!$class->isInstantiable()) throw new GlobalException("Class $_class can not be instantiate");

            if ($class->isSubclassOf($_abstract)) return;

            throw new GlobalException("Class $_class does not extend or implement $_abstract");
        }

        /**
         *  Resolve and return the object for when binding method has option
         */
        private function ResolveBindingDefault(string $_abstract, $_option, bool $_flag = self::BIND_NORMAL) {

            //  if $_option is closure(annonymouse function)
            //  the function passed must not have parameter 
            //  and return an instance of the bound class
            if (is_callable($_option)) {

                $func = new ReflectionFunction($_option);
                $params = $func->getParameters();

                if (!empty($params)) throw new GlobalException('Function that passed to the binding method must not have paramater!');

                $object = $func->invoke();

                if (is_null($object)) throw new GlobalException('Function that pass to the binding method must return value!');

                if (!($object instanceof $_abstract)) throw new GlobalException("Return object of function must be instance of the $_abstract class");

                //  return the closure
                return $_option;
            }

            //  When binding singleton
            //  We can pass an object to be a default object for calling singleton
            //  if object is passed when BINDING_NORMAL session is occurring
            //  throw exception
            //echo $_flag;
            if ($_flag !== self::BIND_SINGLETON) {
                throw new GlobalException('Could not pass object to non singleton binding!');
            }

            //  if $_option is object
            //  check if the object is instance of the bound class
            if (!($_option instanceof $_abstract)) {
                throw new GlobalException("Object that pass to the binding method is not instance of $_abstract");
            }

            return $_option;
        }

        /**
         * function that binds a name with a dependency of a class
         * @param string $_name
         * @param Application\Container\Dependency
         */
        private function SetAlias() {
            $last_bound_dependency = end($this->bindStack);

            $name = $last_bound_dependency->GetName();

            if (!$this->AliasExists($name)) {
                

                $this->alias[$name] = $last_bound_dependency;

                return true;
            }

            throw new Exception\AliasNameExistsException($name);
        }

        private function AliasExists($_name) {

            return array_key_exists($_name, $this->alias);
        }

        /**
         * Function to check an abstract is bound before
         * @param string $_class 
         * @return bool
         */
        private function IsBound(string $_abstract): bool {

            return array_key_exists($_abstract, $this->bindMap);
        }

        /**
         *  Extender of Bind function that bind a dependency to a singleton object in object pool
         * 
         * @param Application\container\Dependency 
         * @param object
         * @return int the address of the allocated object
         */
        public function BindSingleton(string $_abstract, string $_class, $_default = null) {

            // call 
            $dependency = $this->Bind($_abstract, $_class, $_default);

            $dependency->AsSingleton();

            // $object = (is_null($_default)) ? $this->Build($_class)
            //             : $this->ResolveBindingDefault($_class, $_default, self::BIND_SINGLETON);

            return $dependency;
        }

        private function Build($_concrete) {

            if (is_callable($_concrete)) {
                return ;
            }

            $class = new ReflectionClass($_concrete);

            if (!$class->isInstantiable()) 
            throw new GlobalException("Could not instantiate class $_concrete because it can be abstract class or interface");


            $constructor = $class->getConstructor();

            //  If the class doesn't has constructor
            //  Just return new instance of it
            if (is_null($constructor)) {
                return new $_concrete;
            }

            $params = $this->ResolveFunctionParameters($constructor);

            //  If constructor has no parameter
            if (empty($params)) return $class->newInstance();
            
            return $class->newInstanceArgs($params);
        }


        private function ResolveFunctionParameters(ReflectionFunctionAbstract $_function): array {
            $reflect_params = $_function->getParameters();

            if (empty($reflect_params)) return [];
            
            $process = function ($param) use ($_function) {
                //  if the parameter is default parameter
                //  just return the function defined default value
                if ($param->isDefaultValueAvailable()) {

                    return $param->getDefaultValue();
                }

                $type = $param->getType();

                //  If the param is not type-hinted 
                //  throw exception
                if (is_null($type)) {

                    $param_name = $param->getName();
                    $function_name = $_function->getName();

                    $function_class = ($_function instanceof ReflectionMethod) ? 
                                        $_function->getDeclaringClass()->getName().'::' : '';

                    throw new GlobalException("Parameter \"$param_name\" of function \"$function_class $function_name()\" is not type hinted");
                } 

                //  If the parameter is built-in type
                //  throw exception
                if ($type->isBuiltin()) throw new GlobalException("Could not inject parameter $param_name with built-in($type)");

                //  When the parameter is not built-in type
                //  Check the parameter is type hinted to a class
                //  Get the type hinted parameter's class
                $class = $param->getClass();

                //  If the parameter is not type hinted throw exception
                if (is_null($class)) {
                    throw new GlobalException("Could not inject parameter $param_name of mix type!");
                }

                //  get the name of the parameter's type hinted class
                $abstract = $class->getName();

                try {
                    //  If The class name(Interface/Class) is bound before
                    //  inject this abstract as bound to the list
                    return $this->Get($abstract);
                }
                catch(GlobalException $e) {
                    
                    //  If there isn't alias for the parameter in container
                    //  Make an instance of it from the beginning
                    return $this->make($abstract);
                }

                //  End $process closure context
            };

            //  $this->ResolveFunctionParameters context
            return array_map($process, $reflect_params);
        }

        public function Make($_abstract) {

            // $dependency = $this->AliasExists($_abstract) ? $this->alias[$_abstract]
            //             : $this->IsBound($_abstract) ? $this->bindMap[$_abstract] : null;

            // if (!is_null($dependency)) {
            //     return $this->ResolveDependency($dependency);
            // } 

            return $this->Build($_abstract);
        }

        public function Call($_abstract, $_option) {
            if (is_string($_abstract)) {

            }

            if ($_abstract instanceof Closure) {

            }


        }

        private function ValidateMethodCall($_method, $_abstract) {

        }

        private function ResolveCallFunctionOption($_option) {
            if (is_string($_option)) {

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
            //  If the dependency is singleton
            //  resolve the object from dependency concrete
            if ($_dependency->IsSingleton()) {

                $address = $_dependency->GetSingletonAddress();
                
                //  If the dependency has not been instantiated for registering to container
                //  Then setup for the dependency to be instantiated
                //  When a singleton dependency is registered for the container
                //  It will keep an address which is provided by the container
                //  to access to the object bool
                if (is_null($address)) {
                    $this->SetupSingleton($_dependency);

                    $address = $_dependency->GetSingletonAddress();
                    //echo $address;
                    return $this->GetObjectByAddress($address);
                }
                
                //  
                return  $this->GetObjectByAddress($address);
            }

            //  When the dependency is not singleton
            //  Check if the dependency has default object generator
            if ($_dependency->HasDefault()) {

                $new_object = $_dependency->GetDefaultGenerator();

                //  Object generator is a closure
                //  Just call it
                return $new_object();
            }

            //  When the dependency doesn't has default object generator
            //  Build an instance of it from the beginning
            return $this->build($_dependency->GetClass());
        }

        private function SetupSingleton(Dependency &$_dependency) {

            $object = $_dependency->HasDefault() ? $_dependency->GetDefaultGenerator()() 
                        : $this->Build($_dependency->GetClass());

            $this->objectPool[] = $object;

            $pool_address = count($this->objectPool) - 1;
            //echo 'pool',$pool_address;

            $_dependency->SetSingletonAddress($pool_address);
        }

        /** 
         *  Get a named dependency
         *  @param string $_name
         *  @param int 
         *  @return object
         */
        public function Get(string $_name) {

            $dependency = $this->AliasExists($_name) ? $this->alias[$_name]
                        : $this->IsBound($_name) ? $this->bindMap[$_name] : null;

            if (!$dependency) throw new GlobalException();

            return $this->ResolveDependency($dependency);
        }

        public function BindEvent() {
            $this->SetAlias();
        }
    }