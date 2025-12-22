<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Parental WiFi</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <!-- Google Fonts - Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
        .font-montserrat {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-[#FFFFCC] flex items-center justify-center font-montserrat">

    <div class="w-full min-h-screen flex flex-col-reverse lg:flex-row">
        
        <!-- Left: Login Form -->
        <div class="flex-1 flex items-center justify-center p-6 bg-[#FFFFCC]">
            <div class="w-full max-w-md bg-white rounded-xl border-4 border-[#FFDE15] p-8 shadow-xl">
                
                <!-- Logo Section -->
                <div class="flex justify-center mb-6">
                    <x-application-logo class="w-16 h-16 fill-current" style="color: #FFDE15;" />
                </div>
                
                <h2 class="text-3xl font-extrabold text-center text-black mb-2 font-montserrat">WELCOME</h2>
                <p class="text-center text-gray-600 font-semibold mb-6 font-montserrat">Please log-in to continue</p>

                <!-- Session Status Messages -->
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 border-2 border-green-400 text-green-700 rounded-lg text-sm font-semibold">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border-2 border-red-400 text-red-700 rounded-lg text-sm">
                        <strong class="font-bold">Error:</strong>
                        <ul class="list-disc list-inside mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-black mb-2 font-montserrat">Email</label>
                        <div class="flex items-center border-2 border-[#FFDE15] rounded-full px-4 py-2 focus-within:ring-2 focus-within:ring-[#FFDE15] focus-within:ring-offset-2 transition">
                            <i data-feather="mail" class="text-[#FFDE15] mr-3 w-5 h-5"></i>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="username"
                                placeholder="Enter your email"
                                class="w-full bg-transparent outline-none text-black placeholder-gray-400 font-montserrat"
                            />
                        </div>
                        @error('email')
                            <p class="text-red-600 text-sm mt-1 ml-3 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-black mb-2 font-montserrat">Password</label>
                        <div class="flex items-center border-2 border-[#FFDE15] rounded-full px-4 py-2 focus-within:ring-2 focus-within:ring-[#FFDE15] focus-within:ring-offset-2 transition">
                            <i data-feather="lock" class="text-[#FFDE15] mr-3 w-5 h-5"></i>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full bg-transparent outline-none text-black placeholder-gray-400 font-montserrat"
                            />
                        </div>
                        @error('password')
                            <p class="text-red-600 text-sm mt-1 ml-3 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <label for="remember_me" class="inline-flex items-center">
                            <input 
                                id="remember_me" 
                                type="checkbox" 
                                class="rounded border-gray-300 w-4 h-4" 
                                style="accent-color: #FFDE15;" 
                                name="remember"
                            />
                            <span class="ms-2 text-sm text-black font-semibold font-montserrat">Remember me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-[#FFDE15] hover:bg-[#FFC107] text-black font-extrabold py-3 rounded-full shadow-lg transition-all duration-200 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#FFDE15] focus:ring-offset-2 font-montserrat uppercase tracking-wide"
                    >
                        LOG IN
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Branding Section -->
        <div class="flex-1 bg-gradient-to-br from-[#FFDE15] to-[#FFC107] text-black flex flex-col items-center justify-center p-8 text-center">
            <x-application-logo class="w-32 h-32 fill-current mb-8" style="color: #000000;" />
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold leading-tight mb-4 font-montserrat uppercase tracking-tight">
                PARENTAL WIFI
            </h1>
            <p class="text-lg sm:text-xl md:text-2xl font-bold mb-6 font-montserrat">
                Parental Control & Internet Management System
            </p>
            <p class="text-base sm:text-lg font-semibold text-gray-800 font-montserrat max-w-md">
                Manage your children's internet access, monitor usage, and ensure safe online experiences.
            </p>
        </div>
    </div>

    <!-- Initialize Feather Icons -->
    <script>
        feather.replace();
    </script>
</body>
</html>
