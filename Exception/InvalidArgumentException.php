<?php

namespace MxcDropshipInnocigs\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public static function fromInvalidObject(string $expected, $object) {

        $msg = sprintf(
            'An invalid object was provided. Expected a valid %s object, '
            . 'but %s was provided',
            $expected,
            is_object($object) ? get_class($object) : gettype($object)
        );
        return new self($msg);
    }


}