<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $currentOrderId = null;
        $aggregateVersion = 0;

        DB::table('outbox_messages')->orderBy('order_id')->orderBy('occurred_at')->orderBy('id')->each(function (object $outbox) use (&$currentOrderId, &$aggregateVersion): void {
            if ($outbox->order_id !== $currentOrderId) {
                $currentOrderId = $outbox->order_id;
                $aggregateVersion = 0;
            }

            $aggregateVersion++;
            $payload = json_decode($outbox->payload, true, 512, JSON_THROW_ON_ERROR);
            $payload['aggregate_type'] = 'order';
            $payload['aggregate_id'] = (string) $outbox->order_id;
            $payload['aggregate_version'] = $aggregateVersion;

            DB::table('outbox_messages')->where('id', $outbox->id)->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            DB::table('orders')->where('id', $outbox->order_id)->update(['aggregate_version' => $aggregateVersion]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Historical event sequencing cannot be safely inferred in reverse.
    }
};
