# Dashboard Monitoring System

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

Sebuah sistem pemantauan (monitoring) website yang dibangun menggunakan framework Laravel. Sistem ini dirancang untuk memantau status *uptime*, waktu respons, masa aktif sertifikat SSL, serta menangkap *snapshot* halaman secara otomatis. Sistem ini juga dilengkapi dengan notifikasi terintegrasi ke Telegram untuk pelaporan insiden (*downtime*).

## ✨ Fitur Utama

- **Uptime Monitoring**: Memantau ketersediaan situs web (HTTP Status) dan mengukur waktu respons.
- **SSL Certificate Monitoring**: Mengecek masa berlaku SSL dan memberikan peringatan sebelum kedaluwarsa.
- **Incident Management**: Mencatat secara otomatis setiap insiden ketika website tidak dapat diakses atau terjadi perubahan konten yang mencurigakan.
- **Content Snapshot**: Menyimpan *snapshot* HTML website saat terjadi insiden untuk analisis lebih lanjut.
- **Telegram Alert Integration**: Mengirimkan pemberitahuan otomatis ke grup Telegram saat terdeteksi *downtime* atau masalah SSL.
- **Role-Based Access Control (RBAC)**: Terdapat pembagian hak akses pengguna, yaitu Admin, Operator, dan Viewer.
- **Visual Dashboard**: Menampilkan grafik dan ringkasan statistik pemantauan.

## 🛠️ Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL atau MariaDB
- Web Server (Apache/Nginx/Laragon)

## 🚀 Panduan Instalasi

Ikuti langkah-langkah di bawah ini untuk menginstal dan menjalankan aplikasi di lingkungan lokal:

1. **Clone repository ini**
   ```bash
   git clone https://github.com/PutuDio/DashboardMonitoring.git
   cd DashboardMonitoring
   ```

2. **Install dependensi PHP dan Node.js**
   ```bash
   composer install
   npm install
   ```

3. **Kompilasi aset frontend (Vite)**
   ```bash
   npm run build
   ```

4. **Konfigurasi Environment**
   Salin file `.env.example` menjadi `.env`.
   ```bash
   cp .env.example .env
   ```
   Buka file `.env` dan sesuaikan pengaturan database Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=monitoring_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *(Opsional)* Jika Anda ingin mengaktifkan notifikasi Telegram, isi bagian berikut:
   ```env
   TELEGRAM_BOT_TOKEN=token_bot_anda
   TELEGRAM_CHAT_IDS=id_grup_atau_user
   ```

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Migrasi Database dan Seeding**
   Perintah ini akan membuat struktur tabel dan mengisi data *dummy* awal (seperti akun default).
   ```bash
   php artisan migrate --seed
   ```

7. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses di `http://localhost:8000`.

## ⏱️ Menjalankan Worker (Untuk Proses Monitoring)

Sistem ini sangat bergantung pada *background jobs* dan *scheduler* untuk melakukan pengecekan situs web secara berkala. Pastikan Anda menjalankan perintah berikut agar proses monitoring berjalan:

1. **Jalankan Queue Worker**:
   ```bash
   php artisan queue:work
   ```
2. **Jalankan Scheduler** (Bisa dimasukkan ke dalam Cron Job jika di server produksi):
   ```bash
   php artisan schedule:work
   ```

## 👥 Pengguna Default (Testing)

Saat menjalankan *seeder*, beberapa pengguna pengujian dibuat secara default:
- Terdapat akun **Admin**, **Operator**, dan **Viewer** (sesuaikan dengan instruksi pada seeder untuk melihat kredensial uji coba).

## 📄 Lisensi

Sistem ini bersifat *Open Source* dan dapat disesuaikan dengan kebutuhan instansi/organisasi Anda.
