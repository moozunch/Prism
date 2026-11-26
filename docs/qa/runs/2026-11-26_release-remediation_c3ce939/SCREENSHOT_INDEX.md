# Bukti Chromium

Chromium headless dan SQLite sementara. Semua akun dan data fixture sintetis. Waktu UTC.

| ID | TC | Peran | Hasil | Tampilan | Berkas | Waktu UTC | SHA |
|---|---|---|---|---|---|---|---|
| IMG-01 | TC-02 | admin | PASS | Akun admin masuk dan melihat dashboard | [PNG](screenshots/TC-02_LOGIN_VALID_PASS.png) | 2026-11-26T10:40:26.688Z | `c3ce939` |
| IMG-02 | TC-02 | guest | PASS | Kredensial salah ditolak oleh formulir login | [PNG](screenshots/TC-02_LOGIN_INVALID_PASS.png) | 2026-11-26T10:40:27.645Z | `c3ce939` |
| IMG-03 | TC-03 | guest | PASS | Formulir permintaan reset kata sandi | [PNG](screenshots/TC-03_RESET_REQUEST_PASS.png) | 2026-11-26T10:40:28.291Z | `c3ce939` |
| IMG-04 | TC-04 | member | PASS | Anggota melihat profil akun miliknya | [PNG](screenshots/TC-04_PROFILE_PASS.png) | 2026-11-26T10:40:29.834Z | `c3ce939` |
| IMG-05 | TC-04 | member | PASS | Profil meminta login kembali setelah sesi diakhiri | [PNG](screenshots/TC-04_LOGOUT_PASS.png) | 2026-11-26T10:40:48.969Z | `c3ce939` |
| IMG-06 | TC-05 | admin | PASS | Admin melihat profil organisasi dummy | [PNG](screenshots/TC-05_ORGANIZATION_PASS.png) | 2026-11-26T10:40:30.704Z | `c3ce939` |
| IMG-07 | TC-06 | admin | PASS | Nama divisi yang diperbarui tampil pada daftar divisi | [PNG](screenshots/TC-06_DIVISION_EDIT_PASS.png) | 2026-11-26T10:40:32.557Z | `c3ce939` |
| IMG-08 | TC-07 | admin | PASS | Daftar anggota dengan akun uji | [PNG](screenshots/TC-07_MEMBERS_LIST_PASS.png) | 2026-11-26T10:40:33.681Z | `c3ce939` |
| IMG-09 | TC-08 | member | PASS | Anggota mendapat HTTP 403 saat membuka pengelolaan divisi | [PNG](screenshots/TC-08_RBAC_DENIED_PASS.png) | 2026-11-26T10:40:34.384Z | `c3ce939` |
| IMG-10 | TC-09 | member | PASS | Daftar proyek menampilkan proyek yang diikuti anggota | [PNG](screenshots/TC-09_PROJECTS_MEMBER_PASS.png) | 2026-11-26T10:40:35.316Z | `c3ce939` |
| IMG-11 | TC-09 | admin | PASS | Proyek baru tampil di daftar proyek setelah disimpan | [PNG](screenshots/TC-09_PROJECT_CREATE_PASS.png) | 2026-11-26T10:40:47.567Z | `c3ce939` |
| IMG-12 | TC-10 | member | PASS | Kartu tugas di kolom To Do pada baseline | [PNG](screenshots/TC-10_BOARD_BEFORE_PASS.png) | 2026-11-26T10:20:56.463Z | `024bd14` |
| IMG-13 | TC-10 | member | PASS | Daftar tugas menampilkan tugas proyek yang diikuti | [PNG](screenshots/TC-10_TASKS_MEMBER_PASS.png) | 2026-11-26T10:40:36.293Z | `c3ce939` |
| IMG-14 | TC-10 | member | PASS | Papan proyek menampilkan tugas pada kolom To Do | [PNG](screenshots/TC-10_BOARD_PASS.png) | 2026-11-26T10:40:37.154Z | `c3ce939` |
| IMG-15 | TC-10 | member | PASS | Kartu tugas tetap di kolom Done setelah drag-and-drop dan muat ulang | [PNG](screenshots/TC-10_BOARD_MOVE_PASS.png) | 2026-11-26T10:40:38.514Z | `c3ce939` |
| IMG-16 | TC-11 | member | PASS | Detail tugas menampilkan anggota yang ditugaskan | [PNG](screenshots/TC-11_ASSIGNEE_PASS.png) | 2026-11-26T10:40:39.539Z | `c3ce939` |
| IMG-17 | TC-12 | member | PASS | Komentar uji tampil pada detail tugas | [PNG](screenshots/TC-12_COMMENT_PASS.png) | 2026-11-26T10:40:40.608Z | `c3ce939` |
| IMG-18 | TC-13 | member | PASS | Isi lampiran lokal tampil untuk anggota yang berhak | [PNG](screenshots/TC-13_ATTACHMENT_VIEW_PASS.png) | 2026-11-26T10:40:41.380Z | `c3ce939` |
| IMG-19 | TC-14 | member | PASS | Notifikasi uji tampil untuk penerima | [PNG](screenshots/TC-14_NOTIFICATION_LIST_PASS.png) | 2026-11-26T10:40:42.382Z | `c3ce939` |
| IMG-20 | TC-15 | admin | PASS | Dashboard administrator setelah login | [PNG](screenshots/TC-15_DASHBOARD_PASS.png) | 2026-11-26T10:40:43.085Z | `c3ce939` |
| IMG-21 | TC-16 | member | PASS | Laporan anggota menampilkan tugas yang dapat diakses | [PNG](screenshots/TC-16_REPORTING_PASS.png) | 2026-11-26T10:40:43.994Z | `c3ce939` |
| IMG-22 | TC-21 | member | PASS | Rute proyek yang dilindungi menolak anggota tanpa keanggotaan | [PNG](screenshots/TC-21_PROJECT_DENIED_PASS.png) | 2026-11-26T10:40:44.710Z | `c3ce939` |
| IMG-23 | TC-22 | admin | PASS | Formulir menolak pengiriman tanpa kolom wajib | [PNG](screenshots/TC-22_PROJECT_REQUIRED_FIELDS_PASS.png) | 2026-11-26T10:40:45.884Z | `c3ce939` |
| IMG-24 | TC-24 | guest | PASS | Panel login dan tema Vite pada aplikasi terisolasi | [PNG](screenshots/TC-24_NATIVE_SMOKE_PASS.png) | 2026-11-26T10:40:25.153Z | `c3ce939` |
