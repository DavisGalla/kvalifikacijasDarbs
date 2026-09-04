<x-guest-layout>
    <div class="space-y-4 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            Create account with Google
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Registration and sign in are handled through Google OAuth only.
        </p>
        <a href="{{ route('google.redirect') }}"
           class="inline-flex justify-center w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-300 transition">
            Continue with Google
        </a>
    </div>
</x-guest-layout>
