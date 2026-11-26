# Instalasi dan upgrade PRISM self-hosted

Siapkan satu aplikasi dan satu database untuk setiap organisasi. Panduan ini berlaku untuk kandidat PRISM dengan Laravel 12, PHP 8.4.1 atau lebih baru dalam seri 8.4, Composer 2, Node 24 untuk build, serta MySQL 8.0. Keputusan distribusi mengikuti [release gate](../qa/README.md).

## Instalasi

1. Gunakan commit yang sudah disetujui oleh pemilik rilis. Bangun dependensi terkunci dengan `composer install --no-dev --prefer-dist --no-interaction --no-scripts` dan `php artisan package:discover --no-interaction`. Jalankan `composer check-platform-reqs --no-dev`.
2. Jalankan `npm ci` dan `npm run build` di lingkungan build. Sertakan `public/build` beserta aset publik yang dilacak repositori. Gunakan Nginx dengan document root `public/` dan PHP-FPM untuk menyajikannya.
3. Buat database dan pengguna MySQL khusus organisasi. Gunakan kredensial aplikasi non-root. Batasi jaringan MySQL dan PHP-FPM untuk layanan internal, serta sajikan HTTPS dari ingress atau reverse proxy yang dikonfigurasi dengan benar. Compose bawaan menyertakan port MySQL dan phpMyAdmin untuk pengembangan. Konfigurasi produksi perlu menonaktifkan publikasi port tersebut dan mengecualikan phpMyAdmin.
4. Konfigurasikan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS, `SESSION_SECURE_COOKIE=true`, kredensial database, cache/sesi/queue, dan transport email melalui konfigurasi privat. Gunakan `php artisan key:generate` sekali untuk instalasi baru. Pertahankan kunci saat upgrade dan simpan cadangan terpisah dengan akses terbatas. Kunci juga melindungi token Google yang tersimpan.
5. Jalankan `php artisan migrate --force`. Pada instalasi baru, muat peran memakai `php artisan db:seed --class=RoleSeeder --force` dan `php artisan db:seed --class=PrismRoleSeeder --force`. Buat admin terverifikasi dengan mekanisme operasional yang disetujui, lalu tetapkan peran `admin` pada akun tersebut. Seeder demo menggunakan akun fixture. Simpan `storage/app`, direktori upload privat, dan storage yang diperlukan pada volume permanen, dengan akses tulis hanya untuk PHP dan worker. Siapkan `php artisan storage:link` untuk aset yang memang publik.
6. Gunakan pengawas proses untuk `php artisan queue:work` dan periksa antrean serta failed jobs. Atur scheduler untuk fitur yang membutuhkannya. Biarkan Google Calendar dan Drive nonaktif sampai persetujuan cakupan dan kredensial untuk organisasi ini tersedia. Untuk login OAuth, amankan atau kosongkan konfigurasi OAuth dan uji UI yang berlaku.
7. Cek homepage, redirect panel, halaman login, aset, login akun admin, RBAC, reset kata sandi, file privat, serta antrean pada target. Jalankan [smoke QA](../qa/README.md) di instance sementara. Minta UAT pada lingkungan staging perusahaan yang sesuai.

## Upgrade dan rollback

- Siapkan salinan staging dengan konfigurasi terisolasi, data yang diizinkan, dan integrasi eksternal dinonaktifkan. Verifikasi target database sebelum migrasi.
- Cadangkan schema dan data MySQL, storage privat/publik, konfigurasi runtime, dan kunci aplikasi. Gunakan file kredensial MySQL berizin terbatas. Salah satu bentuk dump InnoDB adalah `mysqldump --single-transaction --no-tablespaces --set-gtid-purged=OFF DATABASE > backup.sql`. Simpan dump terenkripsi dan uji restore ke skema kosong terisolasi dengan `mysql DATABASE_RESTORE < backup.sql`. Verifikasi row count penting dan smoke aplikasi sebelum mengandalkan backup.
- Untuk upgrade, bangun paket dan aset, ambil backup, hentikan atau drain worker sesuai jadwal perawatan, jalankan migrasi, lalu restart PHP-FPM dan worker. Uji kesehatan HTTP, rute autentikasi, penyimpanan, dan job setelah aktivasi. Preserve peran yang dikustomisasi saat upgrade. Seeder peran dapat menyinkronkan izin.
- Jika harus rollback, pertahankan backup dan konfigurasi versi sebelumnya. Gunakan prosedur restore latihan ketika migrasi mengubah schema atau data yang tidak kompatibel. Koordinasikan penulisan selama pemulihan untuk mencegah kehilangan transaksi.

## Lisensi dan distribusi

`LICENSE` berisi teks MIT dengan pemberitahuan hak cipta DewaKoding Project Management. `composer.json` menyatakan `GPL-3.0-or-later`. Tim yang bertanggung jawab perlu memverifikasi asal kode, hak distribusi, lisensi dependensi/aset, pemberitahuan atribusi, kewajiban penyediaan sumber bila berlaku, dan penyelarasan metadata dengan keputusan tersebut. Pemeriksaan teknis tidak merupakan persetujuan lisensi.
