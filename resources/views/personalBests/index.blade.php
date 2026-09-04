<x-app-layout>

    <div class="min-h-screen bg-stone-50 dark:bg-gray-900">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

            {{-- Page heading --}}
            <div class="mb-10">
                <h1 class="text-5xl font-serif font-bold text-gray-900 dark:text-gray-100 leading-tight tracking-tight">
                    Personal Bests
                </h1>
                <div class="mt-3 h-px w-16 bg-amber-400"></div>
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">Track your strongest lifts and watch them grow.</p>
            </div>

            {{-- Add new PB card --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-8">
                <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500">Add new PB</p>
                </div>
                <form method="POST" action="{{ route('pbs.store') }}" class="px-6 py-5">
                    @csrf
                    <div class="flex flex-wrap gap-3">
                        <div class="flex-[2] min-w-[140px]">
                            <input
                                type="text"
                                name="exercise"
                                placeholder="Exercise name"
                                value="{{ old('exercise') }}"
                                list="exercise-suggestions"
                                required
                                class="w-full h-11 px-4 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                            />
                            @error('exercise')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex-1 min-w-[90px]">
                            <input
                                type="number"
                                name="weight"
                                placeholder="kg"
                                value="{{ old('weight') }}"
                                min="0"
                                step="0.5"
                                required
                                class="w-full h-11 px-4 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                            />
                            @error('weight')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="h-11 px-6 text-sm font-semibold rounded-xl bg-gray-800 dark:bg-gray-700 text-white hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150 whitespace-nowrap"
                        >
                            + Add
                        </button>
                    </div>
                </form>
            </div>

            {{-- PB list --}}
            @if ($bests->isEmpty())
                <div class="text-center py-20">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4 text-2xl">🏋️</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500">No PBs yet — add your first one above.</p>
                </div>
            @else
                <div class="flex flex-col gap-px">
                    @foreach ($bests as $best)
                        <div
                            class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-6 py-5 first:rounded-t-2xl last:rounded-b-2xl group transition-all duration-150 hover:border-gray-300 dark:hover:border-gray-500 hover:shadow-sm"
                            x-data="{ editing: false }"
                        >
                            {{-- View mode --}}
                            <div class="flex items-center gap-4" x-show="!editing" x-transition>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $best->exercise }}</p>
                                </div>

                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-serif font-bold text-gray-900 dark:text-gray-100">{{ $best->weight + 0 }}</span>
                                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500">kg</span>
                                </div>

                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    <button
                                        @click="editing = true"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-400 dark:hover:border-gray-400 transition text-sm"
                                        title="Edit"
                                    >✎</button>
                                    <form method="POST" action="{{ route('pbs.destroy', $best) }}"
                                        onsubmit="return confirm('Delete this PB?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-400 hover:text-red-500 hover:border-red-300 dark:hover:border-red-500 transition text-sm"
                                            title="Delete"
                                        >✕</button>
                                    </form>
                                </div>
                            </div>

                            {{-- Edit mode --}}
                            <form method="POST" action="{{ route('pbs.update', $best) }}" x-show="editing" x-transition>
                                @csrf
                                @method('PATCH')
                                <div class="flex items-center gap-3 flex-wrap">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 flex-1 min-w-0 truncate">{{ $best->exercise }}</p>
                                    <input
                                        type="number"
                                        name="weight"
                                        value="{{ $best->weight + 0 }}"
                                        min="0"
                                        step="0.5"
                                        required
                                        class="w-24 h-9 px-3 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                                    />
                                    <button
                                        type="submit"
                                        class="h-9 px-4 text-sm font-semibold rounded-xl bg-gray-800 dark:bg-gray-700 text-white hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150"
                                    >Save</button>
                                    <button
                                        type="button"
                                        @click="editing = false"
                                        class="h-9 px-3 text-sm rounded-xl border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-400 transition"
                                    >Cancel</button>
                                </div>
                            </form>

                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>