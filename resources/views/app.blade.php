<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PII Redactor Admin</title>
    <script>
        window.PII_REDACTOR_ADMIN = @json($config);
    </script>
    @foreach (($assets['css'] ?? []) as $cssUrl)
        <link rel="stylesheet" href="{{ $cssUrl }}">
    @endforeach
    @if ($assets['js'] ?? null)
        <script type="module" src="{{ $assets['js'] }}"></script>
    @endif
</head>
<body>
    <div id="pii-redactor-admin-root"></div>
</body>
</html>
