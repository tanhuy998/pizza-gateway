<?php 
    namespace Dependencies\Notification;

    use Dependencies\Event\EventArgs as EventArgs;

    interface ISubscribable {

        public function Subscribe(INotifiable $_notifier);
        public function RecieveNotification(EventArgs $_notification);
    }