<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SportWeb') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <div class="min-h-screen">
        <header class="border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="text-xl font-semibold tracking-tight">
                    SportWeb
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="px-4 py-2 rounded-lg bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-sm font-medium hover:opacity-90 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Log in with Google
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                <p class="text-sm uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400 font-semibold">
                    Track. Plan. Improve.
                </p>
                <h1 class="mt-4 text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight tracking-tight max-w-4xl">
                    Keep track of your sport progress in one place.
                </h1>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 max-w-3xl leading-relaxed">
                    SportWeb helps you record personal bests, plan your training sessions in Google Calendar,
                    and stay motivated by sharing updates with the community forum.
                </p>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <div class="grid gap-6 md:grid-cols-3">
                    <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <h2 class="text-xl font-semibold">Progress Tracking</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Save your personal bests and monitor how your performance improves over time.
                        </p>
                        <a href="{{ auth()->check() ? route('pbs.index') : route('login') }}"
                           class="inline-block mt-5 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                            View PB section
                        </a>
                    </article>

                    <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <h2 class="text-xl font-semibold">Training Planner</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Plan workouts with Google Calendar so your schedule and goals stay aligned.
                        </p>
                        <a href="{{ auth()->check() ? route('calendar.index') : route('login') }}"
                           class="inline-block mt-5 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                            Open calendar tools
                        </a>
                    </article>

                    <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <h2 class="text-xl font-semibold">Forum & Community</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Share progress, discuss training ideas, and learn from other athletes.
                        </p>
                        <a href="{{ auth()->check() ? route('blog.index') : route('login') }}"
                           class="inline-block mt-5 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                            Visit forum
                        </a>
                    </article>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
