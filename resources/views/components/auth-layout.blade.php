@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
</head>

<body>
    <main>
        @isset($title)
            <h1>{{ $title }}</h1>
        @endisset

        <x-auth.status />
        <x-auth.errors />

        {{ $slot }}
    </main>
</body>

</html>
