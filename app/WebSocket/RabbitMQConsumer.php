<?php

namespace App\WebSocket;

use App\Domain\RabbitMQ\RabbitMQClient;
use App\Domain\Redis\RedisClient;
use App\Domain\User\Repositories\UserRepository;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{
    private AMQPStreamConnection $connection;
    private RedisClient $redisClient;
    private UserRepository $userRepository;
    private string $queueName = 'post_created';

    public function __construct()
    {
        $config = \App\Config::get('rabbitmq');
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );
        $this->redisClient = new RedisClient();
        $this->userRepository = new UserRepository();
    }

    public function consume(): void
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($this->queueName, false, false, false, false);

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function (AMQPMessage $msg) {
            echo " [x] Received ", $msg->body, "\n";
            $this->processMessage($msg->body);
            $msg->ack();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }

    private function processMessage(string $body): void
    {
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            echo " [!] Invalid JSON: " . $e->getMessage() . "\n";
            return;
        }

        $postId = $data['post_id'] ?? null;
        $authorUserId = $data['author_user_id'] ?? null;
        $postText = $data['text_preview'] ?? '';

        if (!$postId || !$authorUserId) {
            echo " [!] Missing required fields\n";
            return;
        }

        // Get friends of author
        $friendIds = $this->userRepository->getFriendIds($authorUserId);
        if (empty($friendIds)) {
            echo " [!] No friends found for user {$authorUserId}\n";
            return;
        }

        // Prepare message according to asyncapi spec
        $wsMessage = json_encode([
            'postId' => $postId,
            'postText' => $postText,
            'author_user_id' => $authorUserId,
        ], JSON_THROW_ON_ERROR);

        // Publish to Redis channel for each friend
        foreach ($friendIds as $friendId) {
            $channel = "user:{$friendId}:posts";
            $this->redisClient->client->publish($channel, $wsMessage);
            echo " [*] Published to {$channel}\n";
        }

        // Also publish to global channel for debugging
        $this->redisClient->client->publish('post_feed', $wsMessage);
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public static function run(): void
    {
        $consumer = new self();
        $consumer->consume();
    }
}