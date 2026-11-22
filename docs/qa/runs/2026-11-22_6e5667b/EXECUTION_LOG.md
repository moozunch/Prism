# Catatan eksekusi

Log memuat perintah atau runner, waktu, SHA jika tersedia, dan exit status. Waktu direkam dalam UTC kecuali baris aplikasi yang mengandalkan Asia/Jakarta.

| Urutan | Perintah yang dijalankan | Hasil |
|---:|---|---|
| 1 | `git status --short --branch`, `git fetch origin main`, `git switch -c qa/automated-testing-evidence origin/main` | Git bersih, baseline sesuai remote |
| 2 | `php vendor/bin/pest --colors=never --log-junit …` dengan DB memory | Exit 2. [Log](logs/baseline.log) |
| 3 | `composer install --prefer-dist --no-interaction --no-progress --no-scripts`, `composer check-platform-reqs`, `php artisan route:list --except-vendor` | Exit 0. [Composer](logs/composer-install.log) · [Platform](logs/platform.log) · [Rute](logs/routes.log) |
| 4 | `composer audit --locked --format=plain`, `npm audit` | Exit 100 dan 1. [Composer](logs/composer-audit.log) · [npm](logs/npm-audit.log) |
| 5 | `PRISM_QA_BROWSER_MODE=baseline scripts/qa/browser.sh …` | Exit 1, edit divisi HTTP 403. [JSON](browser-baseline.json) |
| 6 | Pest fokus dan Pest penuh setelah perbaikan | Exit 0. [Fokus](logs/focused.log) · [Penuh](logs/post-fix.log) |
| 7 | `scripts/qa/run.sh …` dan Pest fokus lintas modul | Exit 0. [Penuh](logs/pest-final.log) · [Fokus](logs/targeted.log) |
| 8 | `scripts/qa/browser.sh …` dengan Chromium sistem | Exit 0, 22 hasil lulus. [Output](logs/browser.log) |
| 9 | `php -l` untuk 173 file, `bash -n`, `node --check` | Exit 0. [Output](logs/syntax.log) |
| 10 | `scripts/qa/isolation.sh`, `scripts/qa/queue.sh`, `php tools/benchmark.php` | Exit 0. [Isolasi](logs/isolation.log) · [Queue](logs/queue.log) · [SQLite](logs/sqlite-benchmark.log) |
| 11 | `docker compose config --quiet` dengan nilai fixture dan `scripts/qa/docker-smoke.sh` | Exit 0. [Compose](logs/compose.log) · [MySQL](logs/docker-mysql.log) |
| 12 | `php artisan route:list --json` dan inspeksi route name/URI, `npm ci` dan `npm run build` | Exit 0. [Registrasi](logs/registration.log) · [Build](logs/npm-ci-build.log) |
| 13 | `python3 scripts/qa/validate-evidence.py …` | [Hasil validator](logs/evidence-validation.log) |

Percobaan awal browser membantu menyesuaikan locator dengan label Filament dan mengisolasi cookie/fixture. Artefak final berisi assertion yang dijalankan, sementara kegagalan produk sebelum perbaikan dipertahankan terpisah. Pemeriksaan konfigurasi Compose berupa rendering lokal. Compose production, nginx end-to-end, dan akses jaringan publik tidak dijalankan.
