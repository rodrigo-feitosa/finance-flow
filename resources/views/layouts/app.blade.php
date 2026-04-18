<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <script>
        function updateIcons() {
            const isDark = document.documentElement.classList.contains('dark');

            const icon = document.getElementById('theme-icon');
            const iconMobile = document.getElementById('theme-icon-mobile');

            if (icon) icon.textContent = isDark ? '☀️' : '🌙';
            if (iconMobile) iconMobile.textContent = isDark ? '☀️' : '🌙';
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }

            updateIcons();
        }

        updateIcons();
    </script>
</head>

<body class="flex flex-col min-h-screen bg-gray-300 dark:bg-[#0B0618]">
    <div
        x-data="toastComponent()"
        x-init="init()"
        x-show="show"
        x-transition
        :class="{
        'bg-green-600': type === 'success',
        'bg-red-600': type === 'error'
    }"
        class="fixed bottom-20 sm:bottom-4
           left-1/2 -translate-x-1/2
           sm:left-auto sm:right-5 sm:translate-x-0
           text-white px-4 py-3
           rounded-lg shadow-lg
           w-[90%] sm:w-auto sm:max-w-sm
           text-sm sm:text-base
           z-[9999]"
        data-toast='@json(session("toast"))'>
        <span x-text="message" class="block break-words"></span>
    </div>
    <livewire:header />

    <main class="flex-1 bg-gray-300 dark:bg-[#170F2F]">
        {{ $slot }}
    </main>

    <livewire:footer />

    @livewireScripts
</body>

</html>