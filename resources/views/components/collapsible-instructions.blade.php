@props([
    'innerClass' => 'mt-3 rounded-md border border-yellow-100 bg-yellow-50 px-4 py-3 text-sm text-gray-700',
])

<div {{ $attributes->merge(['class' => 'mb-4']) }} x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl border border-amber-200/90 bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-100/70 px-4 py-2.5 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-black/5 transition hover:border-amber-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2 sm:w-auto sm:justify-start"
    >
        <span class="flex shrink-0 items-center text-gray-600 group-hover:text-gray-800" aria-hidden="true">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
        </span>
        <span class="min-w-0 flex-1 text-left sm:flex-none" x-text="open ? 'Hide instructions' : 'Show instructions'"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 ease-out group-hover:text-gray-700" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="{{ $innerClass }}"
    >
        {{ $slot }}
    </div>
</div>
