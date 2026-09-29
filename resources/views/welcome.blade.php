<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SportWeb') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md border-b border-gray-200/70 dark:border-gray-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 text-lg font-bold tracking-tight">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    SportWeb
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center px-4 py-2 rounded-full bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-semibold shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-150">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-4 py-2 rounded-full bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-semibold shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-150">
                            Log in with Google
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                    <div class="absolute -top-40 -right-40 w-[36rem] h-[36rem] rounded-full bg-gradient-to-br from-amber-200/40 via-indigo-200/30 to-transparent dark:from-amber-500/10 dark:via-indigo-500/10 blur-3xl"></div>
                </div>

                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        {{-- Left: copy --}}
                        <div>
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                                Track. Plan. Improve.
                            </span>
                            <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold leading-[1.05] tracking-tight">
                                Keep track of your
                                <span class="bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent">sport progress</span>
                                in one place.
                            </h1>
                            <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                                SportWeb helps you record personal bests, plan your training sessions in Google Calendar,
                                organize teams and competitions, and stay motivated by sharing updates with the community.
                            </p>
                            <div class="mt-10 flex flex-wrap items-center gap-4">
                                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-semibold shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-150">
                                    Get started
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                                <a href="{{ route('competitions.index') }}"
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-gray-200 dark:border-gray-700 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors duration-150">
                                    Browse competitions
                                </a>
                            </div>
                        </div>

                        {{-- Right: mock product visual --}}
                        <div class="hidden lg:block relative h-[26rem]">
                            {{-- Back card: PB trend --}}
                            <div class="absolute top-2 right-4 w-64 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl p-5 -rotate-6">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Bench Press</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">102.5 <span class="text-sm font-medium text-gray-400">kg</span></p>
                                <svg class="mt-3 w-full h-12" viewBox="0 0 200 50" fill="none">
                                    <polyline points="0,40 30,35 60,38 90,22 120,26 150,10 200,4" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                                </svg>
                                <span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-green-600 dark:text-green-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 15 7-7 7 7"/></svg>
                                    +7.5kg this month
                                </span>
                            </div>

                            {{-- Front card: competition --}}
                            <div class="absolute bottom-2 left-2 w-72 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl p-5 rotate-3">
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Athletics</span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Open
                                    </span>
                                </div>
                                <p class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">City Marathon</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sat, Oct 18 · Riverside Park</p>
                                <div class="mt-4 flex items-center -space-x-2">
                                    <span class="w-7 h-7 rounded-full bg-amber-400 border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-bold text-white">A</span>
                                    <span class="w-7 h-7 rounded-full bg-indigo-400 border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-bold text-white">L</span>
                                    <span class="w-7 h-7 rounded-full bg-emerald-400 border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-bold text-white">M</span>
                                    <span class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-800 border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-semibold text-gray-500">+12</span>
                                </div>
                            </div>

                            {{-- Floating badge --}}
                            <div class="absolute top-32 left-0 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-semibold px-3 py-2 shadow-lg rotate-3">
                                🏆 New PB unlocked
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Feature cards --}}
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
                <div class="grid gap-6 md:grid-cols-3">
                    <article class="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-7 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold">Progress Tracking</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Save your personal bests and monitor how your performance improves over time.
                        </p>
                        <a href="{{ auth()->check() ? route('pbs.index') : route('login') }}"
                           class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600 dark:text-amber-400 group-hover:gap-2.5 transition-all duration-150">
                            View PB section
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </article>

                    <article class="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-7 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                            </svg>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold">Training Planner</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Plan workouts with Google Calendar so your schedule and goals stay aligned.
                        </p>
                        <a href="{{ auth()->check() ? route('calendar.index') : route('login') }}"
                           class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 dark:text-indigo-400 group-hover:gap-2.5 transition-all duration-150">
                            Open calendar tools
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </article>

                    <article class="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-7 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m5-2.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-2a4 4 0 1 0 0-8m-14 8a4 4 0 1 1 0-8" />
                            </svg>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold">Forum & Community</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Share progress, discuss training ideas, and learn from other athletes.
                        </p>
                        <a href="{{ auth()->check() ? route('blog.index') : route('login') }}"
                           class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400 group-hover:gap-2.5 transition-all duration-150">
                            Visit forum
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </article>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
