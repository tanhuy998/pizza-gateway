<?php
    namespace Dependencies\Router;

    interface IRedirectable {

        public function Location(string $_location, int $_status = 302);
        public function Route(string $_name, array $_params = [], int $_status = 302);
        public function Back();
    }