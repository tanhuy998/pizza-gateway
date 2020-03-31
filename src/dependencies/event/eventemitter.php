<?php
    namespace Dependencies\event;

    use Closure;

    abstract class EventEmitter {

        private $events;

        public function EventExist(string $_name): bool {
            return array_key_exists($_name, $this->events);
        }

        protected function SetEvent(string $_event_name) {

        }

        protected function AddEventlistener(string $_event_name, Closure $_callback) {

        }

        public function On(string $_event_name) {
            if (array_key_exists($_event_name, $this->events));
        }
    }