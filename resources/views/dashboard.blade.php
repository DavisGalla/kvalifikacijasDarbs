<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-6 sm:p-8 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">
                                Welcome back
                            </p>
                            <h3 class="mt-2 text-2xl sm:text-3xl font-bold leading-tight">
                                {{ auth()->user()->name }}
                            </h3>
                            <p class="mt-3 text-gray-600 dark:text-gray-300 max-w-2xl">
                                Track your sport progress, plan training in Google Calendar, and keep the momentum with the community forum.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('calendar.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">
                                Calendar
                            </a>
                            <a href="{{ route('pbs.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900/10 dark:bg-gray-100/10 border border-gray-200/70 dark:border-gray-700 text-gray-900 dark:text-gray-100 text-sm font-semibold hover:bg-gray-900/15 dark:hover:bg-gray-100/15 transition">
                                Personal Bests
                            </a>
                            <a href="{{ route('blog.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900/10 dark:bg-gray-100/10 border border-gray-200/70 dark:border-gray-700 text-gray-900 dark:text-gray-100 text-sm font-semibold hover:bg-gray-900/15 dark:hover:bg-gray-100/15 transition">
                                Blog
                            </a>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4 lg:grid-cols-3">
                        <div class="lg:col-span-2 rounded-2xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700 p-5">
                            @if(auth()->user()->google_access_token)
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-green-700 dark:text-green-400">Google Calendar connected</p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            Click any day in the calendar to create a training session, then view it later in your event list.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM9 9a1 1 0 012 0v1a1 1 0 11-2 0V9zm2 4a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-red-700 dark:text-red-400">Connect your Google Calendar</p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            You only need to do this once. After that, you can plan training events directly from the app.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="/auth/google"
                                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition">
                                        Connect Google Calendar
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
