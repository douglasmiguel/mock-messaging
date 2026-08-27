<?php

namespace App\Console\Commands;

use App\Services\RiderEventHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ConsumeRiderEvents extends Command
{
    protected $signature = 'riders:consume {--once : Process one available message and stop}';

    protected $description = 'Assign available riders when orders are ready for pickup';

    public function handle(RiderEventHandler $handler): int
    {
        $rabbit = config('messaging.rabbitmq');
        $connection = new AMQPStreamConnection($rabbit['host'], $rabbit['port'], $rabbit['user'], $rabbit['password'], $rabbit['vhost']);
        $channel = $connection->channel();
        $this->configureQueues($channel, $rabbit);
        $channel->basic_qos(null, 1, null);

        $consume = function (AMQPMessage $message) use ($handler, $channel, $rabbit): void {
            try {
                $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $handler->handle($payload);
                $message->ack();
                $this->publishDiagnostic($channel, $rabbit, $message, 'messaging.message_processed', 0);
                $this->line('Processed '.($payload['event_type'] ?? 'order event'));
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Message failed: '.$exception->getMessage());
                $this->retryOrDeadLetter($channel, $rabbit, $message, $exception);
            }
        };

        if ($this->option('once')) {
            $message = $channel->basic_get($rabbit['queue'], false);
            if ($message instanceof AMQPMessage) {
                $consume($message);
            } else {
                $this->line('No messages available.');
            }
            $channel->close();
            $connection->close();

            return self::SUCCESS;
        }

        $this->info('Waiting for ready-for-pickup events. Press Ctrl+C to stop.');
        $channel->basic_consume($rabbit['queue'], '', false, false, false, false, $consume);
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $rabbit */
    private function configureQueues(AMQPChannel $channel, array $rabbit): void
    {
        $channel->exchange_declare($rabbit['exchange'], 'topic', false, true, false);
        $channel->exchange_declare($rabbit['dead_letter_exchange'], 'topic', false, true, false);
        $channel->exchange_declare($rabbit['retry_exchange'], 'topic', false, true, false);
        $channel->queue_declare($rabbit['queue'], false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $rabbit['dead_letter_exchange'],
        ]));
        $channel->queue_declare($rabbit['retry_queue'], false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $rabbit['exchange'],
        ]));
        $channel->queue_declare($rabbit['dead_letter_queue'], false, true, false, false);
        $channel->queue_bind($rabbit['queue'], $rabbit['exchange'], 'order.ready_for_pickup');
        $channel->queue_bind($rabbit['queue'], $rabbit['exchange'], 'order.delivered');
        $channel->queue_bind($rabbit['retry_queue'], $rabbit['retry_exchange'], '#');
        $channel->queue_bind($rabbit['dead_letter_queue'], $rabbit['dead_letter_exchange'], '#');
    }

    /** @param array<string, mixed> $rabbit */
    private function retryOrDeadLetter(AMQPChannel $channel, array $rabbit, AMQPMessage $message, \Throwable $exception): void
    {
        $retryCount = $this->retryCount($message);
        if (! $exception instanceof \InvalidArgumentException && $retryCount < $rabbit['max_retries']) {
            $nextRetryCount = $retryCount + 1;
            $channel->basic_publish(new AMQPMessage($message->getBody(), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $this->eventId($message),
                'expiration' => (string) $this->retryDelay($rabbit, $nextRetryCount),
                'application_headers' => new AMQPTable(['x_retry_count' => $nextRetryCount]),
            ]), $rabbit['retry_exchange'], (string) $message->getRoutingKey());
            $message->ack();
            $this->publishDiagnostic($channel, $rabbit, $message, 'messaging.message_retry_scheduled', $nextRetryCount, $exception);

            return;
        }

        $this->publishDiagnostic($channel, $rabbit, $message, 'messaging.message_dead_lettered', $retryCount, $exception);
        $message->nack(false);
    }

    /** @param array<string, mixed> $rabbit */
    private function publishDiagnostic(
        AMQPChannel $channel,
        array $rabbit,
        AMQPMessage $message,
        string $type,
        int $retryCount,
        ?\Throwable $exception = null,
    ): void {
        try {
            try {
                $event = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $event = [];
            }
            $diagnostic = [
                'event_id' => (string) Str::ulid(),
                'event_type' => $type,
                'event_version' => 1,
                'occurred_at' => now()->toIso8601String(),
                'data' => [
                    'service' => 'rider-service',
                    'source_event_id' => $event['event_id'] ?? $this->eventId($message),
                    'source_event_type' => $event['event_type'] ?? 'unknown',
                    'retry_count' => $retryCount,
                    'order' => ['id' => data_get($event, 'data.order.id')],
                    'error' => $exception?->getMessage(),
                ],
            ];
            $channel->basic_publish(new AMQPMessage(json_encode($diagnostic, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $diagnostic['event_id'],
            ]), $rabbit['exchange'], $type);
        } catch (\Throwable $diagnosticException) {
            report($diagnosticException);
        }
    }

    private function retryCount(AMQPMessage $message): int
    {
        if (! $message->has('application_headers')) {
            return 0;
        }

        return (int) (($message->get('application_headers')->getNativeData()['x_retry_count'] ?? 0));
    }

    private function eventId(AMQPMessage $message): string
    {
        return $message->has('message_id') ? (string) $message->get('message_id') : (string) Str::ulid();
    }

    /** @param array<string, mixed> $rabbit */
    private function retryDelay(array $rabbit, int $retryCount): int
    {
        return ($rabbit['retry_base_delay_ms'] * (2 ** ($retryCount - 1))) + random_int(0, $rabbit['retry_jitter_ms']);
    }
}
