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
        'database' => [
            'write' => [
                'DB_DRIVER' => 'pgsql',
                'DB_HOST' => 'pgmaster',
                'DB_PORT' => '5432',
                'DB_NAME' => 'postgres',
                'DB_PASSWORD' => 'admin',
                'DB_USER' => 'postgres',
                'DB_CHARSET' => 'utf8mb4',
                'DB_COLLATION' => 'utf8mb4_unicode_ci',
            ],
            'read' => [
                [
                    'DB_DRIVER' => 'pgsql',
                    'DB_HOST' => 'haproxy',
                    'DB_PORT' => '5433',
                    'DB_NAME' => 'postgres',
                    'DB_PASSWORD' => 'admin',
                    'DB_USER' => 'postgres',
                    'DB_CHARSET' => 'utf8mb4',
                    'DB_COLLATION' => 'utf8mb4_unicode_ci',
                ],
                [
                    'DB_DRIVER' => 'pgsql',
                    'DB_HOST' => 'haproxy',
                    'DB_PORT' => '5433',
                    'DB_NAME' => 'postgres',
                    'DB_PASSWORD' => 'admin',
                    'DB_USER' => 'postgres',
                    'DB_CHARSET' => 'utf8mb4',
                    'DB_COLLATION' => 'utf8mb4_unicode_ci',
                ],
            ],
            'common' => [
                'DB_DRIVER' => 'pgsql',
                'DB_HOST' => 'pgmain',
                'DB_PORT' => '5432',
                'DB_NAME' => 'postgres',
                'DB_PASSWORD' => 'admin',
                'DB_USER' => 'postgres',
                'DB_CHARSET' => 'utf8mb4',
                'DB_COLLATION' => 'utf8mb4_unicode_ci',
            ],
        ],
        'rabbitmq' => [
            'host' => 'rabbitmq',
            'port' => 5672,
            'user' => 'admin',
            'password' => 'admin',
            'vhost' => '/',
            'queue' => 'post_created',
        ],
    ];

    public static function get(string $key): mixed
    {
        return self::CONFIGS[$key];
    }
}