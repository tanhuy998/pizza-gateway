<?php
    namespace Application\Container;

    interface IContainer {
        public function BindClass($_class);
        public function BindInterface($_interface, $_class);

        public function GetClassInstance($_class);
        public function GetInterfaceInstance($_interface);
    }