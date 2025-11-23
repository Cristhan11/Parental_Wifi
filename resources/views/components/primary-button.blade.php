<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest transition ease-in-out duration-150 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2']) }} style="background-color: #FFDE15; focus:ring-color: #FFDE15;">
    {{ $slot }}
</button>
