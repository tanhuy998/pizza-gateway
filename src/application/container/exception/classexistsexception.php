<?php
    namespace Application\Container\Exception;

    use Exception;

    class ClassExistsException extends Exception{
        public function __construct($_class) {
            $message = 'Class '.$_class.' is bound before';
            
            parent::__construct($message);
        }
    }