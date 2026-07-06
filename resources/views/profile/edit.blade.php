{{--
    Profile Edit — Skeleton (Sprint 2.1.5)
    ───────────────────────────────────────
--}}
<x-layout.app
    pageTitle="User Profile"
    pageSubtitle="Kelola detail akun personal Anda"
>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-md items-start">
        <!-- Profile Info -->
        <x-ui.analytics-card title="Informasi Profil" subtitle="Perbarui informasi profil akun dan alamat email Anda.">
            <div class="mt-4">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-ui.analytics-card>

        <!-- Change Password -->
        <x-ui.analytics-card title="Ubah Kata Sandi" subtitle="Pastikan akun Anda menggunakan kata sandi acak yang panjang untuk menjaga keamanan.">
            <div class="mt-4">
                @include('profile.partials.update-password-form')
            </div>
        </x-ui.analytics-card>
    </div>
</x-layout.app>
