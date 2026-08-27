<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Your order #{{ $order->order_id }}</title>
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
                h2 { margin-bottom: 0; font-size: 20px; }
                section { border: 1px solid #ddd6fe; border-top: 4px solid #4c1d95; border-radius: 16px; padding: 32px; background: white; box-shadow: 0 1px 3px rgb(76 29 149 / 8%); }
                section > p { color: #334155; }
                textarea { display: block; width: 100%; min-height: 112px; border: 1px solid #c4b5fd; border-radius: 8px; padding: 8px 12px; color: #24103d; font: inherit; }
                textarea:focus { border-color: #c026d3; outline: 3px solid #f3e8ff; }
                button { min-height: 44px; border: 0; border-radius: 8px; padding: 8px 16px; background: #4c1d95; color: white; cursor: pointer; font: inherit; font-weight: 600; }
                section > form > button { background: #b91c1c; }
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
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Your order #{{ $order->order_id }}</h1>
                </div>
            </header>

            <section class="rounded-2xl border border-growth-lilac border-t-4 border-t-growth-plum bg-white p-6 shadow-sm sm:p-8">
                <p class="text-slate-700">From {{ $order->restaurant_name }} · Status: <strong>{{ str($order->status)->headline() }}</strong></p>

                @if (session('success'))
                    <p class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-medium text-emerald-800" role="status">{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
                @endif

                @if (in_array($order->status, ['new', 'placed', 'accepted']))
                    <form class="mt-8" method="post" action="{{ route('client-orders.cancel', $token->token) }}">
                        @csrf
                        <button class="min-h-11 rounded-lg bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-400" type="submit">Cancel order</button>
                    </form>
                @elseif ($order->status === 'rider_assigned')
                    <div class="mt-8">
                        <h2 class="text-xl font-bold">Raise an issue</h2>
                        <p class="mt-2 text-slate-700">Tell us about missing delivery or wrong items.</p>
                        <form class="mt-5 grid gap-3" method="post" action="{{ route('client-orders.issues', $token->token) }}">
                            @csrf
                            <label class="font-semibold" for="description">Issue description</label>
                            <textarea class="min-h-28 rounded-lg border border-violet-300 bg-white px-3 py-2 text-growth-ink focus:border-growth-magenta focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-growth-magenta" id="description" name="description" required maxlength="1000" placeholder="Describe the problem"></textarea>
                            @error('description')
                                <p class="font-medium text-red-700" role="alert">{{ $message }}</p>
                            @enderror
                            <button class="min-h-11 w-fit rounded-lg bg-growth-plum px-4 py-2 font-semibold text-white hover:bg-violet-950 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-growth-magenta" type="submit">Send issue</button>
                        </form>
                    </div>
                @else
                    <p class="mt-8 text-slate-700">There is no action required right now.</p>
                @endif
            </section>
        </main>
    </body>
</html>
