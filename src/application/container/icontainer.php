<?php
    namespace Application\Container;

    interface IContainer {
        public function BindClass($_class);
        public function BindInterface($_interface, $_class);

        public function GetClass(string $_class, $_instanciate);
        public function GetInterface(string $_interface, $_instanciate);
    }