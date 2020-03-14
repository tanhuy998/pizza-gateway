<?php
    namespace Application\Container;

use Closure;

    interface IContainer {
        public function Bind(string $_abstract, string $_concrete, Closure $_default): Dependency;
        public function BindSingleton(string $_abstract, string $_concrete, $_default): Dependency;
        public function Get(string $_name);
        public function Make(string $_concrete);
        public function Call($_option1, $_option2, array $_option3);
    }