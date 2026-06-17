<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Dashboard Rekapitulasi'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body { font-family: 'Inter', sans-serif; }
            
            /* Sidebar transitions */
            .sidebar-expanded { width: 260px; }
            .sidebar-collapsed { width: 72px; }
            .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
            
            /* Content offset */
            .content-offset-expanded { margin-left: 260px; }
            .content-offset-collapsed { margin-left: 72px; }
            .content-transition { transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
            
            /* Custom scrollbar */
            .scrollbar-thin::-webkit-scrollbar { width: 4px; }
            .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
            .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.2); border-radius: 9999px; }
            .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: rgba(100,116,139,0.4); }
            
            /* Overlay */
            .sidebar-overlay {
                background: rgba(15, 23, 42, 0.5);
                backdrop-filter: blur(4px);
            }

            @media (max-width: 1023px) {
                .content-offset-expanded, .content-offset-collapsed { margin-left: 0; }
            }
        </style>
    </head>
    <body class="bg-[#F4F1EF] text-slate-800 antialiased min-h-screen font-sans" x-data="sidebarApp()">
        
        {{-- ==================== SIDEBAR ==================== --}}
        
        {{-- Mobile Overlay --}}
        <div x-show="mobileOpen" x-transition.opacity class="fixed inset-0 z-40 sidebar-overlay lg:hidden" @click="mobileOpen = false"></div>

        <aside :class="[
                sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded',
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
            class="fixed top-0 left-0 h-full z-50 bg-slate-900 text-white flex flex-col sidebar-transition transform overflow-hidden shadow-2xl">
            
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-700/50 flex-shrink-0">
                <img src="{{ asset('images/dkk_logo.png') }}" alt="Logo" class="h-9 w-9 object-contain flex-shrink-0 rounded-lg">
                <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="overflow-hidden">
                    <h1 class="text-sm font-bold text-white leading-tight truncate">Dashboard</h1>
                    <p class="text-[10px] text-slate-400 leading-tight truncate">Rekapitulasi Penyakit</p>
                </div>
            </div>
            
            {{-- Navigation --}}
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto scrollbar-thin">
                <p x-show="!sidebarCollapsed" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Menu</p>
                
                <a href="{{ route('recap.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('recap.*') ? 'bg-red-600/90 text-white shadow-lg shadow-red-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   :class="sidebarCollapsed ? 'justify-center' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="truncate">Rekapitulasi</span>
                </a>
                
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('profile.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   :class="sidebarCollapsed ? 'justify-center' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="truncate">Profil</span>
                </a>
            </nav>
            
            {{-- Sidebar Actions --}}
            <div class="px-3 py-3 border-t border-slate-700/50 space-y-2 flex-shrink-0">
                @if(request()->routeIs('recap.*'))
                    <button @click="$dispatch('open-aggregate-modal'); mobileOpen = false" 
                        class="flex items-center gap-2 w-full px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 bg-slate-700/80 hover:bg-slate-700 text-slate-200"
                        :class="sidebarCollapsed ? 'justify-center' : ''"
                        :title="sidebarCollapsed ? 'Agregasi Manual' : ''">
                        <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"></path></svg>
                        <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="truncate">Agregasi Manual</span>
                    </button>
                    <button @click="$dispatch('open-export-modal'); mobileOpen = false" 
                        class="flex items-center gap-2 w-full px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-900/30"
                        :class="sidebarCollapsed ? 'justify-center' : ''"
                        :title="sidebarCollapsed ? 'Export Laporan' : ''">
                        <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="truncate">Export Laporan</span>
                    </button>
                @endif
            </div>
            
            {{-- User Profile --}}
            <div class="px-3 py-3 border-t border-slate-700/50 flex-shrink-0">
                <div class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-slate-800 transition-colors cursor-pointer"
                     :class="sidebarCollapsed ? 'justify-center' : ''"
                     x-data="{ showUserMenu: false }" @click="showUserMenu = !showUserMenu" @click.away="showUserMenu = false">
                    <img class="h-8 w-8 rounded-full border-2 border-slate-600 object-cover flex-shrink-0" 
                         src="https://ui-avatars.com/api/?name={{ Auth::user()->name ?? 'Admin' }}&background=1e293b&color=e2e8f0&bold=true" alt="Avatar">
                    <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                    </div>
                    
                    {{-- User dropdown --}}
                    <div x-show="showUserMenu" x-transition 
                         class="absolute bottom-full left-3 mb-2 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden z-50" 
                         @click.stop>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-slate-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ==================== MAIN CONTENT ==================== --}}
        <div :class="sidebarCollapsed ? 'content-offset-collapsed' : 'content-offset-expanded'" 
             class="min-h-screen flex flex-col content-transition">
            
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-slate-200/80 h-14 flex items-center px-4 lg:px-6 gap-4 shadow-sm">
                {{-- Mobile menu button --}}
                <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                
                {{-- Collapse toggle (desktop) --}}
                <button @click="toggleSidebar()" class="hidden lg:flex p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Toggle sidebar">
                    <svg class="w-5 h-5 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
                
                {{-- Breadcrumb --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-400">Dashboard</span>
                    @if(request()->routeIs('recap.*'))
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="text-slate-700 font-semibold">Rekapitulasi</span>
                    @elseif(request()->routeIs('profile.*'))
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="text-slate-700 font-semibold">Profil</span>
                    @endif
                </div>
                
                <div class="flex-1"></div>
                
                {{-- Right side --}}
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400 hidden sm:block">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </header>
            
            {{-- Page Content --}}
            <main class="flex-grow w-full">
                @if(isset($slot))
                    {{ $slot }}
                @else
                    <div class="p-4 lg:p-6">
                        {{-- Notifications --}}
                        @if (session('success'))
                            <div class="mb-6 flex items-center p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl shadow-sm">
                                <span class="font-medium text-sm">{{ session('success') }}</span>
                            </div>
                        @endif
                        
                        @yield('content')
                    </div>
                @endif
            </main>
            
            {{-- Footer --}}
            <footer class="border-t border-slate-200/60 mt-auto bg-white/50">
                <div class="py-4 px-4 lg:px-6">
                    <p class="text-center text-xs text-slate-400">
                        &copy; {{ date('Y') }} Dashboard Rekapitulasi Penyakit. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>

        <script>
        function sidebarApp() {
            return {
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                mobileOpen: false,
                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                }
            }
        }
        </script>
        
        @stack('scripts')
    </body>
</html>
