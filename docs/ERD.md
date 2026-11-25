# ERD Database Peminjaman Alat

```mermaid
erDiagram
    USERS ||--o{ PEMINJAMAN : mengajukan
    USERS o|--o{ PEMINJAMAN : menyetujui
    USERS o|--o{ PENGEMBALIAN : memproses
    USERS o|--o{ LOG_AKTIVITAS : melakukan
    KATEGORI ||--o{ ALAT : mengelompokkan
    PEMINJAMAN ||--|{ DETAIL_PEMINJAMAN : memiliki
    ALAT ||--o{ DETAIL_PEMINJAMAN : dipinjam
    PEMINJAMAN ||--o| PENGEMBALIAN : dikembalikan

    USERS {
        bigint id PK
        string name
        string username UK
        string email UK
        string role
        string password
    }
    KATEGORI {
        bigint id PK
        string nama_kategori
        text deskripsi
    }
    ALAT {
        bigint id PK
        bigint kategori_id FK
        string nama_alat
        string kode_alat UK
        int stok
        string kondisi
    }
    PEMINJAMAN {
        bigint id PK
        bigint user_id FK
        bigint petugas_id FK
        date tanggal_pinjam
        date tanggal_kembali_rencana
        timestamp pengembalian_diajukan_at
        string status
    }
    DETAIL_PEMINJAMAN {
        bigint id PK
        bigint peminjaman_id FK
        bigint alat_id FK
        int jumlah
        string kondisi_pinjam
        string kondisi_kembali
    }
    PENGEMBALIAN {
        bigint id PK
        bigint peminjaman_id FK_UK
        bigint petugas_id FK
        date tanggal_dikembalikan
        string status
        decimal denda
        decimal denda_kerusakan
    }
    LOG_AKTIVITAS {
        bigint id PK
        bigint user_id FK
        string aktivitas
        text deskripsi
        timestamp created_at
    }
```

`user_id` pada log boleh `NULL` agar histori tetap ada setelah akun dihapus. `petugas_id` boleh `NULL` untuk data lama atau jika akun petugas dihapus. `peminjaman_id` pada pengembalian unik untuk memastikan satu peminjaman hanya diproses sekali. Foreign key alat dan kategori mencegah penghapusan data master yang masih dipakai transaksi.
