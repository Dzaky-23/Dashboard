<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $title ?? __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p>{{ $message ?? __("You're logged in!") }}</p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Role: {{ auth()->user()->role }}</p>
@extends('layouts.app')

@section('title', 'Dashboard - RekamPasien')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Selamat Datang, Admin!</h1>
    <p class="mt-2 text-base text-slate-600">Berikut adalah ringkasan aktivitas pencatatan pasien.</p>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat 1: Total -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pasien</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalPasien) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 2: New Today -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Pasien Hari Ini</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($pasienBaruToday) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 3: BPJS -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-teal-50 p-3 text-teal-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Pasien BPJS</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalBPJS) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 4: Umum -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-orange-50 p-3 text-orange-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Pasien Umum</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalUmum) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent (Layout Split) -->
<div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
    <!-- Main Content Area -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow-sm ring-1 ring-slate-100 rounded-2xl overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Pasien Terbaru</h3>
                <a href="{{ route('pasiens.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                    Lihat semua &rarr;
                </a>
            </div>
            <div class="p-6">
                <!-- Empty state placeholder inside modern card -->
                <div class="text-center py-10">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum Ada Data Terkini</h3>
                    <p class="mt-1 text-sm text-slate-500">Fitur daftar pasien terkini akan muncul di sini.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar actions -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-indigo-600 rounded-2xl p-6 shadow-md text-white">
            <h3 class="text-lg font-semibold">Tindakan Cepat</h3>
            <p class="mt-2 text-indigo-100 text-sm">Daftarkan pasien baru yang datang hari ini.</p>
            <a href="{{ route('pasiens.create') }}" class="mt-6 w-full inline-flex justify-center items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-slate-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Daftar Pasien Baru
            </a>
        </div>
        
        <!-- Helpful Info Card -->
        <div class="bg-white shadow-sm ring-1 ring-slate-100 rounded-2xl p-6">
            <h4 class="text-sm font-bold text-slate-900 mb-4">Informasi Sistem</h4>
            <div class="space-y-3 text-sm text-slate-600">
                <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                    <span>Status Server</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium text-emerald-700 bg-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
                    </span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                    <span>Sinkronisasi Data</span>
                    <span class="text-slate-800 font-medium">Hari ini, 08:00 AM</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
</div>
@endsection
