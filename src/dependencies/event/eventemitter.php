<?php
    namespace Dependencies\Event;

    use Dependencies\Event\Event as Event;
    use Application\Container\DIContainer as DIContainer;
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

        protected final function Emit($_event_name) {
            $_event_name = strtolower($_event_name);

            $listeners = $this->OnEvent($_event_name)->Getlistener();
            
            foreach ($listeners as $listener) {
                
                $eventArgs = $this->OnEvent($_event_name)->GetEventArgs();

                $this->InvokeListener($listener, $eventArgs);
            }
        }

        /**
         *  Invoke a listener
         * 
         */
        private final function InvokeListener(Closure $_listener, EventArgs $_eventArgs) {
            $function = new ReflectionFunction($_listener);

            $_args = $this->ResolveListenerParameters($function, $_eventArgs);

            if (class_exists('Autoloader')) {
                $container = DIContainer::GetInstance();
                
                return $container->Call($_listener, $_args);
            }
            
            return $function->invokeArgs($_args);
        }


        private final function ResolveListenerParameters(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {
            if (class_exists('Autoloader')) {

                $container = DIContainer::GetInstance();

                return $this->ResolveParameterByContainer($_listener, $_eventArgs);
            }

            return $this->ResolveParameterManually($_listener, $_eventArgs);
        }

        private final function ResolveParameterByContainer(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {

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

                $param_class = $class->name;

                if ($param_class === 'EventArgs') {

                    $ret[$param_name] = clone $_eventArgs;

                    continue;
                }
            }

            return $ret;
        }

        private final function ResolveParameterManually(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {
            $detect_eventArgs = function (ReflectionParameter $param) use ($_eventArgs)  {
                $class = $param->getClass();

                $param_type = (!is_null($class)) ? $class->name : $param->getType()->getName();

                return ($param_type === 'EvenArgs' || $param_type === 'NULL') ? $_eventArgs : null;
            };

            $params = $_listener->getParameters();

            if (empty($params)) return [];

            if (count($params) === 1) {
                $ret = $detect_eventArgs($params[0]);
                
                return [$ret];
            }

            return array_map($detect_eventArgs, $params);
        }

        public final function OnEvent(string $_event): Event {
            $_event = strtolower($_event);

            if ($this->EventExist($_event)) return $this->events[$_event];

            $class = get_class($this);
            throw new Exception("Call on undefined Event \'$_event\' of class \'$class\'");
        }
    }