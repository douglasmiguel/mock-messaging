<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\OrderBusinessMetrics;
use Illuminate\Http\Response;

class OrderBusinessMetricsController extends Controller
{
    public function __invoke(OrderBusinessMetrics $metrics): Response
    {
        return response($metrics->render(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
