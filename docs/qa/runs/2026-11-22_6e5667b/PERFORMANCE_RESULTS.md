# Hasil pengukuran kinerja

Skrip `tools/benchmark.php` membuat 20 proyek dan 200 tugas. Setelah fixture dibuat, setiap skenario dijalankan lima kali. Tabel menampilkan sampel median menurut waktu total skrip. Waktu dalam milidetik.

| Skenario | SQLite ms | Query | DB ms | MySQL 8.0.46 ms | Query | DB ms |
|---|---:|---:|---:|---:|---:|---:|
| Progress 20 proyek | 0.860 | 1 | 0.140 | 1.946 | 1 | 1.270 |
| Reporting 25 baris | 2.863 | 4 | 0.390 | 3.590 | 4 | 1.450 |
| Timeline dashboard | 53.396 | 6 | 3.860 | 43.210 | 6 | 7.080 |
| Opsi assignee board | 0.219 | 1 | 0.080 | 0.630 | 1 | 0.420 |
| Data timeline tugas | 2.060 | 3 | 0.120 | 3.429 | 3 | 1.230 |

Puncak memori PHP 57.147.392 byte pada SQLite dan 55.050.240 byte pada MySQL. Tujuh tes regresi query termasuk dalam suite fokus. Tidak tersedia ambang NFR yang dapat diverifikasi. Hasil berlaku pada fixture dan sumber daya Codex Cloud ini. Pengukuran HTTP produksi, beban paralel, desktop Windows, serta render CDN timeline tidak dijalankan.

- SQLite in-memory, `APP_ENV=testing DB_URL='' php tools/benchmark.php`. [Output](logs/sqlite-benchmark.log)
- MySQL kosong di container, `scripts/qa/docker-smoke.sh` memanggil `php tools/benchmark.php --mysql-test`. [Output](logs/docker-mysql.log)
- Pemeriksaan regresi scope, query, dan refresh agregat. [Output](logs/targeted.log)
