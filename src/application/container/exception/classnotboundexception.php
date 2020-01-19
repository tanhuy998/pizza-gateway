<?php
    namespace Application\Container\Exception;

    use Exception;

    class ClassNotBoundException extends Exception{
        public function __construct($_class) {
            $message = 'Try to get instance of '.$_class.' that is not bound in the DI container before';
            
            parent::__construct($message);
        }
    }