@extends('layouts.app')

@section('title', 'Rekam Medis - ' . $pasien->nama)

@section('content')
<!-- Header Section -->
<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('pasiens.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <span class="text-sm font-medium text-slate-500">Kembali ke Daftar</span>
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
            Rekam Medis Khusus
            <span class="ml-1 bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-xs">{{ $pasien->rekamMedis->count() }}</span>
        </a>
    </nav>
</div>

<div class="space-y-6">
    @forelse($pasien->rekamMedis as $rm)
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden group mb-6">
        <!-- Header: Informasi Kunjungan -->
        <div class="border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between">
            <div class="p-6 md:w-2/3">
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full">{{ $rm->tanggal ? date('d M Y', strtotime($rm->tanggal)) : '-' }}</span>
                    <span class="text-sm font-semibold text-slate-700">No Reg: <span class="text-slate-900">{{ $rm->no_reg ?: '-' }}</span></span>
                    @if($rm->status)
                        <span class="inline-flex rounded-md bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10">{{ $rm->status }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap text-sm text-slate-500 gap-x-4 gap-y-1 mt-3">
                    <p>Puskesmas: <span class="font-medium text-slate-700">{{ $rm->kpusk ?: '-' }}</span></p>
                    <p>Cara Bayar: <span class="font-medium text-slate-700">{{ $rm->cara_bayar ?: '-' }} ({{ $rm->kode_pemayaran ?: '-' }})</span></p>
                    <p>Diisi Pada: <span class="font-medium text-slate-700">{{ $rm->diisi_pada ? date('H:i', strtotime($rm->diisi_pada)) : '-' }}</span></p>
                </div>
            </div>
            <div class="p-6 md:w-1/3 bg-slate-50/50 md:border-l border-t md:border-t-0 border-slate-100 h-full flex flex-col justify-center">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Perawatan & Rujukan</h4>
                <div class="space-y-1.5 text-sm">
                    <p class="flex justify-between"><span class="text-slate-500">Jenis</span> <span class="font-medium text-slate-800">{{ $rm->jenis_perawatan ?: '-' }}</span></p>
                    <p class="flex justify-between"><span class="text-slate-500">Unit</span> <span class="font-medium text-slate-800">{{ $rm->unit ?: '-' }}</span></p>
                    <p class="flex justify-between"><span class="text-slate-500">RS Rujuan</span> <span class="font-medium text-slate-800">{{ $rm->rujukan ?: '-' }} {{ $rm->poli_rs ? '('.$rm->poli_rs.')' : '' }}</span></p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Kolom Kiri: TTV & Alergi -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Tanda-Tanda Vital -->
                    <div class="bg-blue-50/50 rounded-xl border border-blue-100 p-4">
                        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Tanda-Tanda Vital (TTV)
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Kesadaran</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->kdSadar ?: '-' }}</span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Tensi (S/D)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->sistole ?: '-' }}/{{ $rm->diastole ?: '-' }} <span class="text-xs font-normal text-slate-400">mmHg</span></span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Suhu</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->suhu ?: '-' }} <span class="text-xs font-normal text-slate-400">°C</span></span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Nadi (HR)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->heartRate ?: '-' }} <span class="text-xs font-normal text-slate-400">bpm</span></span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Napas (RR)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->respRate ?: '-' }} <span class="text-xs font-normal text-slate-400">x/mnt</span></span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-blue-50/50">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold mb-0.5">Prognosa</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->kdPrognosa ?: '-' }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between bg-white p-3 rounded border border-blue-50/50">
                            <div class="text-center w-1/3">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold">TB (cm)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->tb ?: '-' }}</span>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div class="text-center w-1/3">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold">BB (kg)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->bb ?: '-' }}</span>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div class="text-center w-1/3">
                                <span class="text-slate-500 block text-[10px] uppercase font-bold">LP (cm)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->lingkarPerut ?: '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Alergi -->
                    <div class="bg-rose-50/50 rounded-xl border border-rose-100 p-4">
                        <h4 class="text-xs font-bold text-rose-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Riwayat Alergi
                        </h4>
                        <div class="space-y-2">
                            <div class="bg-white p-2.5 rounded border border-rose-50/50">
                                <span class="text-slate-500 font-semibold text-[11px] uppercase tracking-wider block mb-1">Makanan</span>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-800 font-medium">{{ $rm->alergiMakan ?: '-' }}</span>
                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">{{ $rm->alergiMakananSS ?: '-' }}</span>
                                </div>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-rose-50/50">
                                <span class="text-slate-500 font-semibold text-[11px] uppercase tracking-wider block mb-1">Udara/Lingkungan</span>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-800 font-medium">{{ $rm->alergiUdara ?: '-' }}</span>
                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">{{ $rm->alergiLingkunganSS ?: '-' }}</span>
                                </div>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-rose-50/50">
                                <span class="text-slate-500 font-semibold text-[11px] uppercase tracking-wider block mb-1">Obat-obatan</span>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-800 font-medium">{{ $rm->alergiObat ?: '-' }}</span>
                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">{{ $rm->alergiObatSS ?: '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: SOAP -->
                <div class="lg:col-span-8 space-y-5">
                    <!-- S - Anamnesa -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-100">
                            <span class="bg-slate-800 text-white w-5 h-5 rounded flex items-center justify-center text-[10px]">S</span>
                            Subjektif (S) <span class="text-slate-400 font-normal text-xs">&middot; Anamnesa / Keluhan</span>
                        </h4>
                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-100 text-sm text-slate-700 leading-relaxed min-h-[60px]">
                            {{ $rm->anamnesa ?: '-' }}
                        </div>
                    </div>

                    <!-- O - Fisik -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-100">
                            <span class="bg-slate-800 text-white w-5 h-5 rounded flex items-center justify-center text-[10px]">O</span>
                            Objektif (O) <span class="text-slate-400 font-normal text-xs">&middot; Pemeriksaan Fisik Terarah</span>
                        </h4>
                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-100 text-sm text-slate-700 leading-relaxed min-h-[60px]">
                            {{ $rm->fisik ?: '-' }}
                        </div>
                    </div>

                    <!-- A - Diagnosa -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-100">
                            <span class="bg-slate-800 text-white w-5 h-5 rounded flex items-center justify-center text-[10px]">A</span>
                            Analisis (A) <span class="text-slate-400 font-normal text-xs">&middot; Diagnosa Medis</span>
                        </h4>
                        <div class="flex items-center gap-3 bg-red-50/50 p-3.5 rounded-lg border border-red-100">
                            <div class="text-sm">
                                <span class="text-slate-500 block text-xs mb-1">Kode Penyakit (ICD):</span>
                                <span class="bg-white text-red-600 border border-red-200 px-3 py-1.5 rounded font-bold inline-block shadow-sm">
                                    {{ $rm->kode_penyakit ?: '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- P - Penatalaksanaan -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-100">
                            <span class="bg-slate-800 text-white w-5 h-5 rounded flex items-center justify-center text-[10px]">P</span>
                            Plan (P) <span class="text-slate-400 font-normal text-xs">&middot; Penatalaksanaan Medis</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Tindakan -->
                            <div class="border border-slate-200 bg-white rounded-lg p-3.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-3 pb-2 border-b border-slate-100">Tindakan Medis</span>
                                <div class="space-y-2.5">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Kode Tindakan:</span>
                                        <span class="font-semibold text-slate-900 bg-slate-50 px-2.5 py-1 rounded border border-slate-100">{{ $rm->kode_tindakan ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">ICD-9 CM:</span>
                                        <span class="font-semibold text-slate-900 bg-slate-50 px-2.5 py-1 rounded border border-slate-100">{{ $rm->kode_tindakan_icd ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Terapi/Obat -->
                            <div class="border border-slate-200 bg-white rounded-lg p-3.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-3 pb-2 border-b border-slate-100">Resep Obat (Rx)</span>
                                <div class="space-y-2.5">
                                    <div class="flex justify-between items-start text-sm">
                                        <span class="text-slate-500 w-1/3">Nama Obat:</span>
                                        <span class="font-bold text-red-600 text-right w-2/3">{{ $rm->kode_obat ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Dosis / Aturan:</span>
                                        <span class="font-semibold text-slate-900">{{ $rm->dosis ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Jumlah:</span>
                                        <span class="font-medium bg-slate-50 px-2 py-0.5 rounded border border-slate-100">{{ $rm->jumlah ?: '-' }} Pcs</span>
                                    </div>
                                    <div class="flex justify-between items-start text-sm pt-2 border-t border-slate-100 mt-2">
                                        <span class="text-slate-500 w-1/3">Racikan:</span>
                                        <span class="font-medium text-slate-800 text-right w-2/3">{{ $rm->racikan ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edukasi & Diet -->
                        <div class="mt-4 bg-amber-50/50 rounded-lg border border-amber-100 p-3.5">
                            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-widest block mb-2 pb-2 border-b border-amber-200/50">Edukasi & Diet</span>
                            <div class="space-y-2.5 divide-y divide-amber-100/50">
                                <div class="pt-1 pb-1">
                                    <span class="text-[10px] font-bold text-amber-500 uppercase block mb-0.5">Edukasi Pasien</span>
                                    <span class="text-sm text-slate-700 leading-relaxed block">{{ $rm->edukasi ?: '-' }}</span>
                                </div>
                                <div class="pt-2">
                                    <span class="text-[10px] font-bold text-amber-500 uppercase block mb-0.5">Rekomendasi Diet</span>
                                    <span class="text-sm text-slate-700 leading-relaxed block">{{ $rm->rekomendasi_diet ?: '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center bg-white mt-6">
        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum Ada Rekam Medis Khusus</h3>
        <p class="mt-1 text-sm text-slate-500">Pasien ini belum memiliki riwayat keluhan, tindakan, atau resep yang dicatat.</p>
        <div class="mt-6">
            <button type="button" class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500">
                + Tambah Kunjungan Pertama
            </button>
        </div>
    </div>
    @endforelse
</div>
@endsection
