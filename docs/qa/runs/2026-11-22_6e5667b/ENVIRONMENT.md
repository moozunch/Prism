# Lingkungan pengujian PRISM

| Komponen | Nilai teramati |
|---|---|
| Baseline | `6e5667bf18f00430d7487e094832950a78df9468`, sama dengan `origin/main` setelah fetch |
| Keadaan awal | Checkout `work`, Git bersih sebelum beralih ke `qa/automated-testing-evidence` |
| Awal baseline | 22 November 2026, 12:27 UTC |
| SHA Pest dan Chromium final | `6ac7ab2e9efdfbce07941ceb2daa272162f53e2a` |
| SHA Docker | Tercatat lengkap di [log Docker](logs/docker-mysql.log). Perubahan setelah SHA Pest terbatas pada alat QA |
| Host | Codex Cloud, Debian GNU/Linux 13 (trixie), amd64 |
| PHP / Composer | PHP 8.4.28, Composer 2.8.8 |
| Laravel / Filament | Laravel 12.69.2, Filament 4.13.4 |
| Node / npm | Node 24.22.3, npm 11.19.1 |
| Browser | Playwright 1.65.1, Chromium headless 156.0.8078.79, viewport 1440 × 950 |
| Container | Docker 29.9.2, Compose 2.40.3, MySQL 8.0.46 |
| Pest | `APP_ENV=testing`, SQLite `:memory:`, cache/sesi/mail array dan antrean sync |
| Chromium | Server loopback, DB SQLite sementara, kunci aplikasi dan kata sandi per-run, aset Vite lokal, dua proyek dan dua tugas awal |
| Queue dan isolasi | SQLite sementara per pemeriksaan. Dua konfigurasi bootstrap dan file DB terpisah untuk isolasi deployment |
| MySQL | Skema awal kosong `prism_perf_qa` di tmpfs, jaringan Docker internal, kredensial acak sementara, tanpa port publik |
| Data benchmark | 20 proyek × 10 tugas, 5 sampel per skenario, hasil median |

Ekstensi yang diperiksa meliputi PDO, pdo_sqlite, sqlite3, curl, dom, exif, gd, intl, mbstring, openssl, pcntl, SimpleXML, xmlreader, xmlwriter, dan zip. PDO MySQL diperiksa melalui migrasi di container. Daftar modul lengkap dan pemeriksaan Composer ada di [versi](logs/versions.log) dan [platform](logs/platform.log).

Automasi memakai data yang dibuat untuk run ini. Browser menolak semua permintaan ke origin eksternal. Skenario Calendar, Drive, dan OAuth menggunakan mock. Kredensial sementara, konfigurasi, file SQLite, dan cookie browser berada di luar artefak.
