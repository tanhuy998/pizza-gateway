<?php
    namespace Application\Container\Exception;

use Exception;

class ObjectTypeNotMatchException extends Exception {
    public function __construct($_dependency_class, $_object_class) {
        $message = "Trying to bind singleton dependency of type \" $_dependency_class \" with object of type \" $_object_class \" that not match convention";

        parent::__construct($message);
    }
}