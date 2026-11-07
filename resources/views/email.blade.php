<!doctype html>
<html lang="en">
<body>
    <h1>{{ config('app.name') }} feedback</h1>
    <p>From: {{ $userName }} ({{ $userEmail }})</p>
    <p>Type: {{ $feedback->type }}</p>
    @if ($feedback->pageUrl)
        <p>Page: {{ $feedback->pageUrl }}</p>
    @endif
    <p style="white-space: pre-wrap">{{ $feedback->message }}</p>
    @if ($path)
        <p>The submitted screenshot is attached to this email.</p>
    @endif
</body>
</html>
