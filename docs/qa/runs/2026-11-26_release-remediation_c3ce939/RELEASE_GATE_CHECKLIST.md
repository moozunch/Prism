# Gerbang distribusi PRISM

| Gerbang | Status | Pemilik dan tindakan |
|---|---|---|
| Critical/high advisory relevan rilis | **PASS** pada snapshot/run final | Maintainer memastikan hasil npm dan Composer terkini tepat sebelum membuat artefak distribusi. [Audit](SECURITY_AUDIT_BEFORE_AFTER.md) |
| Composer terverifikasi | **PASS** audit live GitHub dan scan independen | Maintainer mempertahankan audit live serta scan independen. [Audit](SECURITY_AUDIT_BEFORE_AFTER.md) |
| Workflow dan otorisasi kritis | Lulus untuk skenario yang dieksekusi | Product owner memvalidasi kasus bisnis dan varian yang masih terbuka pada matriks. [Matriks](TEST_CASE_RESULTS.csv) |
| SQLite, MySQL, worker, UI, FPM, Nginx dan CI | **PASS** pada kandidat `c3ce939` | Operasi memverifikasi konfigurasi hosting, HTTPS, backup/restore serta akses privat pada staging. [Hasil](TEST_SUMMARY.md) |
| Cakupan Google dan live consent | **BLOCKED** | Pemilik produk menentukan cakupan. Jika termasuk rilis, jalankan UAT Google pada sandbox berizin. Jika ditunda, catat keputusan cakupan eksplisit. Mock tetap aktif. |
| UAT manusia | **OPEN** | QA-04 In-Progress. Evaluasi konsistensi dan navigasi 43 item telah dilakukan menurut pemilik. Selesaikan sesi pengguna, catat umpan balik dan dapatkan sign-off. [Checklist](UAT_CHECKLIST.md) · [QA-04](https://trello.com/c/zRurNy6A) |
| Lisensi, hak distribusi, dan panduan instalasi | **OPEN** | Panduan teknis tersedia. Pemilik lisensi dan distribusi harus meninjau asal kode/aset, atribusi, hak distribusi, kewajiban dan metadata. Keputusan belum diterbitkan. [Panduan](../../../deployment/self-hosted.md) · [LICENSE](https://github.com/moozunch/Prism/blob/main/LICENSE) · [composer.json](https://github.com/moozunch/Prism/blob/main/composer.json) |

**Rekomendasi distribusi: NO_GO.** UAT dan lisensi masih terbuka sesuai konfirmasi pemilik. Persetujuan Google dan varian yang diperlukan ditentukan dalam cakupan rilis. Hasil automasi dapat ditinjau sebagai bukti kandidat.
