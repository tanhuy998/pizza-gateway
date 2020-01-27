<?php 
    namespace Application\Container\Exception;

use Exception;

class AliasNameNotRegisteredException extends Exception {
        public function __construct($_name) {
            $message = "Trying to get dependency with alias name \" $_name \" that hasn't been registered yet";

            parent::__construct($message);
        }
    }