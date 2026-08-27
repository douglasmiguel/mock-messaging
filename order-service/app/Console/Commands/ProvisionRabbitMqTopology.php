<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

class ProvisionRabbitMqTopology extends Command
{
    protected $signature = 'messaging:provision-topology';

    protected $description = 'Provision durable RabbitMQ exchanges, queues, bindings, retries, and DLQs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rabbit = config('messaging.rabbitmq');
        $connection = new AMQPStreamConnection($rabbit['host'], $rabbit['port'], $rabbit['user'], $rabbit['password'], $rabbit['vhost']);
        $channel = $connection->channel();
        $channel->exchange_declare($rabbit['exchange'], 'topic', false, true, false);

        foreach (config('messaging.topology') as $name => $topology) {
            $this->provisionConsumer($channel, $rabbit['exchange'], $topology);
            $this->line("Provisioned {$name} topology.");
        }

        $channel->close();
        $connection->close();
        $this->info('RabbitMQ topology is ready for publishers and consumers.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $topology */
    private function provisionConsumer(AMQPChannel $channel, string $exchange, array $topology): void
    {
        $channel->exchange_declare($topology['dead_letter_exchange'], 'topic', false, true, false);
        $channel->exchange_declare($topology['retry_exchange'], 'topic', false, true, false);
        $channel->queue_declare($topology['queue'], false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $topology['dead_letter_exchange'],
        ]));
        $channel->queue_declare($topology['retry_queue'], false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $exchange,
        ]));
        $channel->queue_declare($topology['dead_letter_queue'], false, true, false, false);

        foreach ($topology['bindings'] as $binding) {
            $channel->queue_bind($topology['queue'], $exchange, $binding);
        }
        $channel->queue_bind($topology['retry_queue'], $topology['retry_exchange'], '#');
        $channel->queue_bind($topology['dead_letter_queue'], $topology['dead_letter_exchange'], '#');
    }
}
