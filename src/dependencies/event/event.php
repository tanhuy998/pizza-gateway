<?php 
    namespace Dependencies\Event;

use Closure;

    class Event {        
        
        private $listener;
        protected $emitter;

        public function __construct(EventEmitter $_emitter) {
            
            $this->emitter = $_emitter;
        }

        public function Do(Closure $_func) {
            $this->listener = $_func;
        }

        public function GetListener() {
            return $this->listener;
        }
    }