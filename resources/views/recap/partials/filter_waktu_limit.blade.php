<!-- Form Filter Waktu & Limit N -->
<form action="{{ $actionUrl }}" method="GET" class="flex flex-wrap items-center justify-end gap-2" x-data="{ pType: '{{ $periodType }}' }">
    <select name="period_type" x-model="pType" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-slate-50 shadow-sm cursor-pointer">
        <option value="year">Per Tahun</option>
        <option value="semester">Per Semester</option>
        <option value="quarter">Per Triwulan</option>
        <option value="month">Per Bulan</option>
    </select>
    
    <select name="year" class="w-24 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
        @for($y = 2024; $y <= date('Y') + 1; $y++)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>

    <template x-if="pType === 'semester'">
        <select name="semester" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
            <option value="1" {{ $semester == 1 ? 'selected' : '' }}>Semester 1</option>
            <option value="2" {{ $semester == 2 ? 'selected' : '' }}>Semester 2</option>
        </select>
    </template>

    <template x-if="pType === 'quarter'">
        <select name="quarter" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
            <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Q1 (Jan-Mar)</option>
            <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Q2 (Apr-Jun)</option>
            <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Q3 (Jul-Sep)</option>
            <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Q4 (Okt-Des)</option>
        </select>
    </template>

    <template x-if="pType === 'month'">
        <select name="month" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
            @php
                $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            @endphp
            @foreach($months as $num => $name)
                <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </template>

    <div class="flex items-center ml-2 pl-3 space-x-2 border-l border-slate-200">
        <label for="limit" class="text-xs font-semibold text-slate-500">N:</label>
        <input type="number" name="limit" id="limit" value="{{ $limit }}" min="1" class="w-16 text-xs font-bold border-slate-300 rounded-md py-1.5 px-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1.5 px-4 rounded-md shadow-sm transition-colors ml-1">Terapkan</button>
    </div>
</form>
