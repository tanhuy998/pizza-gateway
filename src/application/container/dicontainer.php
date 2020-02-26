<?php
    namespace Application\Container;

    use Application\Container\IContainer as IContainer;
    use Application\Container\Exception as Exception;
    use Application\Container\Dependency as Dependency;
    use Application\Container\Exception\ClassExistsException;
    use Closure;
    use Exception as GlobalException;
use Reflection;
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
        protected const ALLOW_NULL = 100;
        protected const BIND_SINGLETON = 1;
        protected const BIND_NORMAL = 2;

        /**
         *  all classes that is mapped to dependencies list
         */
        private $bindMap;

        /**
         *  
         */
        private $alias;

        /**
         * Property $objectPool stores classes/interfaces that is registered as singleton 
         */
        private $objectPool;

        private $bindStack;

        protected static $containerInstance;

        private function __construct() {
            
            $this->bindStack = [];
            $this->bindMap = [];
            $this->dependenciesList = [];
            $this->alias = [];
            $this->interfaceList = [];
            $this->objectPool = [];

            //$this->BindSingleton(IContainer::class, self::class, $this);
            $this->bindMap[self::class] = $depen = new Dependency(self::class, $this);
            $this->bindMap[IContainer::class] = $depen;
            $this->objectPool[] = $this;
            $address = count($this->objectPool) - 1;
            $depen->AsSingleton();
            $depen->SetSingletonAddress($address);
        }

        public static function GetInstance() {

            if (is_null(self::$containerInstance) || !(self::$containerInstance instanceof self)) {

                self::$containerInstance = new Self();
            }

            return self::$containerInstance;
        }

        /**
         * function that add specific class to the the container's class list
         * @param string $_class 
         * @return void
         */
        public function Bind($_abstract, $_concrete, Closure $_default = null): Dependency {

            if ($this->IsBound($_abstract)) throw new Exception\ClassExistsException($_concrete);

            $this->ValidateBinding($_abstract, $_concrete);
            //
            
            $dependency = null;

            if (is_null($_default)) {
                $dependency = new Dependency($_concrete, $this);
            }
            else {
                $default = $this->ValidateBindingDefault($_concrete, $_default);
                $default = Closure::fromCallable($default);
                $dependency = new Dependency($_concrete, $this, $default);
            }

            $this->bindMap[$_abstract] = $dependency;
            $this->bindStack[] = $dependency;

            return $dependency;
        }

        private function ValidateBinding(string $_abstract, string $_concrete) {

            if ($_abstract === $_concrete) return;

            $concrete = new ReflectionClass($_concrete);
            $abstract = new ReflectionClass($_abstract);
            
            if (!$concrete->isInstantiable()) throw new GlobalException("Class $_concrete can not be instantiate");

            if ($concrete->isSubclassOf($_abstract)) return;

            throw new GlobalException("Class $_concrete does not extend or implement $_abstract");
        }

        /**
         *  Resolve and return the object for when binding method has option
         */
        protected function ValidateBindingDefault(string $_abstract, $_option, bool $_flag = self::BIND_NORMAL) {

            //  if $_option is closure(annonymouse function)
            //  the function passed must not have parameter 
            //  and return an instance of the bound class
            if ($_option instanceof Closure) {

                $func = new ReflectionFunction($_option);
                $params = $func->getParameters();

                //if (!empty($params)) throw new GlobalException('Function that passed to the binding method must not have paramater!');
                $params = $this->ResolveFunctionParameters($func);

                $object = $func->invokeArgs($params);

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
        protected function SetAlias() {
            $last_bound_dependency = end($this->bindStack);

            $name = $last_bound_dependency->GetName();

            if (!$this->AliasExists($name)) {
                

                $this->alias[$name] = $last_bound_dependency;

                return true;
            }

            throw new Exception\AliasNameExistsException($name);
        }

        protected function AliasExists($_name) {

            return array_key_exists($_name, $this->alias);
        }

        /**
         * Function to check an abstract is bound before
         * @param string $_class 
         * @return bool
         */
        protected function IsBound(string $_abstract): bool {

            return array_key_exists($_abstract, $this->bindMap);
        }

        /**
         *  Extender of Bind function that bind a dependency to a singleton object in object pool
         * 
         * @param Application\container\Dependency 
         * @param object
         * @return int the address of the allocated object
         */
        public function BindSingleton(string $_abstract, string $_concrete, $_default = null): Dependency {
            
            if ($_default instanceof Closure) {
                $dependency = $this->Bind($_abstract, $_concrete, $_default);
            }
            else {
                
                $dependency = $this->Bind($_abstract, $_concrete);
                
                if (!($_default instanceof $_abstract)) {
                    throw new GlobalException("Object pass to singleton binding method is not instance of $_abstract");
                }
                
                $this->objectPool[] = $_default;

                $pool_address = count($this->objectPool) - 1;

                $dependency->SetSingletonAddress($pool_address);
            }

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


        protected function ResolveFunctionParameters(ReflectionFunctionAbstract $_function, int $_mode = 0): array {
            $reflect_params = $_function->getParameters();

            if (empty($reflect_params)) return [];
            
            $process = function ($param) use ($_function, $_mode) {
                //  if the parameter is default parameter
                //  just return the function defined default value
                if ($param->isDefaultValueAvailable()) {

                    return $param->getDefaultValue();
                }

                $type = $param->getType();

                //  If the param is not type-hinted 
                //  throw exception
                if (is_null($type)) {

                    //  The second parameter of ResolveFunctionParameters
                    //  to set the mode to allow non-type hinted parameter
                    //  the container will pass null value for this parameter 
                    if ($_mode = self::ALLOW_NULL) return null;

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

        public function Call($_func, $_option = null ,  array $_args = []) {
            if (is_string($_func)) {

                if (is_string($_option)) return $this->CallMethodFromClass($_func, $_option, $_args);

                if (is_array($_option)) return $this->CallFunction($_func, $_option);
                
            }

            if ($_func instanceof Closure) {
                return $this->CallClosure($_func, $_option);
            }
        }

        private function CallMethodFromClass(string $_method, string $_class, array $_args) {
            $class = new ReflectionClass($_class);
            $method = $class->getMethod($_method);

            $params = $method->getParameters();

            //  Get resolved set of arguments that allowed non-type hinted
            $resolved_args = $this->ResolveFunctionParameters($method, self::ALLOW_NULL);

            $resolved_args = $this->ResolveNullArguments($resolved_args, $params, $_args);

            //return $_function->invokeArgs($resolved_args);
        }  

        private function CallFunction(string $_function, array $_args) {
            $func = new ReflectionFunction($_function);
            $params = $_function->getParameters();

            //  Get resolved set of arguments that allowed non-type hinted
            $resolved_args = $this->ResolveFunctionParameters($func, self::ALLOW_NULL);

            $resolved_args = $this->ResolveNullArguments($resolved_args, $params, $_args);

            return $_function->invokeArgs($resolved_args);
        }

        private function CallClosure(Closure $_function, array $_args) {
            

        }

        /**
         *  This method is used in call method to resolve the null arguments
         *  that is return by ResolveFunctionParameters method in ALLOW_NULL mode
         * 
         *  @param array $_resolved_args 
         *  @param array $_parameters of type ReflectionParameter 
         *  @param array $_args the set of arguments to inject to the null arguments
         *  
         *  @return array
         */
        private function ResolveNullArguments(array $_resolved_args, array $_parameters, array $_args): array {

            // index to iterate through $_parameters array
            $i = 0;

            $inject_null = function($_param) use ($_args, $_parameters, &$i) {

                if ($_param === null) {
                    $param_name = $_parameters[$i]->getName();
                    
                    if (array_key_exists($param_name, $_args)) {
                        ++$i;

                        return $_args[$param_name];
                    }
                    else {
                        ++$i;

                        return array_pop($_args);
                    }
                }

                ++$i;
                return $_param;
            };

            return array_map($inject_null ,$_resolved_args);
        }

        private function ResolveOptionCall($_option) {

            if (is_string($_option)) {

            }

            //if ()
        }

        private function ValidateMethodCall($_method, $_abstract) {

        }

        private function ValidateCallFunctionOption($_option) {
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
            return $this->build($_dependency->GetConcrete());
        }

        private function SetupSingleton(Dependency &$_dependency) {

            // $object = $_dependency->HasDefault() ? $_dependency->GetDefaultGenerator() 
            //             : $this->Build($_dependency->GetConcrete());

            if ($_dependency->HasDefault()) {
                $generator = $_dependency->GetDefaultGenerator();
                $reflect_generator = new ReflectionFunction($generator);

                $args = $this->ResolveFunctionParameters($reflect_generator);

                $object = $reflect_generator->invokeArgs($args);
            }
            else {
                $object = $this->Build($_dependency->GetConcrete());
            }

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

            if (!$dependency) throw new GlobalException("Trying to get $_name from the container that is unbound before");

            return $this->ResolveDependency($dependency);
        }

        public function BindEvent() {
            $this->SetAlias();
        }
    }