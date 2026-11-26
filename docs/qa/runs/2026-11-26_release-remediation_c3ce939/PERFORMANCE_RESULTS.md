# Pengukuran kinerja

Fixture terdiri dari 20 proyek dan 200 tugas. Median masing-masing berasal dari lima sampel per skenario.

| Skenario | SQLite ms | Query SQLite | MySQL ms | Query MySQL |
|---|---:|---:|---:|---:|
| Progress 20 proyek | 0.847 | 1 | 1.993 | 1 |
| Reporting 25 baris | 2.087 | 4 | 3.596 | 4 |
| Timeline dashboard | 43.527 | 6 | 36.441 | 6 |
| Opsi assignee board | 0.222 | 1 | 0.618 | 1 |
| Data timeline task | 2.017 | 3 | 2.956 | 3 |

Puncak memori PHP SQLite 57147392 byte dan MySQL 55050240 byte.

Smoke HTTP terpisah memakai Nginx, PHP-FPM dan MySQL. Sebanyak 12 permintaan anonim ke halaman login dengan concurrency 4 semuanya menerima HTTP 200. Median 47.8 ms dan maksimum 77.2 ms dari curl pada jaringan container yang sama. Benchmark query dan latensi HTTP mengukur hal berbeda.

Ambang NFR dan kapasitas produksi belum diklaim. Staging organisasi dan TLS publik belum diuji.

[Output SQLite](logs/benchmark-sqlite.json) · [Output MySQL dan HTTP](logs/docker-fpm-final.log)
