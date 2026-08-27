<?php

namespace App\Http\Controllers\TestOrder;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $order = $request->session()->has('test_order_id')
            ? Order::with(['client', 'items', 'restaurant'])->find($request->session()->get('test_order_id'))
            : null;

        return view('home', compact('order'));
    }
}
