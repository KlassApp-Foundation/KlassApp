<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parent Dashboard — KlassApp</title>
    <link rel="stylesheet" href="{{ asset('build/assets/tailwind-B_WMBoJS.css') }}">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-3xl px-6 py-16">
        <p class="text-sm font-medium text-emerald-700">KlassApp Parent Portal</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-4 text-slate-600">
            This dashboard is a Phase 2 placeholder. You arrived via a signed magic link from WhatsApp.
            Linked children and cross-school views will ship in a later phase.
        </p>
        <p class="mt-8 text-sm text-slate-500">Signed in as parent #{{ auth()->id() }}.</p>
    </main>
</body>
</html>
