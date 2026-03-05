<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Konfirmasi Kata Sandi</h2>
        <p class="mt-2 text-sm text-slate-500">
            Ini adalah area aman dari aplikasi. Harap konfirmasi kata sandi Anda sebelum melanjutkan.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-gray-400 focus:ring-gray-400 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-sm" />
        </div>

        <div class="pt-2 flex justify-end">
            <button type="submit" class="inline-flex justify-center rounded-xl bg-[#fa302e] py-2.5 px-6 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 transition-colors">
                Konfirmasi
            </button>
        </div>
    </form>
</x-guest-layout>
