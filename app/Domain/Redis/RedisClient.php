<?php

namespace App\Domain\Redis;

class RedisClient
{
    public \Redis $client;
    public function __construct()
    {
        $this->client = new \Redis();
        $this->client->connect('redis', 6379);
    }
}