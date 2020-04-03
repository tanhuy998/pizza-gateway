<?php
    namespace Dependencies\Event;

    class EventArgs {

        private $sender;
        private $eventName;

        public function __construct(EventEmitter $_sender, string $_event) {
            $this->sender = $_sender;
            $this->eventName = $_event;
        }

        public function GetEventName() {
            return $this->eventName;
        }

        public final function Sender() {

            return $this->sender;
        }
    }