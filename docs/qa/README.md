# Pengujian PRISM

Bukti pertama tersedia di [run 2026-11-22](runs/2026-11-22_6e5667b/FINAL_REPORT_HANDOFF.md).

## Codex Cloud dan Linux

Gunakan checkout yang ada. Di Codex Cloud, aktifkan toolchain dengan `source /workspace/.prism-tools/activate`. Siapkan dependensi terkunci dan aset sebelum browser atau Docker smoke. Pada Codex Cloud yang sudah disiapkan, arsip Composer tersedia di cache setup. PHP minimal 8.4.1 sesuai lockfile.

```bash
composer install --prefer-dist --no-interaction --no-scripts
php artisan package:discover --no-interaction
npm ci
npm run build
scripts/qa/run.sh /tmp/prism-pest
scripts/qa/isolation.sh
scripts/qa/queue.sh
scripts/qa/docker-smoke.sh
```

Untuk browser, gunakan package Playwright yang dipasang di luar checkout. Jika Chromium sistem sudah tersedia, ikuti contoh `CHROMIUM_PATH`. Jika tidak, pasang Chromium dari Playwright dan hilangkan variabel `CHROMIUM_PATH`.

```bash
npm install --prefix /tmp/prism-playwright --no-audit --no-fund playwright@1.65.1
/tmp/prism-playwright/node_modules/.bin/playwright install --with-deps chromium
PRISM_PLAYWRIGHT_MODULE=/tmp/prism-playwright/node_modules/playwright scripts/qa/browser.sh /tmp/prism-browser
# Codex Cloud dengan Chromium sistem:
CHROMIUM_PATH=/usr/bin/chromium PRISM_PLAYWRIGHT_MODULE=/tmp/prism-playwright/node_modules/playwright scripts/qa/browser.sh /tmp/prism-browser
python3 scripts/qa/validate-evidence.py docs/qa/runs/2026-11-22_6e5667b
```

Browser runner membuat database dan akun sementara, memakai port loopback 8010, lalu menghapus fixture dan menghentikan server saat selesai. Atur `PRISM_QA_PORT` jika port tersebut digunakan. Runner Docker membangun image dari `git archive HEAD`, memakai jaringan terisolasi, dan mengirim hanya vendor serta aset lokal yang sudah dibangun ke container pengujian. Data DB ada di tmpfs.

## Windows PowerShell dan Docker Desktop

Perintah replay berikut disediakan untuk Windows. Pengujian run yang tercatat dilakukan di Linux Codex Cloud.

```powershell
composer install --prefer-dist --no-interaction --no-scripts
php artisan package:discover --no-interaction
npm ci
npm run build
$env:APP_ENV = 'testing'
$env:APP_DEBUG = 'false'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
$env:MAIL_MAILER = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:GOOGLE_CALENDAR_ENABLED = 'false'
$env:GOOGLE_DRIVE_ENABLED = 'false'
$env:APP_CONFIG_CACHE = Join-Path $env:TEMP ('prism-config-' + [guid]::NewGuid() + '.php')
$env:APP_KEY = php -r 'echo "base64:".base64_encode(random_bytes(32));'
php vendor/bin/pest --log-junit (Join-Path $env:TEMP 'prism-junit.xml')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
```

Gunakan WSL 2 dengan Docker Desktop integration untuk menjalankan pemeriksaan Docker dan browser Bash dari checkout WSL yang sudah dibangun. `scripts/qa/docker-smoke.sh` membuat jaringan/skema sendiri. Pengujian ini tidak memakai stack Compose pengembangan yang berisi data pengguna.

CI GitHub Actions menjalankan Pest dan Chromium pada PR dengan izin baca. Artefak dikaitkan ke run Actions yang benar-benar tersedia. Publikasi dan persetujuan UAT dilakukan terpisah.
