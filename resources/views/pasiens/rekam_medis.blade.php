@extends('layouts.app')

@section('title', 'Rekam Medis - ' . $pasien->nama)

@section('content')
<!-- Header Section -->
<div class="mb-8">
    <div class="mb-2">
        <a href="{{ route('pasiens.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-600 transition-colors group">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span class="text-sm font-medium text-slate-500 group-hover:text-slate-600 transition-colors">Kembali ke Daftar</span>
        </a>
    </div>
    
    <div class="flex flex-col md:flex-row md:items-start lg:items-center justify-between mt-4 gap-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-red-500 to-orange-600 flex shrink-0 items-center justify-center text-white text-2xl font-bold shadow-md">
                {{ substr($pasien->nama, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $pasien->sapaan ? $pasien->sapaan . ' ' : '' }}{{ $pasien->nama }}
                </h1>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-2">
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                        No Reg: {{ $pasien->no_reg }}
                    </span>
                    @if($pasien->status == 'Baru')
                        <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10 whitespace-nowrap">Pasien Baru</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 whitespace-nowrap">Pasien Lama</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors">
                <svg class="h-4 w-4 text-red-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Rekam Medis
            </button>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="border-b border-slate-200 mb-8 overflow-x-auto">
    <nav class="-mb-px flex space-x-8 min-w-max" aria-label="Tabs">
        <!-- Tab 1: Inactive -->
        <a href="{{ route('pasiens.show', $pasien->id) }}" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-2 text-sm font-medium flex items-center gap-2 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Data Pasien
        </a>
        
        <!-- Tab 2: Active -->
        <a href="{{ route('pasiens.rekam_medis', $pasien->id) }}" class="border-red-500 text-red-600 whitespace-nowrap border-b-2 py-4 px-2 text-sm font-semibold flex items-center gap-2" aria-current="page">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path></svg>
            Rekam Medis
            <span class="ml-1 bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-xs">{{ $pasien->rekamMedis->count() }}</span>
        </a>
    </nav>
</div>

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-8">
    <!-- Filter Bar -->
    <div class="border-b border-slate-100 p-4 sm:p-5 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h3 class="text-sm font-semibold text-slate-800">Riwayat Kunjungan</h3>
        <form action="{{ route('pasiens.rekam_medis', $pasien->id) }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative flex-grow">
                <input type="date" name="search_date" id="search_date" value="{{ request('search_date') }}" class="block w-full max-w-[200px] rounded-lg border-0 py-2 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6 transition-all text-slate-500">
            </div>
            <button type="submit" class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 transition-colors">
                Filter Waktu
            </button>
            @if(request('search_date'))
                <a href="{{ route('pasiens.rekam_medis', $pasien->id) }}" class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors" title="Reset Filter">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-900 uppercase tracking-wider">Tanggal & Reg</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-900 uppercase tracking-wider">Puskesmas / Unit</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-900 uppercase tracking-wider">Diagnosa Utama</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-900 uppercase tracking-wider">Tindakan Khusus</th>
                    <th scope="col" class="relative px-6 py-3.5">
                        <span class="sr-only">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rekamMedis as $rm)
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="whitespace-nowrap px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold border border-blue-100">
                                {{ $rm->tanggal ? date('d', strtotime($rm->tanggal)) : '-' }}
                            </div>
                            <div>
                                <div class="font-medium text-slate-900">{{ $rm->tanggal ? date('M Y', strtotime($rm->tanggal)) : '-' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Reg: {{ $rm->no_reg ?: '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4">
                        <div class="text-sm font-medium text-slate-900">{{ $rm->kpusk ?: '-' }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $rm->unit ?: '-' }} &middot; {{ $rm->jenis_perawatan ?: '-' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">ICD: {{ $rm->kode_penyakit ?: '-' }}</span>
                            @if($rm->status == 'Baru')
                                <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10 whitespace-nowrap">Baru</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-700 max-w-[200px] truncate" title="{{ $rm->kode_tindakan ?: '-' }}">
                            {{ $rm->kode_tindakan ?: 'Tidak ada' }}
                        </div>
                    </td>
                    <td class="relative whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('pasiens.rekam_medis.show', ['pasien' => $pasien->id, 'rekam_medis' => $rm->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                            Lihat Detail
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum Ada Riwayat Kunjungan</h3>
                        <p class="mt-1 text-sm text-slate-500">Pasien ini belum memiliki rekam medis yang dicatat.</p>
                        <div class="mt-6">
                            <button type="button" class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500">
                                + Tambah Kunjungan Pertama
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
