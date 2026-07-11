<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
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

            document.querySelectorAll('input[type="checkbox"]').forEach(el => {
                el.checked = isDark;
            });
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');

            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }

            updateIcons();
        }

        function sendConsentToServer(consent) {
            fetch("{{ route('cookie-consent') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    consent
                })
            });
        }

        function acceptCookies() {
            localStorage.setItem('cookie_consent', 'accepted');
            document.getElementById('cookie-banner').style.display = 'none';

            sendConsentToServer('accepted');
        }

        function rejectCookies() {
            localStorage.setItem('cookie_consent', 'rejected');
            document.getElementById('cookie-banner').style.display = 'none';

            sendConsentToServer('rejected');
        }

        function syncConsentWithServer() {
            const serverConsent = @json(optional(auth()->user())->cookie_consent);

            if (serverConsent !== null && !localStorage.getItem('cookie_consent')) {
                localStorage.setItem(
                    'cookie_consent',
                    serverConsent ? 'accepted' : 'rejected'
                );
            }
        }

        function checkCookieConsent() {
            const banner = document.getElementById('cookie-banner');

            const isLogged = @json(auth()->check());
            const serverConsent = @json(optional(auth()->user())->cookie_consent);

            // 🔥 REGRA PRINCIPAL
            if (isLogged) {
                if (serverConsent === null) {
                    banner.style.display = 'flex'; // mostra banner
                } else {
                    banner.style.display = 'none'; // já decidiu
                }
                return;
            }

            // 👇 fallback para usuário não logado
            const consent = localStorage.getItem('cookie_consent');

            if (consent) {
                banner.style.display = 'none';
            } else {
                banner.style.display = 'flex';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Tema
            const savedTheme = localStorage.getItem('theme');

            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            updateIcons();

            // Cookies (🔥 ordem importa)
            syncConsentWithServer();
            checkCookieConsent();
        });

        // Aplicação imediata do tema (evita flicker)
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
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

    <div id="cookie-banner"
        class="fixed bottom-0 left-0 w-full z-[9999]
           bg-gray-900 text-white
           dark:bg-[#0B0618]
           p-4 flex flex-col sm:flex-row
           gap-3 sm:gap-4
           items-center justify-between">

        <span class="text-sm sm:text-base">
            Usamos cookies para melhorar sua experiência no sistema.
        </span>

        <div class="flex gap-2">
            <button onclick="acceptCookies()"
                class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded text-sm">
                Aceitar
            </button>

            <button onclick="rejectCookies()"
                class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">
                Recusar
            </button>
        </div>
    </div>
    <livewire:header />

    <main class="flex-1 bg-gray-300 dark:bg-[#170F2F]">
        {{ $slot }}
    </main>

    <livewire:footer />

    @livewireScripts
</body>

</html>
