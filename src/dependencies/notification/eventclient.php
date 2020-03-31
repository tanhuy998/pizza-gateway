<?php
    namespace Dependencies\Notification;

use Closure;
use Dependencies\Event\EventEmitter as EventEmitter;
    use Dependencies\Event\EventArgs as EventArgs;
    use Dependencies\Event\Event as Event;

    class EventClient extends EventEmitter implements INotifiable, ISubscribable {

        private $subscribers;

        public function __construct() {
            $this->subscribers = [];
            
            $this->AddEvent('eventclient-notified');

            $this->AddEvent('eventclient-notify');
        }

        public final function Subscribe(INotifiable $_notifier) {
            $_notifier->AddSubscriber($this);
        }

        public final function AddSubscriber(ISubscribable $_subscriber) {

            if ($this->subscribers === null) $this->subscribers = [];

            $this->subscribers[] = $_subscriber;
        }

        public final function Notify(string $_event_name) {
            $this->Emit($_event_name);

            $notification = $this->On($_event_name)->GetEventArgs();

            foreach ($this->subscribers as $subscriber) {
                $subscriber->RecieveNotification($notification);
            }
        }

        public function RecieveNotification(EventArgs $_notification) {
            $this->Emit('eventclient-notified');
        }

        public final function OnNotified(Closure $_listener) {

            if (!array_key_exists('eventclient-notified', $this->events)) {
                $this->AddEvent('eventclient-notified');
            }

            $this->AddEventlistener('eventclient-notified', $_listener);
        }
    }