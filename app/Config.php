<?php

namespace App;

class Config
{
//    private const array CONFIGS = [
//        'DB_DRIVER' => 'mysql',
//        'DB_HOST' => 'mysql',
//        'DB_PORT' => '3306',
//        'DB_NAME' => 'socnet',
//        'DB_PASSWORD' => 'root',
//        'DB_USER' => 'root',
//        'DB_CHARSET' => 'utf8mb4',
//        'DB_COLLATION' => 'utf8mb4_unicode_ci',
//    ];

    private const array CONFIGS = [
        'DB_DRIVER' => 'pgsql',
        'DB_HOST' => 'dbmain',
        'DB_PORT' => '5432',
        'DB_NAME' => 'socnet',
        'DB_PASSWORD' => 'root',
        'DB_USER' => 'master_user',
        'DB_CHARSET' => 'utf8mb4',
        'DB_COLLATION' => 'utf8mb4_unicode_ci',
    ];

    public static function get(string $key): mixed
    {
        return self::CONFIGS[$key];
    }
}