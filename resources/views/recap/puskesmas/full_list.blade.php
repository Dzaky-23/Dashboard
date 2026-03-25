@extends('layouts.app')

@section('title', "Data Penyakit Puskesmas $puskesmas - Laporan")

@php
    $displayPeriod = '';
    if ($periodType === 'month') {
        $displayPeriod = \Carbon\Carbon::create()->month((int)$periodValue)->translatedFormat('F') . ' ' . $year;
    } elseif ($periodType === 'quarter') {
        $displayPeriod = 'Triwulan ' . $periodValue . ' ' . $year;
    } elseif ($periodType === 'semester') {
        $displayPeriod = 'Semester ' . $periodValue . ' ' . $year;
    } elseif ($periodType === 'year') {
        $displayPeriod = 'Tahun ' . $year;
    }
@endphp

@section('content')
<!-- Header & Back Button -->
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <a href="{{ route('recap.show', $puskesmas) }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-red-500 mb-3 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dasbor Puskesmas
        </a>
        <h1 class="text-2xl font-bold text-slate-900">
            Daftar Penyakit <span class="text-red-600 uppercase">{{ $puskesmas }}</span>
        </h1>
        <p class="mt-1 text-sm text-slate-500 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Periode: <strong>{{ $displayPeriod }}</strong>
        </p>
    </div>
</div>

<!-- Search and Table Container -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
    <!-- Search Bar -->
    <div class="border-b border-slate-200 p-4 sm:p-6 bg-slate-50/50">
        <form action="{{ route('recap.full_list', $puskesmas) }}" method="GET" class="flex flex-col md:flex-row items-center gap-3">
            <input type="hidden" name="period_type" value="{{ $periodType }}">
            <input type="hidden" name="period_value" value="{{ $periodValue }}">
            <input type="hidden" name="year" value="{{ $year }}">
            
            <div class="relative flex-grow w-full md:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="block w-full rounded-xl border-0 py-2.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6 transition-all" placeholder="Cari Kode Penyakit (ICD-X)...">
            </div>

            <div class="w-full md:w-48">
                <select name="sort" onchange="this.form.submit()" class="block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6 transition-all">
                    <option value="cases_desc" {{ $sort === 'cases_desc' ? 'selected' : '' }}>Kasus Terbanyak</option>
                    <option value="cases_asc" {{ $sort === 'cases_asc' ? 'selected' : '' }}>Kasus Tersedikit</option>
                    <option value="alphabet_asc" {{ $sort === 'alphabet_asc' ? 'selected' : '' }}>Abjad Penyakit (A-Z)</option>
                    <option value="alphabet_desc" {{ $sort === 'alphabet_desc' ? 'selected' : '' }}>Abjad Penyakit (Z-A)</option>
                </select>
            </div>

            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="flex-grow md:flex-none rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-800 transition-colors">
                    Cari
                </button>
                @if(request('search') || (request('sort') && request('sort') !== 'cases_desc'))
                    <a href="{{ route('recap.full_list', ['puskesmas' => $puskesmas, 'period_type' => $periodType, 'period_value' => $periodValue, 'year' => $year]) }}" class="flex-grow md:flex-none rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors text-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="w-20 py-4 pl-6 pr-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">No</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kode Penyakit (ICD-X)</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total Kasus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($penyakits as $index => $item)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-500 text-center">
                        {{ ($penyakits->currentPage() - 1) * $penyakits->perPage() + $index + 1 }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-800">
                        {{ $item->kode_penyakit }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-right">
                        <span class="inline-flex items-center justify-center px-3 py-1 text-sm font-bold leading-none rounded-full min-w-[3rem] text-slate-700 bg-slate-100">
                            {{ number_format($item->count, 0, ',', '.') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            @if(request('search'))
                                <span class="text-base font-bold text-slate-900">Penyakit Tidak Ditemukan</span>
                                <span class="block mt-1 text-sm text-slate-500">Tidak ada kode penyakit yang cocok dengan pencarian "<span class="font-semibold text-slate-700">{{ request('search') }}</span>".</span>
                                <a href="{{ route('recap.full_list', ['puskesmas' => $puskesmas, 'period_type' => $periodType, 'period_value' => $periodValue, 'year' => $year]) }}" class="mt-4 text-sm font-semibold text-red-600 hover:text-red-500 transition-colors">Hapus filter pencarian</a>
                            @else
                                <span class="text-base font-medium text-slate-900">Belum ada rekam medis pada bulan ini.</span>
                                <span class="block mt-1 text-sm text-slate-500">Silahkan pilih bulan atau tahun lain pada filter.</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Paginasi Footer Bawaan Tailwind Laravel -->
    @if($penyakits->hasPages())
    <div class="border-t border-slate-200 bg-white px-6 py-4">
        {{ $penyakits->links() }}
    </div>
    @endif
</div>
@endsection
