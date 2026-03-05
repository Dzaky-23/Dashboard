<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-red-600">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.
        </p>
    </header>

    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="inline-flex justify-center rounded-xl bg-red-600 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-colors"
    >Hapus Akun</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-900">
                Apakah Anda yakin ingin menghapus akun Anda?
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Silakan masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.
            </p>

            <div class="mt-6">
                <label for="password" class="sr-only">Kata Sandi</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4 rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                    placeholder="Kata Sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="inline-flex justify-center rounded-xl bg-white border border-slate-300 py-2.5 px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-600 transition-colors">
                    Batal
                </button>

                <button type="submit" class="inline-flex justify-center rounded-xl bg-red-600 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-colors">
                    Hapus Akun Permanen
                </button>
            </div>
        </form>
    </x-modal>
</section>
