<?php 
    namespace Autoload;

    use Exception;

    class ClassNotDefinedException extends Exception {

        public function __construct(string $_class) {
            parent::__construct("Can not find the file contain definition of $_class");
        }
    }