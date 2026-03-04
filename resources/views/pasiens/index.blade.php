@extends('layouts.app')

@section('title', 'Daftar Pasien - RekamPasien')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Data Pasien</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar seluruh pasien yang pernah mendaftar di klinik Anda.</p>
    </div>
    <div class="mt-4 sm:mt-0">
        <a href="{{ route('pasiens.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
            <svg class="-ml-1 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            Pasien Baru
        </a>
    </div>
</div>

<!-- Table Card -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/50">
                <tr>
                    <th scope="col" class="py-4 pl-6 pr-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No Reg</th>
                    <th scope="col" class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nama Pasien</th>
                    <th scope="col" class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">NIK</th>
                    <th scope="col" class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Daftar</th>
                    <th scope="col" class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th scope="col" class="relative py-4 pl-3 pr-6">
                        <span class="sr-only">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($pasiens as $pasien)
                <tr class="hover:bg-slate-50/80 transition-colors group">
                    <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900">
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-slate-600 bg-slate-100 ring-1 ring-inset ring-slate-200">
                            {{ $pasien->no_reg }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                {{ substr($pasien->nama, 0, 1) }}
                            </div>
                            {{ $pasien->nama }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 font-mono text-xs">{{ $pasien->nik ?: '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $pasien->tanggal ? date('d M Y', strtotime($pasien->tanggal)) : '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($pasien->status == 'Baru')
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Baru</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Lama</span>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                        <a href="{{ route('pasiens.show', $pasien->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors inline-block opacity-0 group-hover:opacity-100 focus:opacity-100">
                            Detail &rarr;
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="text-base font-medium text-slate-900">Belum ada pasien terdaftar.</span>
                            <span class="block mt-1 text-sm text-slate-500">Mulai dengan mendaftarkan pasien baru.</span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Footer -->
    @if($pasiens->hasPages())
    <div class="border-t border-slate-200 bg-white px-6 py-4">
        {{ $pasiens->links() }}
    </div>
    @endif
</div>
@endsection
