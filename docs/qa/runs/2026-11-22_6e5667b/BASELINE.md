# Baseline dan pembandingan

`git fetch origin main` mengonfirmasi baseline `6e5667bf18f00430d7487e094832950a78df9468`. Checkout dimulai pada branch `work` dengan status bersih.

| Tahap | Lulus | Gagal | Assertion | Exit | Bukti |
|---|---:|---:|---:|---:|---|
| Baseline SQLite in-memory | 44 | 8 | 106 | 2 | [Output utuh](logs/baseline.log) · [JUnit](baseline-junit.xml) |
| Fokus setelah koreksi | 15 | 0 | 29 | 0 | [Output](logs/focused.log) |
| Suite penuh setelah koreksi | 52 | 0 | 116 | 0 | [Output](logs/post-fix.log) |
| Fokus akhir lintas modul | 56 | 0 | 167 | 0 | [Output](logs/targeted.log) |
| Suite penuh akhir | 63 | 0 | 177 | 0 | [Output](logs/pest-final.log) · [JUnit](junit.xml) |

Baseline berlangsung 2.101 detik menurut wrapper. Kegagalan awal mencakup satu fixture attachment tanpa status tugas, tiga kasus divisi, dua kasus organisasi dengan sesi yang belum diautentikasi, dan dua kebijakan kepemilikan. Browser mereproduksi HTTP 403 pada edit divisi oleh admin. Setelah perbaikan, browser memverifikasi nama divisi baru muncul di daftar. [Baseline browser](browser-baseline.json) · [Retest browser](browser-final.json)

Composer install, platform requirements, build Vite, dan registrasi rute dasar berhasil. Audit Composer terhalang HTTP 403 dari proxy. Audit npm selesai dengan exit 1 karena advisori yang dilaporkan. [Audit Composer](logs/composer-audit.log) · [Audit npm](logs/npm-audit.log)
