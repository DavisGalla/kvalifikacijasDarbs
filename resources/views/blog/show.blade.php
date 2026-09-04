<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900">

        {{-- Post header --}}
        <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-3xl mx-auto px-6 pt-16 pb-12">

                <a href="/blog" class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    All posts
                </a>

                <h1 class="text-4xl sm:text-5xl font-serif font-bold text-gray-900 dark:text-gray-100 leading-tight tracking-tight">
                    {{ $post->title }}
                </h1>

                <div class="mt-5 flex items-center gap-3">
                    <div class="w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center text-xs font-bold text-white">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $post->user->name }}</span>
                    <span class="text-gray-200 dark:text-gray-600">·</span>
                    <span class="text-sm text-gray-400 dark:text-gray-500">{{ $post->created_at->format('M j, Y') }}</span>
                </div>

                @if(auth()->id() === $post->user_id)
                    <div class="mt-6">
                        <form method="POST" action="{{ route('blog.destroy', $post->id) }}" onsubmit="return confirm('Delete this post?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m10 0a1 1 0 0 0-1-1h-1M10 6a1 1 0 0 0-1 1m0 0h6"/>
                                </svg>
                                Delete Post
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-6 py-12">

            {{-- Post body --}}
            <div class="prose prose-stone dark:prose-invert prose-lg max-w-none font-serif text-gray-700 dark:text-gray-300 leading-relaxed">
                {!! nl2br(e($post->content)) !!}
            </div>

            {{-- Divider --}}
            <div class="my-14 flex items-center gap-4">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            {{-- Comments section --}}
            <div>
                <h2 class="text-xl font-serif font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Comments
                    <span class="text-base font-sans font-normal text-gray-400 dark:text-gray-500 ml-2">({{ $post->comments->count() }})</span>
                </h2>

                @if($post->comments->isEmpty())
                    <p class="text-gray-400 dark:text-gray-500 text-sm italic">No comments yet. Be the first.</p>
                @else
                    <div class="space-y-4">
                        @foreach($post->comments as $comment)
                            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl px-6 py-5">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-500 dark:text-gray-400">
                                            {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                        </div>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $comment->user->name }}</span>
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">·</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>

                                    @if(auth()->id() === $comment->user_id)
                                        <form method="POST" action="{{ route('comments.destroy', $comment->id) }}" onsubmit="return confirm('Delete this comment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center bg-transparent hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400 text-xs font-semibold px-2 py-1 rounded-lg transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add comment --}}
                <div class="mt-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl overflow-hidden">
                    <div class="px-6 pt-6 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Leave a comment</p>
                    </div>
                    <form method="POST" action="{{ route('comments.store') }}" class="px-6 py-5">
                        @csrf
                        <input type="hidden" name="post_id" value="{{ $post->id }}">

                        <textarea
                            name="content"
                            rows="4"
                            placeholder="Write a thoughtful reply..."
                            class="w-full text-sm text-gray-800 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none"
                        ></textarea>

                        <div class="flex justify-end mt-3">
                            <button
                                type="submit"
                                class="bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold px-6 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150">
                                Post Comment
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>