<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Expose auth user id so dashboard JS can subscribe to private user.{id} websocket channel. -->
        <meta name="auth-user-id" content="{{ auth()->id() }}">
        <link rel="icon" type="image/png" href="{{ asset('PARENTAL_WIFI_LOGO.png') }}">
        <link rel="shortcut icon" href="{{ asset('PARENTAL_WIFI_LOGO.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Dashboard: Vite-bundled JS/CSS only; fonts from @fontsource in app.css. No CDN (LAN / captive without WAN). --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex bg-[#FFFFCC]" 
             x-data="{ 
                 sidebarOpen: window.innerWidth >= 1280,
                 manuallyToggled: false,
                toggleSidebar() {
                    this.manuallyToggled = true;
                    this.sidebarOpen = !this.sidebarOpen;
                    // Dispatch event to update sidebar
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: this.sidebarOpen } }));
                    });
                },
                 init() {
                     // Watch for window resize to auto-show/hide sidebar (only if not manually toggled)
                     const handleResize = () => {
                         if (!this.manuallyToggled) {
                             if (window.innerWidth >= 1280) {
                                 this.sidebarOpen = true;
                             } else {
                                 this.sidebarOpen = false;
                             }
                             // Dispatch event to update sidebar
                             window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: this.sidebarOpen } }));
                         }
                     };
                     window.addEventListener('resize', handleResize);
                     
                 }
             }">
            <!-- Backdrop (Visible when sidebar is open on smaller screens only) -->
            <div x-show="sidebarOpen && window.innerWidth < 1280" 
                 @click="sidebarOpen = false; manuallyToggled = false; window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: false } }))"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 xl:hidden"
                 style="display: none;"
                 aria-hidden="true"></div>

            <!-- Sidebar Navigation -->
            @include('layouts.sidebar')

            <!-- Hamburger Menu Button (Always visible when sidebar is hidden, or on smaller screens) -->
            <button @click.stop="manuallyToggled = true; sidebarOpen = !sidebarOpen; window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: sidebarOpen } }));" 
                    class="fixed top-4 left-4 z-50 p-2 rounded-md bg-white shadow-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-all duration-200"
                    :class="(sidebarOpen && window.innerWidth >= 1280) ? 'opacity-0 pointer-events-none' : 'opacity-100 pointer-events-auto'"
                    style="display: block;"
                    aria-label="Toggle navigation menu">
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Main Content Area (Margin on xl screens when sidebar is visible) -->
            <div class="flex-1 flex flex-col w-full overflow-x-hidden min-h-screen transition-all duration-300"
                 :class="sidebarOpen && window.innerWidth >= 1280 ? 'xl:ml-64' : ''">
                <!-- Top Header (if needed) -->
                @isset($header)
                    <header class="bg-white shadow-sm border-b border-gray-200">
                        <div class="px-6 py-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <div id="appPageScroll" class="flex-1 min-h-0 overflow-y-auto">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @stack('floating-actions')
        @stack('scripts')
    </body>
</html>
