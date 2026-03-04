@extends('layouts.app')

@section('title', 'Daftar Pasien Baru')

@section('content')
<div class="max-w-4xl mx-auto pb-10">
    <div class="mb-6">
        <a href="{{ route('pasiens.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
            <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Daftar Pasien
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="px-6 py-8 sm:p-10 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 tracking-tight">Pendaftaran Pasien Baru</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Lengkapi formulir di bawah ini untuk memasukkan data pasien ke dalam sistem rekam medis.</p>
        </div>

        <form action="{{ route('pasiens.store') }}" method="POST" class="px-6 py-8 sm:p-10">
            @csrf
            
            <div class="space-y-12">
                <!-- Section 1 -->
                <div>
                    <h3 class="text-base font-semibold leading-7 text-slate-900 flex items-center gap-2 mb-6">
                        <span class="bg-indigo-100 text-indigo-700 h-6 w-6 rounded-full flex items-center justify-center text-sm">1</span>
                        Informasi Utama
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                        <!-- No Reg -->
                        <div>
                            <label for="no_reg" class="block text-sm font-semibold leading-6 text-slate-900">Nomor Registrasi / RM <span class="text-rose-500">*</span></label>
                            <div class="mt-2 relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                </span>
                                <input type="text" name="no_reg" id="no_reg" required value="{{ old('no_reg', date('YmdHis')) }}" class="block w-full rounded-xl border-0 py-2.5 pl-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" readonly>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Auto-generated berdasarkan timestamp.</p>
                        </div>

                        <!-- Nama Lengkap -->
                        <div>
                            <label for="nama" class="block text-sm font-semibold leading-6 text-slate-900">Nama Lengkap Sesuai KTP <span class="text-rose-500">*</span></label>
                            <div class="mt-2">
                                <input type="text" name="nama" id="nama" required value="{{ old('nama') }}" placeholder="Contoh: Budi Santoso" class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow">
                            </div>
                        </div>

                        <!-- NIK -->
                        <div>
                            <label for="nik" class="block text-sm font-medium leading-6 text-slate-900">Nomor Induk Kependudukan (NIK)</label>
                            <div class="mt-2">
                                <input type="text" name="nik" id="nik" value="{{ old('nik') }}" placeholder="16 digit NIK" class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow font-mono">
                            </div>
                        </div>

                        <!-- Tanggal Periksa -->
                        <div>
                            <label for="tanggal" class="block text-sm font-medium leading-6 text-slate-900">Tanggal Pemeriksaan Awal</label>
                            <div class="mt-2">
                                <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Section 2 -->
                <div>
                    <h3 class="text-base font-semibold leading-7 text-slate-900 flex items-center gap-2 mb-6">
                        <span class="bg-indigo-100 text-indigo-700 h-6 w-6 rounded-full flex items-center justify-center text-sm">2</span>
                        Administrasi & Domisili
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium leading-6 text-slate-900">Status Pasien</label>
                            <div class="mt-2 relative">
                                <select id="status" name="status" class="block w-full appearance-none rounded-xl border-0 py-2.5 pl-4 pr-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white transition-shadow">
                                    <option value="Baru" {{ old('status') == 'Baru' ? 'selected' : '' }}>Pasien Baru</option>
                                    <option value="Lama" {{ old('status', 'Lama') == 'Lama' ? 'selected' : '' }}>Pasien Lama</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cara Bayar -->
                        <div>
                            <label for="cara_bayar" class="block text-sm font-medium leading-6 text-slate-900">Metode Pembayaran</label>
                            <div class="mt-2 relative">
                                <select id="cara_bayar" name="cara_bayar" class="block w-full appearance-none rounded-xl border-0 py-2.5 pl-4 pr-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white transition-shadow">
                                    <option value="Umum" {{ old('cara_bayar') == 'Umum' ? 'selected' : '' }}>Umum / Mandiri</option>
                                    <option value="BPJS" {{ old('cara_bayar') == 'BPJS' ? 'selected' : '' }}>BPJS Kesehatan</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Alamat (Jalan) -->
                        <div class="col-span-full">
                            <label for="jalan" class="block text-sm font-medium leading-6 text-slate-900">Alamat Tempat Tinggal (Domisili)</label>
                            <div class="mt-2">
                                <textarea name="jalan" id="jalan" rows="3" placeholder="Nama jalan, RT/RW, kelurahan..." class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow">{{ old('jalan') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Submit Actions -->
            <div class="mt-10 flex items-center justify-end gap-x-4 border-t border-slate-100 pt-8">
                <a href="{{ route('pasiens.index') }}" class="text-sm font-semibold leading-6 text-slate-500 hover:text-slate-900 px-4 py-2">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                    Simpan Data Pasien
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
