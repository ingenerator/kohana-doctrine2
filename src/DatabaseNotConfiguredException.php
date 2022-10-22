<?php

namespace Ingenerator\KohanaDoctrine;


class DatabaseNotConfiguredException extends \RuntimeException
{

    public static function forMethod($method)
    {
        return new static('The database connection is not configured - method `'.$method.'` failed');
    }

    public static function forGetAttribute(int $attribute)
    {
        return new static('The database connection is not configured - cannot getAttribute('.$attribute.')');
    }

}
