<?php

namespace App\Console\Commands;

use App\Models\RiderAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ReconcileRiderAssignments extends Command
{
    protected $signature = 'riders:reconcile {--apply : Persist the safe status corrections discovered by the dry run}';

    protected $description = 'Reconcile pending rider assignments with the Order Service authority';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $corrected = 0;
        $mismatches = 0;

        RiderAssignment::query()->where('status', 'pending')->orderBy('id')->each(function (RiderAssignment $assignment) use ($apply, &$corrected, &$mismatches): void {
            $response = Http::acceptJson()
                ->connectTimeout(config('messaging.order_service_connect_timeout_seconds'))
                ->timeout(config('messaging.order_service_timeout_seconds'))
                ->retry([100, 500])
                ->withHeader('X-Service-Key', config('messaging.service_key'))
                ->get(config('messaging.order_service_url').'/api/v1/internal/orders/'.$assignment->order_id);

            if (! $response->successful()) {
                $this->warn("Order {$assignment->order_id} could not be read for reconciliation.");

                return;
            }

            $status = (string) $response->json('data.status');
            $riderId = (int) $response->json('data.rider_id');
            $targetStatus = match (true) {
                $status === 'rider_assigned' && $riderId === $assignment->rider_id => 'confirmed',
                $status === 'delivered' && $riderId === $assignment->rider_id => 'completed',
                $status === 'ready_for_pickup' => 'pending',
                default => null,
            };

            if ($targetStatus === null) {
                $mismatches++;
                $this->error("Order {$assignment->order_id} does not match rider {$assignment->rider_id}; no local change was made.");

                return;
            }

            if ($targetStatus !== $assignment->status) {
                $this->line("Order {$assignment->order_id}: {$assignment->status} -> {$targetStatus}".($apply ? '' : ' (dry run)'));
                if ($apply) {
                    $assignment->update(['status' => $targetStatus]);
                }
                $corrected++;
            }
        });

        $this->info("Reconciliation complete: {$corrected} correction(s), {$mismatches} mismatch(es).");

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}
