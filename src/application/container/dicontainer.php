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
     *  DIContiner class defines a container that stores dependencies
     *  for the Dependency injection 
     */
    class DIContainer implements Icontainer{
        protected const MODE_ALLOW_NULL = 100;
        protected const MODE_NOT_ALLOW_NULL = 101;
        protected const BIND_SINGLETON = 1;
        protected const BIND_TRANSIENT = 2;

        /**
         *  Set of dependencies
         *  
         *  @var array Application\Container\Dependency
         */
        protected $bindMap;

        /**
         *  Set of registered alias for dependencies
         * 
         *  @var array Application\Container\Dependency
         */
        protected $alias;

        /**
         *  stores concretes's object that is bind as singleton 
         *  
         *  @var array
         */
        private $objectPool;

        /**
         *  To trace the binding status for binding events
         * 
         *  @var array
         */
        protected $bindStack;

        /**
         *  This class is implementing singleton pattern 
         *  To make the whole script use just one container object 
         *  for better accurate of injecting dependencies 
         *  and prevents confusions while using
         * 
         *  @var Application\Container\Container
         */
        protected static $containerInstance;

        private function __construct() {
            
            $this->bindStack = [];
            $this->bindMap = [];
            $this->dependenciesList = [];
            $this->alias = [];
            $this->interfaceList = [];
            $this->objectPool = [];

            //  Bind itself as a singleton for injecting
            //  Conatiner couldn't bind class with singleton design pattern 
            //  because singleton pattern is uninstantiable
            //  So we can not do this action $this->BindSingleton(IContainer::class, self::class, $this);
            //  then we have to bind itself internally
            $this->bindMap[self::class] = $depen = new Dependency(self::class, $this);
            $this->bindMap[IContainer::class] = $depen;
            $this->objectPool[] = $this;
            $address = count($this->objectPool) - 1;
            $depen->AsSingleton();
            $depen->SetSingletonAddress($address);
        }

        /**
         *  Get static instance of DIContainer class
         */
        public static function GetInstance() {

            if (is_null(self::$containerInstance) || !(self::$containerInstance instanceof self)) {

                self::$containerInstance = new Self();
            }

            return self::$containerInstance;
        }

        /**
         *  Method that bind an abstract(classes/interfaces) to concrete class.
         *  Concrete class must extends or imnplements abstract.
         *  When abstract is bound by this function, the concrete class will be instantiate and inject 
         *  for constructor each time by default.
         *  the abstract is called to get an instance via the container.
         *  This function has an option is to choose how the concrete class is instantiate by passing the third
         *  parameter as a closure that return a modified concrete instance
         * 
         * @param string $_abstract The abstact class/interface to be injected
         * @param string $_concrete The concrete class to be instantiate
         * @param Closure $_default The default object generator when getting instance from the Container
         * 
         * @return Application\Container\Dependency
         */
        public function Bind(string $_abstract, string $_concrete, Closure $_default = null): Dependency {

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

        /**
         *  Validate the binding between abstract and concrete class.
         *  Concrete class must extend or implement abstract.
         * 
         *  @param string $_abstract
         *  @param string $_concrete
         * 
         *  @throws Exception When $_abstract and $_concrete is not suitable
         */
        private function ValidateBinding(string $_abstract, string $_concrete) {

            if ($_abstract === $_concrete) return;

            $concrete = new ReflectionClass($_concrete);
            $abstract = new ReflectionClass($_abstract);
            
            if (!$concrete->isInstantiable()) throw new GlobalException("Class $_concrete can not be instantiate");

            if ($concrete->isSubclassOf($_abstract)) return;

            throw new GlobalException("Class $_concrete does not extend or implement $_abstract");
        }

        /**
         *  Validate the default generator of concrete class.
         *  The default generator of a dependency is a closure that return must
         *  return an instance of the concrete class.
         * 
         *  @param string $_concrete The concrete class
         *  @param mixed $_option the default Generator
         *  @param mixed $_flag
         * 
         *  @return mixed $_option
         * 
         *  @throws Exception when the $_option is not match rule
         */
        protected function ValidateBindingDefault(string $_concrete, $_option, $_flag = self::BIND_TRANSIENT) {

            //  if $_option is closure(annonymouse function)
            //  the function passed must not have parameter 
            //  and return an instance of the bound class
            if ($_option instanceof Closure) {

                $func = new ReflectionFunction($_option);
                $params = $func->getParameters();

                //if (!empty($params)) throw new GlobalException('Function that passed to the binding method must not have paramater!');
                $params = $this->InjectFunctionParameters($func);

                $object = $func->invokeArgs($params);

                if (is_null($object)) throw new GlobalException('Function that pass to the binding method must return value!');

                if (!($object instanceof $_concrete)) throw new GlobalException("Return object of function must be instance of the $_concrete class");

                //  return the closure
                return $_option;
            }

            // //  When binding singleton
            // //  We can pass an object to be a default object for calling singleton
            // //  if object is passed when BINDING_NORMAL session is occurring
            // //  throw exception
            // //echo $_flag;
            // if ($_flag !== self::BIND_SINGLETON) {
            //     throw new GlobalException('Could not pass object to non singleton binding!');
            // }

            // //  if $_option is object
            // //  check if the object is instance of the bound class
            // if (!($_option instanceof $_concrete)) {
            //     throw new GlobalException("Object that pass to the binding method is not instance of $_concrete");
            // }

            return $_option;
        }

        /**
         *  method to check an abstract is bound before
         * 
         *  @param string $_abstract
         * 
         *  @return bool
         */
        protected function IsBound(string $_abstract): bool {

            return array_key_exists($_abstract, $this->bindMap);
        }

        /**
         *  Bind an abstact to a concrete class as a singleton dependency.
         *  A singleton dependency is a dependenccy that it's concrete instance
         *  is instantiated just one time and is stored and managed by the container.
         *  A singleton dependency's concrete instance is shared over a request context.
         * 
         *  @param string $_abstract
         *  @param string $_concrete
         *  @param mixed $_default can be object of type $_concrete or a closure for generating object
         *  for the binding
         * 
         *  @return Application\Container\Depedency
         *  
         *  @throws Exception
         */
        public function BindSingleton(string $_abstract, string $_concrete, $_default = null): Dependency {
            
            if ($_default instanceof Closure) {
                $dependency = $this->Bind($_abstract, $_concrete, $_default);
            }
            else {
                
                $dependency = $this->Bind($_abstract, $_concrete);
                
                if ($_default !== null) {

                    if (!($_default instanceof $_concrete)) {
                        throw new GlobalException("Object pass to singleton binding method is not instance of $_abstract");
                    }

                    $this->objectPool[] = $_default;

                    $pool_address = count($this->objectPool) - 1;

                    $dependency->SetSingletonAddress($pool_address);
                }
            }

            $dependency->AsSingleton();

            // $object = (is_null($_default)) ? $this->Build($_class)
            //             : $this->ResolveBindingDefault($_class, $_default, self::BIND_SINGLETON);

            return $dependency;
        }

        /**
         *  Build a concrete class 
         * 
         *  @param string $_concrete The concrete class
         * 
         *  @return mixed a new object with totally-injected using constructor injection method
         * 
         *  @throws Exception
         */
        protected function Build(string $_concrete) {

            $class = new ReflectionClass($_concrete);

            if (!$class->isInstantiable()) 
            throw new GlobalException("Could not instantiate class $_concrete because it can be abstract class or interface");


            $constructor = $class->getConstructor();

            //  If the class doesn't has constructor
            //  Just return new instance of it
            if (is_null($constructor)) {
                return new $_concrete;
            }

            $params = $this->InjectFunctionParameters($constructor);

            //  If constructor has no parameter
            if (empty($params)) return $class->newInstance();
            
            return $class->newInstanceArgs($params);
        }

        /**
         *  Resolve(inject) a callable's arguments,
         *  
         *  Container only Inject the parameter that is type-hinted to a class.
         *  
         *  The conatainer firstly check the type of the parameter.
         *  If the type is bound as abstract before, the container just get the instance
         *  and inject it as argument.
         *  If the type is not bound, the container will build and inject the parameter.
         *  
         *  The third parameter of this method is the instruction to tell the container
         *  what parameter would be injected. When a parameter is needed to be injected, 
         *  just assign null value to the element of the $_instruction array related to 
         *  the specific parameter.
         * 
         *  This method just "read" instruction of numeric part of the $_instruction array.
         *  The order of the instructions is related to the order of the injected function's parameters
         *   
         *  @param ReflectionFunctionAbstract $_function
         *  @param mixed $_mode the mode that the arguments is resolved 
         * 
         *  @return array the injected list
         * 
         *  @throws Exception
         */
        protected function InjectFunctionParameters(ReflectionFunctionAbstract $_function, $_mode = self::MODE_NOT_ALLOW_NULL, array $_instruction = []): array {

            $parameters = $_function->getParameters();
            
            if (empty($parameters)) return [];
            
            if (count($parameters) > count($_instruction)) {
                $_instruction = array_pad($_instruction, count($parameters), null);
            }
            
            $error = false;

            //  To iterate $_reference array
            $i = 0;
            
            //  The closure that process the reflection for parameters
            //  $process is passed to array_map() to return the argument list for the declaring function
            $process = function (ReflectionParameter $param) use (&$error, $_instruction, &$i, $_mode) {
                
                $return_arg = null;

                if ($_instruction[$i] != null) {
                    
                    return $_instruction[$i++];
                }
                
                try {
                    //  $this->IsInjectable() will throw exception when the parameter
                    //  can't be injected
                    if ($this->IsInjectable($param)) {
                        //  if the parameter is default parameter
                        //  just return the function defined default value
                        if ($param->isDefaultValueAvailable()) {
                                
                            $return_arg = $param->getDefaultValue();
                        }
                        
                        $abstract = $param->getClass()->getName();

                        if (!$error) {
                            try {
                                //  If The class name(Interface/Class) is bound before
                                //  inject this abstract as bound to the list
                                $return_arg = $this->Get($abstract);

                                //++$i;

                                //return $ret;
                            }
                            catch(GlobalException $e) {
                                //++$i;
                                //  If there isn't alias for the $abstract in container
                                //  Make an instance of it from the beginning
                                //return $this->make($abstract);
                                $return_arg = $this->make($abstract);
                            }
                        }
                    }
                }
                catch (GlobalException $ex) {
                    //++$i;

                    if ($param->isDefaultValueAvailable()) {

                        $return_arg = $param->getDefaultValue();
                    }

                    if ($_mode == self::MODE_NOT_ALLOW_NULL) {

                        if (is_null($return_arg)) {

                            $error = true;

                            $return_arg = $ex;
                        }
                    }
                }
                
                ++$i;
                return $return_arg;
                //  End $process closure context
            };

            $resolve_args = array_map($process, $parameters);

            //  error reporting
            if ($error && $_mode == self::MODE_NOT_ALLOW_NULL) {
                $this->ReportInjectionError($resolve_args);
            }
            //  $this->InjectFunctionParameters context
            return $resolve_args;
        }

        private function ReportInjectionError(array $_error) {

            $process = function ($_element) {
                if ($_element instanceof GlobalException) {
                    return $_element->getMessage();
                } 
            };

            $error_params =  array_filter($_error, $process);

            $message = implode(', ', $error_params);

            throw new GlobalException($message);
        }

        /**
         *  Make a concrete class with totally injection
         * 
         *  @param string $_concrete
         *  
         *  @return mixed
         */
        public function Make(string $_concrete) {

            return $this->Build($_concrete);
        }

        /**
         *  Method to call a callable.
         * 
         *  Before invoking the callable, the container will inject parameters.
         *  When arguments is pass for the callable's parameters, the container 
         *  will validate arguments. An argument will be pass to a parameter 
         *  If the argument's type matches the parameter type.
         *  
         *  Arguments is and array can be numeric, associative or both. 
         *  Associative part use key to define the parameter's name
         *  and it's value is the argument to pass. Numeric part is pass to parameters 
         *  by it's order.
         * 
         *  This Method can work in 4 forms.
         *  
         *  #1 Call a global function
         *  @param string $_option1 The name of the function
         *  @param array  $_option2 The arguments list 
         * 
         *  #2 Call a closure
         *  @param closure $_option1 The closure 
         *  @param array $_option2  The arguments list
         * 
         *  #3 Call a method 
         *  @param string $_option1 The class name
         *  @param string $_option2 The method name
         *  @param array $_option3  The arguments list
         * 
         *  @throws Exception when the class injection can't match rules
         * 
         *  #4 call a method*
         *  @param array $_option1 The array with form [ 'class' => 'className' , 'method' => 'methodName']
         *  @param array $_option2 The argument list
         * 
         *  @return mixed the result of the called callable
         * 
         *  @throws exception when the class injection can match rules
         */
        public function Call($_option1, $_option2 = [] ,  array $_option3 = []) {
            if (is_string($_option1)) {

                if (is_string($_option2)) return $this->CallMethodOfClass($_option2, $_option1, $_option3);

                if (is_array($_option2)) return $this->CallFunction($_option1, $_option2);
                
                return null;
            }

            if (is_array($_option1)) {
                $method_name = is_string($_option1['method']) ? $_option1['method'] : null;
                $class_name = is_string($_option1['class']) ? $_option1['class'] : null;

                if (is_null($method_name) || is_null($class_name)) throw new GlobalException();

                $this->CallMethodOfClass($method_name, $class_name, $_option2);
            }

            if ($_option1 instanceof Closure) {

                if (is_array($_option2)) {
                    return $this->CallClosure($_option1, $_option2);
                }
                else return $this->CallClosure($_option1, []);
            }
        }

        /**
         *  Inject and Invoke a method of a class
         *  
         *  @param string $_method
         *  @param string $_class
         *  @param array $_args
         *  
         *  @return mixed
         * 
         *  @throws Exception when instantiate the class failed
         */
        protected function CallMethodOfClass(string $_method, string $_class, array $_args) {

            $class = new ReflectionClass($_class);
            $method = $class->getMethod($_method);

            $resolved_args = $this->ResolveCallableParameters($method, $_args);

            $object = $method->isStatic() ? null : $this->Make($_class);

            // Invoking a method need an instance of the class do the method
            // the $object is null when the method is static
            return $method->invokeArgs($object, $resolved_args);
        }  

        /**
         *  Inject and Invoke a Global function
         *  
         *  @param string $_function_name
         *  @param array $_args
         * 
         *  @return mixed
         * 
         *  @throws Exception when instantiate a parameter failed
         */
        protected function CallFunction(string $_function_name, array $_args) {
            
            $function = new ReflectionFunction($_function_name);

            $resolved_args = $this->ResolveCallableParameters($function, $_args);

            return $function->invokeArgs($resolved_args);
        }

        /**
         *  Inject and Invoke a closure
         * 
         *  @param Closure $_function
         *  @param array $_args
         * 
         *  @return mixed
         * 
         *  @throws Exception when instantiate a parameter failed
         */
        protected function CallClosure(Closure $_function, array $_args) {
            
            $function = new ReflectionFunction($_function);

            $resolved_args = $this->ResolveCallableParameters($function, $_args);

            return $function->invokeArgs($resolved_args);            
        }

        /**
         *  Resolve argument for a callable's parameter
         *  
         *  This method first pass the argument that is pass to the specific function
         *  After that, The container inject the rest.
         * 
         *  @param ReflectionFunctionAbstract
         *  @param array
         * 
         *  @return array
         */
        protected function ResolveCallableParameters(ReflectionFunctionAbstract $_callable, array $_args = []): array {

            $reference = $this->PassArguments($_callable, $_args);
            
            $resolved_args = $this->InjectFunctionParameters($_callable, self::MODE_ALLOW_NULL, $reference);
            
            return $resolved_args;
        }


        /**
         *  Pass arguments to untype-hinted Parameter that is resolved.
         *  
         *  This method only use the associative part of the second parameter as the
         *  arguments list to pass the the specific parameter
         * 
         * 
         *  @param array $_resolved_args 
         *  @param array $_parameters of type ReflectionParameter 
         *  @param array $_args the set of arguments to inject to the null arguments
         *  
         *  @return array
         */
        protected function PassArguments(ReflectionFunctionAbstract $_callable, array $_args): array {

            // index that used to iterate through $_parameters array
            //$i = 0;
            
            $inject = function(ReflectionParameter $_parameter) use (&$_args) {
                
                $param_name = $_parameter->getName();

                //  resolve when the paramether's name is specified in $_args
                if (array_key_exists($param_name, $_args)) {
                    
                    if ($_args[$param_name] === null && $_parameter->allowsNull()) {
                        $ret = $_args[$param_name];

                        unset($_args[$param_name]);

                        return $ret;
                    }

                    $param_type = $_parameter->getType();

                    //var_dump($_args[$key]);
                    if (is_null($param_type)) {

                        $ret = $_args[$param_name];

                        unset($_args[$param_name]);

                        return $ret;
                    }

                    //  because ReflectionType::getType() return 'int'
                    //  and gettype() return 'integer' of type int
                    //  so we have to convert the result to 'integer' when the parameter's type is int
                    $param_type_name = $param_type->getName() === 'int' ? 'integer' : $param_type->getName();
                    $argument_type_name = gettype($_args[$param_name]);

                    if ($param_type_name == $argument_type_name) {

                        $ret = $_args[$param_name];
                        
                        unset($_args[$param_name]);

                        return $ret;
                    }

                    //  when parameter's type and argument's type is not the same
                    //  and argument's type is not object
                    //  pass null for this parameter
                    if ($argument_type_name !== 'object') {

                        return null;
                    }
                    
                    //  when the parameter is not buitin type,
                    //  Then check if the parameter's class
                    //  and the argument's class is the same
                    $param_class = $_parameter->getClass()->getName();
                    $reflection = new ReflectionObject($_args[$param_name]);
                    $argument_class = $reflection->getName();

                    if ($param_class === $argument_class) {
                        
                        $ret = $_args[$param_name];
                        
                        unset($_args[$param_name]);

                        return $ret;
                    }
                }
            };

            $parameters = $_callable->getParameters();

            return array_map($inject , $parameters);
        }

        protected function IsInjectable(ReflectionParameter $_parameter) {
            // $param_name = $_parameter->getName();

            // $function = $_parameter->getDeclaringFunction();

            // $function_name = $function->getName();

            // $function_class = ($function instanceof ReflectionMethod) ? 
            //                     $function->getDeclaringClass()->getName().'::' : '';

            // $type = $_parameter->getType();
                
            //     //  Check if the parameter is not type-hinted
            // if (is_null($type)) {

            //     //  The second parameter of InjectFunctionParameters
            //     //  to set the mode to allow non-type hinted parameter
            //     //  the container will pass null value for this parameter 
            //     //if ($_mode === self::MODE_ALLOW_NULL) return null;

            //     throw new GlobalException("Parameter \"$param_name\" of function \"$function_class $function_name()\" is not type hinted");
            // } 

            //     //  Check the parameter is type-hinted to a built-in type
            // if ($type->isBuiltin()) {

            //         //if ($_mode === self::MODE_ALLOW_NULL) return null;

            //     throw new GlobalException("Could not inject parameter $param_name with built-in($type)");
            // }

            //  When the parameter is not built-in type
            //  Check the parameter is type-hinted to a class
            //  Get the type-hinted parameter's class
            $class = $_parameter->getClass();

            //  If the parameter is not type hinted throw exception
            if (is_null($class)) {
                //throw new GlobalException("Could not inject parameter $param_name of mix type!");
                throw new GlobalException($_parameter->getName());
            }

            //  get the name of the parameter's type hinted class
            //$abstract = $class->getName();

            return true;
        }

        /**
         *  Get object in object pool by address
         * 
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
         *  Resolve object of an Application\Container\Dependency
         *   
         *  @param Application\Container\Dependency
         *  @return mixed
         * 
         *  @throws Exception
         */
        private function ResolveDependency(Dependency &$_dependency) {
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

                $generator = $_dependency->GetDefaultGenerator();

                //  Object generator is a closure
                //  we have to resolve this closure
                //  to inject it's dependency arguments
                return $this->ResolveDefaultGenerator($generator);
            }

            //  When the dependency doesn't has default object generator
            //  Build an instance of it from the beginning
            return $this->build($_dependency->GetConcrete());
        }

        /**
         *  Instantiate an Application\Container\Dependency 's concrete
         *  for singleton
         * 
         *  @param Application\Container\Dependency
         * 
         *  @throws Exception
         */
        private function SetupSingleton(Dependency &$_dependency) {

            // $object = $_dependency->HasDefault() ? $_dependency->GetDefaultGenerator() 
            //             : $this->Build($_dependency->GetConcrete());

            if (!$_dependency->IsSingleton()) return;

            $address = $_dependency->GetSingletonAddress();

            if ($address !== null) return;

            if ($_dependency->HasDefault()) {
                $generator = $_dependency->GetDefaultGenerator();

                $object = $this->ResolveDefaultGenerator($generator);
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
         *  Inject and Invoke Application\Container\Dependency 's
         *  default generator
         * 
         *  @param Closure $_generator
         *  
         *  @return mixed
         */
        private function ResolveDefaultGenerator(Closure $_generator) {

            $reflect_generator = new ReflectionFunction($_generator);

            $args = $this->InjectFunctionParameters($reflect_generator);

            return $reflect_generator->invokeArgs($args);
        }

        /** 
         *  Get a Bound instance 
         * 
         *  @param string $_name
         *  
         *  @return mix
         * 
         *  @throws Exception
         */
        public function Get(string $_name) {

            $dependency = $this->AliasExists($_name) ? $this->alias[$_name]
                        : $this->IsBound($_name) ? $this->bindMap[$_name] : null;

            if (!$dependency) throw new GlobalException("Trying to get $_name from the container that is unbound before");

            return $this->ResolveDependency($dependency);
        }

        /**
         *  This method is call when a Dependency is bound
         */
        public function BindEvent() {
            $this->SetAlias();
        }

        /**
         *  binds an alias with the last bound dependency
         * 
         *  @throws Exception
         */
        protected function SetAlias() {
            $last_bound_dependency = end($this->bindStack);

            $name = $last_bound_dependency->GetName() ?? null;

            if (is_null($name)) return;

            if (!$this->AliasExists($name)) {
                
                $this->alias[$name] = $last_bound_dependency;

                return;
            }

            throw new Exception\AliasNameExistsException($name);
        }

        protected function AliasExists($_name) {

            return array_key_exists($_name, $this->alias);
        }

    }