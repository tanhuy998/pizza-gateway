<?php
    namespace Dependencies\Event;

    class EventArgs {

        private $state;
        private $sender;

        public function __construct(EventEmitter $_sender, $_state = null) {
            $this->state = $_state;
            $this->sender = $_sender;
        }

        public final function GetState() {

            if (gettype($this->state) === 'object') {
                return clone $this->state;
            }
            
            return $this->state;
        }

        public function SetState($_state) {
            $this->state = $_state;
        }

        public final function Sender() {

            return $this->sender;
        }
    }