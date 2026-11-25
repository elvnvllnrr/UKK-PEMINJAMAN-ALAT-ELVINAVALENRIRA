# Alur dan Dokumentasi Modul

## Alur login dan role

```mermaid
flowchart TD
    A[Isi username dan password] --> B{Kredensial valid?}
    B -- Tidak --> C[Tampilkan pesan kesalahan]
    B -- Ya --> D[Buat ulang session]
    D --> E[Catat log login]
    E --> F{Role}
    F -->|Admin| G[Dashboard admin]
    F -->|Petugas| H[Dashboard petugas]
    F -->|Peminjam| I[Dashboard peminjam]
    G --> J{Akses route sesuai role?}
    H --> J
    I --> J
    J -- Tidak --> K[HTTP 403]
```

Logout mencatat log, mengakhiri guard, menginvalidasi session, lalu menghasilkan CSRF token baru.

## Alur pengajuan dan persetujuan

```mermaid
flowchart TD
    A[Peminjam memilih alat dan jumlah] --> B[Validasi tanggal, pilihan, dan stok]
    B --> C{Valid?}
    C -- Tidak --> D[Tampilkan kesalahan; transaksi batal]
    C -- Ya --> E[DB::transaction dan lock stok]
    E --> F[Simpan peminjaman Menunggu Persetujuan]
    F --> G[Simpan detail alat]
    G --> H[Stok tetap]
    H --> I[Petugas memeriksa tanggal mulai dan stok terbaru]
    I --> J{Disetujui?}
    J -- Tidak --> K[Simpan status Ditolak dan log]
    J -- Ya --> L[DB::transaction dan lock peminjaman serta stok]
    L --> M{Tanggal mulai dan stok cukup?}
    M -- Tidak --> N[Rollback seluruh perubahan]
    M -- Ya --> O[Kurangi stok dan catat petugas]
    O --> P[Simpan status Dipinjam]
```

## Alur pengembalian dan denda

```mermaid
flowchart TD
    A[Peminjam mengajukan pengembalian] --> B[Lock peminjaman]
    B --> C{Aktif dan belum diajukan?}
    C -- Tidak --> D[Tolak pengajuan duplikat]
    C -- Ya --> E[Simpan waktu pengajuan dan log]
    E --> F[Petugas mencatat tanggal dan kondisi tiap detail]
    F --> G[Lock peminjaman serta data alat dalam transaksi]
    G --> H{Masih dapat diproses satu kali?}
    H -- Tidak --> I[Rollback dan tampilkan error]
    H -- Ya --> J[Hitung hari setelah jatuh tempo]
    J --> K[Denda terlambat = hari terlambat x tarif harian]
    K --> L[Tambahkan denda kerusakan/kehilangan terpisah]
    L --> M{Kondisi alat}
    M -->|Baik| N[Tambahkan jumlah ke stok]
    M -->|Rusak atau Hilang| O[Stok tidak berubah]
    N --> P[Simpan catatan, status, petugas, dan log]
    O --> P
    P --> Q[Commit transaksi]
```

Tarif harian dibaca dari `config/peminjaman.php`. Perhitungan dimulai pada tanggal setelah `tanggal_kembali_rencana`; denda kerusakan/kehilangan diwajibkan jika kondisi detail bukan `Baik`.

## Dokumentasi modul

| Modul | Input | Proses | Output | Controller / method |
|---|---|---|---|---|
| Login/logout | Username, password | Autentikasi, regenerasi/invalidate session, catat log | Dashboard sesuai role / kembali ke login | `Admin\LoginLogoutController@login`, `logout` |
| User | Nama, email, username, role, password | Validasi unik, Hash password, cegah penghapusan akun/transaksi yang masih dipakai | Daftar dan akun tersimpan | `Admin\UserController` CRUD |
| Kategori | Nama, deskripsi | Validasi unik dan cegah hapus kategori yang masih dipakai | Kategori alat | `Admin\KategoriController` CRUD |
| Alat | Nama, kategori, kode, stok, kondisi, foto | Validasi stok dan kategori; cegah hapus alat yang punya detail transaksi | Inventaris/katalog | `Admin\DataAlatController` CRUD |
| Pengajuan peminjaman | Daftar alat, jumlah, tanggal pinjam/rencana kembali | Validasi batas stok, transaction, simpan status menunggu tanpa mengurangi stok | Peminjaman dan detail | `Admin\PeminjamanController@ajukanPeminjaman` |
| Persetujuan | ID peminjaman | Kunci transaksi/stok, validasi tanggal mulai dan stok, kurangi stok atau tolak | Status dan petugas persetuju | `Admin\PeminjamanController@approve`, `reject` |
| Pengajuan pengembalian | ID peminjaman milik peminjam | Kunci peminjaman dan mencegah pengajuan ganda | Timestamp pengajuan untuk petugas | `Admin\PeminjamanController@ajukanPengembalian` |
| Proses pengembalian | Tanggal, kondisi tiap detail, denda kerusakan, catatan | Lock data, hitung denda, update stok hanya untuk alat baik, tulis log dalam transaksi | Status dan rincian pengembalian | `Admin\PengembalianController@store` |
| Laporan transaksi | Status, rentang tanggal | Query database peminjaman/pengembalian dan eager load relasi | Tabel lengkap yang dapat dicetak | `Auth\Petugas\CetakLaporanController@index` |
| Log aktivitas | Tidak ada input | Ambil log terbaru dan nama pengguna | Tabel log terpaginasikan | `Admin\LogAktivitasController@index` |
