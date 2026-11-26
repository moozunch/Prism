# Pemetaan bukti untuk laporan IEEE 829

| Elemen laporan | Artefak dan cakupan |
|---|---|
| Identitas rencana dan item yang diuji | [ENVIRONMENT](ENVIRONMENT.md) dengan baseline, SHA eksekusi dan toolchain |
| Fitur, eksklusi, dan pendekatan | [TEST_CASE_RESULTS](TEST_CASE_RESULTS.csv), mock Google, SQLite terisolasi, MySQL/container dan Chromium |
| Prosedur dan kriteria lulus/gagal | [EXECUTION_LOG](EXECUTION_LOG.md), [replay QA](../../README.md), dan [release gate](RELEASE_GATE_CHECKLIST.md) |
| Kasus dan traceability | [REQUIREMENTS_TRACEABILITY](REQUIREMENTS_TRACEABILITY.csv), ID eksternal REQUIRES_EXTERNAL_SRS_REVIEW |
| Catatan insiden | [DEFECT_LOG](DEFECT_LOG.csv), baseline dan retest |
| Ringkasan dan rekomendasi | [TEST_SUMMARY](TEST_SUMMARY.md), [hand-off laporan](FINAL_REPORT_HANDOFF.md) |
| Bukti visual | [SCREENSHOT_INDEX](SCREENSHOT_INDEX.md) dan manifest PNG |
| Persetujuan | [UAT_CHECKLIST](UAT_CHECKLIST.md) dan keputusan pemilik rilis |

Dokumen SRS dan DOCX pengujian asli belum tersedia di checkout atau lampiran tugas. Gunakan referensi internal dan minta pemilik spesifikasi memvalidasi ID FR. ADR-006 menetapkan autentikasi web sesi Filament. Registrasi publik tidak terdaftar dan perlu dikonfirmasi terhadap naskah SRS sebelum melakukan perubahan lingkup. Pemetaan ini membantu penyusunan dokumen dan tidak merupakan klaim kepatuhan.
