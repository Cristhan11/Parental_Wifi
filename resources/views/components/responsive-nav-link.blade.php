@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium text-black focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium hover:text-black hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-black focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
$activeStyle = ($active ?? false) ? 'border-color: #FFDE15; background-color: #FFFFCC40;' : '';
$inactiveStyle = ($active ?? false) ? '' : 'color: #00000080;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $activeStyle }}{{ $inactiveStyle }}">
    {{ $slot }}
</a>
