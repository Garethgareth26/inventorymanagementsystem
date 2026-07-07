# Analisis Kebutuhan Sistem (Laravel)
## Sistem Manajemen Stok — CV Akuna

---

## Latar Belakang

Sistem ini dirancang untuk mendigitalisasi manajemen stok barang pada CV Akuna yang sebelumnya dikelola secara manual. Sistem akan mengotomatisasi pencatatan stok produk jadi, menghitung kuantitas pesanan optimal ke supplier, menentukan titik pemesanan kembali, serta menyediakan laporan bulanan siap cetak.

Sistem yang dirancang mencakup enam modul utama, yaitu:

1. Autentikasi dan Manajemen Hak Akses
2. Dashboard dan Pemantauan
3. Manajemen Inventori dan Mutasi Stok
4. Kalkulasi dan Optimasi Persediaan (EOQ, Safety Stock, dan Reorder Point)
5. Tracking Pesanan dan Smart Procurement
6. Pelaporan dalam format PDF

Sistem ditujukan untuk digunakan oleh dua peran pengguna, yaitu **Admin** dan **Owner**, dengan hak akses yang berbeda.

---

## Pengguna Sistem

### Aktor

**1. Owner**
- Hanya bisa melihat data (Read-Only) dan memantau seluruh aktivitas inventori secara real-time.
- Tidak memiliki tombol atau hak akses untuk menambah, mengedit, atau menghapus data apa pun di dalam sistem (mencegah manipulasi data).
- Bisa melihat dashboard analisis (EOQ, ROP, Safety Stock, Status Stok).
- Bisa mengunduh laporan bulanan dalam bentuk PDF.

**2. Karyawan (Admin)**
- Memiliki hak akses penuh untuk melakukan input, edit, dan hapus pada transaksi harian.
- Menginput data produk baru dan memperbarui parameter inventori (biaya, lead time).
- Mencatat mutasi stok masuk dan stok keluar.
- Melihat dashboard dan mengunduh laporan bulanan PDF.

---

## Analisis Kebutuhan Sistem

### 1. Login

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Login sistem | Pengguna dapat masuk ke sistem menggunakan username/email dan kata sandi. | Admin, Owner |
| Pemilihan peran saat login | Sistem menyediakan mekanisme penentuan peran (Admin/Owner) yang menentukan hak akses setelah login. | Admin, Owner |
| Pembatasan akses berbasis peran | Sistem menonaktifkan (mengunci) seluruh elemen input, tombol simpan, dan tombol tambah data apabila peran pengguna adalah Owner. | Sistem |
| Keluar sistem (logout) | Pengguna dapat mengakhiri sesi aktif secara aman. | Admin, Owner |

### 2. Dashboard dan Pemantauan

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Tampilan KPI ringkasan | Sistem menampilkan kartu ringkasan: total jenis bahan baku, nilai investasi tahunan, dan status logistik. | Admin, Owner |
| Visualisasi klasifikasi ABC | Sistem menampilkan grafik donat yang menunjukkan proporsi bahan baku pada Kelas A, B, dan C. | Admin, Owner |
| Visualisasi bahan baku termahal | Sistem menampilkan grafik batang horizontal untuk lima bahan baku dengan nilai tertinggi. | Admin, Owner |
| Notifikasi stok kritis | Sistem menampilkan lonceng notifikasi beserta daftar bahan baku yang berada di bawah titik aman. | Admin, Owner |
| Tabel peringatan stok kritis | Sistem menampilkan daftar bahan baku berstatus kritis beserta tombol tindak lanjut (mis. pengiriman PO darurat) yang hanya aktif bagi Admin. | Admin, Owner |

### 3. Manajemen Inventori dan Mutasi Stok

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Pemisahan gudang | Sistem menyediakan tampilan terpisah antara Gudang Bahan Baku dan Gudang Barang Jadi. | Admin, Owner |
| Pencarian dan filter data | Pengguna dapat mencari data berdasarkan kode/nama barang serta memfilter berdasarkan kategori. | Admin, Owner |
| Input mutasi barang masuk/keluar | Admin dapat menambahkan transaksi barang masuk maupun keluar beserta jumlah dan tanggal. | Admin |
| Riwayat pembaruan data | Sistem mencatat dan menampilkan tanggal pembaruan terakhir untuk setiap item persediaan. | Sistem |

### 4. Modul Kalkulasi dan Optimasi Persediaan

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Pemilihan bahan baku simulasi | Pengguna memilih satu jenis bahan baku dari daftar untuk disimulasikan. | Admin, Owner |
| Input parameter simulasi | Pengguna menginput biaya pesan baru dan lead time baru sebagai variabel simulasi. | Admin, Owner |
| Eksekusi perhitungan | Sistem menghitung ulang nilai EOQ, Safety Stock, dan Reorder Point berdasarkan parameter yang diinput. | Sistem |
| Perbandingan hasil | Sistem menampilkan tabel perbandingan antara nilai lama dan nilai hasil simulasi. | Admin, Owner |
| Penerapan parameter baru | Admin dapat menyimpan/menerapkan hasil simulasi sebagai parameter resmi yang berlaku pada sistem. | Admin |

### 5. Modul Tracking Pesanan dan Smart Procurement

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Ringkasan status logistik | Sistem menampilkan jumlah pesanan berdasarkan status: Menunggu, Dalam Proses, dan Diterima. | Admin, Owner |
| Log pengadaan | Sistem menampilkan riwayat pemesanan meliputi tanggal, kode bahan baku, jumlah pesan (EOQ), pemasok, rute pengiriman, dan status. | Admin, Owner |

### 6. Modul Pelaporan

| Kebutuhan | Deskripsi | Aktor |
|---|---|---|
| Filter rentang tanggal laporan | Pengguna dapat menentukan rentang tanggal sebelum mengunduh laporan. | Admin, Owner |
| Unduh laporan valuasi aset gudang | Sistem menghasilkan dokumen PDF berisi ringkasan nilai persediaan. | Admin, Owner |
| Unduh laporan performa supplier | Sistem menghasilkan dokumen PDF berisi evaluasi kinerja pemasok. | Admin, Owner |
| Unduh laporan mutasi bulanan | Sistem menghasilkan dokumen PDF berisi rekapitulasi transaksi barang masuk/keluar per bulan. | Admin, Owner |

---

## Struktur Halaman (Acuan Implementasi Laravel)

Rancangan antarmuka (wireframe) telah disusun sebelumnya dan dapat dijadikan acuan visual bagi pengembang. Berikut ringkasan struktur halaman yang perlu diimplementasikan dalam Laravel (disarankan menggunakan Blade dengan Livewire, atau Laravel dikombinasikan dengan Inertia.js apabila komponen interaktif berbasis JavaScript diperlukan).

| Halaman | Elemen Utama | Catatan RBAC |
|---|---|---|
| Login | Form username/email, kata sandi, pemilihan peran, tombol "Masuk ke Sistem" | - |
| Dashboard | 3 kartu KPI, grafik donat ABC, grafik batang 5 bahan baku termahal, tabel peringatan stok kritis | Tombol "Kirim PO Darurat" terkunci bagi Owner |
| Inventory & Mutasi Stok | Sub-tab gudang, kolom pencarian, filter kategori, tabel data, tombol input | Tombol "Input Barang Masuk/Keluar" dan "Edit" terkunci bagi Owner |
| Kalkulasi & Optimasi | Dropdown bahan baku, input biaya pesan dan lead time, tombol jalankan simulasi, tabel perbandingan | Tombol "Terapkan Parameter Baru" terkunci bagi Owner |
| Tracking & Procurement | Kartu status (Menunggu/Proses/Diterima), tabel log pengadaan | Seluruh elemen bersifat hanya-baca bagi kedua peran |
| Laporan PDF | Kartu laporan, filter rentang tanggal, tombol unduh | Tombol unduh aktif bagi kedua peran |

---

## Alur Sistem

### Alur Pemantauan dan Peringatan Stok Kritis

1. Sistem membandingkan `stok_saat_ini` setiap bahan baku dengan nilai `reorder_point` pada tabel `parameter_persediaan`.
2. Apabila stok berada di bawah `reorder_point`, sistem menandai status sebagai "kritis" dan menampilkan notifikasi pada lonceng peringatan serta tabel *Live Stock Critical Alert*.
3. Admin dapat menindaklanjuti peringatan dengan membuat pesanan pembelian darurat; Owner hanya dapat memantau status tersebut.

### Alur Simulasi dan Penerapan Parameter Persediaan

4. Pengguna memilih bahan baku dan menginput biaya pesan serta lead time baru.
5. Sistem menghitung EOQ = √(2DS/H), Safety Stock berdasarkan faktor pengaman dan variabilitas pemakaian, serta Reorder Point = (pemakaian harian × lead time) + Safety Stock.
6. Sistem menampilkan hasil perbandingan nilai lama dan nilai baru kepada pengguna.
7. Admin dapat menerapkan hasil simulasi sehingga memperbarui nilai resmi pada tabel `parameter_persediaan`; Owner tidak dapat melakukan tindakan ini.

### Alur Pengadaan (Procurement)

8. Admin membuat pesanan pembelian berdasarkan nilai EOQ yang berlaku, memilih pemasok, dan mencatat tanggal pesan.
9. Status pesanan diperbarui secara berkala: Menunggu → Dalam Proses → Diterima.
10. Ketika status menjadi "Diterima", sistem menambah nilai `stok_saat_ini` pada tabel `bahan_baku` secara otomatis melalui pencatatan pada tabel `mutasi_stok`.

---

## Usulan Struktur Basis Data (Acuan Migrasi Laravel)

Struktur ini dapat disesuaikan lebih lanjut oleh pengembang sesuai kebutuhan implementasi teknis.

### 6.1 Tabel `users`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas unik pengguna |
| name | varchar(100) | Nama pengguna |
| email | varchar(150), unique | Alamat email/username login |
| password | varchar(255) | Kata sandi terenkripsi (hashed) |
| role | enum('admin','owner') | Peran pengguna untuk keperluan RBAC |
| created_at / updated_at | timestamp | Waktu pencatatan sistem |

### 6.2 Tabel `kategori_barang`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas kategori |
| nama_kategori | varchar(100) | Contoh: Surfaktan, Emolien, Humektan |
| jenis_gudang | enum('bahan_baku','barang_jadi') | Menentukan kategori berlaku pada gudang yang mana |

### 6.3 Tabel `bahan_baku`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas unik |
| kode | varchar(20), unique | Contoh: BB01, BB02 |
| nama | varchar(150) | Nama bahan baku |
| kategori_id | bigint, foreign key | Relasi ke tabel `kategori_barang` |
| satuan | varchar(20) | Contoh: kg, liter |
| stok_saat_ini | decimal(12,2) | Jumlah stok terkini |
| harga_per_unit | decimal(14,2) | Digunakan untuk perhitungan nilai investasi dan analisis ABC |
| kelas_abc | enum('A','B','C') | Hasil klasifikasi analisis ABC |
| updated_at | timestamp | Tanggal pembaruan terakhir |

### 6.4 Tabel `barang_jadi`

Struktur kolom pada tabel ini serupa dengan tabel `bahan_baku` (`kode`, `nama`, `kategori_id`, `satuan`, `stok_saat_ini`, `updated_at`), disesuaikan untuk mencatat produk hasil produksi.

### 6.5 Tabel `mutasi_stok`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas transaksi |
| item_id | bigint, foreign key | Relasi ke `bahan_baku` atau `barang_jadi` (polymorphic) |
| jenis_item | enum('bahan_baku','barang_jadi') | Penentu tabel relasi (polymorphic) |
| jenis_transaksi | enum('masuk','keluar') | Jenis mutasi |
| jumlah | decimal(12,2) | Kuantitas mutasi |
| tanggal_transaksi | date | Tanggal transaksi terjadi |
| dicatat_oleh | bigint, foreign key ke `users` | Admin yang menginput transaksi (audit trail) |

### 6.6 Tabel `parameter_persediaan`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas parameter |
| bahan_baku_id | bigint, foreign key | Relasi ke tabel `bahan_baku` |
| permintaan_tahunan | decimal | Variabel D pada rumus EOQ |
| biaya_pesan | decimal | Variabel S (ordering cost) |
| biaya_simpan | decimal | Variabel H (holding cost) |
| lead_time_hari | integer | Waktu tunggu pemesanan |
| pemakaian_harian | decimal | Rata-rata pemakaian harian |
| eoq | decimal | Hasil perhitungan EOQ berlaku |
| safety_stock | decimal | Hasil perhitungan SS berlaku |
| reorder_point | decimal | Hasil perhitungan ROP berlaku |
| diperbarui_oleh | bigint, foreign key ke `users` | Admin yang menerapkan parameter (audit trail) |

### 6.7 Tabel `supplier`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas pemasok |
| nama_supplier | varchar | Nama perusahaan pemasok |
| rute_pengiriman | varchar | Contoh: Semarang, Jakarta |
| kontak | varchar | Nomor telepon/email pemasok |

### 6.8 Tabel `pesanan_pembelian` (procurement)

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | bigint, primary key | Identitas pesanan |
| tanggal_pesan | date | Tanggal pesanan dibuat |
| bahan_baku_id | bigint, foreign key | Relasi ke tabel `bahan_baku` |
| supplier_id | bigint, foreign key | Relasi ke tabel `supplier` |
| jumlah_pesan | decimal | Umumnya mengacu pada nilai EOQ berlaku |
| status | enum('menunggu','dalam_proses','diterima') | Status tracking pesanan |
| tanggal_diterima | date, nullable | Diisi saat status menjadi 'diterima' |

### 6.9 Tabel `laporan_log` (opsional)

Digunakan untuk mencatat riwayat pengunduhan laporan (jenis laporan, rentang tanggal, pengguna, dan waktu unduh) guna keperluan audit.

---

## Rumus Perhitungan Parameter Persediaan

| Parameter | Rumus | Keterangan Variabel |
|---|---|---|
| EOQ | EOQ = √(2DS / H) | D = permintaan tahunan; S = biaya pesan; H = biaya simpan per unit per tahun |
| Safety Stock | SS = Z × σ × √(Lead Time) | Z = faktor tingkat layanan; σ = standar deviasi pemakaian; Lead Time dalam hari/periode yang konsisten |
| Reorder Point | ROP = (Pemakaian Harian × Lead Time) + SS | Menentukan titik stok saat pemesanan ulang harus dilakukan |
