<?php
    namespace Application\Container\Exception;

    use Exception;

    class AliasNameExistsException extends Exception{
        public function __construct($_name) {
            $message = 'Name '.$_name.' is registered before';
            
            parent::__construct($message);
        }
    }