<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coming soon</title>
    <style>
        body { font-family: "DM Sans", system-ui, sans-serif; background: #F8FAFC; color: #0F172A; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        main { max-width: 28rem; margin: 24px; padding: 28px; background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        p { margin: 0 0 16px; color: #475569; line-height: 1.45; }
        a { color: #15803D; font-weight: 700; }
    </style>
</head>
<body>
<main data-testid="public-demo-soon">
    <h1>Coming soon</h1>
    <p>{{ $message }}</p>
    <p><a href="{{ url('/admin/dashboard') }}">Back to the dashboard</a></p>
</main>
</body>
</html>
