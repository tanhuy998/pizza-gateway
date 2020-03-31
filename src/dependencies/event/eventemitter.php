<?php
    namespace Dependencies\Event;

    use Dependencies\Event\Event as Event;
    use Closure;
    use Exception;

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
                $listener();
            }
        }

        public final function On(string $_event_name): Event {
            if (array_key_exists($_event_name, $this->events)) return $this->events[$_event_name];

            $class = get_class($this);
            throw new Exception("Call on undefined Event \'$_event_name\' of class \'$class\'");
        }
    }