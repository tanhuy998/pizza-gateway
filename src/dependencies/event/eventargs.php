<?php
    namespace Dependencies\Event;

    class EventArgs {

        private $sender;

        public function __construct(EventEmitter $_sender) {
            $this->sender = $_sender;
        }

        public final function Sender() {

            return $this->sender;
        }
    }