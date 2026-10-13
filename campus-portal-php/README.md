# Portal Kampus Fiktif - PHP (Latihan Keamanan Web)

Aplikasi PHP murni + SQLite (PDO) untuk latihan. **Semua akun dan data sintetis.**
Jalankan hanya di komputer sendiri. Jangan di-deploy ke internet.

## Fitur
- **Mahasiswa**: login, melihat nilai, mengunggah tugas, berdiskusi di forum mata kuliah.
- **Dosen**: memberi nilai & catatan pada tugas mahasiswa, membuat tugas baru, ikut forum.
- **Admin**: membuat akun, mengubah data/peran akun, menonaktifkan/mengaktifkan akun, reset password,
  memantau nilai dan forum.
- Setiap peran punya beranda sendiri dengan ringkasan; halaman login menampilkan daftar akun latihan.

## Kebutuhan
PHP 8.1+ dengan ekstensi `pdo_sqlite` (sudah ada di XAMPP/Laragon/PHP standar).

## Menjalankan
```bash
cd campus-portal-php
php -S 127.0.0.1:8000 -t public
```
Buka http://127.0.0.1:8000 . Database `data/campus.db` dibuat otomatis saat pertama diakses
(hapus folder `data/` untuk reset data).

Jika memakai XAMPP/Laragon: arahkan document root ke folder `public/`
(folder `includes/` dan `data/` sebaiknya berada di luar document root).

## Akun sintetis (unik semua)
| Peran | Username | Password |
|---|---|---|
| Admin | admin.sistem | Adm!n-Kampus-7391 |
| Dosen | dosen.hartono | Dsn#Hartono-5528 |
| Dosen | dosen.sulastri | Dsn#Sulastri-4417 |
| Mahasiswa | mhs.rina | Mhs#Rina-2026-81 |
| Mahasiswa | mhs.dimas | Mhs#Dimas-2026-42 |
| Mahasiswa | mhs.putri | Mhs#Putri-2026-67 |
| Mahasiswa | mhs.fajar | Mhs#Fajar-2026-15 |

## Kerentanan yang sengaja dibuat (2)

### 1. IDOR / kontrol akses lemah
- **Lokasi**: `public/grades.php`, parameter `student_id`.
- **Penyebab**: server tidak memeriksa apakah `student_id` milik pengguna yang login,
  sehingga mahasiswa dapat melihat nilai mahasiswa lain dengan mengganti ID di URL.
- **Cara mengamati (lab)**: login sebagai `mhs.rina` (ID 4), buka *Nilai*
  (`grades.php?student_id=4`), lalu ubah ID ke 5 atau 6.
- **Perbaikan**: tambahkan
  `if ($me['role'] === 'mahasiswa' && $sid !== (int)$me['id']) forbid();`

### 2. Stored XSS
- **Lokasi**: `public/discussion.php` (`<?= $p['body'] ?>` tanpa escape; pesan disimpan tanpa sanitasi).
- **Penyebab**: isi pesan forum dirender sebagai HTML mentah, sehingga markup/skrip yang
  disimpan akan dieksekusi di browser setiap pengguna yang membuka halaman.
- **Cara mengamati (lab)**: kirim pesan berisi tag HTML sederhana, mis. `<b>tebal</b>`;
  bila tampil tebal, input tidak di-escape. Uji dengan akun lab saja.
- **Perbaikan**: gunakan `e($p['body'])` (htmlspecialchars) saat menampilkan dan/atau sanitasi
  input dengan allowlist; tambahkan header `Content-Security-Policy`.

## Bagian yang sengaja dibuat aman
Prepared statement PDO, token CSRF di semua form POST, `password_hash`/`password_verify`,
pengecekan peran untuk dosen & admin, validasi ekstensi berkas dan nama berkas acak,
`session_regenerate_id` saat login, cookie sesi HttpOnly.
