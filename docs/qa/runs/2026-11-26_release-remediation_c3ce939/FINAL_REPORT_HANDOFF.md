# Handoff laporan remediasi PRISM

Pengujian dilaksanakan pada 26 November 2026 di Codex Cloud. Baseline `024bd14192620fe3bef013f4c28d4722e2bda511` adalah merge QA #5 yang terverifikasi di `origin/main`. Kandidat aplikasi yang diuji `c3ce939c514a8a10b060c8f4804bb8fba8b7e7a3`. [Lingkungan](ENVIRONMENT.md) · [Prosedur dan bukti eksekusi](EXECUTION_LOG.md) · [Pemetaan IEEE 829](TEST_PLAN_IEEE829_MAPPING.md)

Pest naik dari 63 lulus/177 assertion menjadi **75 lulus/247 assertion**, dengan nol kegagalan pada suite final. Chromium naik dari 22 menjadi **23 skenario lulus**. Matriks 28 kasus mencatat 26 PASS, 0 FAIL, 1 BLOCKED, 0 NOT_RUN, 1 N_A. Setiap label dibatasi pada skenario yang tercatat. [Matriks](TEST_CASE_RESULTS.csv) · [Traceability](REQUIREMENTS_TRACEABILITY.csv) · [JUnit Pest](junit.xml) · [JUnit browser](browser-junit.xml)

Empat defect produk dikoreksi. Perbaikan mencakup scope update/delete proyek, validasi status board milik proyek yang sama, pencatatan timeout sinkronisasi Calendar, dan pengiriman HTML lokal sebagai unduhan. Regresi ditambah untuk scope lima peran, URL edit manajer proyek, email ganda, perubahan divisi, filter periode, download, dan drag-and-drop yang tetap tersimpan setelah muat ulang. [Defect log dan retest](DEFECT_LOG.csv) · [Remediasi](REMEDIATION_SUMMARY.md)

npm audit turun dari 13 paket terdampak menjadi nol. Pemindaian GitHub Advisory Database atas 174 paket Composer turun dari satu menjadi nol. Audit langsung Packagist di GitHub menemukan advisori tambahan dan memandu patch lanjutan. Langkah audit live final lulus pada commit kandidat. Akses Packagist langsung dari Codex tetap dibatasi proxy. [Audit dan bukti mentah](SECURITY_AUDIT_BEFORE_AFTER.md) · [GitHub Actions final](https://github.com/moozunch/Prism/actions/runs/45803416420)

Smoke MySQL 8.0.46 melewati migrasi, policy scope, antrean, backup/restore 200 tugas, FPM, Nginx, login, tema Vite, dan 12 permintaan HTTP anonim dengan concurrency 4. Dua konfigurasi Laravel SQLite memakai DB masing-masing. Hasil benchmark lokal berisi median dan jumlah query. [Kinerja](PERFORMANCE_RESULTS.md) · [Ringkasan tes](TEST_SUMMARY.md) · [Panduan self-hosted](../../../deployment/self-hosted.md)

## Putusan distribusi

**NO_GO.** Gerbang teknis kandidat telah diperiksa sesuai bukti. QA-04 masih *In-Progress*. Evaluasi konsistensi dan navigasi 43 item telah dilakukan menurut pemilik, sementara sesi pengguna, umpan balik dan penerimaan akhir masih terbuka. `LICENSE` berisi MIT dengan atribusi DewaKoding dan `composer.json` menyebut GPL-3.0-or-later. Keputusan lisensi distribusi masih terbuka. Consent Google live belum dilakukan dan memerlukan sandbox berizin atau keputusan cakupan eksplisit. Otomasi Google tetap menggunakan mock. [Release gate](RELEASE_GATE_CHECKLIST.md) · [Checklist UAT](UAT_CHECKLIST.md) · [QA-04](https://trello.com/c/zRurNy6A)

Branch review telah terunggah. GitHub API menolak pembuatan draft PR di Codex dengan HTTP 403. [Buka PR dari branch](https://github.com/moozunch/Prism/compare/main...qa/release-readiness-remediation-2026-11-26?expand=1) · [Draf deskripsi](PR_DRAFT.md)

## LAMPIRAN BUKTI SCREENSHOT PENGUJIAN

Pilih 20 gambar representatif berikut dari 24 PNG tervalidasi. Satu frame mencatat board pada baseline, dan 23 frame berasal dari suite final yang lulus. Semua tangkapan menggunakan fixture sintetis. Caption siap dipakai untuk DOCX; berkas disertakan terpisah. [Indeks gambar](SCREENSHOT_INDEX.md) · [Manifest](SCREENSHOT_EVIDENCE.csv)

| Bukti | TC | Caption | Berkas | Hasil | Waktu UTC | SHA | Google |
|---|---|---|---|---|---|---|---|
| IMG-01 | TC-02 | Gambar 1. Akun admin masuk dan melihat dashboard | [PNG](screenshots/TC-02_LOGIN_VALID_PASS.png) | PASS | 2026-11-26T10:40:26.688Z | `c3ce939` | N/A |
| IMG-02 | TC-02 | Gambar 2. Kredensial salah ditolak oleh formulir login | [PNG](screenshots/TC-02_LOGIN_INVALID_PASS.png) | PASS | 2026-11-26T10:40:27.645Z | `c3ce939` | N/A |
| IMG-03 | TC-03 | Gambar 3. Formulir permintaan reset kata sandi | [PNG](screenshots/TC-03_RESET_REQUEST_PASS.png) | PASS | 2026-11-26T10:40:28.291Z | `c3ce939` | N/A |
| IMG-05 | TC-04 | Gambar 4. Profil meminta login kembali setelah sesi diakhiri | [PNG](screenshots/TC-04_LOGOUT_PASS.png) | PASS | 2026-11-26T10:40:48.969Z | `c3ce939` | N/A |
| IMG-06 | TC-05 | Gambar 5. Admin melihat profil organisasi dummy | [PNG](screenshots/TC-05_ORGANIZATION_PASS.png) | PASS | 2026-11-26T10:40:30.704Z | `c3ce939` | N/A |
| IMG-07 | TC-06 | Gambar 6. Nama divisi yang diperbarui tampil pada daftar divisi | [PNG](screenshots/TC-06_DIVISION_EDIT_PASS.png) | PASS | 2026-11-26T10:40:32.557Z | `c3ce939` | N/A |
| IMG-08 | TC-07 | Gambar 7. Daftar anggota dengan akun uji | [PNG](screenshots/TC-07_MEMBERS_LIST_PASS.png) | PASS | 2026-11-26T10:40:33.681Z | `c3ce939` | N/A |
| IMG-09 | TC-08 | Gambar 8. Anggota mendapat HTTP 403 saat membuka pengelolaan divisi | [PNG](screenshots/TC-08_RBAC_DENIED_PASS.png) | PASS | 2026-11-26T10:40:34.384Z | `c3ce939` | N/A |
| IMG-11 | TC-09 | Gambar 9. Proyek baru tampil di daftar proyek setelah disimpan | [PNG](screenshots/TC-09_PROJECT_CREATE_PASS.png) | PASS | 2026-11-26T10:40:47.567Z | `c3ce939` | N/A |
| IMG-12 | TC-10 | Gambar 10. Kartu tugas di kolom To Do pada baseline | [PNG](screenshots/TC-10_BOARD_BEFORE_PASS.png) | PASS | 2026-11-26T10:20:56.463Z | `024bd14` | N/A |
| IMG-15 | TC-10 | Gambar 11. Kartu tugas tetap di kolom Done setelah drag-and-drop dan muat ulang | [PNG](screenshots/TC-10_BOARD_MOVE_PASS.png) | PASS | 2026-11-26T10:40:38.514Z | `c3ce939` | N/A |
| IMG-16 | TC-11 | Gambar 12. Detail tugas menampilkan anggota yang ditugaskan | [PNG](screenshots/TC-11_ASSIGNEE_PASS.png) | PASS | 2026-11-26T10:40:39.539Z | `c3ce939` | N/A |
| IMG-17 | TC-12 | Gambar 13. Komentar uji tampil pada detail tugas | [PNG](screenshots/TC-12_COMMENT_PASS.png) | PASS | 2026-11-26T10:40:40.608Z | `c3ce939` | N/A |
| IMG-18 | TC-13 | Gambar 14. Isi lampiran lokal tampil untuk anggota yang berhak | [PNG](screenshots/TC-13_ATTACHMENT_VIEW_PASS.png) | PASS | 2026-11-26T10:40:41.380Z | `c3ce939` | N/A |
| IMG-19 | TC-14 | Gambar 15. Notifikasi uji tampil untuk penerima | [PNG](screenshots/TC-14_NOTIFICATION_LIST_PASS.png) | PASS | 2026-11-26T10:40:42.382Z | `c3ce939` | N/A |
| IMG-20 | TC-15 | Gambar 16. Dashboard administrator setelah login | [PNG](screenshots/TC-15_DASHBOARD_PASS.png) | PASS | 2026-11-26T10:40:43.085Z | `c3ce939` | N/A |
| IMG-21 | TC-16 | Gambar 17. Laporan anggota menampilkan tugas yang dapat diakses | [PNG](screenshots/TC-16_REPORTING_PASS.png) | PASS | 2026-11-26T10:40:43.994Z | `c3ce939` | N/A |
| IMG-22 | TC-21 | Gambar 18. Rute proyek yang dilindungi menolak anggota tanpa keanggotaan | [PNG](screenshots/TC-21_PROJECT_DENIED_PASS.png) | PASS | 2026-11-26T10:40:44.710Z | `c3ce939` | N/A |
| IMG-23 | TC-22 | Gambar 19. Formulir menolak pengiriman tanpa kolom wajib | [PNG](screenshots/TC-22_PROJECT_REQUIRED_FIELDS_PASS.png) | PASS | 2026-11-26T10:40:45.884Z | `c3ce939` | N/A |
| IMG-24 | TC-24 | Gambar 20. Panel login dan tema Vite pada aplikasi terisolasi | [PNG](screenshots/TC-24_NATIVE_SMOKE_PASS.png) | PASS | 2026-11-26T10:40:25.153Z | `c3ce939` | N/A |
