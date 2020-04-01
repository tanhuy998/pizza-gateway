<?php 
    namespace Dependencies\Notification;

    use Dependencies\Event\EventArgs as EventArgs;

    interface ISubscribable {

        public function Subscribe(INotifiable $_notifier, string $_event);
        public function RecieveNotification(EventArgs $_notification);
    }