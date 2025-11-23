@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 text-black focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 hover:text-black hover:border-gray-300 focus:outline-none focus:text-black focus:border-gray-300 transition duration-150 ease-in-out';
$activeStyle = ($active ?? false) ? 'border-color: #FFDE15;' : '';
$inactiveStyle = ($active ?? false) ? '' : 'color: #00000080;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $activeStyle }}{{ $inactiveStyle }}">
    {{ $slot }}
</a>
