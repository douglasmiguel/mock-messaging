@extends('layouts.admin')

@section('content')
    <section class="intro">
        <p class="eyebrow">Local order simulator</p>
        <h1>Walk an order through its delivery flow.</h1>
        <p class="muted">Generate a realistic local order, then progress it one step at a time. Each step records an integration event in the outbox.</p>
    </section>

    @if (session('success'))
        <div class="notice success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="notice error-notice">{{ session('error') }}</div>
    @endif

    <section class="card generate-card">
        <div>
            <h2>New test order</h2>
            <p class="muted">Uses a seeded restaurant menu and client, and always starts with status <strong>new</strong>.</p>
        </div>
        <form method="POST" action="{{ route('test-orders.store') }}">
            @csrf
            <button type="submit">Generate test order</button>
        </form>
    </section>

    @if ($order)
        <section class="card order-card">
            <div class="order-head">
                <div>
                    <p class="eyebrow">Active test order</p>
                    <h2>Order #{{ $order->id }}</h2>
                    <p class="muted">{{ $order->restaurant->name }} → {{ $order->client->name }}</p>
                </div>
                <span class="status status-{{ str_replace('_', '-', $order->status->value) }}">{{ str_replace('_', ' ', $order->status->value) }}</span>
            </div>

            @php($steps = ['new', 'accepted', 'ready_for_pickup', 'rider_assigned', 'picked_up', 'delivered'])
            @php($currentStep = array_search($order->status->value, $steps, true))
            <ol class="flow" aria-label="Order progress">
                @foreach ($steps as $index => $step)
                    <li class="{{ $order->status->value === $step ? 'current' : '' }} {{ $currentStep !== false && $index < $currentStep ? 'complete' : '' }}">
                        {{ str_replace('_', ' ', $step) }}
                    </li>
                @endforeach
            </ol>

            <div class="order-summary">
                <div><span>Restaurant</span><strong>{{ $order->restaurant->name }}</strong></div>
                <div><span>Client</span><strong>{{ $order->client->name }}</strong><small>{{ $order->client->email }}</small></div>
                <div><span>Rider</span><strong>{{ $order->rider_id ? 'Rider #'.$order->rider_id : 'Not assigned' }}</strong></div>
                <div><span>Total</span><strong>£{{ number_format($order->total_price / 100, 2) }}</strong></div>
            </div>

            <h3>Order items</h3>
            <ul class="items">
                @foreach ($order->items as $item)
                    <li><span>{{ $item->quantity }}× {{ $item->item_name }}</span><strong>£{{ number_format($item->total_price / 100, 2) }}</strong></li>
                @endforeach
            </ul>

            <div class="actions">
                @if ($order->status->value === 'new')
                    <form method="POST" action="{{ route('test-orders.confirm', $order) }}">@csrf <button type="submit">Restaurant confirms order</button></form>
                @endif
                @if ($order->status->value === 'accepted')
                    <form method="POST" action="{{ route('test-orders.ready', $order) }}">@csrf <button type="submit">Restaurant marks ready for rider</button></form>
                @endif
                @if ($order->status->value === 'ready_for_pickup')
                    <p class="muted final-state">Waiting for the Rider Service to assign an available rider.</p>
                @endif
                @if ($order->status->value === 'rider_assigned')
                    <form method="POST" action="{{ route('test-orders.pick-up', $order) }}">@csrf <button type="submit">Rider picks up order</button></form>
                @endif
                @if ($order->status->value === 'picked_up')
                    <form method="POST" action="{{ route('test-orders.deliver', $order) }}">@csrf <button type="submit">Rider delivers order</button></form>
                @endif
                @if (in_array($order->status->value, ['new', 'accepted'], true))
                    <form method="POST" action="{{ route('test-orders.cancel', $order) }}">@csrf <button class="button-danger" type="submit">Client cancels order</button></form>
                @endif
                @if (in_array($order->status->value, ['delivered', 'cancelled'], true))
                    <p class="muted final-state">This test order is complete. Generate another to try a new path.</p>
                @endif
            </div>
        </section>
    @endif
@endsection

@push('styles')
    <style>
        .intro { max-width: 720px; margin-bottom: 24px; }
        .intro h1 { margin: 5px 0 9px; color: #24103d; font-size: clamp(30px, 5vw, 42px); letter-spacing: -1.2px; line-height: 1.08; }
        .intro p { margin: 0; }
        .eyebrow { color: #4c1d95; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .notice { margin-bottom: 18px; border: 1px solid; border-radius: 8px; padding: 12px 14px; font-weight: 600; }
        .success { border-color: #abefc6; background: #ecfdf3; color: #027a48; }
        .error-notice { border-color: #fecdca; background: #fef3f2; color: #b42318; }
        .generate-card, .order-card { padding: 22px; }
        .generate-card { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 24px; }
        .generate-card h2, .order-card h2, .order-card h3 { margin: 0 0 5px; color: #24103d; }
        .generate-card p { margin: 0; }
        .order-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
        .flow { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin: 25px 0; padding: 0; list-style: none; }
        .flow li { position: relative; border-radius: 7px; padding: 9px 7px; background: #f2f4f7; color: #667085; font-size: 12px; font-weight: 750; text-align: center; text-transform: capitalize; }
        .flow .current { background: #ede9fe; color: #4c1d95; }
        .flow .complete { background: #ecfdf3; color: #027a48; }
        .order-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; border-top: 1px solid #eaecf0; border-bottom: 1px solid #eaecf0; padding: 17px 0; }
        .order-summary div { display: grid; gap: 3px; }
        .order-summary span { color: #667085; font-size: 12px; font-weight: 700; }
        .order-summary strong { color: #344054; }
        .order-summary small { color: #667085; font-size: 12px; }
        .order-card h3 { margin-top: 20px; font-size: 15px; }
        .items { display: grid; gap: 7px; margin: 10px 0 0; padding: 0; list-style: none; }
        .items li { display: flex; justify-content: space-between; gap: 15px; color: #475467; font-size: 14px; }
        .items strong { color: #344054; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; border-top: 1px solid #eaecf0; padding-top: 20px; }
        .button-danger { background: #b42318; }
        .button-danger:hover { background: #912018; }
        .final-state { margin: 0; }
        @media (max-width: 720px) { .generate-card { align-items: flex-start; flex-direction: column; } .flow { grid-template-columns: repeat(2, 1fr); } .order-summary { grid-template-columns: repeat(2, 1fr); } }
    </style>
@endpush
