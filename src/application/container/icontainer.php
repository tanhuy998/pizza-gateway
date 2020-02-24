<?php
    namespace Application\Container;

    interface IContainer {
        public function Bind(string $_abstract, string $_class);
        public function Get(string $_name);
    }