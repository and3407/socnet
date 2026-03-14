<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Domain\Token\Repositories\TokenRepository;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\Redis\RedisClient;
use React\EventLoop\LoopInterface;
use Clue\React\Redis\Client as AsyncRedisClient;
use Clue\React\Redis\Factory as RedisFactory;
use React\Socket\Server as ReactServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Server implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected array $userConnections = []; // userId => ConnectionInterface[]
    protected TokenRepository $tokenRepository;
    protected UserRepository $userRepository;
    protected ?AsyncRedisClient $redisSubscriber = null;
    protected LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->tokenRepository = new TokenRepository();
        $this->userRepository = new UserRepository();
        $this->loop = $loop;
        $this->setupRedisSubscriber();
    }

    private function setupRedisSubscriber(): void
    {
        $factory = new RedisFactory($this->loop);
        $redisUri = 'redis://' . (getenv('REDIS_HOST') ?: 'redis') . ':6379';
        $factory->createClient($redisUri)->then(
            function (AsyncRedisClient $client) {
                $this->redisSubscriber = $client;
                $this->redisSubscriber->on('message', function ($channel, $message) {
                    $this->onRedisMessage($channel, $message);
                });
                // Subscribe to all user channels? We'll subscribe dynamically when users connect
                // For now subscribe to a global channel
                $this->redisSubscriber->subscribe('post_feed');
                echo "Redis subscriber connected and subscribed to post_feed\n";
            },
            function (\Exception $e) {
                echo "Failed to connect to Redis: " . $e->getMessage() . "\n";
            }
        );
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Authenticate via query parameters
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        $token = $params['token'] ?? null;

        if (!$token) {
            $conn->close(1008, 'Missing token');
            return;
        }

        $tokenEntity = $this->tokenRepository->getTokenByToken($token);
        if (!$tokenEntity) {
            $conn->close(1008, 'Invalid token');
            return;
        }
        // Check expiration
        $expiredAt = $tokenEntity->expiredAt;
        if (strtotime($expiredAt) < time()) {
            $conn->close(1008, 'Token expired');
            return;
        }

        $userId = $tokenEntity->userid;
        $conn->userId = $userId;

        // Store connection mapping
        if (!isset($this->userConnections[$userId])) {
            $this->userConnections[$userId] = [];
        }
        $this->userConnections[$userId][] = $conn;

        // Subscribe to Redis channel for this user's feed
        $this->subscribeToUserFeed($userId);

        echo "User {$userId} authenticated and connected\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // For now, we don't expect messages from clients
        // But we could handle subscriptions or pings
        echo "Received message: {$msg}\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        if (isset($conn->userId)) {
            $userId = $conn->userId;
            if (isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = array_filter(
                    $this->userConnections[$userId],
                    function ($c) use ($conn) {
                        return $c !== $conn;
                    }
                );
                if (empty($this->userConnections[$userId])) {
                    unset($this->userConnections[$userId]);
                    $this->unsubscribeFromUserFeed($userId);
                }
            }
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function subscribeToUserFeed(int $userId): void
    {
        if ($this->redisSubscriber) {
            $channel = "user:{$userId}:posts";
            $this->redisSubscriber->subscribe($channel);
            echo "Subscribed to Redis channel {$channel}\n";
        }
    }

    private function unsubscribeFromUserFeed(int $userId): void
    {
        if ($this->redisSubscriber) {
            $channel = "user:{$userId}:posts";
            $this->redisSubscriber->unsubscribe($channel);
            echo "Unsubscribed from Redis channel {$channel}\n";
        }
    }

    private function onRedisMessage(string $channel, string $message): void
    {
        // Channel format: user:{userId}:posts
        // Message is JSON encoded post data
        $parts = explode(':', $channel);
        if (count($parts) === 3 && $parts[0] === 'user' && $parts[2] === 'posts') {
            $userId = (int) $parts[1];
            $this->broadcastToUser($userId, $message);
        } elseif ($channel === 'post_feed') {
            // Global feed: need to parse message and determine which users to send to
            $this->handleGlobalFeedMessage($message);
        }
    }

    private function broadcastToUser(int $userId, string $message): void
    {
        if (isset($this->userConnections[$userId])) {
            foreach ($this->userConnections[$userId] as $conn) {
                $conn->send($message);
            }
        }
    }

    private function handleGlobalFeedMessage(string $message): void
    {
        // Decode message to get author_user_id, then find friends and send
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }
        $authorUserId = $data['author_user_id'] ?? null;
        if (!$authorUserId) {
            return;
        }
        // TODO: Get friends of authorUserId and broadcast to each friend's connections
        // For now, we'll broadcast to all connected users (simplified)
        // We'll implement friend resolution later
        foreach ($this->userConnections as $userId => $connections) {
            // Skip author themselves? Not needed
            foreach ($connections as $conn) {
                $conn->send($message);
            }
        }
    }

    public static function run(): void
    {
        $loop = \React\EventLoop\Loop::get();
        $server = new self($loop);

        $socket = new ReactServer('0.0.0.0:8080', $loop);
        $wsServer = new WsServer($server);
        $httpServer = new HttpServer($wsServer);
        $ioServer = new IoServer($httpServer, $socket, $loop);

        echo "WebSocket server started on port 8080\n";
        $loop->run();
    }
}