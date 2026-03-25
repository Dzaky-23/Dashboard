@extends('layouts.app')

@section('title', 'Dashboard - RekamPasien')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Selamat Datang, {{ Auth::user()->name }}!</h1>
    <p class="mt-2 text-base text-slate-600">Berikut adalah ringkasan aktivitas pencatatan pasien dan rekap penyakit.</p>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat 1: Total -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-orange-50 p-3 text-orange-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pasien</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalPasien ?? 0) }}</p>
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
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($pasienBaruToday ?? 0) }}</p>
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
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalBPJS ?? 0) }}</p>
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
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalUmum ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disease Stats Overview (Added Section) -->
<div class="mt-8 mb-4">
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Disease Stat 1: Total Cases -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-red-50 p-3 text-red-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Kasus Penyakit</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalKasusPenyakit ?? 0) }}</p>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs font-medium text-slate-500 italic">
            *jumlah kasus penyakit pertahun {{ date('Y') }}
        </p>
    </div>


    <!-- Disease Stat 2: Top Disease -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-indigo-50 p-3 text-indigo-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <div class="overflow-hidden w-full">
                <p class="text-sm font-medium text-slate-500 whitespace-nowrap">Penyakit Terbanyak</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-lg font-bold text-slate-900 truncate" title="{{ $topPenyakitGlobal ?? '-' }}">
                        {{ $topPenyakitGlobal ?? '-' }}
                    </p>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs font-medium text-slate-500 italic">
            *jumlah kasus penyakit terbanyak pertahun {{ date('Y') }}
        </p>
    </div>

    <!-- Disease Stat 3: Total Puskesmas -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Mencakup Puskesmas</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalPuskesmas ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Disease Stat 4: Total Kecamatan -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="rounded-xl bg-purple-50 p-3 text-purple-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Wilayah Kecamatan</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalKecamatan ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection
