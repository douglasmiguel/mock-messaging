@extends('layouts.admin')

@section('content')
    <section class="heading">
        <div>
            <p class="eyebrow">Operations</p>
            <h1>Orders</h1>
            <p class="muted">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }} in this local service.</p>
        </div>
        <form method="GET" action="{{ route('admin.orders.index') }}" class="search-form">
            <label class="sr-only" for="restaurant">Search restaurant</label>
            <input id="restaurant" name="restaurant" value="{{ $restaurant }}" placeholder="Search by restaurant">
            <button type="submit">Search</button>
            @if ($restaurant !== '')
                <a class="button button-secondary" href="{{ route('admin.orders.index') }}">Clear</a>
            @endif
        </form>
    </section>

    <section class="card table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Restaurant</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->restaurant->name }}</td>
                            <td>{{ $order->client->name }}</td>
                            <td>£{{ number_format($order->total_price / 100, 2) }}</td>
                            <td><span class="status status-{{ str_replace('_', '-', $order->status->value) }}">{{ str_replace('_', ' ', $order->status->value) }}</span></td>
                            <td>{{ $order->created_at->format('d M Y, H:i') }}</td>
                            <td class="expand-cell">
                                <details>
                                    <summary>Details</summary>
                                    <div class="order-details">
                                        <dl>
                                            <div><dt>Client email</dt><dd>{{ $order->client->email }}</dd></div>
                                            <div><dt>Delivery</dt><dd>{{ $order->delivery_address }}</dd></div>
                                            <div><dt>Rider reference</dt><dd>{{ $order->rider_id ? 'Rider #'.$order->rider_id : 'Not assigned' }}</dd></div>
                                            <div><dt>Order price</dt><dd>£{{ number_format($order->order_price / 100, 2) }}</dd></div>
                                            <div><dt>Delivery fee</dt><dd>£{{ number_format($order->delivery_fee / 100, 2) }}</dd></div>
                                        </dl>
                                        <h2>Items</h2>
                                        <table class="items-table">
                                            <thead><tr><th>Item</th><th>Unit price</th><th>Quantity</th><th>Line total</th></tr></thead>
                                            <tbody>
                                                @foreach ($order->items as $item)
                                                    <tr>
                                                        <td>{{ $item->item_name }}</td>
                                                        <td>£{{ number_format($item->item_price / 100, 2) }}</td>
                                                        <td>{{ $item->quantity }}</td>
                                                        <td>£{{ number_format($item->total_price / 100, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No orders match this restaurant search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->total() > 0)
            <nav class="pagination" aria-label="Order pagination">
                <span>Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}</span>
                <div>
                    @if ($orders->onFirstPage())
                        <span class="button button-disabled">Previous</span>
                    @else
                        <a class="button button-secondary" href="{{ $orders->previousPageUrl() }}">Previous</a>
                    @endif
                    @if ($orders->hasMorePages())
                        <a class="button button-secondary" href="{{ $orders->nextPageUrl() }}">Next</a>
                    @else
                        <span class="button button-disabled">Next</span>
                    @endif
                </div>
            </nav>
        @endif
    </section>
@endsection

@push('styles')
    <style>
        .heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .heading h1 { margin: 3px 0; color: #24103d; font-size: 30px; letter-spacing: -0.7px; }
        .heading p { margin: 0; }
        .eyebrow { color: #4c1d95; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
        .search-form { display: flex; gap: 8px; min-width: min(100%, 440px); }
        .search-form input { min-width: 220px; }
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 13px 16px; background: #f9fafb; color: #475467; font-size: 12px; font-weight: 750; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
        td { border-top: 1px solid #eaecf0; padding: 14px 16px; color: #344054; font-size: 14px; vertical-align: top; }
        .expand-cell { min-width: 305px; }
        summary { color: #4c1d95; cursor: pointer; font-weight: 700; }
        .order-details { margin-top: 12px; border-left: 3px solid #c4b5fd; padding: 2px 0 2px 14px; }
        dl { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 11px 18px; margin: 0; }
        dt { color: #667085; font-size: 12px; font-weight: 700; }
        dd { margin: 2px 0 0; }
        .order-details h2 { margin: 17px 0 8px; color: #344054; font-size: 14px; }
        .items-table th, .items-table td { padding: 8px; font-size: 12px; }
        .items-table th { background: #f8fafc; }
        .pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-top: 1px solid #eaecf0; padding: 14px 16px; color: #667085; font-size: 14px; }
        .pagination > div { display: flex; gap: 8px; }
        .button-disabled { cursor: default; background: #f2f4f7; color: #98a2b3; }
        @media (max-width: 720px) { .heading { align-items: stretch; flex-direction: column; } .search-form { flex-wrap: wrap; } .search-form input { flex: 1; min-width: 180px; } .pagination { align-items: flex-start; flex-direction: column; } }
    </style>
@endpush
