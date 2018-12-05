<?php

namespace Ingenerator\KohanaDoctrine;


class DatabaseNotConfiguredException extends \RuntimeException
{

    public static function forMethod($method)
    {
        return new static('The database connection is not configured - method `'.$method.'` failed');
    }

}