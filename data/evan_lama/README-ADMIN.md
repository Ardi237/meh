# Penambahan Role Admin - CeLOE LMS

## Role yang tersedia
- **Admin**: melihat seluruh mata kuliah, mengelola mata kuliah/materi/tugas, melihat pengumpulan, serta mengelola akun dan role pengguna.
- **Dosen**: mengelola mata kuliah yang diampu, materi, tugas, serta persetujuan mahasiswa pada kelasnya sendiri.
- **Mahasiswa**: melihat katalog, mengajukan pendaftaran kelas, melihat materi/tugas setelah disetujui, dan mengumpulkan tugas sendiri.

## Akun awal setelah seeding
- Admin: `admin@celoe.test` / `password`
- Dosen: `dosen@celoe.test` / `password`
- Mahasiswa: `mahasiswa@celoe.test` / `password`

Segera ganti password akun contoh setelah login. Jangan gunakan password contoh untuk server produksi.

## Cara menerapkan
1. Ekstrak ZIP ini ke folder proyek Laravel (gabungkan dengan struktur yang sudah ada).
2. Pastikan file `.env` milikmu tetap dipakai dan jangan menimpa dengan file `.env` dari sumber lain.
3. Jalankan:
   ```bash
   php artisan optimize:clear
   php artisan db:seed
   ```
4. Login dengan akun Admin di atas.
5. Buka menu **Kelola Pengguna** untuk mengubah role akun yang sudah terdaftar menjadi Dosen atau Admin.

Jika database belum dibuat/migration belum dijalankan, jalankan `php artisan migrate` terlebih dahulu. Jika database sudah berisi data, jangan menjalankan `migrate:fresh` karena perintah itu menghapus seluruh tabel dan data.

## Pengamanan yang diterapkan
- Semua halaman selain login/register wajib login (`auth` middleware).
- Endpoint login dan register dibatasi 5 percobaan per menit.
- Register publik selalu membuat role `mahasiswa`; input role dari browser tidak digunakan.
- Route pengelolaan pengguna hanya dapat diakses role `admin`.
- Admin tidak dapat menghapus atau menurunkan role akun Admin yang sedang dipakai.
- Dosen dibatasi pada kelas yang diampu.
- Mahasiswa tidak dapat membuat/mengubah mata kuliah, materi, atau tugas.
- Pengumpulan tugas hanya untuk Mahasiswa yang sudah disetujui pada kelas terkait.
- Logout menghapus sesi dan membuat token CSRF baru.
- Pemeriksaan izin tetap dilakukan di backend; menyembunyikan menu saja tidak dianggap sebagai pengamanan.

## Catatan
- Role Admin ditambahkan pada model dan seeder; tidak perlu mengubah struktur tabel karena kolom `users.role` sudah tersedia.
- Akun Admin dibuat melalui seeder, bukan melalui formulir registrasi publik.
- File ini belum menyertakan folder `vendor` atau `node_modules`. Jalankan dependency install sesuai setup proyek bila belum tersedia.
