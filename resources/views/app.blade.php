<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => \App\Support\Theme::appearance() == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="appName" content="{{ \App\Facades\Settings::get(\App\Enums\Preference::PlatformName) }}">

    <script>
        (function() {
            const appearance = '{{ \App\Support\Theme::appearance() }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    <style>
        :root {
            --primary: {{ \App\Support\Theme::primaryLightBackgroundColor() }};
            --primary-foreground: {{ \App\Support\Theme::primaryLightForegroundColor() }};
        }

        .dark {
            --primary: {{ \App\Support\Theme::primaryDarkBackgroundColor() }};
            --primary-foreground: {{ \App\Support\Theme::primaryDarkForegroundColor() }};
        }

        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.141 0.005 285.823);
        }
    </style>

    <title inertia>{{ \App\Facades\Settings::get(\App\Enums\Preference::PlatformName) }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
@inertia
</body>
</html>
