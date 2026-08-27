<?php

namespace App\Console\Commands;

use App\Services\GenerateDemoOrderLifecycles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:generate-orders {--count=300 : Number of orders to generate} {--days=14 : Number of past days across which to spread order creation}')]
#[Description('Generate demo orders and their full lifecycle events for local observability testing.')]
class GenerateDemoOrders extends Command
{
    public function handle(GenerateDemoOrderLifecycles $lifecycleGenerator): int
    {
        $count = $this->positiveIntegerOption('count');
        $days = $this->positiveIntegerOption('days');

        if ($count === null || $days === null) {
            return self::FAILURE;
        }

        try {
            $generatedByStatus = $lifecycleGenerator->generate($count, $days);
        } catch (\LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Generated {$count} demo orders and queued their lifecycle events in the transactional outbox.");
        $this->table(['Status', 'Orders'], collect($generatedByStatus)->map(fn (int $total, string $status): array => [$status, $total])->all());

        return self::SUCCESS;
    }

    private function positiveIntegerOption(string $name): ?int
    {
        $value = (string) $this->option($name);

        if (! ctype_digit($value) || (int) $value < 1) {
            $this->error("The --{$name} option must be a positive integer.");

            return null;
        }

        return (int) $value;
    }
}
