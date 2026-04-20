@extends('layouts.app')

@section('title', 'Profile - RekamPasien')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Profil Pengguna</h1>
    <p class="mt-2 text-base text-slate-600">Kelola informasi akun dan pengaturan keamanan Anda.</p>
</div>

<div class="space-y-8">
    <div class="bg-white shadow-sm ring-1 ring-slate-100 rounded-2xl overflow-hidden p-6 sm:p-8">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-slate-100 rounded-2xl overflow-hidden p-6 sm:p-8">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>


</div>
@endsection
