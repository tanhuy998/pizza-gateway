<?php
    namespace Dependencies\Notification;

    interface INotifiable {

        public function AddEventSubscriber(ISubscribable $_subscriber, string $_event);
    }