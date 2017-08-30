<?php

namespace AppBundle\Exceptions;

/**
* used to represent a Token that was generated too long ago and, as such, is
* considered expired and invalid
*/
class TokenExpiredException extends \Exception {
    public function __toString() {
        return(__CLASS__.": [{$this->code}]: {$this->message}");
    }
}