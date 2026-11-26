# Catatan eksekusi

| Tahap | Perintah atau runner | Evidensi |
|---|---|---|
| Ambil baseline 26 November | `git fetch origin main`, `git switch --no-track -c qa/release-readiness-remediation-2026-11-26 origin/main`, `sha256sum composer.lock package-lock.json` | [Baseline](ENVIRONMENT.md) |
| Lock dan dependensi awal | `composer install --prefer-dist --no-scripts --no-interaction`, `composer check-platform-reqs`, `npm ci`, `npm run build` | [Composer](logs/composer-install-before.log) · [Npm](logs/npm-ci-before.log) · [Vite](logs/build-before.log) |
| Baseline regresi | `scripts/qa/run.sh`, `scripts/qa/browser.sh` dan `scripts/qa/docker-smoke.sh` sebelum perubahan | [Pest](logs/pest-before.log) · [Browser](logs/browser-before.log) · [Docker CLI server lama](logs/docker-before.log) |
| Temuan lokal awal | `npm audit --json`, `composer audit --locked --format=json`, scan GitHub Advisory Database | [Audit](SECURITY_AUDIT_BEFORE_AFTER.md) |
| Reproduksi dan perbaikan | Pest fokus `ReleaseReadinessTest`, Calendar, serta lampiran. Suite penuh diulang setelah perbaikan | [Defect log](DEFECT_LOG.csv) |
| Nginx/FPM | Smoke dengan Git archive, MySQL tmpfs, jaringan internal. Diagnostik memperbaiki direktori, proxy internal dan permission worker pada salinan `vendor` | [Hasil final](logs/docker-fpm-final.log) |
| CI branch | Git push branch QA. Job SQLite dan Chromium berjalan di GitHub. Tahap audit live awal menemukan advisori tambahan, kemudian lock diperbarui dan CI diulang | [Status CI](logs/ci-status.txt) |
| Audit final dan retest | `composer install`, `composer check-platform-reqs`, Composer native/independen, npm audit, ulang Pest, Chromium, Docker, isolasi, queue, dan benchmark | [Audit](SECURITY_AUDIT_BEFORE_AFTER.md) · [Ringkasan](TEST_SUMMARY.md) |
| Sintaks dan validasi | `php -l`, `bash -n`, `node --check`, `python3 scripts/qa/validate-evidence.py` | [PHP](logs/syntax.log) · [Validasi](logs/evidence-validation.log) |
