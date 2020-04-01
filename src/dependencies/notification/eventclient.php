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

        public final function Subscribe(INotifiable $_notifier, string $_event_name) {
            $_notifier->AddSubscriber($this, $_event_name);
        }

        public final function AddSubscriber(ISubscribable $_subscriber, string $_event) {

            if ($this->subscribers === null) $this->subscribers = [];

            if (isset($this->subscribers[$_event])) {
                $this->subscribers[$_event] = [];
            }

            $this->subscribers[$_event][] = $_subscriber;
        }

        public final function Notify(string $_event) {
            
            $this->Emit($_event);

            $notification = $this->On($_event)->GetEventArgs();

            foreach ($this->subscribers[$_event] as $subscriber) {

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