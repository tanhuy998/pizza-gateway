<?php
    namespace Dependencies\Router;

    use Dependencies\Notification\Notification as Notification;

    class RouteCreateNotification extends Notification {

        private $createdRoute;

        public function __construct(\Dependencies\Event\EventEmitter $_sender, string $_event, Route $_route = null) {
            parent::__construct($_sender, $_event);

            $this->createdRoute = $_route;
        }

        public function SetRoute(Route $_route) {
            $this->createdRoute = $_route;
        }

        public function GetCreatedRoute(): Route {

            return $this->createdRoute;
        }
    }