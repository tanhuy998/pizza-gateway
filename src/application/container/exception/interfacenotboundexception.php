<?php 
    namespace Application\Container\Exception;

    use Exception;

    class InterfaceNotBoundException extends Exception {
        public function __construct($_interface) {
            $message = 'Try to get instance of '.$_interface.' that is not bound to a class in the DI container before';

            parent::__construct($message);
        }
    }