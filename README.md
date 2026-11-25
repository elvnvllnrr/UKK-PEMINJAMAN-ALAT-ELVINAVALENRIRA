# Aplikasi Peminjaman Alat

Aplikasi Laravel untuk mengelola akun, kategori dan inventaris alat, pengajuan peminjaman, persetujuan petugas, pengembalian, denda, dan log aktivitas. Tampilan mengikuti layout aplikasi yang sudah ada.

## Persyaratan

- PHP 8.2 atau lebih baru beserta ekstensi Laravel yang dibutuhkan.
- Composer.
- MySQL 8 atau MariaDB yang mendukung foreign key dan transaksi InnoDB.
- Node.js dan npm untuk membangun aset frontend.

## Instalasi lokal

1. Buat database MySQL:

   ```sql
   CREATE DATABASE db_peminjaman_alat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Salin `.env.example` menjadi `.env`, lalu atur koneksi MySQL. Konfigurasi XAMPP lokal biasanya memakai `root` tanpa password; isi `DB_PASSWORD` sesuai konfigurasi MySQL setempat.

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_peminjaman_alat
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Pasang dependency, buat application key, lalu siapkan database dan aset:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate:fresh --seed
   npm install
   npm run build
   ```

   **Perhatian:** `migrate:fresh` menghapus semua tabel pada database yang dikonfigurasi. Untuk memperbarui database berisi data, gunakan `php artisan migrate` sebagai gantinya.

4. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

   Buka alamat yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.

## Akun demo

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `password` |
| Petugas | `petugas` | `password` |
| Peminjam | `peminjam` | `password` |

Akun dibuat oleh `DatabaseSeeder` melalui `UserSeeder`, dan password disimpan menggunakan `Hash::make`. Ganti password demo sebelum aplikasi dipakai pada lingkungan bersama atau produksi.

## Alur utama

1. Peminjam memilih alat di katalog, mengisi tanggal dan jumlah, lalu mengirim pengajuan. Stok belum berubah.
2. Petugas memeriksa ketersediaan dan menyetujui atau menolak pengajuan. Persetujuan mengurangi stok secara transaksional.
3. Peminjam mengajukan pengembalian satu kali. Petugas mencatat tanggal serta kondisi setiap alat.
4. Pengembalian menaikkan stok hanya untuk kondisi `Baik`. Keterlambatan dihitung per hari setelah tanggal jatuh tempo; denda kerusakan/kehilangan disimpan terpisah.
5. Admin mengelola data master dan dapat memantau log aktivitas.

Semua perubahan stok menggunakan `DB::transaction` dan penguncian baris (`lockForUpdate`) agar pembaruan status, detail, denda, dan stok tidak tersimpan sebagian. Constraint unik pada `pengembalian.peminjaman_id` juga mencegah lebih dari satu catatan pengembalian untuk satu peminjaman.

## Fitur dan route utama

- Admin: `/admin/dashboard`, `/admin/users`, `/admin/kategori`, `/admin/alat`, `/admin/peminjaman`, `/admin/pengembalian`, `/admin/log-aktivitas`.
- Petugas: `/petugas/dashboard`, `/petugas/peminjaman`, `/petugas/pengembalian`, `/petugas/laporan/pengembalian`.
- Peminjam: `/peminjam/dashboard`, `/peminjam/katalog`, `/peminjam/peminjaman`.
- Route profile, verifikasi email, konfirmasi password, serta reset password didaftarkan dari `routes/auth.php`.
- Route dengan role berbeda mengembalikan HTTP `403`; pengunjung yang belum login diarahkan ke `/login`.

Halaman laporan petugas memuat daftar peminjaman dan pengembalian, status transaksi, peminjam, petugas, denda, rentang tanggal, filter status, serta tombol cetak browser. Navigasi dan filter tidak ikut tercetak.

## Pengujian dan build

```bash
php artisan test
npm run build
```

Pengujian menggunakan database SQLite in-memory dari `phpunit.xml`; pengujian tidak menghapus database MySQL lokal. Daftar hasil uji dokumentasi tersedia pada [docs/evaluasi.md](docs/evaluasi.md), dengan skenario tambahan pada [docs/screenshots/README.md](docs/screenshots/README.md).

## Dokumentasi

- [ERD database](docs/ERD.md)
- [Alur dan dokumentasi modul](docs/alur-dan-modul.md)
- [Evaluasi dan hasil verifikasi](docs/evaluasi.md)
- [Export SQL database](docs/db_peminjaman_alat.sql)
- [Screenshot skenario](docs/screenshots/)
