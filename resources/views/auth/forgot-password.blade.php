<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Lupa Kata Sandi</h2>
        <p class="mt-2 text-sm text-slate-500">
            Lupa kata sandi Anda? Tidak masalah. Cukup beri tahu kami alamat email Anda dan kami akan mengirimi Anda tautan reset kata sandi melalui email.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-sm" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center rounded-xl bg-indigo-600 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
                Kirim Tautan Reset Kata Sandi
            </button>
        </div>
    </form>
</x-guest-layout>
