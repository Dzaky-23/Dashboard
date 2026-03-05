<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Buat Kata Sandi Baru</h2>
        <p class="mt-1 text-sm text-slate-500">Silakan masukkan email dan kata sandi baru Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-sm" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-sm" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-600 text-sm" />
        </div>

        <div class="pt-2 flex items-center justify-end">
            <button type="submit" class="w-full flex justify-center rounded-xl bg-red-600 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-colors">
                Reset Kata Sandi
            </button>
        </div>
    </form>
</x-guest-layout>
