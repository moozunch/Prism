# Lingkungan remediasi dan pengujian

| Item | Nilai |
|---|---|
| Baseline remote | `024bd14192620fe3bef013f4c28d4722e2bda511`, hasil fetch `origin/main`, status bersih |
| Baseline di atas | Merge PR #5, bukan baseline QA historis `6e5667b` |
| Tanggal | 26 November 2026, Codex Cloud |
| Mesin | Debian 13 amd64, PHP 8.4.28, SQLite 3.46.1, Composer 2.8.8, Node 24.22.3, npm 11.19.1 |
| Aplikasi | Laravel 12.69.2 baseline, 12.71.0 setelah patch, Filament 4.13.4 |
| Browser | Chromium 156, Playwright 1.65.1, headless, 1440 × 950 |
| Docker | 29.9.2, MySQL 8.0.46, Nginx alpine dan PHP-FPM 8.4 |
| Isolasi | Pest SQLite in-memory, database Chromium dan worker sementara, MySQL tmpfs tanpa port publik, fixture sintetis |
| Bukti | SHA dan data browser direkam pada JSON. Audit menyimpan hash lock dan versi advisory. Log CI memuat SHA job |

Pemeriksaan platform mencakup ekstensi Laravel/Filament dan PDO SQLite. Container menguji PDO MySQL melalui migrasi. Browser mengunci origin ke server uji. Google HTTP dan OAuth disimulasikan.

[Lock sebelum](logs/lock-before.txt) · [Lock setelah](logs/lock-after.txt) · [Platform sebelum](logs/platform-before.log) · [Platform setelah](logs/platform-after.log)

Kandidat aplikasi akhir `c3ce939c514a8a10b060c8f4804bb8fba8b7e7a3`. Commit evidensi berikutnya hanya menambah artefak.
