<?php 
    namespace Dependencies\Notification;

    use Dependencies\Event\EventArgs as EventArgs;

    interface ISubscribable {

        public function SubscribeEvent(INotifiable $_notifier, string $_event);
        public function RecieveEventNotification(EventArgs $_notification);
    }