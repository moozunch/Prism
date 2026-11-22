# Indeks tangkapan layar

Chromium headless, Codex Cloud, SQLite sementara, data buatan. Setiap berkas ditangkap langsung dari aplikasi. Waktu pada tabel dalam UTC.

| Bukti | TC | Hasil | Tampilan | PNG | Waktu UTC | Google |
|---|---|---|---|---|---|---|
| IMG-01 | TC-02 | PASS | Akun admin masuk dan melihat dashboard | [PNG](screenshots/TC-02_LOGIN_VALID_PASS.png) | 2026-11-22T15:37:14.670Z | N/A |
| IMG-02 | TC-02 | PASS | Kredensial salah ditolak oleh formulir login | [PNG](screenshots/TC-02_LOGIN_INVALID_PASS.png) | 2026-11-22T15:37:15.509Z | N/A |
| IMG-03 | TC-03 | PASS | Formulir permintaan reset kata sandi | [PNG](screenshots/TC-03_RESET_REQUEST_PASS.png) | 2026-11-22T15:37:16.083Z | N/A |
| IMG-04 | TC-04 | PASS | Anggota melihat profil akun miliknya | [PNG](screenshots/TC-04_PROFILE_PASS.png) | 2026-11-22T15:37:17.464Z | N/A |
| IMG-05 | TC-04 | PASS | Profil meminta login kembali setelah sesi diakhiri | [PNG](screenshots/TC-04_LOGOUT_PASS.png) | 2026-11-22T15:37:32.109Z | N/A |
| IMG-06 | TC-05 | PASS | Admin melihat profil organisasi dummy | [PNG](screenshots/TC-05_ORGANIZATION_PASS.png) | 2026-11-22T15:37:18.194Z | N/A |
| IMG-07 | TC-06 | FAIL | Admin mendapat HTTP 403 pada halaman edit divisi sebelum perbaikan | [PNG](screenshots/TC-06_DIVISION_BASELINE_FAIL.png) | 2026-11-22T12:29:44.352Z | N/A |
| IMG-08 | TC-06 | PASS | Nama divisi yang diperbarui tampil pada daftar divisi | [PNG](screenshots/TC-06_DIVISION_EDIT_PASS.png) | 2026-11-22T15:37:19.768Z | N/A |
| IMG-09 | TC-07 | PASS | Daftar anggota dengan akun uji | [PNG](screenshots/TC-07_MEMBERS_LIST_PASS.png) | 2026-11-22T15:37:20.610Z | N/A |
| IMG-10 | TC-08 | PASS | Anggota mendapat HTTP 403 saat membuka pengelolaan divisi | [PNG](screenshots/TC-08_RBAC_DENIED_PASS.png) | 2026-11-22T15:37:21.296Z | N/A |
| IMG-11 | TC-09 | PASS | Daftar proyek menampilkan proyek yang diikuti anggota | [PNG](screenshots/TC-09_PROJECTS_MEMBER_PASS.png) | 2026-11-22T15:37:22.085Z | N/A |
| IMG-12 | TC-09 | PASS | Proyek baru tampil di daftar proyek setelah disimpan | [PNG](screenshots/TC-09_PROJECT_CREATE_PASS.png) | 2026-11-22T15:37:31.188Z | N/A |
| IMG-13 | TC-10 | PASS | Daftar tugas menampilkan tugas proyek yang diikuti | [PNG](screenshots/TC-10_TASKS_MEMBER_PASS.png) | 2026-11-22T15:37:22.877Z | N/A |
| IMG-14 | TC-10 | PASS | Papan proyek menampilkan tugas pada kolom To Do | [PNG](screenshots/TC-10_BOARD_PASS.png) | 2026-11-22T15:37:23.704Z | N/A |
| IMG-15 | TC-11 | PASS | Detail tugas menampilkan anggota yang ditugaskan | [PNG](screenshots/TC-11_ASSIGNEE_PASS.png) | 2026-11-22T15:37:24.559Z | N/A |
| IMG-16 | TC-12 | PASS | Komentar uji tampil pada detail tugas | [PNG](screenshots/TC-12_COMMENT_PASS.png) | 2026-11-22T15:37:25.449Z | N/A |
| IMG-17 | TC-13 | PASS | Isi lampiran lokal tampil untuk anggota yang berhak | [PNG](screenshots/TC-13_ATTACHMENT_VIEW_PASS.png) | 2026-11-22T15:37:26.037Z | N/A |
| IMG-18 | TC-14 | PASS | Notifikasi uji tampil untuk penerima | [PNG](screenshots/TC-14_NOTIFICATION_LIST_PASS.png) | 2026-11-22T15:37:26.738Z | N/A |
| IMG-19 | TC-15 | PASS | Dashboard administrator setelah login | [PNG](screenshots/TC-15_DASHBOARD_PASS.png) | 2026-11-22T15:37:27.347Z | N/A |
| IMG-20 | TC-16 | PASS | Laporan anggota menampilkan tugas yang dapat diakses | [PNG](screenshots/TC-16_REPORTING_PASS.png) | 2026-11-22T15:37:28.065Z | N/A |
| IMG-21 | TC-21 | PASS | Rute proyek yang dilindungi menolak anggota tanpa keanggotaan | [PNG](screenshots/TC-21_PROJECT_DENIED_PASS.png) | 2026-11-22T15:37:28.620Z | N/A |
| IMG-22 | TC-22 | PASS | Formulir menolak pengiriman tanpa kolom wajib | [PNG](screenshots/TC-22_PROJECT_REQUIRED_FIELDS_PASS.png) | 2026-11-22T15:37:29.742Z | N/A |
| IMG-23 | TC-24 | PASS | Panel login dan tema Vite pada aplikasi terisolasi | [PNG](screenshots/TC-24_NATIVE_SMOKE_PASS.png) | 2026-11-22T15:37:13.186Z | N/A |
