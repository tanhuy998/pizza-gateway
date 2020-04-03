<?php
    namespace Dependencies\Notification;

    use Dependencies\Event\EventArgs;
use Dependencies\Event\EventEmitter;

class Notification extends EventArgs {

        protected $state;

        public function __construct(EventEmitter $_sender, string $_event) {
            parent::__construct($_sender, $_event);
        }

        public function GetState() {
            
            return $this->state;
        }

        public function SetState($_state) {

            $this->state = $_state;
        }
    }