<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Masuk ke Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Silakan masukkan kredensial Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors" placeholder="admin@example.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-sm" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Lupa kata sandi?</a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password" 
                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-sm" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember" 
                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <label for="remember_me" class="ml-2 block text-sm text-slate-600">Ingat saya</label>
        </div>

        <div>
            <button type="submit" class="w-full flex justify-center rounded-xl bg-indigo-600 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
                Masuk
            </button>
        </div>
    </form>
</x-guest-layout>
