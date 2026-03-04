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
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex shrink-0 items-center justify-center text-white text-2xl font-bold shadow-md">
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
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 whitespace-nowrap">Pasien Baru</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 whitespace-nowrap">Pasien Lama</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors">
                <svg class="h-4 w-4 text-indigo-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
        <a href="{{ route('pasiens.rekam_medis', $pasien->id) }}" class="border-indigo-500 text-indigo-600 whitespace-nowrap border-b-2 py-4 px-2 text-sm font-semibold flex items-center gap-2" aria-current="page">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path></svg>
            Rekam Medis Khusus
            <span class="ml-1 bg-indigo-100 text-indigo-700 py-0.5 px-2 rounded-full text-xs">{{ $pasien->rekamMedis->count() }}</span>
        </a>
    </nav>
</div>

<div class="space-y-6">
    @forelse($pasien->rekamMedis as $rm)
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden group">
        <!-- RM Card Header -->
        <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-100 text-indigo-700 h-10 w-10 rounded-full flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 leading-none">Pemeriksaan & Tindakan</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1 uppercase tracking-wider">
                        Tgl: {{ date('d M Y - H:i', strtotime($rm->diisi_pada)) }} 
                        &middot; Puskesmas: {{ $rm->kpusk ?: '-' }}
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                @if($rm->status)
                    <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $rm->status }}</span>
                @endif
                <button type="button" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors text-sm font-medium">Edit Data</button>
            </div>
        </div>

        <!-- RM Card Body -->
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left Details (TTV & Basic) -->
                <div class="lg:col-span-4 space-y-6">
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Tanda-Tanda Vital</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Kesadaran</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->kdSadar ?: '-' }}</span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Tekanan Darah</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->sistole ?: '0' }}/{{ $rm->diastole ?: '0' }} <span class="text-xs font-normal text-slate-400">mmHg</span></span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Suhu Tubuh</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->suhu ?: '-' }} <span class="text-xs font-normal text-slate-400">°C</span></span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Detak Jantung (HR)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->heartRate ?: '-' }} <span class="text-xs font-normal text-slate-400">bpm</span></span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Pernafasan (RR)</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->respRate ?: '-' }} <span class="text-xs font-normal text-slate-400">x/mnt</span></span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-xs mb-1">Prognosa</span>
                                <span class="font-semibold text-slate-900 text-sm">{{ $rm->kdPrognosa ?: '-' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 border-t border-slate-100 pt-4">
                       <div>
                           <span class="text-slate-500 block text-xs mb-1">Tinggi Badan</span>
                           <span class="font-semibold text-slate-900 text-sm">{{ $rm->tb ?: '-' }} <span class="text-xs font-normal text-slate-400">cm</span></span>
                       </div>
                       <div class="h-6 w-px bg-slate-200"></div>
                       <div>
                           <span class="text-slate-500 block text-xs mb-1">Berat Badan</span>
                           <span class="font-semibold text-slate-900 text-sm">{{ $rm->bb ?: '-' }} <span class="text-xs font-normal text-slate-400">kg</span></span>
                       </div>
                       <div class="h-6 w-px bg-slate-200"></div>
                       <div>
                           <span class="text-slate-500 block text-xs mb-1">Lingkar Perut</span>
                           <span class="font-semibold text-slate-900 text-sm">{{ $rm->lingkarPerut ?: '-' }} <span class="text-xs font-normal text-slate-400">cm</span></span>
                       </div>
                    </div>
                </div>
                
                <!-- Center/Right Details (Anamnesa, Obat, dll) -->
                <div class="lg:col-span-8 lg:border-l lg:border-slate-100 lg:pl-8 space-y-6">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            Anamnesa & Keluhan Utama
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                            {{ $rm->anamnesa ?: 'Tidak ada catatan anamnesa atau keluhan dari pasien.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                                <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"></path></svg>
                                Pemeriksaan Fisik / Diagnosa (Dx)
                            </h4>
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                <div class="mb-2">
                                    <span class="text-xs text-slate-500 block">Kode Penyakit (ICD)</span>
                                    <span class="font-bold text-rose-600 text-lg">{{ $rm->kode_penyakit ?: '-' }}</span>
                                </div>
                                <p class="text-sm text-slate-600">{{ $rm->fisik ?: 'Tidak ada hasil pemeriksaan fisik spesifik.' }}</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                                <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path></svg>
                                Pemberian Terapi / Obat (Rx)
                            </h4>
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                <ul class="space-y-3">
                                    <li class="flex items-start justify-between">
                                        <div>
                                            <span class="font-semibold text-indigo-700 block text-sm">{{ $rm->kode_obat ?: 'Pemberian Resep' }}</span>
                                            <span class="text-xs text-slate-500">{{ $rm->racikan ? 'Racikan: ' . $rm->racikan : 'Non-racikan' }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="block text-sm font-bold text-slate-800">{{ $rm->jumlah ? $rm->jumlah . ' Pcs' : '-' }}</span>
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 mt-1">{{ $rm->dosis ?: 'Dosis tidak diatur' }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    @if($rm->edukasi || $rm->rekomendasi_diet)
                    <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                        <h4 class="text-xs font-bold text-amber-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"></path></svg>
                            Edukasi & Rekomendasi
                        </h4>
                        <ul class="list-disc list-inside text-sm text-amber-900 space-y-1">
                            @if($rm->edukasi)<li>{{ $rm->edukasi }}</li>@endif
                            @if($rm->rekomendasi_diet)<li>Diet: {{ $rm->rekomendasi_diet }}</li>@endif
                        </ul>
                    </div>
                    @endif
                    
                    @if($rm->kode_tindakan || $rm->jenis_perawatan || $rm->rujukan)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                        <div>
                            <span class="text-xs text-slate-500 block">Tindakan Khusus (ICD/Kode)</span>
                            <span class="font-medium text-slate-800 text-sm mt-0.5 block">{{ $rm->kode_tindakan ?: '-' }} {{ $rm->kode_tindakan_icd ? '('.$rm->kode_tindakan_icd.')' : '' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block">Jenis Perawatan & Unit</span>
                            <span class="font-medium text-slate-800 text-sm mt-0.5 block">{{ $rm->jenis_perawatan ?: '-' }} {{ $rm->unit ? '- '.$rm->unit : '' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block">Rujukan & Poli RS</span>
                            <span class="font-medium text-slate-800 text-sm mt-0.5 block">{{ $rm->rujukan ?: 'Tidak' }} {{ $rm->poli_rs ? '('.$rm->poli_rs.')' : '' }}</span>
                        </div>
                    </div>
                    @endif
                    
                </div>
            </div>
        </div>
        
    </div>
    @empty
    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center bg-white">
        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum Ada Rekam Medis Khusus</h3>
        <p class="mt-1 text-sm text-slate-500">Pasien ini belum memiliki riwayat keluhan, tindakan, atau resep yang dicatat.</p>
        <div class="mt-6">
            <button type="button" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                + Tambah Kunjungan Pertama
            </button>
        </div>
    </div>
    @endforelse
</div>
@endsection
