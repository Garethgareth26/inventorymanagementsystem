{{--
    User Management Index — Skeleton (Sprint 2.1.5)
    ────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="User Management"
    pageSubtitle="Manajemen Akun — Kelola hak akses pengguna sistem Karyawan dan Owner"
>
    <!-- Page Header Actions -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->isOwner())
            <button class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Add User
            </button>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari nama / email..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Role</option>
                    <option value="owner">Owner</option>
                    <option value="karyawan">Karyawan</option>
                </select>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </x-slot:filters>
            <x-slot:actions>
                <button class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-lg py-md hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export CSV
                </button>
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    <!-- Data Table -->
    <div class="mb-lg">
        <x-tables.data-table
            :headers="['Nama', 'Email', 'Role', 'Status', 'Login Terakhir', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex items-center gap-md">
                        <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center text-xs font-bold uppercase select-none">
                            O
                        </div>
                        <span class="font-semibold text-text-primary">Direktur Utama (Owner)</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">owner@akuna.com</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Owner</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Aktif</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">06 Jul 2026 18:30</td>
                <td class="px-lg py-md">
                    @if(auth()->user()->isOwner())
                        <div class="flex items-center gap-sm">
                            <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Edit">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-negative-bg text-danger-red transition-all" title="Deactivate">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                            </button>
                        </div>
                    @else
                        <span class="text-text-secondary font-body-md">—</span>
                    @endif
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex items-center gap-md">
                        <div class="w-8 h-8 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant flex items-center justify-center text-xs font-bold uppercase select-none">
                            K
                        </div>
                        <span class="font-semibold text-text-primary">Staff Gudang (Karyawan)</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">karyawan@akuna.com</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Karyawan</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Aktif</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">06 Jul 2026 08:15</td>
                <td class="px-lg py-md">
                    @if(auth()->user()->isOwner())
                        <div class="flex items-center gap-sm">
                            <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Edit">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-negative-bg text-danger-red transition-all" title="Deactivate">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                            </button>
                        </div>
                    @else
                        <span class="text-text-secondary font-body-md">—</span>
                    @endif
                </td>
            </tr>

            <x-slot:pagination>
                <div class="flex justify-between items-center w-full">
                    <span class="text-body-md text-text-secondary font-body-md">Showing 1 to 2 of 2 entries</span>
                    <div class="flex gap-sm">
                        <button class="px-md py-sm rounded-full border border-border-divider text-text-secondary hover:bg-surface-container-high text-xs cursor-pointer select-none transition-all disabled:opacity-50" disabled>Previous</button>
                        <button class="px-md py-sm rounded-full bg-primary-container text-on-primary text-xs cursor-pointer select-none transition-all">1</button>
                        <button class="px-md py-sm rounded-full border border-border-divider text-text-secondary hover:bg-surface-container-high text-xs cursor-pointer select-none transition-all disabled:opacity-50" disabled>Next</button>
                    </div>
                </div>
            </x-slot:pagination>
        </x-tables.data-table>
    </div>
</x-layout.app>
