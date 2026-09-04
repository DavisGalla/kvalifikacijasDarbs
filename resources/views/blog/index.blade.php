<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900">

        <div class="max-w-4xl mx-auto px-6 py-14">

            {{-- Page title + New Post button --}}
            <div class="mb-12 flex items-end justify-between">
                <div>
                    <h1 class="text-5xl font-serif font-bold text-gray-900 dark:text-gray-100 leading-tight tracking-tight">Latest Posts</h1>
                    <div class="mt-3 h-px w-16 bg-amber-400"></div>
                </div>
                <a href="/blog/create"
                    class="inline-flex items-center gap-2 bg-gray-800 dark:bg-gray-700 text-white text-sm font-medium px-5 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Post
                </a>
            </div>

            {{-- Post list --}}
            <div class="space-y-px">
                @foreach($posts as $post)
                    <a href="/blog/{{ $post->id }}"
                        class="group flex items-start justify-between gap-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-7 py-6 hover:border-gray-300 dark:hover:border-gray-500 hover:shadow-sm transition-all duration-200 first:rounded-t-2xl last:rounded-b-2xl">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-serif font-semibold text-gray-900 dark:text-gray-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150 truncate">
                                {{ $post->title }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500 font-medium">by {{ $post->user->name }}</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-amber-500 group-hover:translate-x-1 transition-all duration-200 mt-1 shrink-0"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>