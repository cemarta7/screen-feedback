<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Livewire feedback fixture</title>
    <link rel="stylesheet" href="http://127.0.0.1:5199/frontend/style.css" />
    <script type="module" src="http://127.0.0.1:5199/frontend/livewire.ts"></script>
    @livewireStyles
</head>
<body>
    <main data-page style="padding:32px;background:oklch(96% 0.02 260);color:#111827">
        <h1>Livewire application</h1><p>Screenshot content with modern colors.</p>
        <p data-feedback-exclude>Do not capture this block.</p>
    </main>
    <livewire:screen-feedback />
    @livewireScripts
</body>
</html>
