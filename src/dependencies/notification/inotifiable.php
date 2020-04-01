<?php
    namespace Dependencies\Notification;

    interface INotifiable {

        public function Notify(string $_event_name);
        public function AddSubscriber(ISubscribable $_subscriber, string $_event);
    }