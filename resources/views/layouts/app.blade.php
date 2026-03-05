<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'RekamPasien'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">
        
        <!-- Condition to either show custom nav or breeze nav -->
        @if(Auth::check() && request()->routeIs('profile.*'))
            @include('layouts.navigation')
        @else
            <!-- Custom Navigation -->
            <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="/" class="flex items-center gap-2 group">
                                    <div class="bg-indigo-600 text-white p-1.5 rounded-lg group-hover:bg-indigo-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </div>
                                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-500">
                                        RekamPasien
                                    </span>
                                </a>
                            </div>
                            
                            <!-- Main Menu Desktop -->
                            <div class="hidden space-x-6 sm:-my-px sm:ml-10 sm:flex">
                                <a href="{{ route('home') }}" class="inline-flex items-center px-2 pt-1 border-b-2 {{ request()->routeIs('home') ? 'border-indigo-600 text-slate-900 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} text-sm transition-all duration-200">
                                    Dashboard
                                </a>
                                <a href="{{ route('pasiens.index') }}" class="inline-flex items-center px-2 pt-1 border-b-2 {{ request()->routeIs('pasiens.*') ? 'border-indigo-600 text-slate-900 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} text-sm transition-all duration-200">
                                    Daftar Pasien
                                </a>
                            </div>
                        </div>
                        
                        <!-- User Profile / Actions -->
                        <div class="hidden sm:ml-6 sm:flex sm:items-center">
                            @auth
                            <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 mr-4">Profile</a>
                            @endauth
                            <div class="ml-4 flex items-center gap-3">
                                <img class="h-8 w-8 rounded-full border border-slate-200 object-cover" src="https://ui-avatars.com/api/?name={{ Auth::user()->name ?? 'Admin' }}&background=eff6ff&color=4f46e5" alt="Avatar">
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        @endif

        <!-- Page Heading (Breeze) -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="flex-grow w-full {{ isset($slot) ? '' : 'py-8' }}">
            @if(isset($slot))
                {{ $slot }}
            @else
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Notifications -->
                    @if (session('success'))
                        <div class="mb-6 flex items-center p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl shadow-sm animate-fade-in-down">
                            <span class="font-medium text-sm">{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            @endif
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-slate-500">
                    &copy; {{ date('Y') }} Aplikasi Rekam Medis. All rights reserved.
                </p>
            </div>
        </footer>
    </body>
</html>
