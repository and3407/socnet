<?php

namespace App\Domain\RabbitMQ;

use App\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQClient
{
    private static ?AMQPStreamConnection $connection = null;
    private static array $config;

    private static function getConfig(): array
    {
        if (!isset(self::$config)) {
            self::$config = Config::get('rabbitmq');
        }
        return self::$config;
    }

    private static function getConnection(): AMQPStreamConnection
    {
        if (self::$connection === null || !self::$connection->isConnected()) {
            $config = self::getConfig();
            self::$connection = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost']
            );
        }
        return self::$connection;
    }

    public static function publish(string $queue, string $message): void
    {
        $connection = self::getConnection();
        $channel = $connection->channel();

        // Declare queue (durable = false, auto_delete = false)
        $channel->queue_declare($queue, false, false, false, false);

        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', $queue);

        $channel->close();
        // Keep connection open for future publishes
    }

    public static function closeConnection(): void
    {
        if (self::$connection !== null && self::$connection->isConnected()) {
            self::$connection->close();
        }
        self::$connection = null;
    }
}