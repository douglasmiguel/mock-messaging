<?php

namespace App\Services;

use App\Models\ConsumerHealth;
use App\Models\ConsumerIncident;
use App\Models\ObservedEvent;
use App\Models\OrderProjection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PrometheusMetrics
{
    public function render(): string
    {
        $lines = [
            '# HELP mock_messaging_events_total Total number of events observed by event type.',
            '# TYPE mock_messaging_events_total counter',
        ];

        foreach (ObservedEvent::query()->selectRaw('event_type, count(*) as total')->groupBy('event_type')->orderBy('event_type')->get() as $event) {
            $lines[] = 'mock_messaging_events_total{event_type="'.$this->escapeLabel($event->event_type).'"} '.$event->total;
        }

        $lines[] = '# HELP mock_messaging_events_last_seen_timestamp_seconds Unix timestamp of the latest event by event type.';
        $lines[] = '# TYPE mock_messaging_events_last_seen_timestamp_seconds gauge';

        foreach (ObservedEvent::query()->selectRaw('event_type, max(occurred_at) as last_seen_at')->groupBy('event_type')->orderBy('event_type')->get() as $event) {
            $lines[] = 'mock_messaging_events_last_seen_timestamp_seconds{event_type="'.$this->escapeLabel($event->event_type).'"} '.Carbon::parse($event->last_seen_at)->getTimestamp();
        }

        $lines[] = '# HELP mock_messaging_orders_by_status Current order projections by status.';
        $lines[] = '# TYPE mock_messaging_orders_by_status gauge';

        foreach (OrderProjection::query()->selectRaw('status, count(*) as total')->groupBy('status')->orderBy('status')->get() as $projection) {
            $lines[] = 'mock_messaging_orders_by_status{status="'.$this->escapeLabel($projection->status).'"} '.$projection->total;
        }

        $lines[] = '# HELP mock_messaging_consumer_retries_total Retry attempts scheduled by each consumer.';
        $lines[] = '# TYPE mock_messaging_consumer_retries_total counter';

        foreach (ConsumerIncident::query()->where('outcome', 'retry_scheduled')->selectRaw('service, count(*) as total')->groupBy('service')->orderBy('service')->get() as $incident) {
            $lines[] = 'mock_messaging_consumer_retries_total{service="'.$this->escapeLabel($incident->service).'"} '.$incident->total;
        }

        $lines[] = '# HELP mock_messaging_consumer_dead_letters_total Messages sent to a dead-letter queue by each consumer.';
        $lines[] = '# TYPE mock_messaging_consumer_dead_letters_total counter';

        foreach (ConsumerIncident::query()->where('outcome', 'dead_lettered')->selectRaw('service, count(*) as total')->groupBy('service')->orderBy('service')->get() as $incident) {
            $lines[] = 'mock_messaging_consumer_dead_letters_total{service="'.$this->escapeLabel($incident->service).'"} '.$incident->total;
        }

        $lines[] = '# HELP mock_messaging_consumer_last_success_timestamp_seconds Unix timestamp when each consumer last processed a message.';
        $lines[] = '# TYPE mock_messaging_consumer_last_success_timestamp_seconds gauge';

        foreach (ConsumerHealth::query()->whereNotNull('last_success_at')->orderBy('service')->get() as $consumer) {
            $lines[] = 'mock_messaging_consumer_last_success_timestamp_seconds{service="'.$this->escapeLabel($consumer->service).'"} '.$consumer->last_success_at->getTimestamp();
        }

        $lines[] = '# HELP mock_messaging_consumer_last_failure_timestamp_seconds Unix timestamp when each consumer last failed a message.';
        $lines[] = '# TYPE mock_messaging_consumer_last_failure_timestamp_seconds gauge';

        foreach (ConsumerHealth::query()->whereNotNull('last_failure_at')->orderBy('service')->get() as $consumer) {
            $lines[] = 'mock_messaging_consumer_last_failure_timestamp_seconds{service="'.$this->escapeLabel($consumer->service).'"} '.$consumer->last_failure_at->getTimestamp();
        }

        return implode("\n", $lines)."\n";
    }

    private function escapeLabel(string $value): string
    {
        return Str::replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
    }
}
