# Titik Temu Kopi POS

Aplikasi kasir dan pemesanan kafe berbasis PHP native, PDO, dan MySQL. Sistem menyediakan autentikasi berbasis peran, ringkasan operasional, transaksi dine-in/takeaway, pengelolaan menu dan stok, serta laporan dengan ekspor CSV dan cetak PDF.

## Struktur proyek

```text
ruang_seduh/
|-- assets/              # CSS, JavaScript, dan ikon aplikasi
|-- views/               # Halaman login, kasir, laporan, dan administrasi
|-- config.php           # Koneksi PDO, sesi, autentikasi, dan helper bersama
|-- index.php            # Front controller, pemrosesan aksi, dan routing
|-- database.sql         # Skema dan data demo
|-- .htaccess            # Proteksi file sensitif dan directory listing
`-- README.md            # Panduan instalasi dan akun demo
```

Halaman dipisahkan di direktori `views/`; akses data menggunakan PDO prepared statements. `index.php` menjadi pintu masuk aplikasi dan menangani alur aksi.
## Teknologi

- PHP 8.1+ (ekstensi `pdo_mysql` dan `mbstring`)
- MySQL 8.0+ atau MariaDB 10.5+
- HTML, CSS, JavaScript tanpa framework
- Google Fonts DM Sans dan Manrope (fallback lokal tersedia)

## Instalasi lokal

1. Pasang XAMPP, Laragon, atau Apache/PHP dan MySQL yang menyediakan PHP 8.1+.
2. Salin folder proyek ke direktori web server, misalnya `htdocs/ruang-seduh`.
3. Jalankan layanan MySQL.
4. Buka phpMyAdmin, pilih menu **Import**, lalu impor `database.sql`. File ini membuat database `ruang_seduh`, struktur tabel, akun demo, menu, dan data transaksi contoh.
5. Jika kredensial MySQL Anda berbeda dari pengaturan lokal XAMPP, sesuaikan `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS` di `config.php`.
6. Buka `http://localhost/ruang-seduh/` di peramban.

Alternatif impor dari terminal:

```sh
mysql -u root -p < database.sql
```

## Akun demo

Kedua akun contoh menggunakan kata sandi `password` (disimpan sebagai hash bcrypt di database).

| Peran | Username | Email |
|---|---|---|
| Administrator | `admin` | `admin@ruangseduh.test` |
| Kasir / waiter | `kasir` | `kasir@ruangseduh.test` |

Ganti kata sandi demo sebelum dipakai pada server publik. Administrator dapat membuat akun baru dari halaman **Pengguna**.

## Modul dan alur

- **Login dan hak akses**: sesi aman, kata sandi `password_hash`, proteksi halaman, token CSRF, dan keluar melalui POST. Administrator dapat menambah akun dan mengaktifkan/menonaktifkan akun lain.
- **Dashboard**: omset, pesanan selesai, menu terlaris, peringatan stok, grafik tujuh hari, dan pesanan terbaru. Kasir melihat angka dan riwayat miliknya; administrator melihat keseluruhan aktivitas.
- **Transaksi**: nomor invoice otomatis, pilihan meja atau takeaway, item dan catatan khusus, PB1 10%, diskon, pembayaran tunai/non-tunai, kembalian, validasi stok, cetak nota thermal, dan perubahan status. Order, detail, serta pengurangan stok diproses dalam satu transaksi database; pembatalan mengembalikan stok.
- **Menu & stok**: administrator dapat mengelola kategori dan menu, harga jual/pokok, satuan, stok, serta status ketersediaan.
- **Laporan (administrator)**: rentang tanggal, kategori, metode pembayaran, ringkasan omset/diskon/pajak/laba kotor/nilai transaksi, ekspor CSV, dan cetak atau simpan PDF melalui dialog cetak peramban. Laba memakai harga pokok yang disimpan sebagai snapshot saat transaksi.

## Catatan operasional

- Invoice memiliki format `INV-POS-YYYYMMDD-XXXX` dan indeks unik di database.
- Menu nonaktif tidak dapat ditambahkan pada transaksi baru.
- Stok dianggap terpakai ketika pesanan disimpan. Pembatalan pesanan berstatus **Diproses** mengembalikan stok secara transaksional.
- Setiap perubahan data menggunakan formulir POST dengan token CSRF. Query aplikasi menggunakan PDO prepared statements.
- Aktifkan HTTPS pada server publik agar cookie sesi memakai atribut `Secure`.
