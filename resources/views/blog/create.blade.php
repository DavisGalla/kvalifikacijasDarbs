<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">

        <div class="w-full max-w-2xl">

            {{-- Back link --}}
            <a href="/blog" class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to posts
            </a>

            {{-- Card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">

                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">Write a post</h1>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Share your thoughts with the world.</p>
                </div>

                <form method="POST" action="{{ route('blog.store') }}" class="px-8 py-8 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Title</label>
                        <input
                            name="title"
                            placeholder="Give your post a title..."
                            class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-lg font-serif focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Content</label>
                        <textarea
                            name="content"
                            placeholder="Write something worth reading..."
                            rows="10"
                            class="w-full px-4 py-3 text-gray-800 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-base leading-relaxed focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none"
                        ></textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="bg-gray-800 dark:bg-gray-700 text-white font-semibold text-sm px-7 py-3 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150">
                            Publish Post
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>