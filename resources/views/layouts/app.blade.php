<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=lora:400,500,600,700|dm-sans:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .font-serif { font-family: 'Lora', Georgia, serif; }
            body { font-family: 'DM Sans', sans-serif; }
        </style>

        @stack('styles') 
    </head>
    <body class="antialiased">
        <div class="min-h-screen bg-stone-50 dark:bg-gray-900">

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                @if ($errors->any())
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-4 flex items-start gap-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl px-4 py-3.5"
                    >
                        <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
                            @endforeach
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600 transition-colors leading-none text-lg">×</button>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-init="setTimeout(() => show = false, 4000)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-4 flex items-center gap-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl px-4 py-3.5"
                    >
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="flex-1 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
                        <button @click="show = false" class="text-red-400 hover:text-red-600 transition-colors leading-none text-lg">×</button>
                    </div>
                @endif

                @if (session('success'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-init="setTimeout(() => show = false, 4000)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-4 flex items-center gap-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-2xl px-4 py-3.5"
                    >
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="flex-1 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
                        <button @click="show = false" class="text-green-400 hover:text-green-600 transition-colors leading-none text-lg">×</button>
                    </div>
                @endif

            </div>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

        </div>
    </body>
</html>