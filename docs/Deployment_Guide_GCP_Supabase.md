# Panduan Deployment: Supabase & Google Cloud Run (CV Akuna)

Dokumen ini berisi instruksi langkah demi langkah untuk melakukan *setup* infrastruktur *Production* untuk Sistem Manajemen Inventori CV Akuna menggunakan **Supabase** (Database & Storage) dan **Google Cloud Platform** (Hosting/Compute).

---

## Tahap 1: Setup Supabase (Database & Storage)
Supabase akan bertindak sebagai *Database PostgreSQL* dan *Object Storage* (untuk menyimpan file PDF laporan).

1. **Buat Akun & Proyek:**
   - Kunjungi [supabase.com](https://supabase.com) dan buat akun (atau login).
   - Klik **"New Project"**, beri nama (misalnya: `cv-akuna-inventory`), buat *password* database yang kuat, dan pilih region terdekat (misal: `Singapore`).
   - Tunggu beberapa menit hingga *database* selesai disiapkan.

2. **Dapatkan Kredensial Database:**
   - Masuk ke menu **Project Settings > Database**.
   - Cari bagian **Connection string (URI)**.
   - Salin URL tersebut. Formatnya akan seperti ini:
     `postgresql://postgres:[YOUR-PASSWORD]@db.xxxx.supabase.co:5432/postgres`
   - Kredensial ini nantinya akan dimasukkan ke variabel `DATABASE_URL` di server.

3. **Setup Supabase Storage (Untuk File Laporan PDF):**
   - Masuk ke menu **Storage** di sidebar kiri.
   - Klik **"New Bucket"**, beri nama `laporan-pdf` (Pastikan huruf kecil semua).
   - Atur bucket ini menjadi **Public** agar link download PDF bisa langsung diakses.

4. **Dapatkan API Keys Supabase:**
   - Masuk ke menu **Project Settings > API**.
   - Salin `Project URL` (sebagai `SUPABASE_URL`).
   - Salin `Project API keys (service_role)` (sebagai `SUPABASE_KEY`).

---

## Tahap 2: Setup Google Cloud Platform (GCP)
Google Cloud Run akan bertindak sebagai *server* (menjalankan kontainer Docker Laravel).

1. **Buat Proyek GCP:**
   - Login ke [Google Cloud Console](https://console.cloud.google.com/).
   - Buat proyek baru (misal: `cv-akuna-prod`). Catat **Project ID**-nya.
   - Pastikan Anda sudah mengaktifkan *Billing* untuk proyek ini (wajib untuk Cloud Run).

2. **Aktifkan API yang Dibutuhkan:**
   - Buka Cloud Shell (ikon terminal di kanan atas) atau cari di bilah pencarian:
   - Aktifkan API berikut:
     - **Cloud Run API**
     - **Artifact Registry API**
     - **IAM Credentials API**

3. **Buat Artifact Registry (Tempat menyimpan Docker Image):**
   - Buka menu **Artifact Registry**.
   - Klik **Create Repository**.
   - Nama: `akuna-registry`, Format: `Docker`, Region: `asia-southeast2 (Jakarta)`.

---

## Tahap 3: Setup GitHub Actions (Otomatisasi CI/CD)
Agar GitHub bisa melakukan *deploy* ke Google Cloud tanpa menggunakan *password* (untuk keamanan tinggi), kita harus menggunakan *Workload Identity Federation* (WIF).

1. **Buat Service Account GCP:**
   - Di GCP, buka menu **IAM & Admin > Service Accounts**.
   - Buat baru, beri nama `github-actions-deployer`.
   - Berikan izin (*Roles*):
     - `Cloud Run Admin`
     - `Artifact Registry Writer`
     - `Service Account User`

2. **Setup Workload Identity Federation (WIF):**
   - Ini adalah cara modern menghubungkan GitHub ke GCP. Prosesnya melibatkan pembuatan *Pool* dan *Provider* di GCP. (Silakan ikuti dokumentasi resmi Google Cloud *Workload Identity* untuk mendapatkan nama *Provider*).
   - Hasil akhir langkah ini adalah Anda akan mendapatkan *string* panjang bernama `WORKLOAD_IDENTITY_PROVIDER`.

3. **Masukkan Secrets ke GitHub:**
   - Buka repositori GitHub Anda, masuk ke **Settings > Secrets and variables > Actions**.
   - Tambahkan *New repository secret* berikut:
     - `GCP_PROJECT_ID` : (Berisi Project ID GCP Anda)
     - `GCP_SERVICE_ACCOUNT` : (Berisi email service account GCP yang dibuat di langkah 1)
     - `GCP_WORKLOAD_IDENTITY_PROVIDER` : (String WIF dari langkah 2)

---

## Tahap 4: Konfigurasi Environment Variables di Cloud Run
Setelah CI/CD berhasil men-deploy *image* Docker ke Cloud Run, Anda harus memasukkan konfigurasi `.env` Laravel ke pengaturan Cloud Run.

1. Buka layanan **Cloud Run** Anda di GCP Console.
2. Klik **Edit & Deploy New Revision**.
3. Buka tab **Variables & Secrets**.
4. Masukkan semua *environment variables* penting, di antaranya:
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `APP_URL` = (URL publik Cloud Run Anda)
   - `APP_KEY` = (Kunci base64 Laravel Anda)
   - `DB_CONNECTION` = `pgsql`
   - `DATABASE_URL` = (URL koneksi Supabase dari Tahap 1)
   - `CACHE_STORE` = `database` (Atau Redis jika Anda menggunakannya)
   - `FILESYSTEM_DISK` = `supabase`
   - `SUPABASE_URL` = (Dari Tahap 1)
   - `SUPABASE_KEY` = (Dari Tahap 1)
5. Simpan dan tunggu Cloud Run melakukan *deploy* ulang.

🎉 **Selesai! Sistem CV Akuna sekarang sudah *live* di internet dengan skala enterprise.**
