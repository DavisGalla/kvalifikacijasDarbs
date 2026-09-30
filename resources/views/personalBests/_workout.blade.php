@php
    $inputClass = 'h-10 px-3 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition';
    $ghostButton = 'h-10 px-4 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition';
    $primaryButton = 'h-10 px-6 text-sm font-semibold rounded-xl bg-gray-800 dark:bg-gray-700 text-white hover:bg-gray-700 dark:hover:bg-gray-600 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150 whitespace-nowrap';
@endphp

{{-- Log a workout: pick a program day, or log a single exercise --}}
<div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-8"
     x-data="{
        mode: {{ $days->isEmpty() ? "'single'" : "'program'" }},
        days: @js($days->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'exercises' => $d->exercises])->values()),
        last: @js($lastWeights),
        day: null,
        rows: [],
        pick(day) {
            this.day = day;
            // Prefill each exercise with the weights used last time, so only changes need typing.
            this.rows = day.exercises.map(name => ({ name, sets: (this.last[name] ?? ['']).map(w => String(w + 0)) }));
        },
        addSet(row) { if (row.sets.length < 100) row.sets.push(row.sets[row.sets.length - 1] ?? ''); },
        removeSet(row) { if (row.sets.length > 1) row.sets.pop(); },
        single: @js(collect(old('set_weights', ['']))->values()->all()),
     }">
    <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500">Log working weights</p>
        <div class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 p-0.5 text-xs font-semibold">
            <button type="button" @click="mode = 'program'"
                    :class="mode === 'program' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                    class="rounded-full px-3 py-1.5 transition">Program day</button>
            <button type="button" @click="mode = 'single'"
                    :class="mode === 'single' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                    class="rounded-full px-3 py-1.5 transition">Single exercise</button>
        </div>
    </div>

    {{-- Program day mode --}}
    <div x-show="mode === 'program'" class="px-6 py-5">
        @if ($days->isEmpty())
            <x-empty-state icon="🗓️" class="py-6" message="Set up your training program (e.g. Push / Pull / Legs) below, then log a whole day in one go." />
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Which day was it?</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="d in days" :key="d.id">
                    <button type="button" @click="pick(d)"
                            :class="day && day.id === d.id ? 'bg-amber-500 border-amber-500 text-white' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition" x-text="d.name"></button>
                </template>
            </div>

            <form x-show="day" x-cloak method="POST" action="{{ route('workout-logs.session') }}" class="mt-6">
                @csrf
                <input type="hidden" name="day_name" :value="day ? day.name : ''">

                <div class="mb-4">
                    <input type="date" name="performed_on" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="{{ $inputClass }}">
                </div>

                <div class="space-y-5">
                    <template x-for="(row, i) in rows" :key="row.name">
                        <div class="rounded-2xl border border-gray-100 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="row.name"></p>
                                <div class="flex gap-1">
                                    <button type="button" @click="removeSet(row)" x-show="row.sets.length > 1" title="Remove last set"
                                            class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-red-500 transition text-sm">−</button>
                                    <button type="button" @click="addSet(row)" title="Add set"
                                            class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 transition text-sm">+</button>
                                </div>
                            </div>
                            <input type="hidden" :name="`exercises[${i}][name]`" :value="row.name">
                            <div class="mt-3 flex flex-wrap gap-2">
                                <template x-for="(w, s) in row.sets" :key="s">
                                    <label class="block">
                                        <span class="block text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="'Set ' + (s + 1)"></span>
                                        <input type="number" min="0" step="0.5" placeholder="kg" x-model="row.sets[s]"
                                               :name="`exercises[${i}][set_weights][]`" class="w-24 {{ $inputClass }}">
                                    </label>
                                </template>
                            </div>
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500" x-show="last[row.name]">Prefilled from your last session. Clear a weight to skip that set; clear all to skip the exercise.</p>
                        </div>
                    </template>
                </div>

                <div class="mt-5">
                    <button type="submit" class="{{ $primaryButton }}">Log workout</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Single exercise mode --}}
    <form x-show="mode === 'single'" method="POST" action="{{ route('workout-logs.store') }}" class="px-6 py-5">
        @csrf
        <div class="flex flex-wrap gap-3">
            <div class="flex-[2] min-w-[140px]">
                <input type="text" name="exercise" placeholder="Exercise name" list="exercise-suggestions" required
                       value="{{ old('exercise') }}" class="w-full h-11 {{ $inputClass }}">
            </div>
            <div>
                <input type="date" name="performed_on" max="{{ now()->toDateString() }}" value="{{ old('performed_on', now()->toDateString()) }}" class="h-11 {{ $inputClass }}">
            </div>
        </div>

        <div class="mt-4 space-y-2">
            <template x-for="(weight, i) in single" :key="i">
                <div class="flex items-center gap-3">
                    <span class="w-14 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="'Set ' + (i + 1)"></span>
                    <input type="number" name="set_weights[]" x-model="single[i]" placeholder="kg" min="0" step="0.5" required class="w-32 {{ $inputClass }}">
                    <span class="text-xs text-gray-400">kg</span>
                    <button type="button" x-show="single.length > 1" @click="single.splice(i, 1)" title="Remove set"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 transition text-sm">✕</button>
                </div>
            </template>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            {{-- A new set starts at the previous set's weight, since it's usually the same or close --}}
            <button type="button" @click="single.length < 100 && single.push(single[single.length - 1] ?? '')" class="{{ $ghostButton }}">+ Add set</button>
            <button type="submit" class="{{ $primaryButton }}">Log exercise</button>
        </div>
    </form>
</div>

{{-- History grouped by day --}}
@if ($logs->isEmpty())
    <x-empty-state icon="📋" message="Nothing logged yet. Record the weights you train with, set by set, to see your workout history." />
@else
    <div class="space-y-6">
        @foreach ($logs as $date => $entries)
            <section>
                <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ \Carbon\Carbon::parse($date)->isToday() ? 'Today' : \Carbon\Carbon::parse($date)->format('D, M j, Y') }}
                    @if ($dayNames = $entries->pluck('day_name')->filter()->unique()->implode(' · '))
                        <span class="ml-2 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-amber-700 dark:text-amber-400 normal-case tracking-normal">{{ $dayNames }}</span>
                    @endif
                </h2>
                <ul class="overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($entries as $log)
                        <li class="group flex items-center gap-4 px-6 py-4">
                            <p class="flex-1 min-w-0 truncate font-medium text-gray-900 dark:text-gray-100">{{ $log->exercise }}</p>
                            <div class="text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100 tabular-nums font-semibold">
                                    {{ $log->weightSummary() }}
                                    <span class="font-normal text-gray-400">kg</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->setCount() }} {{ Str::plural('set', $log->setCount()) }}</p>
                            </div>
                            <form method="POST" action="{{ route('workout-logs.destroy', $log) }}" onsubmit="return confirm('Delete this entry?')"
                                  class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-400 hover:text-red-500 hover:border-red-300 transition text-sm">✕</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@endif

{{-- My training program --}}
<div class="mt-10 bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-700">
        <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500">My training program</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create days like Push, Pull or Legs with their exercises, then just pick the day when you log.</p>
    </div>

    <div class="px-6 py-5 space-y-3">
        @foreach ($days as $day)
            <details class="group/day rounded-2xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                    <span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $day->name }}</span>
                        <span class="ml-2 text-xs text-gray-400 dark:text-gray-500">{{ count($day->exercises) }} {{ Str::plural('exercise', count($day->exercises)) }}</span>
                    </span>
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">Edit</span>
                </summary>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ implode(' · ', $day->exercises) }}</p>

                <form method="POST" action="{{ route('workout-days.update', $day) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="text" name="name" value="{{ $day->name }}" required maxlength="50" placeholder="Day name" class="w-full {{ $inputClass }}">
                    <textarea name="exercises" rows="5" required placeholder="One exercise per line"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">{{ implode("\n", $day->exercises) }}</textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="{{ $primaryButton }}">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('workout-days.destroy', $day) }}" onsubmit="return confirm('Delete {{ addslashes($day->name) }}? Logged history is kept.')" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">Delete day</button>
                </form>
            </details>
        @endforeach

        <details class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 px-4 py-3" @if ($days->isEmpty() || old('name')) open @endif>
            <summary class="cursor-pointer list-none text-sm font-semibold text-gray-700 dark:text-gray-200">+ Add a program day</summary>
            <form method="POST" action="{{ route('workout-days.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="50" placeholder="Day name, e.g. Push" class="w-full {{ $inputClass }}">
                <textarea name="exercises" rows="5" required placeholder="One exercise per line&#10;Bench press&#10;Overhead press&#10;Triceps pushdown"
                          class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">{{ is_string(old('exercises')) ? old('exercises') : '' }}</textarea>
                <button type="submit" class="{{ $primaryButton }}">Save day</button>
            </form>
        </details>
    </div>
</div>
