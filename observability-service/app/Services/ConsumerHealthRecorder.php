<?php

namespace App\Services;

use App\Models\ConsumerHealth;

class ConsumerHealthRecorder
{
    public function recordSuccess(string $service): void
    {
        ConsumerHealth::query()->updateOrCreate(
            ['service' => $service],
            ['last_success_at' => now()],
        );
    }

    public function recordFailure(string $service, string $error): void
    {
        ConsumerHealth::query()->updateOrCreate(
            ['service' => $service],
            ['last_failure_at' => now(), 'last_error' => $error],
        );
    }
}
