<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Order Service Admin' }}</title>
        <style>
            :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #24103d; background: #fcfaff; }
            * { box-sizing: border-box; }
            body { margin: 0; background: #fcfaff; }
            .shell { max-width: 1200px; margin: 0 auto; padding: 32px 20px; }
            .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; }
            .brand { display: inline-flex; align-items: center; gap: 10px; color: #24103d; font-size: 20px; font-weight: 750; text-decoration: none; }
            .brand img { width: 40px; height: 40px; object-fit: contain; }
            .muted { color: #667085; }
            .card { overflow: hidden; border: 1px solid #ddd6fe; border-radius: 16px; background: #fff; box-shadow: 0 1px 3px rgb(76 29 149 / 8%); }
            .button, button { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; border: 1px solid transparent; border-radius: 8px; padding: 8px 13px; background: #4c1d95; color: #fff; cursor: pointer; font: inherit; font-weight: 600; text-decoration: none; }
            .button:hover, button:hover { background: #3b0764; }
            .button:focus-visible, button:focus-visible, input:focus-visible { outline: 3px solid #c026d3; outline-offset: 2px; }
            .button-secondary { border-color: #4c1d95; background: #fff; color: #4c1d95; }
            .button-secondary:hover { background: #f5f3ff; }
            input { min-height: 44px; width: 100%; border: 1px solid #c4b5fd; border-radius: 8px; padding: 9px 11px; background: #fff; font: inherit; color: #24103d; }
            input:focus { border-color: #c026d3; outline: 3px solid #f3e8ff; }
            .error { margin-top: 6px; color: #b42318; font-size: 14px; }
            .status { display: inline-block; border-radius: 999px; padding: 4px 9px; background: #ede9fe; color: #4c1d95; font-size: 12px; font-weight: 700; text-transform: capitalize; }
            .status-delivered { background: #ecfdf3; color: #027a48; }
            .status-cancelled { background: #fef3f2; color: #b42318; }
            .status-picked-up, .status-ready-for-pickup { background: #fffaeb; color: #b54708; }
            @media (max-width: 720px) { .shell { padding: 20px 12px; } .topbar { align-items: flex-start; flex-direction: column; } }
        </style>
        @stack('styles')
    </head>
    <body>
        <main class="shell">
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}" aria-label="Order Service home">
                    <img src="{{ asset('images/growth-loop-logo.png') }}" alt="">
                    <span>Order Service</span>
                </a>
                @auth
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="button-secondary" type="submit">Sign out</button>
                    </form>
                @else
                    <a class="button button-secondary" href="{{ route('admin.orders.index') }}">Admin</a>
                @endauth
            </header>

            @yield('content')
        </main>
    </body>
</html>
