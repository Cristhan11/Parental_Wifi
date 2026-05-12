@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0 mt-2',
    'top' => 'origin-top mt-2',
    // Opens above the trigger; used when the trigger sits at the bottom of a
    // container (e.g. the sidebar user menu) and a downward menu would be clipped.
    'left-up' => 'ltr:origin-bottom-left rtl:origin-bottom-right start-0 bottom-full mb-2',
    'up' => 'ltr:origin-bottom-right rtl:origin-bottom-left end-0 bottom-full mb-2',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0 mt-2',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="
            open = ! open;
            if (open) {
                $nextTick(() => {
                    $refs.dropdownPanel?.scrollIntoView({ block: 'nearest', behavior: 'smooth', inline: 'nearest' });
                });
            }
        ">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-ref="dropdownPanel"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
