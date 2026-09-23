# PRISM Documentation

**Project:** PRISM – Project Management System  
**Base project:** DewaKoding Project Management  
**Documentation purpose:** menjadi single source of truth untuk modifikasi DewaKoding menjadi PRISM dengan bantuan AI coding agent.

## Prinsip utama

1. PRISM adalah template project-management yang reusable dan configurable.
2. Deployment model wajib: **1 Organization = 1 Deployment = 1 Database**.
3. Tidak ada shared multi-tenant database.
4. Fitur PRISM mengikuti requirement project yang sudah disetujui; AI tidak boleh menambahkan fitur besar tanpa keputusan eksplisit.
5. DewaKoding adalah **starting codebase**, bukan specification PRISM.
6. Semua perubahan harus mempertahankan fitur DewaKoding yang masih relevan atau menggantinya dengan implementasi PRISM yang setara.
7. Integrasi Google Calendar dan Google Drive bersifat deployment-specific dan bergantung pada credential/authorization organisasi.
8. Setiap perubahan harus dapat diverifikasi melalui test, migration check, permission check, dan UI/manual check sesuai jenis perubahan.

## Urutan membaca

AI coding agent sebaiknya membaca hanya file yang relevan dengan task:

- `AGENTS.md` — aturan kerja AI.
- `docs/PRISM_REQUIREMENTS.md` — requirement fungsional utama.
- `docs/ARCHITECTURE.md` — keputusan arsitektur dan batasan deployment.
- `docs/DEWAKODING_BASELINE.md` — apa yang sudah tersedia dari base project.
- `docs/MIGRATION_PLAN.md` — urutan transformasi DewaKoding → PRISM.
- `docs/TASK_BACKLOG.md` — pekerjaan yang sedang/akan dikerjakan.
- `docs/DECISIONS.md` — keputusan yang tidak boleh diasumsikan ulang.

## Status dokumentasi

Dokumen ini adalah baseline sebelum modifikasi source code. Jika implementasi berubah, dokumentasi harus diperbarui pada task yang sama.

## Sumber

- PRISM Project Charter
- PRISM Project Management Plan
- PRISM SRS, khususnya Bab 10–12
- DewaKoding Project Management repository

DewaKoding repository:
https://github.com/SeptiawanAjiP/dewakoding-project-management
