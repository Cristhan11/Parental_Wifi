<aside x-data="{ 
        open: window.innerWidth >= 1280,
        generalSettingsOpen: {{ request()->routeIs(['blocked-websites.*', 'flagged-websites.*', 'schedules.*']) ? 'true' : 'false' }},
        init() {
            // Listen for state updates from parent component and sync
            window.addEventListener('sidebar-state-updated', (e) => {
                this.open = e.detail.open;
            });
        }
    }" 
       class="fixed left-0 top-0 h-screen w-64 bg-white border-r-4 border-[#FFDE15] shadow-2xl z-30 transform transition-transform duration-300 ease-in-out overflow-y-auto"
       :class="open ? 'translate-x-0' : '-translate-x-full'"
       @click.outside="if (window.innerWidth < 1280 && open) { open = false; $root.sidebarOpen = false; $root.manuallyToggled = false; window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: false } })); }"
       aria-label="Main navigation"
       style="scrollbar-width: thin; scrollbar-color: #FFDE15 #F3F4F6;">
    <!-- Logo Section -->
    <div class="h-16 flex items-center justify-between border-b border-gray-200 px-4">
        <a href="{{ auth()->user()->canAccessParentDashboard() ? route('dashboard') : route('admin.dashboard') }}" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded">
            <x-application-logo class="block w-16 h-auto" />
            <span class="font-semibold text-lg text-black">Parental WiFi</span>
        </a>
        <!-- Close Button (Visible only on smaller screens when sidebar overlaps) -->
        <button @click.stop="open = false; $root.sidebarOpen = false; $root.manuallyToggled = false; window.dispatchEvent(new CustomEvent('sidebar-state-updated', { detail: { open: false } }));" 
                class="xl:hidden p-1 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                aria-label="Close navigation menu">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4" aria-label="Main navigation">
        <ul class="space-y-1 px-3">
            @if (auth()->user()->hasAdminCapability())
            <li>
                <div class="px-4 py-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Parent Owner</span>
                </div>
            </li>
            <li>
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>Parent Owner Home</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.parents.pending') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.parents.pending') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }}">
                    <span>Pending parents</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.parents.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.parents.index') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }}">
                    <span>Parent accounts</span>
                </a>
            </li>
            @endif

            @if (auth()->user()->canAccessParentDashboard())
            <!-- Dashboard -->
            <li>
                <div class="px-4 py-2 mt-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Parent dashboard</span>
                </div>
            </li>
            <li>
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- General Settings Dropdown -->
            <li>
                <button @click="generalSettingsOpen = !generalSettingsOpen" 
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>General Settings</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="generalSettingsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <!-- Dropdown Menu -->
                <ul x-show="generalSettingsOpen" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="mt-1 ml-4 space-y-1">
                    <li>
                        <a href="{{ route('blocked-websites.index') }}" 
                           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('blocked-websites.*') ? 'bg-yellow-100 text-black' : 'text-gray-600 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                           aria-current="{{ request()->routeIs('blocked-websites.*') ? 'page' : 'false' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Blocked Websites</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('flagged-websites.index') }}" 
                           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('flagged-websites.*') ? 'bg-yellow-100 text-black' : 'text-gray-600 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                           aria-current="{{ request()->routeIs('flagged-websites.*') ? 'page' : 'false' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>Flagged Websites</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('schedules.index') }}" 
                           class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('schedules.*') ? 'bg-yellow-100 text-black' : 'text-gray-600 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                           aria-current="{{ request()->routeIs('schedules.*') ? 'page' : 'false' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Schedules</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Child Devices -->
            <li>
                <a href="{{ route('child_devices.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('child_devices.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('child_devices.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Child Devices</span>
                </a>
            </li>

            <!-- Accounts -->
            <li>
                <a href="{{ route('accounts.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('accounts.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('accounts.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Accounts</span>
                </a>
            </li>

            <!-- Reports -->
            <li>
                <a href="{{ route('reports.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Reports</span>
                </a>
            </li>

            <!-- Logs -->
            <li>
                {{--
                    Unified logs entry point.
                    Why: operational monitoring and policy-audit views are now centralized.
                    Connection: route resolves to LogsController@index where stream separation,
                    role scoping, filtering, and export context are all coordinated.
                --}}
                <a href="{{ route('logs.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('logs.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('logs.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Logs</span>
                </a>
            </li>

            <!-- Educational Content -->
            <li>
                <div class="px-4 py-2 mt-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Educational Content</span>
                </div>
            </li>
            <li>
                <a href="{{ route('quizzes.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('quizzes.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('quizzes.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <span>Quiz</span>
                </a>
            </li>
            <li>
                <a href="{{ route('videos.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('videos.*') ? 'bg-yellow-100 text-black' : 'text-gray-700 hover:bg-gray-100 hover:text-black' }} focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                   aria-current="{{ request()->routeIs('videos.*') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Videos</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>

    <!-- User Section -->
    <div class="border-t border-gray-200 p-4">
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <button class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center font-semibold text-black">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 text-left">
                        <div class="font-medium">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ Auth::user()->accountTypeLabel() }}</div>
                    </div>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</aside>
