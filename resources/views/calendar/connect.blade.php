<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-lg">
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Connect Google Calendar</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional. Competitions, teams, workouts and the blog all work without it.</p>
                </div>

                <div class="px-8 py-8 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">What connecting does</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li>Adds competitions you register for to your calendar, and removes them when you withdraw.</li>
                            <li>Shows your upcoming events on the dashboard and calendar page.</li>
                            <li>Lets you add training sessions to your calendar from this site.</li>
                        </ul>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">What Google will ask for</p>
                        <p class="mt-2">
                            Permission to see and edit events in your Google Calendar. Only events in your primary calendar
                            are read or changed, and only for the features above. Your access tokens are stored encrypted.
                        </p>
                    </div>

                    <p>
                        You can disconnect at any time from the calendar page; the site's access is then revoked at Google as well.
                        If you cancel or untick calendar access on Google's screen, nothing is connected and you return here.
                    </p>

                    <div class="flex items-center gap-3 pt-2">
                        <a href="{{ route('google.calendar.redirect') }}"
                           class="inline-flex items-center gap-2 bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600">
                            Continue to Google
                        </a>
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-gray-600 dark:text-gray-400 hover:underline">Not now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
