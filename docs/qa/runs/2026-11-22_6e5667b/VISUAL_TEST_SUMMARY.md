# Ringkasan visual

Chromium menyelesaikan **22 skenario akhir**, semuanya lulus. Satu tangkapan baseline mendokumentasikan kegagalan HTTP 403 edit divisi. Tersedia **23 PNG asli** beserta hasil assertion. [Indeks](SCREENSHOT_INDEX.md) · [Manifest](SCREENSHOT_EVIDENCE.csv) · [JUnit browser](browser-junit.xml)

- Alur dengan screenshot mencakup halaman login, login salah, permintaan reset, profil dan logout, organisasi, edit divisi, anggota, RBAC, proyek, tugas dan board, assignee, komentar, lampiran, notifikasi, dashboard, laporan, penolakan proyek, serta validasi kolom wajib.
- Bukti backend mencakup ketidaktersediaan registrasi publik, notifikasi reset dan token, peran dan kebijakan, mock Calendar/Drive/OAuth, pemeriksaan deployment, queue, kinerja, dan isolasi DB. [Hasil kasus](TEST_CASE_RESULTS.csv)
- Browser menggunakan SQLite dan fixture yang dibuat per-run. Screenshot TC-24 membuktikan smoke native. Bukti MySQL berupa log container. [Bukti MySQL](logs/docker-mysql.log)
- Gambar Google live, halaman consent, dan bukti UAT manusia belum tersedia. Tidak ada screenshot mock Google karena pemeriksaan yang dilakukan berupa request atau respons dan assertion persistensi. Tampilan timeline eksternal, unggah/hapus lampiran, serta beberapa mutasi form belum dicakup oleh skenario Chromium.
- Playwright merekam console error per skenario. Origin eksternal sengaja diblokir, sehingga pembatalan asset eksternal dapat tercatat. Trace/video dan laporan HTML tidak disimpan karena browser menjalankan alur sesi dan kredensial sementara. Hasil JSON/JUnit serta PNG ditinjau sebagai bukti yang dapat dibagikan.
