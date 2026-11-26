# UAT PRISM untuk pemilik rilis

Gunakan staging dengan satu organisasi dan database terisolasi. Siapkan akun admin, kepala divisi, manajer proyek, dan anggota. Catat SHA kandidat, konfigurasi, tester, tanggal, hasil yang diamati, bukti, dan deviasi pada setiap langkah.

| ID | Penanggung jawab uji | Langkah dan kriteria penerimaan | Hasil aktual dan bukti |
|---|---|---|---|
| UAT-01 | Admin | Login, reset melalui email staging, ubah profil, logout, dan buka kembali tautan panel. Sesi lama harus ditolak. | Belum dilakukan |
| UAT-02 | Admin | Ubah informasi organisasi dan divisi, buat anggota, tetapkan divisi dan peran, serta coba email duplikat. | Belum dilakukan |
| UAT-03 | Manajer proyek | Buat dan edit proyek yang diizinkan. Tambah/hapus anggota. Buat tugas dan pindahkan ke Done. Progress harus sesuai. | Belum dilakukan |
| UAT-04 | Kepala divisi dan anggota | Bandingkan cakupan fungsi sesuai matriks yang disetujui. Buka URL proyek, tugas, dan lampiran milik grup lain di staging dan verifikasi penolakan. | Belum dilakukan |
| UAT-05 | Anggota | Buat komentar, unggah file uji, pratinjau format aman, unduh format lainnya, dan periksa notifikasi penerima. | Belum dilakukan |
| UAT-06 | Admin dan manajer proyek | Periksa total dashboard dan filter proyek/status/periode saat ada hasil serta saat kosong. | Belum dilakukan |
| UAT-07 | Operasi | Uji instalasi, HTTPS, worker, backup/restore DB dan storage, serta upgrade/rollback di staging. | Belum dilakukan |
| UAT-08 | Pemilik integrasi | Putuskan cakupan Google. Jika termasuk rilis, uji consent, Calendar dan Drive dengan sandbox berizin. | Belum dilakukan |

Isi setelah UAT berlangsung:

- SHA dan lingkungan staging
- Tanggal dan nama penguji
- Referensi hasil aktual dan deviasi per ID
- Putusan penerimaan pengguna dan tanda tangan
- Putusan pemilik produk tentang cakupan Google dan tanggal persetujuan
- Persetujuan lisensi/distribusi oleh pihak berwenang dan referensi keputusan

Status QA-04 menurut pembaruan pemilik pada 26 November 2026 adalah **In-Progress**. Evaluasi konsistensi dan navigasi antarmuka mencakup 43 item. Sesi bersama pengguna, dokumentasi umpan balik, dan penerimaan akhir masih terbuka. [Kartu QA-04](https://trello.com/c/zRurNy6A)
