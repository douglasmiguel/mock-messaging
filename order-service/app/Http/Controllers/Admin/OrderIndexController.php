<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $restaurant = trim((string) $request->query('restaurant', ''));

        $orders = Order::query()
            ->with(['client', 'restaurant', 'items'])
            ->when($restaurant !== '', function ($query) use ($restaurant): void {
                $query->whereHas('restaurant', function ($query) use ($restaurant): void {
                    $query->where('name', 'like', "%{$restaurant}%");
                });
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'restaurant'));
    }
}
