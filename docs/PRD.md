# Product Requirements Document (PRD)
## Sistem Manajemen Inventori & Optimasi Persediaan — CV Akuna

| Info | Keterangan |
|---|---|
| Versi Dokumen | 1.1 |
| Tanggal | 4 Juli 2026 |
| Klien | CV Akuna |
| Platform | Web Application (Laravel), arsitektur monolith, deployment serverless |
| Status | Draft — untuk direview bersama klien |

**Riwayat perubahan:**
- v1.0 — Draf awal berdasarkan dokumen analisis kebutuhan klien.
- v1.1 — Menambahkan keputusan arsitektur (monolith), finalisasi tech stack (PostgreSQL/Supabase menggantikan MySQL, hosting Google Cloud Run), dan daftar pertanyaan terbuka untuk klien.

---

## 1. Ringkasan Eksekutif

CV Akuna saat ini mengelola stok bahan baku dan barang jadi secara manual. Sistem ini akan menggantikan proses tersebut dengan platform berbasis web yang mengotomatisasi pencatatan stok, menghitung kuantitas pesanan optimal ke supplier (EOQ), menentukan kapan harus memesan ulang (Reorder Point), dan menghasilkan laporan bulanan siap cetak dalam format PDF.

Sistem melayani dua peran pengguna dengan hak akses berbeda: **Karyawan** (input, edit, hapus data operasional) dan **Owner** (read-only, pemantauan dan pengambilan keputusan strategis).

## 2. Latar Belakang & Masalah yang Diselesaikan

- Pencatatan stok manual rentan terhadap kesalahan pencatatan dan keterlambatan informasi.
- Tidak ada perhitungan baku untuk menentukan jumlah pesanan optimal, sehingga berisiko overstock (biaya simpan tinggi) atau stockout (produksi terhambat).
- Owner tidak punya visibilitas real-time terhadap kondisi gudang tanpa meminta laporan manual ke staf.
- Tidak ada audit trail yang jelas atas siapa mengubah data apa dan kapan.

## 3. Tujuan Produk

1. Digitalisasi pencatatan stok bahan baku dan barang jadi secara terpusat.
2. Otomatisasi perhitungan EOQ, Safety Stock, dan Reorder Point berbasis data historis.
3. Memberi peringatan dini saat stok mendekati/di bawah titik aman.
4. Menyediakan dashboard analitik (klasifikasi ABC, bahan baku termahal, status logistik).
5. Menjamin integritas data melalui pemisahan hak akses (RBAC) antara Karyawan dan Owner.
6. Menghasilkan laporan PDF siap unduh untuk kebutuhan audit dan evaluasi bulanan.

## 4. Ruang Lingkup

### 4.1 Termasuk dalam Ruang Lingkup (In-Scope)
- Modul Autentikasi & Manajemen Hak Akses (2 role: Karyawan, Owner)
- Dashboard KPI & visualisasi (grafik donat ABC, grafik batang, notifikasi stok kritis)
- Manajemen Inventori: gudang bahan baku & gudang barang jadi, mutasi stok masuk/keluar
- Kalkulasi & simulasi EOQ / Safety Stock / Reorder Point
- Tracking pesanan pembelian & smart procurement (status: Menunggu → Dalam Proses → Diterima)
- Modul Pelaporan PDF (valuasi aset gudang, performa supplier, mutasi bulanan)

### 4.2 Di Luar Ruang Lingkup (Out-of-Scope) — perlu dikonfirmasi ke klien
- Integrasi akuntansi/keuangan (mis. jurnal otomatis, hutang-piutang supplier)
- Modul produksi/manufaktur (BOM, work order)
- Aplikasi mobile native (Android/iOS)
- Notifikasi via WhatsApp/Email otomatis (kecuali diminta lebih lanjut)
- Multi-cabang/multi-gudang fisik di lokasi berbeda
- E-procurement terintegrasi langsung dengan sistem supplier

> Catatan: Bagian out-of-scope ini adalah asumsi awal berdasarkan dokumen kebutuhan yang ada — perlu dikonfirmasi eksplisit ke CV Akuna agar tidak ada scope creep saat development.

## 5. Peran Pengguna & Hak Akses (RBAC)

| Aspek | Karyawan | Owner |
|---|---|---|
| Login & lihat dashboard | ✅ | ✅ |
| Lihat data inventori & mutasi | ✅ | ✅ (read-only) |
| Input/edit/hapus mutasi stok | ✅ | ❌ |
| Input produk baru & parameter (biaya, lead time) | ✅ | ❌ |
| Jalankan simulasi EOQ/SS/ROP | ✅ | ✅ (lihat hasil, tidak bisa terapkan) |
| Terapkan hasil simulasi sebagai parameter resmi | ✅ | ❌ |
| Tindak lanjuti stok kritis (buat PO darurat) | ✅ | ❌ (hanya memantau) |
| Unduh laporan PDF | ✅ | ✅ |

**Prinsip desain:** seluruh elemen UI destruktif/mengubah data (tombol simpan, tambah, edit, hapus) di-*disable* di level frontend **dan** diblok di level backend (middleware/policy) ketika role = Owner, untuk mencegah manipulasi data lewat request langsung ke API/route.

## 6. Kebutuhan Fungsional (Ringkasan per Modul)

### 6.1 Autentikasi & Hak Akses
- Login dengan email/username + password, penentuan role otomatis dari data user (bukan dipilih manual saat login, untuk keamanan — role ditentukan sistem berdasarkan akun).
- Middleware pembatasan akses berbasis role di semua route yang bersifat mutasi data.
- Logout aman (invalidasi sesi).

### 6.2 Dashboard & Pemantauan
- Kartu ringkasan KPI: total jenis bahan baku, nilai investasi tahunan, status logistik.
- Grafik donat klasifikasi ABC.
- Grafik batang horizontal 5 bahan baku termahal.
- Notifikasi lonceng + tabel *Live Stock Critical Alert* untuk bahan baku di bawah Reorder Point.

### 6.3 Manajemen Inventori & Mutasi Stok
- Tampilan terpisah Gudang Bahan Baku vs Gudang Barang Jadi.
- Pencarian & filter berdasarkan kode/nama/kategori.
- Input mutasi barang masuk/keluar (khusus Karyawan) dengan jejak audit (siapa & kapan).
- Riwayat pembaruan otomatis tercatat sistem.

### 6.4 Kalkulasi & Optimasi Persediaan
- Simulasi EOQ, Safety Stock, dan ROP dengan input biaya pesan & lead time baru.
- Tabel perbandingan nilai lama vs hasil simulasi.
- Penerapan parameter baru sebagai nilai resmi (khusus Karyawan), tercatat audit trail.

### 6.5 Tracking Pesanan & Smart Procurement
- Ringkasan status logistik (Menunggu / Dalam Proses / Diterima).
- Log pengadaan lengkap: tanggal, kode bahan baku, jumlah (EOQ), supplier, rute pengiriman, status.
- Update status "Diterima" otomatis menambah `stok_saat_ini` via pencatatan `mutasi_stok`.

### 6.6 Pelaporan PDF
- Filter rentang tanggal sebelum unduh.
- 3 jenis laporan: valuasi aset gudang, performa supplier, mutasi bulanan — seluruhnya PDF, dapat diunduh oleh kedua role.

## 7. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
|---|---|
| Keamanan | Password ter-hash (bcrypt/argon2), proteksi CSRF, validasi role di server-side (bukan hanya UI), rate limiting di form login |
| Performa | Dashboard & tabel besar menggunakan pagination/lazy loading; query kalkulasi ABC di-cache/dijadwalkan agar tidak membebani setiap request |
| Auditability | Setiap perubahan data mutasi stok & parameter persediaan mencatat `dicatat_oleh`/`diperbarui_oleh` |
| Usability | UI responsif (desktop-first, karena dipakai staf gudang & owner di kantor), elemen terkunci untuk Owner diberi indikator visual jelas (bukan sekadar hilang) |
| Reliabilitas | Backup database terjadwal (harian/mingguan) |
| Skalabilitas | Struktur tabel mendukung penambahan role/gudang baru di masa depan tanpa refactor besar |

## 8. Arsitektur & Tech Stack

### 8.1 Keputusan Arsitektur

**Monolith**, bukan microservices atau API+SPA terpisah. Dengan skala 1 bisnis, 2 role pengguna, dan tim developer kecil, arsitektur monolith memberi kecepatan development tertinggi tanpa overhead operasional (tidak perlu API gateway, service discovery, atau orkestrasi banyak service). Backend dan frontend disajikan dari satu aplikasi Laravel yang sama (server-rendered dengan komponen reaktif via Livewire), tanpa REST API terpisah untuk versi MVP.

### 8.2 Tech Stack

| Layer | Pilihan | Alasan |
|---|---|---|
| Backend framework | Laravel 13.x (PHP 8.3+) | Versi stabil terbaru (rilis Maret 2026), zero breaking changes dari Laravel 12 |
| Arsitektur | Monolith, server-rendered | Skala kecil, tim kecil — lihat 8.1 |
| Autentikasi | Laravel Fortify / Breeze | Boilerplate login siap pakai, mudah dikustom untuk RBAC 2 role |
| Otorisasi/RBAC | Laravel Gate & Policy + middleware role | Cukup dengan fitur native Laravel untuk 2 role; tidak perlu package tambahan seperti Spatie Permission di versi ini |
| Frontend interaktif | Livewire 3 + Alpine.js | Reaktivitas dashboard (grafik, filter, notifikasi) tanpa build step SPA terpisah — mempercepat development solo |
| Styling | Tailwind CSS | Standar ekosistem Laravel, mempercepat implementasi sesuai wireframe |
| Charting | Chart.js atau ApexCharts | Untuk grafik donat ABC dan grafik batang horizontal |
| **Database** | **PostgreSQL (dikelola oleh Supabase)** | Diganti dari MySQL karena mengikuti pilihan hosting database di Supabase; struktur tabel tetap sama, hanya sedikit penyesuaian tipe kolom saat migration |
| Session, cache, queue | **Redis (Upstash)** | Wajib menggantikan driver `file` bawaan Laravel karena Cloud Run bersifat stateless (lihat 8.3) |
| Storage file | Google Cloud Storage atau Supabase Storage | Untuk menyimpan file PDF laporan, karena disk Cloud Run tidak persisten |
| Generator PDF | barryvdh/laravel-dompdf atau spatie/laravel-pdf | Untuk 3 jenis laporan PDF |
| Job terjadwal | Laravel Scheduler dipicu via **Cloud Scheduler** | Cloud Run tidak punya cron bawaan — Cloud Scheduler memanggil endpoint HTTP terproteksi secara berkala |
| Testing | Pest | Terutama untuk validasi RBAC dan rumus EOQ/SS/ROP |
| Version control | Git + GitHub | Kolaborasi dan riwayat perubahan kode |
| **Hosting/Compute** | **Google Cloud Run** | Scale-to-zero, cocok untuk budget terbatas dan traffic rendah (lihat 8.3) |
| CI/CD | GitHub Actions → Artifact Registry → Cloud Run | Build, test, dan deploy otomatis setiap push ke branch utama |

### 8.3 Rencana Deployment (Cloud Run + Supabase)

Dipilih karena selaras dengan sumber daya yang tersedia (kredit GCP terbatas, kebutuhan biaya rendah untuk sistem internal skala kecil).

**Alur:** GitHub Actions membangun & menguji kode → deploy image ke Cloud Run → aplikasi Laravel (monolith, PHP-FPM + Nginx dalam satu container) terhubung ke Supabase (PostgreSQL) untuk data utama, Upstash Redis untuk session/cache/queue, dan Google Cloud Storage untuk file laporan PDF. Cloud Scheduler memicu endpoint cron secara berkala untuk pengecekan stok kritis dan tugas terjadwal lainnya.

**Konsekuensi teknis yang perlu diperhitungkan tim development:**
- Session & cache **tidak boleh** pakai driver `file` — wajib `database` atau Redis, karena container Cloud Run tidak persisten antar instance.
- File yang digenerate (PDF laporan) harus langsung diunggah ke object storage, bukan disimpan di disk lokal container.
- Cold start beberapa detik akan terjadi saat aplikasi idle lalu diakses kembali (scale-to-zero) — dapat diterima untuk sistem internal, tapi perlu dikomunikasikan sebagai batasan ke klien.
- Supabase tier gratis akan **auto-pause setelah ±7 hari tanpa aktivitas** — perlu diaktifkan kembali manual jika sistem tidak diakses dalam periode tersebut (misal saat libur panjang).

**Alternatif jika kebutuhan berubah (misal traffic naik atau budget bertambah):**

| Opsi | Kelebihan | Kekurangan |
|---|---|---|
| Cloud Run + Supabase (dipilih) | Nyaris gratis untuk skala kecil, scale-to-zero hemat biaya | Perlu arsitektur stateless, ada cold start, DB bisa idle-pause |
| VPS (Contabo/Vultr, ~$5-6/bulan) | Setup persisten khas Laravel, tanpa cold start | Bukan gratis, perlu maintenance server sendiri |
| Railway / Render | Setup sangat mudah, ada Postgres bawaan | Free tier umumnya sleep juga saat idle |



## 9. Asumsi & Batasan

- Role ditentukan oleh akun user di database (bukan dipilih bebas saat login) — ini penyesuaian dari draf awal demi keamanan; perlu dikonfirmasi ke klien.
- Klasifikasi ABC dihitung otomatis oleh sistem berdasarkan `harga_per_unit × pemakaian`, bukan diinput manual (perlu konfirmasi metode/threshold A/B/C ke klien).
- Satu bahan baku memiliki satu set parameter persediaan aktif (bukan riwayat banyak versi) kecuali diminta lain.
- Sistem berjalan single-tenant untuk satu entitas usaha (CV Akuna), bukan multi-perusahaan.
- Database menggunakan PostgreSQL (Supabase), bukan MySQL — konsekuensi dari pilihan hosting; tidak mengubah struktur data secara konseptual.
- Aplikasi dirancang stateless (siap dijalankan di Cloud Run) — session, cache, dan queue wajib menggunakan backend eksternal (Redis), bukan penyimpanan file lokal.
- Sistem menerima kemungkinan cold start beberapa detik saat idle lama, dan kemungkinan database perlu diaktifkan manual jika tidak diakses lebih dari ±7 hari (batasan tier gratis Supabase) — ini trade-off yang disadari demi menekan biaya operasional di tahap awal.

## 10. Kriteria Sukses (Acceptance Criteria — level tinggi)

- Owner tidak dapat melakukan operasi tulis apa pun melalui UI maupun manipulasi request langsung.
- Perhitungan EOQ/SS/ROP menghasilkan nilai yang konsisten dengan rumus baku dan dapat diverifikasi manual.
- Notifikasi stok kritis muncul maksimal dalam waktu nyata/near real-time setelah stok melewati ROP.
- Laporan PDF dapat diunduh sesuai filter tanggal dan datanya akurat dibanding data mutasi.
- Seluruh aksi mutasi data tercatat dengan audit trail yang bisa ditelusuri.

## 11. Tahapan Pengembangan yang Disarankan (High-Level)

1. **Fase 0 — Fondasi:** PRD, ERD, tech stack (dokumen ini), setup project & environment.
2. **Fase 1:** Modul Autentikasi & RBAC + struktur dashboard kosong.
3. **Fase 2:** Manajemen Inventori & Mutasi Stok (CRUD inti).
4. **Fase 3:** Modul Kalkulasi EOQ/SS/ROP + integrasi ke dashboard (ABC, notifikasi kritis).
5. **Fase 4:** Tracking Pesanan & Smart Procurement.
6. **Fase 5:** Modul Pelaporan PDF.
7. **Fase 6:** QA, UAT bersama klien, deployment.

## 12. Lampiran — Pertanyaan Terbuka untuk Klien

Daftar ini sebaiknya dikonfirmasi ke CV Akuna sebelum development masuk ke fase inti, agar tidak terjadi perubahan scope di tengah jalan.

**Autentikasi & Role**
1. Role user ditentukan otomatis dari akun (bukan dipilih manual saat login) — apakah disetujui?
2. Apakah akan ada role tambahan di luar Karyawan/Owner di masa depan (mis. Supervisor, Finance)?

**Perhitungan & Klasifikasi**
3. Threshold pembagian kelas A/B/C berdasarkan apa persisnya? (mis. A = 80% nilai investasi kumulatif, B = 15%, C = 5%)
4. Faktor tingkat layanan (Z) untuk Safety Stock memakai standar berapa (mis. 95% → Z=1.65), atau ada kebijakan internal?
5. Pemakaian harian & standar deviasi dihitung dari data histori berapa bulan/hari terakhir?
6. Apakah histori tiap kali parameter disimulasikan/diterapkan perlu disimpan sebagai log, atau cukup satu nilai aktif per bahan baku?

**Notifikasi & Laporan**
7. Notifikasi stok kritis cukup di dalam sistem saja, atau perlu juga dikirim ke WhatsApp/Email?
8. Apakah ada format/template laporan PDF khusus (logo, kop surat) yang harus diikuti?

**Data & Operasional**
9. Apakah sudah ada data stok/supplier/histori transaksi dalam Excel yang perlu diimpor ke sistem baru?
10. Berapa perkiraan jumlah user aktif (staf gudang + owner) dan pola pemakaian (jam kerja saja atau 24 jam)?
11. Apakah sistem hanya diakses di jaringan kantor, atau perlu akses remote dari luar?
12. Siapa yang akan menanggung biaya hosting setelah versi awal — pihak developer atau CV Akuna langsung?

**Timeline & Ruang Lingkup**
13. Target tanggal go-live?
14. Konfirmasi ulang bagian *out-of-scope* pada Bagian 4.2 — apakah benar semuanya tidak dibutuhkan di versi ini?

---
*Dokumen ini adalah draf berbasis dokumen analisis kebutuhan yang diberikan klien, disertai keputusan arsitektur dan tech stack final dari sesi perencanaan teknis. Bagian yang ditandai "perlu dikonfirmasi" sebaiknya divalidasi ke CV Akuna sebelum masuk tahap development agar tidak terjadi perubahan scope di tengah jalan.*
