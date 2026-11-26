# Remediasi dari baseline 024bd14

- Memperbarui Axios, Vite, dan transitif terkait. Audit npm pada registry terkonfigurasi turun dari 13 ke nol. Memperbarui Laravel berdasarkan advisory Packagist yang ditinjau GitHub.
- Menjaga operasi update/delete proyek mengikuti scope membership pada peran non-administratif. Administrator mempertahankan scope yang didefinisikan oleh policy yang ada. Menolak status board yang berasal dari proyek lain, dan memeriksa izin mutasi task saat aksi.
- Merekam failed pada timeout event Calendar serta menguji refresh yang ditolak. Google dan kredensial nyata tidak digunakan selama automasi.
- Menyajikan file lokal sebagai preview hanya untuk MIME aman yang dideteksi dari file. HTML inert pada uji regresi diterima sebagai unduhan. Format teks dan gambar yang didukung tetap bisa dipratinjau.
- Menambah pengujian peran, URL edit, duplikasi email, perubahan divisi, filter laporan, attachment, Calendar, dan Playwright drag-and-drop. Memperluas smoke Docker ke Nginx, FastCGI, queue MySQL, backup/restore skema terisolasi, dan concurrency terbatas.
- Menyimpan bukti baseline sebelum perbaikan dan run remediasi baru. Run historis 22 November tetap tersedia. [Defect log](DEFECT_LOG.csv) · [Bukti historis](../2026-11-22_6e5667b/FINAL_REPORT_HANDOFF.md)
