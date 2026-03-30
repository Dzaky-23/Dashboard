@extends('layouts.app')

@section('title', 'Detail Pasien - ' . $pasien->nama)

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
                    @if($pasien->cara_bayar == 'BPJS')
                        <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20 whitespace-nowrap">BPJS</span>
                    @elseif($pasien->cara_bayar)
                        <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20 whitespace-nowrap">{{ $pasien->cara_bayar }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="border-b border-slate-200 mb-8 overflow-x-auto overflow-y-hidden">
    <nav class="-mb-px flex space-x-8 min-w-max" aria-label="Tabs">
        <!-- Tab 1: Active -->
        <a href="{{ route('pasiens.show', $pasien->id) }}" class="border-red-500 text-red-600 whitespace-nowrap border-b-2 py-4 px-2 text-sm font-semibold flex items-center gap-2" aria-current="page">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Data Pasien
        </a>
        
        <!-- Tab 2: Inactive -->
        <a href="{{ route('pasiens.rekam_medis', $pasien->id) }}" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-2 text-sm font-medium flex items-center gap-2 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path></svg>
            Rekam Medis Khusus
            <span class="ml-1 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ $pasien->rekamMedis->count() }}</span>
        </a>
    </nav>
</div>

<div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
    <!-- Identitas Pribadi -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden lg:col-span-1">
        <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                Identitas Pribadi
            </h3>
        </div>
        <div class="p-5">
            <div class="space-y-4 text-sm max-w-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">Sapaan</span>
                    <span class="text-slate-900 w-2/3 sm:text-right">{{ $pasien->sapaan ?: '-' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">NIK (KTP)</span>
                    <span class="text-slate-900 font-mono w-2/3 sm:text-right">{{ $pasien->nik ?: '-' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">Kelahiran</span>
                    <span class="text-slate-900 w-2/3 sm:text-right">
                        {{ $pasien->t_lahir ?: '-' }}, {{ $pasien->tg_lahir ? date('d M Y', strtotime($pasien->tg_lahir)) : '-' }}
                    </span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">Kelamin</span>
                    <span class="text-slate-900 w-2/3 sm:text-right">{{ $pasien->jkl == 'L' ? 'Laki-laki' : ($pasien->jkl == 'P' ? 'Perempuan' : str_replace(['L','P'], ['Laki-laki','Perempuan'], $pasien->jkl ?: '-')) }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">Gol. Darah</span>
                    <span class="text-slate-900 w-2/3 sm:text-right">
                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ $pasien->gd ?: '-' }}</span>
                    </span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between border-b border-slate-100 pb-3 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">Pekerjaan</span>
                    <span class="text-slate-900 w-2/3 sm:text-right">{{ $pasien->pekerjaan ?: '-' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between pb-1 gap-1">
                    <span class="font-medium text-slate-500 w-1/3">No. Telepon</span>
                    <span class="text-slate-900 font-mono w-2/3 sm:text-right">{{ $pasien->telp ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alamat & Keluarga -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden lg:col-span-1">
        <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Alamat & Keluarga
            </h3>
        </div>
        <div class="p-5">
            <div class="space-y-4 text-sm">
                <div class="flex flex-col gap-1.5 border-b border-slate-100 pb-4">
                    <span class="font-semibold text-slate-800">Alamat Domisili</span>
                    <span class="text-slate-600 leading-relaxed">{{ $pasien->domisili ?: '-' }}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-x-6 gap-y-4 border-b border-slate-100 pb-4">
                    <div class="flex flex-col gap-1">
                        <span class="font-medium text-slate-500">RT/RW</span>
                        <span class="text-slate-900">{{ $pasien->rt_rw ?: '-' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="font-medium text-slate-500">Desa/Kelurahan</span>
                        <span class="text-slate-900">{{ $pasien->kdesa ?: '-' }}</span>
                    </div>
                </div>
                
                <div class="space-y-3 pt-2">
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <span class="font-medium text-slate-500">No. KK</span>
                        <span class="text-slate-900 font-mono font-medium">{{ $pasien->kk ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <span class="font-medium text-slate-500">Nama Ibu Kandung</span>
                        <span class="text-slate-900 font-medium">{{ $pasien->ibu ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <span class="font-medium text-slate-500">NIK Ibu</span>
                        <span class="text-slate-900 font-mono font-medium">{{ $pasien->nik_ibu ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:col-span-1">
        <!-- Administrasi & Asuransi -->
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Administrasi & Asuransi
                </h3>
            </div>
        <div class="p-5">
            <div class="space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-x-6 gap-y-6 pb-4 border-b border-slate-100">
                    <div class="flex flex-col gap-1">
                        <span class="font-medium text-slate-500">Kode Pusk</span>
                        <span class="text-slate-900 font-bold bg-slate-100 w-fit px-2 py-1 rounded">{{ $pasien->kpusk ?: '-' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="font-medium text-slate-500">Tgl. Terdaftar</span>
                        <span class="text-slate-900 font-medium">{{ $pasien->tanggal ? date('d M Y', strtotime($pasien->tanggal)) : '-' }}</span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-3 py-2 border-b border-slate-100">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-slate-500">No. ASN</span>
                        <span class="text-slate-900 font-mono font-medium">{{ $pasien->no_asn ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-slate-500">Kelas BPJS</span>
                        <span class="text-teal-700 font-bold bg-teal-50 px-2.5 py-1 rounded-md">{{ $pasien->jenis_bpjs ?: '-' }}</span>
                    </div>
                </div>
                
                <div class="pt-2 flex flex-col items-center">
                    <span class="font-medium text-slate-400 text-xs text-center w-full block mb-1">Log Sistem</span>
                    <span class="text-slate-400 text-xs font-mono text-center">{{ $pasien->submited_at ?: '-' }}</span>
                </div>
            </div>
        </div>
        </div>

        <!-- Peringatan Medis -->
        <div class="bg-amber-50 rounded-2xl shadow-sm ring-1 ring-amber-200 overflow-hidden">
            <div class="border-b border-amber-200/50 px-6 py-4">
                <h3 class="text-sm font-semibold text-amber-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Peringatan Khusus
                </h3>
            </div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="font-medium text-amber-800">Berat Lahir (BBLR)</dt>
                        <dd class="text-amber-900 font-bold">{{ $pasien->berat ? $pasien->berat.' gram' : '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="font-medium text-amber-800">Peserta Prolanis</dt>
                        <dd class="text-red-800 font-bold bg-red-100 px-2 py-0.5 rounded">{{ $pasien->prolanis ? 'YA' : 'TIDAK' }}</dd>
                    </div>
                    
                    @if($pasien->alergi)
                    <div class="pt-3 border-t border-amber-200/50">
                        <dt class="font-bold text-rose-800">Alergi Terdaftar</dt>
                        <dd class="text-black mt-1 bg-white p-2 rounded-lg border border-rose-100 italic">{{ $pasien->alergi }}</dd>
                    </div>
                    @endif
                    
                    @if($pasien->catatan)
                    <div class="pt-3 {{ !$pasien->alergi ? 'border-t border-amber-200/50' : '' }}">
                        <dt class="font-bold text-amber-800">Catatan Klinik</dt>
                        <dd class="text-amber-900 mt-1">{{ $pasien->catatan }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
