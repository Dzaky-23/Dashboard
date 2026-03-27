@extends('layouts.app')

@section('title', 'Daftar Pasien - RekamPasien')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Data Pasien</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar seluruh pasien yang terdapat pada database.</p>
    </div>

</div>

<!-- Search and Table Container -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
    <!-- Search Bar -->
    <div class="border-b border-slate-200 p-4 sm:p-6 bg-slate-50/50">
        <form action="{{ route('pasiens.index') }}" method="GET" class="flex max-w-xl items-center gap-3">
            <div class="relative w-32 flex-shrink-0">
                <select name="year" onchange="this.form.submit()" class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-8 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6 transition-all font-semibold bg-white cursor-pointer shadow-sm">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $yearInput == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative flex-grow">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="block w-full rounded-xl border-0 py-2.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6 transition-all" placeholder="Cari nama atau NIK pasien...">
            </div>
            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-800 transition-colors">
                Cari
            </button>
            @if(request('search') || request('year') != date('Y'))
                <a href="{{ route('pasiens.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-white">
                <tr>
                    <th scope="col" class="w-[15%] py-4 pl-6 pr-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No Reg</th>
                    <th scope="col" class="w-[30%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nama Pasien</th>
                    <th scope="col" class="w-[25%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">NIK</th>
                    <th scope="col" class="w-[20%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Daftar</th>
                    <th scope="col" class="w-[10%] relative py-4 pl-3 pr-6">
                        <span class="sr-only">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($pasiens as $pasien)
                <tr class="hover:bg-rose-50/80 transition-colors group">
                    <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900">
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-slate-600 bg-slate-100 ring-1 ring-inset ring-slate-200">
                            {{ $pasien->no_reg }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold text-xs">
                                {{ substr($pasien->nama, 0, 1) }}
                            </div>
                            {{ $pasien->nama }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 font-mono text-xs">{{ $pasien->nik ?: '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $pasien->tanggal ? date('d M Y', strtotime($pasien->tanggal)) : '-' }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                        <a href="{{ route('pasiens.show', $pasien->id) }}" class="text-red-600 hover:text-indigo-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors inline-block opacity-0 group-hover:opacity-100 focus:opacity-100">
                            Detail &rarr;
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            @if(request('search'))
                                <span class="text-base font-bold text-slate-900">Pasien Tidak Ditemukan</span>
                                <span class="block mt-1 text-sm text-slate-500">Sistem tidak menemukan pasien yang cocok dengan pencarian "<span class="font-semibold text-slate-700">{{ request('search') }}</span>".</span>
                                <a href="{{ route('pasiens.index') }}" class="mt-4 text-sm font-semibold text-red-600 hover:text-red-500 transition-colors">Clear pencarian</a>
                            @else
                                <span class="text-base font-medium text-slate-900">Belum ada pasien terdaftar.</span>
                            @endif
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
        {{ $pasiens->appends(['search' => request('search'), 'year' => request('year')])->links() }}
    </div>
    @endif
</div>
@endsection
