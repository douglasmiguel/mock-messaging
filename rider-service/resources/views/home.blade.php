<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Rider Service</title>
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
                dl { display: grid; gap: 24px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; }
                dt { color: #475569; font-size: 14px; font-weight: 600; }
                dd { margin: 4px 0 0; color: #4c1d95; font-size: 36px; font-weight: 700; }
                dl div:last-child dd { color: #c026d3; }
                section > p { margin: 32px 0 0; color: #334155; font-size: 18px; line-height: 1.8; }
                code { border-radius: 4px; padding: 2px 6px; background: #ddd6fe; color: #24103d; font-size: 14px; }
                @media (max-width: 640px) { body { padding: 32px 16px; } section { padding: 24px; } dl { grid-template-columns: 1fr; } }
            </style>
        @endif
    </head>
    <body class="min-h-screen bg-growth-surface px-4 py-10 text-growth-ink sm:px-6">
        <main class="mx-auto flex max-w-3xl flex-col gap-8">
            <header class="flex items-center gap-4">
                <img class="size-20 shrink-0 object-contain" src="{{ asset('images/growth-loop-logo.png') }}" alt="Growth Loop logo">
                <div>
                    <p class="text-sm font-bold tracking-[0.16em] text-growth-plum uppercase">Mock Messaging</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Rider Service</h1>
                </div>
            </header>

            <section class="rounded-2xl border border-growth-lilac border-t-4 border-t-growth-plum bg-white p-6 shadow-sm sm:p-8">
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-semibold text-slate-600">Available rider records</dt>
                        <dd class="mt-1 text-4xl font-bold text-growth-plum">{{ $riderCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-600">Active assignments</dt>
                        <dd class="mt-1 text-4xl font-bold text-growth-magenta">{{ $activeAssignments }}</dd>
                    </div>
                </dl>
                <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-700">
                    It listens for <code class="rounded bg-growth-lilac px-1.5 py-0.5 font-mono text-sm text-growth-ink">order.ready_for_pickup</code>, selects a free rider, and calls the Order Service to store the external rider ID.
                </p>
            </section>
        </main>
    </body>
</html>
