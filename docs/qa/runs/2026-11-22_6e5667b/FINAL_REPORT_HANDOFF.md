# Handoff laporan pengujian PRISM

Pada 22 November 2026, pengujian terotomasi PRISM dijalankan pada Codex Cloud menggunakan PHP 8.4.28, Laravel 12.69.2, Filament 4.13.4, SQLite dan MySQL 8.0.46, serta Chromium headless. Baseline `6e5667bf18f00430d7487e094832950a78df9468` cocok dengan `origin/main`. Suite akhir dan bukti browser menggunakan `6ac7ab2e9efdfbce07941ceb2daa272162f53e2a`. Konfigurasi dan dataset dicatat pada [ENVIRONMENT](ENVIRONMENT.md).

## Ringkasan akademik

Sebanyak 28 kasus direncanakan. Hasil akhir **26 PASS, 0 FAIL, 1 BLOCKED, 0 NOT_RUN, 1 N_A**. Sebanyak 26 kasus menyelesaikan skenario yang ditetapkan, TC-27 diverifikasi sebagian melalui mock namun consent live diblokir, dan TC-01 dinyatakan tidak berlaku setelah pemeriksaan rute registrasi. PASS berlaku pada skenario dalam matriks. Gunakan catatan per kasus untuk varian yang tersisa. [Matriks](TEST_CASE_RESULTS.csv) · [Traceability](REQUIREMENTS_TRACEABILITY.csv) · [Ringkasan](TEST_SUMMARY.md)

Pest awal menghasilkan 44 lulus dan 8 gagal dengan 106 assertion. Setelah koreksi, 52 tes awal lulus dengan 116 assertion. Suite akhir menghasilkan **63 lulus dan 177 assertion**. Chromium menyelesaikan **22 skenario akhir** dan menyimpan satu FAIL historis. Total **23 tangkapan asli** dan **14 rekomendasi lampiran**. [Baseline](BASELINE.md) · [JUnit baseline](baseline-junit.xml) · [JUnit akhir](junit.xml) · [JUnit browser](browser-junit.xml) · [Eksekusi](EXECUTION_LOG.md)

## Perbaikan dan temuan

Empat koreksi produk menyamakan default divisi di model dengan schema, memberi admin akses edit divisi sesuai alur organisasi, serta membatasi akses record komentar dan notifikasi. Koreksi fixture membuat status tugas wajib tersedia dan mengautentikasi peran uji. Uji ulang dilakukan setelah koreksi dan setelah tes tambahan. [Defect log](DEFECT_LOG.csv) · [Commit fixture](https://github.com/moozunch/Prism/commit/d0bde72) · [Commit koreksi](https://github.com/moozunch/Prism/commit/e90ba68) · [Commit automasi](https://github.com/moozunch/Prism/commit/6ac7ab2)

Audit npm melaporkan 13 temuan, dengan 2 kritis, 8 tinggi, dan 3 sedang. Audit Composer tidak tersedia karena HTTP 403 dari proxy. Baca hasil itu sebagai temuan dependensi yang memerlukan triase sebelum rilis. [Audit npm](logs/npm-audit.log) · [Audit Composer](logs/composer-audit.log)

## Integrasi dan kinerja

Calendar create/update/cancel serta 403/429/503, metadata Drive dan kegagalan HTTP 500, dan callback OAuth telah diuji menggunakan data sintetis dan respons mock. Validasi termasuk payload, status sync, pembatasan sesi/state, token tersembunyi dan terenkripsi, serta job queue tanpa kontak Google. Consent OAuth live **BLOCKED / REQUIRES AUTHORIZED LIVE UAT**.

Instalasi dan build aset, migrasi MySQL dalam container, HTTP smoke, queue, dan isolasi dua konfigurasi DB berhasil. Benchmark lima skenario menjalankan 1 hingga 6 query per skenario. Hasil lokal tidak menetapkan ambang NFR atau kapasitas produksi. [Kinerja](PERFORMANCE_RESULTS.md) · [MySQL](logs/docker-mysql.log) · [Queue](logs/queue.log)

## Kesimpulan dan tindak lanjut

**NO_GO untuk rilis** sampai temuan kritis ditriase dan diperbaiki, audit Composer selesai, dan persetujuan UAT dipenuhi. Uji consent Google secara terpisah bila integrasi akan diaktifkan. Prioritaskan validasi variasi input, matriks peran bisnis, pembaruan profil/mutasi attachment, dan cakupan browser lain yang dicatat. Dokumen pengujian DOCX dan SRS asli tidak disertakan. Pertahankan referensi bagian persyaratan internal dan jangan mengklaim ID FR asli, persentase coverage, atau kepatuhan ISO.

Branch review `qa/automated-testing-evidence` sudah terunggah. Draft PR belum terbuka karena GitHub API menolak request dengan HTTP 403. CI GitHub belum dieksekusi pada PR. [Buka PR](https://github.com/moozunch/Prism/pull/new/qa/automated-testing-evidence) · [Draf deskripsi](PR_DRAFT.md)

## LAMPIRAN BUKTI SCREENSHOT PENGUJIAN

Gunakan 14 gambar berikut sebagai set representatif. Semua PNG berasal dari PRISM yang berjalan di server uji terisolasi, dengan data sintetis dan kredensial yang dibuat per-run. Berkas gambar masih terpisah dari DOCX. [Daftar semua PNG](SCREENSHOT_INDEX.md) · [Manifest CSV](SCREENSHOT_EVIDENCE.csv) · [Cakupan visual dan gambar yang belum tersedia](VISUAL_TEST_SUMMARY.md)

| Bukti | TC | Caption | Berkas | Hasil | Status | Waktu UTC | SHA | Google |
|---|---|---|---|---|---|---|---|---|
| IMG-01 | TC-02 | Gambar 1. Akun admin masuk dan melihat dashboard | [PNG](screenshots/TC-02_LOGIN_VALID_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:14.670Z | `6ac7ab2` | N/A |
| IMG-02 | TC-02 | Gambar 2. Kredensial salah ditolak oleh formulir login | [PNG](screenshots/TC-02_LOGIN_INVALID_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:15.509Z | `6ac7ab2` | N/A |
| IMG-03 | TC-03 | Gambar 3. Formulir permintaan reset kata sandi | [PNG](screenshots/TC-03_RESET_REQUEST_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:16.083Z | `6ac7ab2` | N/A |
| IMG-06 | TC-05 | Gambar 4. Admin melihat profil organisasi dummy | [PNG](screenshots/TC-05_ORGANIZATION_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:18.194Z | `6ac7ab2` | N/A |
| IMG-07 | TC-06 | Gambar 5. Admin mendapat HTTP 403 pada halaman edit divisi sebelum perbaikan | [PNG](screenshots/TC-06_DIVISION_BASELINE_FAIL.png) | HTTP 403 pada baseline | FAIL | 2026-11-22T12:29:44.352Z | `6e5667b` | N/A |
| IMG-08 | TC-06 | Gambar 6. Nama divisi yang diperbarui tampil pada daftar divisi | [PNG](screenshots/TC-06_DIVISION_EDIT_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:19.768Z | `6ac7ab2` | N/A |
| IMG-10 | TC-08 | Gambar 7. Anggota mendapat HTTP 403 saat membuka pengelolaan divisi | [PNG](screenshots/TC-08_RBAC_DENIED_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:21.296Z | `6ac7ab2` | N/A |
| IMG-12 | TC-09 | Gambar 8. Proyek baru tampil di daftar proyek setelah disimpan | [PNG](screenshots/TC-09_PROJECT_CREATE_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:31.188Z | `6ac7ab2` | N/A |
| IMG-14 | TC-10 | Gambar 9. Papan proyek menampilkan tugas pada kolom To Do | [PNG](screenshots/TC-10_BOARD_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:23.704Z | `6ac7ab2` | N/A |
| IMG-15 | TC-11 | Gambar 10. Detail tugas menampilkan anggota yang ditugaskan | [PNG](screenshots/TC-11_ASSIGNEE_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:24.559Z | `6ac7ab2` | N/A |
| IMG-16 | TC-12 | Gambar 11. Komentar uji tampil pada detail tugas | [PNG](screenshots/TC-12_COMMENT_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:25.449Z | `6ac7ab2` | N/A |
| IMG-20 | TC-16 | Gambar 12. Laporan anggota menampilkan tugas yang dapat diakses | [PNG](screenshots/TC-16_REPORTING_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:28.065Z | `6ac7ab2` | N/A |
| IMG-21 | TC-21 | Gambar 13. Rute proyek yang dilindungi menolak anggota tanpa keanggotaan | [PNG](screenshots/TC-21_PROJECT_DENIED_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:28.620Z | `6ac7ab2` | N/A |
| IMG-22 | TC-22 | Gambar 14. Formulir menolak pengiriman tanpa kolom wajib | [PNG](screenshots/TC-22_PROJECT_REQUIRED_FIELDS_PASS.png) | Assertion browser lulus | PASS | 2026-11-22T15:37:29.742Z | `6ac7ab2` | N/A |
