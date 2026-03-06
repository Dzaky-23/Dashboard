<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RekamPasien') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased font-sans">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <!-- Logo Section -->
            <div class="mb-6 text-center">
                <a href="/" class="inline-flex items-center gap-2 group justify-center">
                    <img src="{{ asset('images/dkk_logo.png') }}" alt="Logo" class="h-24 w-auto object-contain">
                </a>
            </div>

            <!-- Form Card Wrapper -->
            <div class="w-full sm:max-w-md mt-2 px-8 py-8 bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-100 sm:rounded-3xl">
                {{ $slot }}
            </div>
            
            <div class="mt-8 text-center text-sm text-slate-400">
                &copy; {{ date('Y') }} Aplikasi Rekam Medis. All rights reserved.
            </div>
        </div>
    </body>
</html>
