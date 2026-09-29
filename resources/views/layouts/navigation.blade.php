{{-- Mobile overlay --}}
<div x-show="mobileNavOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/60 lg:hidden"
     @click="mobileNavOpen = false"
     style="display: none;"></div>

{{-- Mobile drawer --}}
<aside x-show="mobileNavOpen"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 lg:hidden"
       style="display: none;">
    @include('layouts.sidebar-nav')
</aside>

{{-- Desktop sidebar --}}
<aside class="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800">
    @include('layouts.sidebar-nav')
</aside>
