<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Restaurant order #{{ $order->order_id }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; color: #24103d; background: #fcfaff; }
                * { box-sizing: border-box; }
                body { min-height: 100vh; margin: 0; padding: 32px 16px; background: #fcfaff; }
                main { max-width: 48rem; margin: 0 auto; }
                header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; }
                header img { width: 64px; height: 64px; object-fit: contain; }
                header p { margin: 0; color: #4c1d95; font-size: 14px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
                h1 { margin: 4px 0 0; font-size: 30px; letter-spacing: -.025em; }
                h2 { margin-top: 32px; font-size: 20px; }
                section { border: 1px solid #ddd6fe; border-top: 4px solid #4c1d95; border-radius: 16px; padding: 32px; background: white; box-shadow: 0 1px 3px rgb(76 29 149 / 8%); }
                section > p { color: #334155; }
                ul { display: grid; gap: 8px; margin: 16px 0 0; padding: 0; list-style: none; color: #334155; }
                li { border-radius: 8px; padding: 12px 16px; background: #f5f3ff; }
                form { display: inline-block; margin: 0 8px 0 0; }
                button { min-height: 44px; border: 0; border-radius: 8px; padding: 8px 16px; background: #4c1d95; color: white; cursor: pointer; font: inherit; font-weight: 600; }
                form + form button { background: #b91c1c; }
                button:focus-visible { outline: 3px solid #c026d3; outline-offset: 2px; }
            </style>
        @endif
    </head>
    <body class="min-h-screen bg-growth-surface px-4 py-8 text-growth-ink sm:px-6">
        <main class="mx-auto max-w-3xl">
            <header class="mb-8 flex items-center gap-4">
                <img class="size-16 shrink-0 object-contain" src="{{ asset('images/growth-loop-logo.png') }}" alt="Growth Loop logo">
                <div>
                    <p class="text-sm font-bold tracking-[0.16em] text-growth-plum uppercase">Mock Messaging</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $order->restaurant_name }}: order #{{ $order->order_id }}</h1>
                </div>
            </header>

            <section class="rounded-2xl border border-growth-lilac border-t-4 border-t-growth-plum bg-white p-6 shadow-sm sm:p-8">
                <p class="text-slate-700">Status: <strong>{{ str($order->status)->headline() }}</strong></p>

                @if (session('success'))
                    <p class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-medium text-emerald-800" role="status">{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
                @endif

                <h2 class="mt-8 text-xl font-bold">Order items</h2>
                <ul class="mt-4 grid gap-2 text-slate-700">
                    @foreach (($order->payload['order']['items'] ?? []) as $item)
                        <li class="rounded-lg bg-growth-lilac/40 px-4 py-3">{{ $item['quantity'] }} × {{ $item['name'] }}</li>
                    @endforeach
                </ul>
                <p class="mt-6 text-slate-700">Delivery address: {{ $order->payload['order']['delivery_address'] ?? '' }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if (in_array($order->status, ['new', 'placed']))
                        <form method="post" action="{{ route('restaurant-orders.confirm', $token->token) }}">
                            @csrf
                            <button class="min-h-11 rounded-lg bg-growth-plum px-4 py-2 font-semibold text-white hover:bg-violet-950 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-growth-magenta" type="submit">Confirm order</button>
                        </form>
                        <form method="post" action="{{ route('restaurant-orders.refuse', $token->token) }}">
                            @csrf
                            <button class="min-h-11 rounded-lg bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-400" type="submit">Refuse order</button>
                        </form>
                    @elseif ($order->status === 'accepted')
                        <form method="post" action="{{ route('restaurant-orders.ready', $token->token) }}">
                            @csrf
                            <button class="min-h-11 rounded-lg bg-growth-plum px-4 py-2 font-semibold text-white hover:bg-violet-950 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-growth-magenta" type="submit">Ready for rider pickup</button>
                        </form>
                    @else
                        <p class="text-slate-700">No further restaurant action is available.</p>
                    @endif
                </div>
            </section>
        </main>
    </body>
</html>
