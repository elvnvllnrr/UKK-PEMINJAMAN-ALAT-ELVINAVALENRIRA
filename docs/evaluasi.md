# Evaluasi Proyek

## Fitur yang berjalan

- Login berbasis username, logout, registrasi peminjam, pemulihan password, verifikasi email, konfirmasi password, dan profil.
- CRUD admin untuk user, kategori, dan alat; admin dapat memantau peminjaman/pengembalian dan log aktivitas.
- Peminjam dapat melihat katalog, mengajukan peminjaman, melihat riwayat, dan mengajukan pengembalian.
- Petugas dapat melihat pengajuan, menyetujui/menolak, mencatat kondisi pengembalian, melihat stok, dan mencetak laporan.
- Denda terlambat dihitung per hari dan denda kerusakan/kehilangan dicatat terpisah.
- Perubahan stok berlangsung di dalam `DB::transaction` dengan penguncian baris; perubahan dibatalkan jika proses gagal.
- Constraint foreign key dan constraint unik mencegah referensi yatim dan pengembalian ganda.

## Bug/perbaikan

- Migration dengan suffix `.php.php` diberi nama Laravel yang valid.
- Kolom tanggal pengembalian memakai `tanggal_dikembalikan`; input model lama tetap dipetakan sementara untuk kompatibilitas internal.
- Kolom `petugas_id` ditambahkan untuk persetujuan dan pemrosesan pengembalian.
- Akses role yang tidak sesuai mengembalikan `403`, pengunjung diarahkan ke login.
- Route auth yang belum terdaftar (reset password, verifikasi, konfirmasi password, dan profile) didaftarkan tanpa menggandakan route login/logout khusus username.
- Laporan menampilkan daftar peminjaman dan pengembalian dengan status, peminjam, petugas, denda, filter status/rentang tanggal, dan CSS cetak.
- Log aktivitas mendapat halaman admin tersendiri.
- Penghapusan alat/user yang masih dipakai transaksi dicegah; penghapusan akun sendiri juga dicegah.

## Kekurangan yang diketahui

- Gaya halaman utama proyek saat ini memakai Bootstrap melalui CDN; build Vite tetap membangun aset dependency proyek, tetapi tampilan yang ada belum dikonversi ke Tailwind agar halaman lama tidak berubah tanpa kebutuhan.
- Akun demo memakai password sederhana untuk pengujian dan wajib diganti sebelum deployment.
- Integrasi pengiriman email produksi (SMTP) perlu dikonfigurasi; testing memakai mailer array.
- Database target dipastikan memakai MySQL lokal `root` tanpa password sesuai konfigurasi XAMPP yang ditemukan; lingkungan lain wajib mengatur kredensialnya sendiri.

## Rencana pengembangan

- Tambahkan SMTP/queue production dan hardening deployment.
- Tambahkan pagination/export CSV pada laporan bila volume transaksi bertambah.
- Tambahkan smoke test browser otomatis pada alur utama lintas role.
- Ganti akun demo dan aktifkan HTTPS sebelum aplikasi dipakai publik.

## Hasil verifikasi

Perintah verifikasi akhir dijalankan dari root proyek dengan hasil:

```text
php artisan migrate:fresh --seed : berhasil; seluruh migration dan UserSeeder selesai
php artisan route:list              : berhasil; 69 route terdaftar
php artisan test                    : 70 passed, 0 failed (272 assertions)
npm run build                       : berhasil; Vite membangun aset produksi
```

`npm run build` dijalankan melalui `cmd.exe /c` karena shell PowerShell lokal menerapkan execution policy pada shim npm. Hasilnya tetap menjalankan skrip `npm run build` yang sama.

Server Laravel berhasil dijalankan dan diuji di browser lokal. Bukti visual untuk lima skenario—dashboard admin, tambah alat, pengajuan, persetujuan, serta laporan berfilter—tersedia di [screenshots/README.md](screenshots/README.md). Ekspor skema dan data demo tersedia di [db_peminjaman_alat.sql](db_peminjaman_alat.sql).

`migrate:fresh --seed` dijalankan pada database `db_peminjaman_alat` sesuai permintaan dan menghapus data sebelumnya sebelum membangun ulang tabel. Pengujian Laravel memakai database SQLite in-memory dan tidak mengubah database MySQL.

Ekspor SQL dibuat dari database demo sebelum reset akhir sehingga berisi data contoh untuk mencoba laporan. Database lokal setelah verifikasi akhir berisi skema baru dan tiga akun seed; tabel alat/transaksi dapat diisi melalui aplikasi.
