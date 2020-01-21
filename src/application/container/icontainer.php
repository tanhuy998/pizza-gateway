<?php
    namespace Application\Container;

    interface IContainer {
        public function BindClass($_class);
        public function BindInterface($_interface, $_class);

        public function GetClassInstance(string $_class);
        public function GetInterfaceInstance(string $_interface);
    }