<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Notification Service</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; color: #24103d; background: #fcfaff; }
                * { box-sizing: border-box; }
                body { min-height: 100vh; margin: 0; padding: 40px 16px; background: #fcfaff; }
                main { display: flex; max-width: 48rem; flex-direction: column; gap: 32px; margin: 0 auto; }
                header { display: flex; align-items: center; gap: 16px; }
                header img { width: 80px; height: 80px; object-fit: contain; }
                header p { margin: 0; color: #4c1d95; font-size: 14px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
                h1 { margin: 4px 0 0; font-size: 30px; letter-spacing: -.025em; }
                section { border: 1px solid #ddd6fe; border-top: 4px solid #4c1d95; border-radius: 16px; padding: 32px; background: white; box-shadow: 0 1px 3px rgb(76 29 149 / 8%); }
                section p { margin: 0; color: #334155; font-size: 18px; line-height: 1.8; }
                code { border-radius: 4px; padding: 2px 6px; background: #ddd6fe; color: #24103d; font-size: 14px; }
                @media (max-width: 640px) { body { padding: 32px 16px; } section { padding: 24px; } }
            </style>
        @endif
    </head>
    <body class="min-h-screen bg-growth-surface px-4 py-10 text-growth-ink sm:px-6">
        <main class="mx-auto flex max-w-3xl flex-col gap-8">
            <header class="flex items-center gap-4">
                <img class="size-20 shrink-0 object-contain" src="{{ asset('images/growth-loop-logo.png') }}" alt="Growth Loop logo">
                <div>
                    <p class="text-sm font-bold tracking-[0.16em] text-growth-plum uppercase">Mock Messaging</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Notification Service</h1>
                </div>
            </header>

            <section class="rounded-2xl border border-growth-lilac border-t-4 border-t-growth-plum bg-white p-6 shadow-sm sm:p-8">
                <p class="max-w-2xl text-lg leading-8 text-slate-700">
                    This service consumes <code class="rounded bg-growth-lilac px-1.5 py-0.5 font-mono text-sm text-growth-ink">orders.events</code>, sends email through Mailpit, and hosts secure action links for restaurants and clients.
                </p>
            </section>
        </main>
    </body>
</html>
