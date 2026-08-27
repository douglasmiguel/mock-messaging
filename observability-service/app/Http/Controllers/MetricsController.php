<?php

namespace App\Http\Controllers;

use App\Services\PrometheusMetrics;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    public function __invoke(PrometheusMetrics $metrics): Response
    {
        return response($metrics->render(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
