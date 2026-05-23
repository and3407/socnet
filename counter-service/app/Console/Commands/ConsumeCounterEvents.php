<?php

namespace App\Console\Commands;

use App\Services\CounterUpdateService;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeCounterEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'counter:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume RabbitMQ events for counter updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting counter event consumer...');

        $host = env('RABBITMQ_HOST', 'rabbitmq');
        $port = env('RABBITMQ_PORT', 5672);
        $user = env('RABBITMQ_LOGIN', 'admin');
        $password = env('RABBITMQ_PASSWORD', 'admin');
        $queue = env('RABBITMQ_QUEUE', 'counter_events');

        try {
            $connection = new AMQPStreamConnection($host, $port, $user, $password);
            $channel = $connection->channel();

            $channel->queue_declare($queue, false, true, false, false);

            $this->info("Waiting for messages on queue '$queue'. To exit press CTRL+C");

            $callback = function (AMQPMessage $msg) {
                $this->processMessage($msg);
            };

            $channel->basic_consume($queue, '', false, true, false, false, $callback);

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function processMessage(AMQPMessage $msg): void
    {
        $body = $msg->getBody();
        $data = json_decode($body, true);

        if (!isset($data['type'])) {
            $this->warn('Missing event type');
            return;
        }

        $service = new CounterUpdateService();

        $eventData = $data['data'] ?? [];

        switch ($data['type']) {
            case 'message.sent':
                $service->handleMessageSent($eventData);
                $this->info('Processed message.sent event');
                break;
            case 'dialog.opened':
                $service->handleDialogOpened($eventData);
                $this->info('Processed dialog.opened event');
                break;
            default:
                $this->warn('Unknown event type: ' . $data['type']);
        }
    }
}
