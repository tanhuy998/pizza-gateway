<?php 
    namespace Dependencies\Event;

    use Closure;
    use Exception;

    class Event {        
        
        private $listener;
        private $eventArgs;
        protected $emitter;

        public final function __construct(EventEmitter $_emitter) {
            $this->listener = [];
            $this->emitter = $_emitter;
        }

        public function Do(Closure $_callback) {
            $this->listener[] = $_callback;
        }

        public function GetListener() {
            return $this->listener;
        }

        public function GetEventArgs() {
            
            return $this->eventArgs ?? new EventArgs($this->emitter);
        }

        public function SetEventArgs(EventArgs $_arg) {

            if ($this->emitter === $_arg->Sender()) {
                $this->eventArgs = $_arg;

                return;
            }
            throw new Exception('The passed EventArgs\'s sender must be the same instance of the event\'s emitter ');
        }
    }