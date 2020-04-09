<?php
    namespace Dependencies\Event;

    use Dependencies\Event\Event as Event;
    use Application\Container\DIContainer as DIContainer;
    use Autoload\ClassNotDefinedException as ClassNotDefinedException;
    use Closure;
    use Exception;
    use ReflectionFunction;
    use ReflectionFunctionAbstract;
    use ReflectionParameter;

    /**
     *  Class EvenEmitter 
     * 
     *  This class will be extended by another class 
     *  to implement event-driven programming that is
     *  a derived classes can set and emit event.
     * 
     *  This class is designed to work independently from Autoloader
     */
    abstract class EventEmitter {

        private $events;

        public function __construct() {
            $this->Init();
        }

        public final function EventExist(string $_name): bool {
            $this->Init();

            return array_key_exists($_name, $this->events);
        }

        /**
         *  Init properties
         * 
         *  Because of derived class's constructor can not call base 
         *  class automatically so this method is used for initiate base
         *  class properties when calling to setter method
         */ 
        private function Init() {
            if (!isset($this->events)) {
                $this->events = [];
            }
        }

        protected final function AddEvent(string $_event_name) {
            $this->Init();

            $lowed_name = strtolower($_event_name);

            if (!$this->EventExist($_event_name)) {

                $event = new Event($this, $_event_name);

                $this->events[$lowed_name] = $event;

                return $event;
            }
        }

        protected final function AddEventlistener(string $_event, Closure $_callback) {

            if (!$this->EventExist($_event)) {

                $this->AddEvent($_event);
            }
            $this->OnEvent($_event)->Do($_callback);
        }

        protected final function Emit(string $_event_name, EventArgs $_eventArgs = null) {
            $_event_name = strtolower($_event_name);

            $listeners = $this->OnEvent($_event_name)->Getlistener();
            
            $eventArgs = null;

            if (is_null($_eventArgs)) {
                $eventArgs = $this->OnEvent($_event_name)->GetEventArgs();
            }
            else {
                if ($_eventArgs->Sender() !== $this) throw new Exception('The sender of $_eventArgs parameter not match the emitter');

                if ($_eventArgs->GetEventName() == $_event_name) throw new Exception('The eventName of $_eventArgs parameter not match the eventName of the Emitter');

                $eventArgs = $_eventArgs;
            }

            foreach ($listeners as $listener) {

                $this->InvokeListener($listener, $eventArgs);
            }
        }

        /**
         *  Invoke a listener
         * 
         */
        private final function InvokeListener(Closure $_listener, EventArgs $_eventArgs) {
            $function = new ReflectionFunction($_listener);

            try {
                $container = DIContainer::GetInstance();
                
                $_args = $this->ResolveParametersForContainer($function, $_eventArgs);

                return $container->Call($_listener, $_args);
            }
            catch (ClassNotDefinedException $ex) {

                $_args = $this->ResolveParametersManually($function, $_eventArgs);

                return $function->invokeArgs($_args);
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }


        // private final function ResolveListenerParameters(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {
        //     if (class_exists('Autoloader')) {

        //         return $this->ResolveParameterByContainer($_listener, $_eventArgs);
        //     }

        //     return $this->ResolveParametersManually($_listener, $_eventArgs);
        // }

        private final function ResolveParametersForContainer(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {

            $params = $_listener->getParameters();

            $ret = [];

            foreach ($params as $parameter) {
                
                $class = $parameter->getClass();
                $param_name = $parameter->getName();

                if (is_null($class)) {

                    $type = $parameter->getType();

                    if (is_null($type)) {
                        $ret[$param_name] = clone $_eventArgs;
                    }

                    continue;
                }

                if ($class->isSubclassOf(EventArgs::class) || $class->name === EventArgs::class) {

                    $ret[$param_name] = clone $_eventArgs;

                    continue;
                }
            }

            return $ret;
        }

        private final function ResolveParametersManually(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {
            $detect_eventArgs = function (ReflectionParameter $param) use ($_eventArgs)  {
                $class = $param->getClass();

                if (is_null($class)) {

                    $type = $param->getType();

                    return is_null($type) ? clone $_eventArgs : null;
                }

                if ($class->isSubclassOf(EventArgs::class) || $class->name === EventArgs::class) {

                    return clone $_eventArgs;
                }

                return null;
            };

            $params = $_listener->getParameters();

            return array_map($detect_eventArgs, $params);
        }

        public final function OnEvent(string $_event): Event {
            $_event = strtolower($_event);

            if ($this->EventExist($_event)) return $this->events[$_event];

            $class = get_class($this);
            throw new Exception("Call on undefined Event \'$_event\' of class \'$class\'");
        }
    }