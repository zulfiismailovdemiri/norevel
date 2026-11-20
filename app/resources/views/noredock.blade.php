<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>norevel · noredock status</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <main class="mx-auto max-w-2xl px-6 py-12">
        <p class="text-sm font-semibold uppercase tracking-wide text-stone-500">norevel</p>
        <h1 class="mt-1 text-3xl font-bold">Laravel on noredock</h1>
        <p class="mt-2 text-stone-600">
            Every visit counts in PostgreSQL, puts a job on the queue, and the queue worker sends a mail to Mailpit.
            If this page is styled, Vite is serving the CSS.
        </p>

        <dl class="mt-8 divide-y divide-stone-200 rounded-xl border border-stone-200 bg-white">
            @foreach ($status as $key => $value)
                <div class="flex justify-between gap-6 px-5 py-3 text-sm">
                    <dt class="text-stone-500">{{ str_replace('_', ' ', $key) }}</dt>
                    <dd class="text-right font-mono">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="mt-6 text-sm text-stone-500">
            Mail inbox: the <code>norevel-mail</code> port in <code>noredock compose ps</code>.
            JSON: <code>curl -H 'Accept: application/json' …/noredock</code>
        </p>
    </main>
</body>
</html>
