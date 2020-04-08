<?php
    namespace Dependencies\Notification;

use Closure;
use Dependencies\Event\EventEmitter as EventEmitter;
    use Dependencies\Event\EventArgs as EventArgs;
    use Dependencies\Event\Event as Event;

    abstract class EventClient extends EventEmitter implements INotifiable, ISubscribable {

        private $subscribers;

        public function __construct() {
            parent::__construct();
            $this->Init();
        }

        public final function SubscribeEvent(INotifiable $_notifier, string $_event_name) {

            $_notifier->AddEventSubscriber($this, $_event_name);
        }

        private function Init() {
            if ($this->subscribers === null) $this->subscribers = [];
            
            $this->AddEvent('eventclient-notified');

            $this->AddEvent('eventclient-notify');
        }

        public final function AddEventSubscriber(ISubscribable $_subscriber, string $_event) {
            $this->Init();

            $_event = strtolower($_event);

            if (isset($this->subscribers[$_event])) {
                $this->subscribers[$_event] = [];
            }

            $this->subscribers[$_event][] = $_subscriber;
        }

        protected final function NotifyEvent(string $_event, EventArgs $_notification = null) {
            $_event = strtolower($_event);

            $this->Emit($_event, $_notification);

            $notification = is_null($_notification) ? $this->OnEvent($_event)->GetEventArgs() : $_notification;
            
            foreach ($this->subscribers[$_event] as $subscriber) {
                
                $subscriber->RecieveEventNotification($notification);
            }
        }

        public final function RecieveEventNotification(EventArgs $_notification) {
            $this->Emit('eventclient-notified');

            $this->HandleEventNotification($_notification);
        }

        /**
         *  Method to handle a notification
         *  
         *  Every derived class has to redefine this method 
         *  for handling specific business context
         */
        protected abstract function HandleEventNotification(EventArgs $_notification);

        public final function OnNotified(Closure $_listener) {

            if (!$this->EventExist('eventclient-notified')) {
                $this->AddEvent('eventclient-notified');
            }

            $this->AddEventlistener('eventclient-notified', $_listener);
        }
    }