# 🧠 Context for AI: Comprehensive Documentation Generation

*Berikan seluruh dokumen ini kepada AI pilihan Anda (ChatGPT, Claude, Gemini) untuk menghasilkan **Satu Buku Panduan Lengkap (Mega-Document)** yang mencakup aspek teknis, panduan pengguna, dan arsitektur.*

---

## 1. Identitas Proyek & Akses
- **Nama Aplikasi:** Inventory Management System CV Akuna
- **Link Repositori Kode:** `https://github.com/Garethgareth26/inventorymanagementsystem.git`
- **URL Akses Lokal:** `http://localhost:8000` (dapat dijalankan via perintah `php artisan serve`)

## 2. Tumpukan Teknologi (Tech Stack) & Deployment Target
- **Backend:** Laravel 12 (PHP 8.4) - Arsitektur Modular Monolith.
- **Frontend:** Livewire 3 + Alpine.js + Tailwind CSS.
- **Database:** PostgreSQL (Lingkungan produksi) / MySQL (Lokal).
- **Infrastruktur Masa Depan (Rencana):** Deployment di Google Cloud Run (Dockerized) dan menggunakan **Supabase** untuk Database & Storage (penyimpanan PDF file).
- **Engine PDF:** `barryvdh/laravel-dompdf` (Saat ini _stream_ langsung, kelak akan di-_upload_ ke Supabase Storage).

## 3. Kredensial Uji Coba (Dummy Accounts)
Sistem memiliki dua jenis peran dengan kredensial bawaan:
1. **Akun Owner (Pemilik)**
   - Email: `owner@akuna.com` | Password: `password`
   - *Hak Akses:* Read-Only untuk logistik. Bisa mengelola Karyawan dan mengatur Konfigurasi Sistem (Default Parameters).
2. **Akun Karyawan (Operator)**
   - Email: `karyawan@akuna.com` | Password: `password`
   - *Hak Akses:* Full akses mutasi stok, pembuatan PO, penambahan master data, dan entry produksi.

## 4. Struktur Database & Skema (ERD Context)
Berikut adalah gambaran relasi tabelnya (gunakan data ini untuk mem-build visualisasi ERD dengan *Mermaid* nantinya):

1. `users` (id, name, email, password, role_id) terhubung ke `roles` (id, name, slug).
2. `bahan_baku` (kode, nama, satuan, stok_saat_ini, harga_satuan, lead_time_hari, supplier_id).
3. `finished_goods` (kode, nama, satuan, stok_saat_ini).
4. `suppliers` (kode, nama, alamat, kontak, is_active).
5. `inventory_parameters` (bahan_baku_id, kebutuhan_tahunan, standar_deviasi_harian, biaya_pesan, biaya_simpan_persen, eoq, safety_stock, reorder_point, z_factor, historical_window_months).
6. `bom` (finished_goods_id, bahan_baku_id, qty_per_unit, satuan) -> *Bill of Materials*.
7. `production_entries` (finished_goods_id, jumlah_diproduksi, tanggal_produksi, dicatat_oleh).
8. `mutasi_stok` (bahan_baku_id, finished_goods_id, jenis_mutasi: MASUK/KELUAR, jumlah, tanggal, sumber, po_id, production_entry_id).
9. `pesanan_pembelian` (kode_po, bahan_baku_id, supplier_id, qty_pesanan, harga_satuan, total_harga, status, jenis: RUTIN/DARURAT).
10. `system_settings` (key, value).
11. `audit_logs` (user_id, action, subject_type, subject_id, old_values, new_values).

## 5. Fitur Bisnis Kritis (Business Logic)
1. **Validasi Lead Time Supplier Otomatis:** 
   Jika Karyawan memasukkan bahan baku:
   - Alamat mengandung "jakarta" -> Maks 2 hari.
   - Alamat mengandung "jogja", "yogyakarta", "bantul", "sleman", "gunungkidul", atau "kulon progo" -> Maks 1 hari.
   - Selain itu -> 3 hingga 5 hari.
2. **Atomic Stock Protection:** 
   Operasi stok (terutama saat Entry Produksi) dikunci pada level database menggunakan `lockForUpdate()`. Jika stok bahan baku tidak mencukupi untuk memproduksi *Barang Jadi*, transaksi mutasi stok dibatalkan seketika (*rolled back*).
3. **Smart Procurement (PO Cerdas):**
   Memanfaatkan algoritma *Economic Order Quantity* (EOQ), *Safety Stock* (SS), dan *Reorder Point* (ROP). Saat stok bahan baku menyentuh ROP, status logistik menjadi "Kritis", dan sistem membuka akses pembuatan "PO Darurat".

---

## 💡 PROMPT UNTUK DIKIRIM KE AI 💡
*(Copy tulisan di bawah ini dan Paste ke ChatGPT/Claude/Gemini Anda)*

> "Gunakan seluruh informasi dari 'Konteks Sistem CV Akuna' di atas. Saya ingin kamu bertindak sebagai *Technical Writer* profesional. Buatkan **SATU BUKU PANDUAN LENGKAP (Mega-Document)** dalam format Markdown yang menggabungkan Dokumentasi Teknis dan User Manual untuk proyek ini.
>
> Struktur dokumen HARUS persis seperti ini:
> 
> # Buku Panduan Lengkap & Dokumentasi Sistem CV Akuna
> 
> **Bab 1: Halaman Utama & Pendahuluan**
> (Daftar Halaman/Daftar Isi, Ringkasan fungsi utama aplikasi)
> 
> **Bab 2: Tech Stack & Panduan Deployment**
> (Jelaskan teknologi yang digunakan, mulai dari Laravel, Livewire, Tailwind. Berikan juga panduan *step-by-step* cara me-running app di lokal (`git clone`, `composer install`, `.env`, `php artisan serve`), serta rancangan arsitektur deployment masa depan di Google Cloud Run + Database/Storage Supabase)
> 
> **Bab 3: Arsitektur Database & Skema ERD**
> (Jelaskan seluruh tabel dan fungsinya. Kemudian WAJIB buatkan satu blok kode visualisasi ERD menggunakan sintaks `mermaid` yang menggambarkan relasi antar tabel berdasarkan konteks yang diberikan)
> 
> **Bab 4: Panduan Penggunaan (User Manual)**
> - **Akses Sistem:** URL aplikasi lokal, cara login, beserta kredensial akun dummy untuk Owner dan Karyawan.
> - **Panduan Untuk Karyawan:** *Step-by-step* cara menggunakan modul Manajemen Data (termasuk aturan otomatis deteksi *Lead Time* Supplier), cara melakukan Penyesuaian Stok Manual, cara menjalankan Simulasi Parameter (EOQ), cara mencatat Entry Produksi, dan cara melakukan Pemesanan (PO Darurat/Rutin).
> - **Panduan Untuk Owner:** *Step-by-step* memantau Dashboard analitik, mencetak Laporan Mutasi/Valuasi, mengatur Sistem Parameter, dan Manajemen Karyawan.
> 
> Tulis dengan bahasa Indonesia yang rapi, profesional, dan sangat mendetail. Jangan abaikan satu pun persyaratan di atas."
