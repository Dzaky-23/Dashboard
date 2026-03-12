@extends('layouts.app')

@section('title', 'Detail Kunjungan Medis - ' . $pasien->nama)

@section('content')
<!-- Header Section -->
<div class="mb-8">
    <div class="mb-2">
        <a href="{{ route('pasiens.rekam_medis', $pasien->id) }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-600 transition-colors group">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span class="text-sm font-medium text-slate-500 group-hover:text-slate-600 transition-colors">Kembali ke Daftar Kunjungan</span>
        </a>
    </div>
    
    <div class="flex flex-col md:flex-row md:items-start lg:items-center justify-between mt-4 gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Detail Kunjungan: {{ $rm->tanggal ? date('d M Y', strtotime($rm->tanggal)) : '-' }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-2">
                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                    Pasien: {{ $pasien->sapaan ? $pasien->sapaan . ' ' : '' }}{{ $pasien->nama }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                    No Reg: {{ $pasien->no_reg }}
                </span>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Cetak RM
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                </svg>
                Edit Kunjungan
            </button>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-8">
    <!-- Header: Informasi Kunjungan -->
    <div class="border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between">
        <div class="p-6 md:w-2/3">
            <div class="flex flex-wrap text-sm text-slate-500 gap-x-4 gap-y-1">
                <p>Puskesmas: <span class="font-medium text-slate-700">{{ $rm->kpusk ?: '-' }}</span></p>
                <p>Cara Bayar: <span class="font-medium text-slate-700">{{ $rm->cara_bayar ?: '-' }} ({{ $rm->kode_pemeriksa ?: '-' }})</span></p>
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
@endsection
