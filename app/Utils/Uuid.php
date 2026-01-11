<?php

namespace App\Utils;

class Uuid
{
    public static function createUuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}