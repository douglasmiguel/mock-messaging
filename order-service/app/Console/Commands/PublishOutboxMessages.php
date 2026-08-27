<?php

namespace App\Console\Commands;

use App\Models\OutboxMessage;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishOutboxMessages extends Command
{
    protected $signature = 'outbox:publish {--limit=100 : Maximum number of pending messages to publish} {--order= : Publish pending events for one order ID}';

    protected $description = 'Publish pending order outbox messages to RabbitMQ';

    public function handle(): int
    {
        $rabbit = config('messaging.rabbitmq');
        $connection = new AMQPStreamConnection(
            $rabbit['host'],
            $rabbit['port'],
            $rabbit['user'],
            $rabbit['password'],
            $rabbit['vhost'],
        );
        $channel = $connection->channel();
        $channel->exchange_declare($rabbit['exchange'], 'topic', false, true, false);
        $channel->set_return_listener(function (int $replyCode, string $replyText, string $exchange, string $routingKey): void {
            throw new \RuntimeException("RabbitMQ returned an unroutable message ({$replyCode} {$replyText}) for {$exchange}:{$routingKey}.");
        });
        $channel->confirm_select();

        $messages = OutboxMessage::query()->whereNull('published_at')
            ->when($this->option('order'), fn ($query, $orderId) => $query->where('order_id', $orderId))
            ->orderBy('occurred_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $published = 0;
        $failures = 0;
        foreach ($messages as $outbox) {
            try {
                $channel->basic_publish(new AMQPMessage(json_encode($outbox->payload, JSON_THROW_ON_ERROR), [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $outbox->payload['event_id'],
                    'type' => $outbox->event_type,
                ]), $rabbit['exchange'], $outbox->event_type, true);
                $channel->wait_for_pending_acks_returns();

                $outbox->update(['published_at' => now(), 'last_publish_error' => null]);
                $published++;
            } catch (\Throwable $exception) {
                report($exception);
                $outbox->increment('publish_attempts', 1, [
                    'last_publish_error' => $exception->getMessage(),
                    'last_publish_failed_at' => now(),
                ]);
                $failures++;
            }
        }

        $channel->close();
        $connection->close();
        $this->info("Published {$published} outbox message(s); {$failures} failure(s).");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
