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

        public final function EventExist(string $_name): bool {
            return array_key_exists($_name, $this->events);
        }

        protected final function AddEvent(string $_event_name) {

            if (!$this->EventExist($_event_name)) {
                $this->events[$_event_name] = new Event($this);
            }
        }

        protected final function AddEventlistener(string $_event_name, Closure $_callback) {

            if (!array_key_exists($_event_name, $this->events)) {

                $this->AddEvent($_event_name);
            }
            $this->On($_event_name)->Do($_callback);
        }

        protected final function Emit($_event_name) {
            $listeners = $this->On($_event_name)->Getlistener();

            foreach ($listeners as $listener) {

                $eventArgs = $this->On($_event_name)->GetEventArgs();

                $this->InvokeListener($listener, $eventArgs);
            }
        }

        /**
         *  Invoke a listener
         * 
         */
        private function InvokeListener(Closure $_listener, EventArgs $_eventArgs) {
            $function = new ReflectionFunction($_listener);

            $_args = $this->ResolveListenerParameters($function, $_eventArgs);

            if (class_exists('Autoloader')) {
                $container = DIContainer::GetInstance();

                return $container->Call($_listener, $_args);
            }
            
            return $function->invokeArgs($_args);
        }


        private function ResolveListenerParameters(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {
            if (class_exists('Autoloader')) {

                $container = DIContainer::GetInstance();

                return $this->ResolveParameterForContainer($_listener, $_eventArgs);
            }

            return $this->ResolveParameterManually($_listener, $_eventArgs);
        }

        private function ResolveParameterForContainer(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {

            $params = $_listener->getParameters();

            $ret = [];

            foreach ($params as $parameter) {
                
                $class = $parameter->getClass();

                if (is_null($class)) continue;

                $param_class = $class->name;

                if ($param_class === 'EventArgs') {

                    $param_name = $parameter->getName();

                    $ret[$param_name] = clone $_eventArgs;

                    continue;
                }
            }

            return $ret;
        }

        private function ResolveParameterManually(ReflectionFunctionAbstract $_listener, EventArgs $_eventArgs): array {

            $params = $_listener->getParameters();

            if (empty($params)) return [];

            if (count($params) === 1) {

                $class = $params[0]->getClass();

                $param_type = (!is_null($class)) ? $class->name : gettype($params[0]);

                return ($param_type === 'EvenArgs' || $param_type === 'NULL') ? [$_eventArgs] : [null];
            }

            $detect_eventArgs = function (ReflectionParameter $parameter) use ($_eventArgs)  {
                $param_class = $parameter->getClass()->name;

                if ($param_class === 'EventArgs') return clone $_eventArgs;
                else return null;
            };

            return array_map($detect_eventArgs, $params);
        }

        public final function On(string $_event_name): Event {
            if (array_key_exists($_event_name, $this->events)) return $this->events[$_event_name];

            $class = get_class($this);
            throw new Exception("Call on undefined Event \'$_event_name\' of class \'$class\'");
        }
    }