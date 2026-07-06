<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chat System') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">

<div class="min-h-screen flex">

    {{-- ── Left branding panel ── --}}
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 flex-col justify-between p-12 relative overflow-hidden">

        {{-- Decorative circles --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full"></div>
        <div class="absolute -bottom-32 -right-32 w-[32rem] h-[32rem] bg-white/5 rounded-full"></div>
        <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2"></div>

        {{-- Logo + name --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10">
                    <x-application-logo class="w-full h-full" />
                </div>
                <span class="text-white text-xl font-bold tracking-tight">{{ config('app.name') }}</span>
            </div>
        </div>

        {{-- Central hero text --}}
        <div class="relative z-10 space-y-6">
            {{-- Decorative chat preview --}}
            <div class="space-y-3 mb-8">
                <div class="flex items-end gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">A</div>
                    <div class="bg-white/20 backdrop-blur text-white text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm max-w-xs">
                        Hey! Have you tried the new Chat System? 👋
                    </div>
                </div>
                <div class="flex items-end gap-2 flex-row-reverse">
                    <div class="w-8 h-8 rounded-full bg-purple-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">B</div>
                    <div class="bg-white/30 backdrop-blur text-white text-sm px-4 py-2.5 rounded-2xl rounded-tr-sm max-w-xs">
                        Yes! Real-time messaging is amazing 🚀
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">A</div>
                    <div class="bg-white/20 backdrop-blur text-white text-sm px-4 py-2.5 rounded-2xl rounded-tl-sm max-w-xs">
                        Instant delivery, file sharing & more ✨
                    </div>
                </div>
            </div>

            <h1 class="text-4xl font-bold text-white leading-tight">
                Connect and chat<br>in real time
            </h1>
            <p class="text-indigo-200 text-lg leading-relaxed max-w-sm">
                Send messages, share files, and stay connected with your team — instantly.
            </p>

            {{-- Feature pills --}}
            <div class="flex flex-wrap gap-2 pt-2">
                @foreach(['⚡ Real-time', '📎 File sharing', '🟢 Online status', '✅ Read receipts'] as $feature)
                    <span class="bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full backdrop-blur">
                        {{ $feature }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Bottom tagline --}}
        <div class="relative z-10">
            <p class="text-indigo-300 text-sm">Powered by Laravel Reverb · Real-time WebSockets</p>
        </div>
    </div>

    {{-- ── Right form panel ── --}}
    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 sm:px-12">

        {{-- Mobile logo (shown only on small screens) --}}
        <div class="lg:hidden mb-8 flex flex-col items-center gap-3">
            <div class="w-14 h-14">
                <x-application-logo class="w-full h-full" />
            </div>
            <span class="text-indigo-700 text-2xl font-bold">{{ config('app.name') }}</span>
        </div>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
