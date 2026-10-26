Syahrir, berdasarkan tugas UTS mata kuliah Arsitektur Perangkat Lunak dan dokumen rancangan Sistem Pengaduan Fasilitas Kampus yang kamu lampirkan, kelompok 7 perlu membuat aplikasi yang bukan hanya bisa menerima laporan kerusakan, tetapi juga menunjukkan penerapan arsitektur perangkat lunak secara nyata.

Ada tiga peran utama dalam sistem kalian: mahasiswa sebagai pelapor, admin sebagai pengelola laporan, dan petugas fasilitas sebagai pelaksana perbaikan. Arsitektur wajibnya adalah Session + Business Logic + Data Access Layer. Jadi, yang dinilai bukan cuma tampilannya, tetapi juga bagaimana proses bisnis dan akses database dipisahkan dengan benar.

Saya sarankan kalian membuat aplikasi web responsif agar mudah didemonstrasikan melalui laptop, tanpa perlu repot dengan emulator HP. Berikut rancangan PRD, desain sistem, pembagian halaman, dan struktur implementasinya.

# 1. Product Requirements Document (PRD)

# Sistem Pengaduan Fasilitas Kampus

Kelompok 7 · Arsitektur Perangkat Lunak

Web Application

3 jenis pengguna

Layered Architecture

## 1.1 Latar belakang

Mahasiswa sering menyampaikan laporan fasilitas rusak secara lisan atau melalui percakapan, sehingga laporan bisa tidak tercatat dengan baik. Informasi yang disampaikan juga kadang tidak lengkap, sementara riwayat pengaduan sulit direkap.

Aplikasi ini menyediakan satu tempat untuk membuat laporan, memeriksa laporan, menugaskan petugas, memperbarui status, dan memantau penyelesaian masalah.

## 1.2 Tujuan produk

1. Mempermudah mahasiswa melaporkan kerusakan fasilitas kampus.
2. Memastikan laporan mempunyai kategori, lokasi, deskripsi, dan foto bukti.
3. Membantu admin mengelola serta meneruskan laporan kepada petugas.
4. Memungkinkan petugas melihat tugas perbaikan dan memperbarui status.
5. Menyimpan riwayat pengaduan agar dapat ditelusuri kembali.

## 1.3 Target pengguna

[Female university student using laptop at school](https://images.openai.com/static-rsc-4/FJZPUGywk5QaAKluZ3uvXG-Kj9Kw5DL_7xLVwmyq2kUfx6CfD-6pIOSon27mlbt6D9ZmiLA0TUEzef8kTfKw4HCOpxewDJ6-A6OWqFsEVLyDTjjQFq2gFiodo8IRyuc8Fpl20E5V2YX_ZCp9fkqAnsCM5ZCDYCk5_wF7ZTz0YfQAUkqFqnY3-kB-TwwdUxLs?purpose=inline)

Mahasiswa

Membuat pengaduan, mengunggah foto, melihat status laporan sendiri, dan memeriksa riwayat pengaduan.

[Management Information Systems Minor — Chico State](https://images.openai.com/static-rsc-4/XSSeevHgnn0T0vcnlmSu1610uRVVdPMwbcaGpG6u_k2wtVsHcGS87cr1vClBx2wlWHcKRBl2aw_3g4kwisFpKqFJ2UI9EwRVHmzP0zXWHwcelqDXqQLI2EYlQdIqdy5O9Vihqn3YSCDvnad2HQADFL31fSR9IofEKffZwQzp2Kk?purpose=inline)

Admin

Memeriksa semua laporan, memastikan kelengkapan data, menetapkan petugas, dan memantau perkembangan penanganan.

[LG전자, 수능 앞두고 시험장 시스템에어컨 사전 점검 | 연합뉴스](https://images.openai.com/static-rsc-4/wGMcBwaitXvWsHxZsW--yoKA3zsOgUNCif7mjjBaElRO0iMVtHem0GlH4X030XGkeaxt_Zzih1djs0B63vDg1Tg8otRBKg01HVDkm1w7cHCfQfqe5Oy8EE2j0OXPwwDoxNPL3rg7iA8yj96SbfKO9076FJldYOeu8kNIUNg9mP4?purpose=inline)

Petugas fasilitas

Melihat tugas yang diberikan kepadanya, membuka detail kerusakan, dan memperbarui status ketika penanganan dimulai atau selesai.

## 1.4 Fitur utama

| Fitur                    | Mahasiswa     | Admin         | Petugas               |
| ------------------------ | ------------- | ------------- | --------------------- |
| Login dan logout         | Ya            | Ya            | Ya                    |
| Membuat pengaduan        | Ya            | Tidak         | Tidak                 |
| Melihat laporan          | Milik sendiri | Semua laporan | Tugas sendiri         |
| Memeriksa detail laporan | Milik sendiri | Semua laporan | Tugas yang diberikan  |
| Menugaskan petugas       | Tidak         | Ya            | Tidak                 |
| Memperbarui status       | Tidak         | Ya            | Ya                    |
| Melihat riwayat          | Milik sendiri | Semua laporan | Riwayat tugas sendiri |

Fitur inti yang harus benar-benar berfungsi adalah autentikasi, pengaduan dengan foto, daftar dan detail laporan, penugasan petugas, perubahan status, serta riwayat.

## 1.5 Status pengaduan

Gunakan tiga status utama sesuai dokumen tugas:

Menunggu

Menunggu

Laporan sudah dikirim, tetapi belum ditindaklanjuti.

Diproses

Diproses

Laporan telah diperiksa dan sedang ditangani oleh petugas.

Selesai

Selesai

Kerusakan telah ditangani dan laporan disimpan sebagai riwayat.

Status harus tersimpan di database. Jangan sekadar mengubah warna label di antarmuka, lalu menganggap masalahnya sudah selesai. Ketika halaman dibuka ulang, status harus tetap sama.

# 2. Berapa halaman yang perlu dibuat?

Saya merekomendasikan 12 halaman utama, dengan halaman yang berbeda berdasarkan peran pengguna. Jumlah ini cukup lengkap untuk menunjukkan alur sistem dan arsitektur yang diwajibkan, tanpa membuat proyek mahasiswa berkembang menjadi sistem informasi kampus nasional.

## Ringkasan halaman

# 12

Halaman umum

Login dan akses masuk

## 1

Halaman mahasiswa

Dashboard, buat laporan, daftar, detail, riwayat

## 5

Halaman admin

Dashboard, daftar laporan, detail/penugasan, data petugas

## 4

Halaman petugas

Dashboard, daftar tugas, detail tugas

## 3

Catatan: beberapa halaman memiliki fungsi detail yang dapat digunakan bersama. Jika halaman admin dan petugas memakai komponen detail yang sama, jumlah file tampilan bisa lebih sedikit dari 12.

## 2.1 Rincian setiap halaman

| No. | Halaman                         | Isi utama                                                         |
| --- | ------------------------------- | ----------------------------------------------------------------- |
| 1   | Login                           | Email/username, password, validasi, masuk sesuai peran            |
| 2   | Dashboard mahasiswa             | Ringkasan jumlah laporan Menunggu, Diproses, Selesai              |
| 3   | Buat pengaduan                  | Kategori, lokasi, deskripsi, foto bukti, tombol kirim             |
| 4   | Daftar pengaduan saya           | Semua laporan milik mahasiswa, filter status                      |
| 5   | Detail pengaduan mahasiswa      | Informasi laporan, foto, status terkini                           |
| 6   | Riwayat pengaduan               | Daftar laporan lama, terutama yang sudah selesai                  |
| 7   | Dashboard admin                 | Statistik laporan dan ringkasan laporan terbaru                   |
| 8   | Daftar semua pengaduan          | Tabel laporan, pencarian, filter status dan kategori              |
| 9   | Detail dan penugasan laporan    | Pemeriksaan laporan, pilih petugas, ubah status                   |
| 10  | Data petugas                    | Daftar petugas, tambah/edit data petugas                          |
| 11  | Dashboard petugas               | Ringkasan tugas yang belum dan sudah diselesaikan                 |
| 12  | Daftar dan detail tugas petugas | Melihat tugas yang ditugaskan, membuka detail, memperbarui status |

Catatan penting: halaman nomor 12 bisa berupa satu halaman daftar tugas dengan panel detail atau halaman detail tersendiri. Jadi, untuk implementasi nyata, ada dua pilihan:

- Versi sederhana: 10–12 halaman dengan detail yang digabung.
- Versi lebih terstruktur: 13–14 halaman jika formulir tambah/edit petugas dan detail tugas dipisah.

Saya menyarankan target 12 halaman sebagai rancangan awal, bukan aturan kaku bahwa setiap halaman harus mempunyai satu file terpisah.

# 3. Desain tampilan aplikasi (UI/UX)

Desain yang cocok adalah dashboard modern dengan sidebar, latar belakang abu-abu sangat muda, kartu putih, dan warna biru tua sebagai warna utama. Antarmuka mahasiswa dibuat sederhana, sedangkan admin dan petugas menggunakan tampilan dashboard yang lebih berorientasi pada pengelolaan data.

## 3.1 Contoh dashboard mahasiswa

[📆 UniFix · ระบบแจ้งซ่อมสำหรับมหาวิทยาลัย (University Repair Request System) - Devtaiban Programs Service](https://images.openai.com/static-rsc-4/8IuD6l5OsclpHgxIhGCEZtxODAPmRxamsD5MvWgYfJXQ-_B5yI3mTcqAqKnwCdqIHl9_fUhsBqrRIYCDsVqUs9gQDE6wwXhmuHL_LfxUUpi6At_gqdhyqkJkD6D6obvkAQZgyjM-ktqqWz5-DT4F2JfV_NEUK8N7xqvlxYRlwE8?purpose=inline)

[kruwirat.com](https://www.kruwirat.com/program/274?utm_source=chatgpt.com)

Susunan komponennya:

- Sidebar: Dashboard, Buat Pengaduan, Pengaduan Saya, Riwayat.
- Bagian atas: judul halaman dan profil pengguna.
- Kartu statistik: jumlah laporan Menunggu, Diproses, dan Selesai.
- Tombol utama: + Buat Pengaduan.
- Tabel laporan terbaru: nomor laporan, kategori, lokasi, tanggal, status, dan tombol Detail.

Angka statistik harus berasal dari database, bukan angka contoh yang ditulis permanen di kode.

## 3.2 Contoh halaman buat pengaduan

[How to submit a request through our 311 system called Hey311](https://images.openai.com/static-rsc-4/xuOokAR1Y2LUpagiq9hBMNel48wCMDMbzhVsKx4NKGHjnT5yzna4FkHJwUXAYS8CSGZNoW3HwvDj6RuTZN9jfW41fJvRnfncIrfFIkRzMdiChVJms-qJOMnkQdm9e6pHSP-MEcYuGafkasjrn5uBZkCq94g1Gtq4-H0dSt_m7fk?purpose=inline)

[heygov.com](https://heygov.com/post/hey311-submitting-a-request?utm_source=chatgpt.com)

Formulir yang dirancang:

1. Kategori fasilitas: AC, lampu, kursi, meja, toilet, proyektor, jaringan internet, dan lainnya.
2. Lokasi: gedung, lantai, ruangan, atau lokasi fasilitas.
3. Deskripsi kerusakan: penjelasan masalah yang dialami.
4. Foto bukti: unggah gambar kerusakan.
5. Tombol Kirim Pengaduan dan Batal.

Setelah berhasil dikirim, sistem menyimpan laporan dengan status Menunggu dan menampilkan nomor pengaduan atau notifikasi keberhasilan.

## 3.3 Contoh dashboard admin

[Guspora | The Technology Behind Your Business](https://images.openai.com/static-rsc-4/Pld7f1SShBeXKSa6HzgsVqsU9V6tmKM6CXMaAsBcMxSPjivUb0KDOV2hM-KbMETzP4oG5TtIQI26U5nZVV98N8BA_oUIWkaq0yG0zIRYg2mjYpF0vYik_ZMpycA3NtnGNasmK1PjItPZ7nWB30ujKKhxvHSN4CmsADRgVuoyfNw?purpose=inline)

[guspora.com](https://guspora.com/?utm_source=chatgpt.com)

Admin membutuhkan tabel yang lebih lengkap daripada mahasiswa. Setiap baris memiliki nomor laporan, pelapor, kategori, lokasi, tanggal, status, petugas, dan aksi Detail.

Pada halaman detail, admin dapat memeriksa foto dan deskripsi, memilih petugas yang sesuai, serta meneruskan laporan. Admin juga dapat memperbarui status sesuai kewenangannya.

## 3.4 Contoh dashboard petugas

[3 Ways to Manage Maintenance Work Orders \[+ Template\]](https://images.openai.com/static-rsc-4/ZFPnnyHFvgCXEMPyBDSVz067b3eexNzNBvjewaTqCqYhE4oqBMxSz3ECPUvDNhtGRwC_RiUiIHd3xKk42Yp0YBhqKYd9lQWMNXe_kjQgtL-MDhX95ZHpxNQYzs_lXdsv4V7y9x7Vsl5qNXw3anhoD9k4IMwSX3Owodm4CFrvc4U?purpose=inline)

[xenia.team](https://www.xenia.team/articles/maintenance-work-order-template?utm_source=chatgpt.com)

Petugas hanya melihat tugas yang ditugaskan kepadanya. Detail tugas menampilkan lokasi, deskripsi, foto bukti, waktu penugasan, dan status. Setelah mulai menangani kerusakan, petugas dapat memperbarui status menjadi Diproses. Setelah pekerjaan benar-benar selesai, status diubah menjadi Selesai.

Gambar-gambar di atas adalah referensi gaya desain, bukan tampilan aplikasi kalian yang sudah terimplementasi.

# 4. Rancangan alur sistem

Berikut alur utama dari pengaduan dibuat hingga selesai ditangani.

1\. Mahasiswa login

Sistem memeriksa akun dan peran

2\. Buat pengaduan

Kategori + lokasi + deskripsi + foto

3\. Validasi dan simpan

Business Logic → Data Access → Database

4\. Admin memeriksa laporan

Periksa data dan tetapkan petugas

5\. Petugas menangani kerusakan

Status diperbarui menjadi Diproses

6\. Pengaduan selesai

Status Selesai, riwayat tetap tersimpan

Ada satu detail yang harus dijaga: status tidak boleh berubah menjadi Selesai hanya karena admin menekan tombol tanpa adanya penyelesaian pekerjaan. Dalam alur utama, petugas mengonfirmasi bahwa perbaikan telah selesai, sementara admin tetap memiliki kewenangan pengelolaan sesuai aturan aplikasi.

# 5. Desain arsitektur perangkat lunak

Sesuai tugas dosen, sistem menggunakan Layered Architecture dengan empat lapisan. Tiga lapisan pertama adalah Presentation, Session dan Business Logic, serta Data Access. Lapisan terakhir adalah database.

1\. Presentation Layer

Halaman Login · Dashboard · Form Pengaduan · Daftar · Detail

2\. Session + Business Logic Layer

Autentikasi · Hak Akses · Validasi · Proses Pengaduan · Perubahan Status

3\. Data Access Layer

Repository / DAO · Query Database · CRUD

4\. Database

Pengguna · Fasilitas · Pengaduan · Penugasan · Riwayat Status

Diagram konseptual: aliran permintaan bergerak dari antarmuka ke logika bisnis, lalu ke akses data dan database. Respons dikembalikan melalui jalur sebaliknya.

## Fungsi setiap lapisan

| Lapisan        | Tanggung jawab                           | Contoh implementasi                                  |
| -------------- | ---------------------------------------- | ---------------------------------------------------- |
| Presentation   | Menampilkan antarmuka dan menerima input | Form pengaduan, dashboard admin                      |
| Session        | Mengelola pengguna login dan hak akses   | Session login, pemeriksaan role                      |
| Business Logic | Menjalankan aturan bisnis dan validasi   | Validasi laporan, penugasan petugas, transisi status |
| Data Access    | Menjalankan operasi database             | `PengaduanRepository`, `UserRepository`              |
| Database       | Menyimpan data permanen                  | Tabel pengguna, pengaduan, petugas, status           |

Session dan Business Logic digabung dalam satu lapisan konseptual sesuai dokumen kalian, tetapi sebaiknya tetap dipisahkan menjadi modul kode yang berbeda agar mudah dirawat.

## Contoh penerapan Business Logic

Misalnya mahasiswa mengirim laporan AC rusak.

1. Presentation menerima data formulir dan foto.
2. Business Logic memeriksa apakah kategori, lokasi, dan deskripsi sudah diisi.
3. Business Logic memastikan pengguna mempunyai peran mahasiswa.
4. Data Access menyimpan pengaduan dan mengembalikan ID laporan.
5. Database menyimpan laporan dengan status `Menunggu`.
6. Presentation menampilkan notifikasi berhasil dan laporan baru.

Dengan pemisahan ini, aturan validasi tidak hanya bergantung pada formulir. Pengguna tidak bisa melewati validasi hanya dengan mengirim permintaan langsung ke server.

# 6. Rancangan database

Saya menyarankan 5 tabel inti untuk versi awal. Ini sudah cukup untuk mengakomodasi tiga peran pengguna, pengaduan, penugasan, dan riwayat perubahan status.

### users

Menyimpan akun semua pengguna

`id` (PK), `nama`, `email`, `password_hash`, `role`

### categories

Daftar kategori fasilitas

`id` (PK), `nama_kategori`

### complaints

Data utama pengaduan

`id` (PK), `pelapor_id` (FK), `kategori_id` (FK), `lokasi`, `deskripsi`, `foto`, `status`, `created_at`

### assignments

Penugasan laporan kepada petugas

`id` (PK), `complaint_id` (FK), `petugas_id` (FK), `assigned_by` (FK), `assigned_at`

### complaint_history

Catatan perubahan status

`id` (PK), `complaint_id` (FK), `changed_by` (FK), `status_lama`, `status_baru`, `catatan`, `changed_at`

## Relasi antar tabel

- `users` 1:N `complaints`: seorang mahasiswa dapat membuat banyak pengaduan.
- `categories` 1:N `complaints`: satu kategori dapat digunakan oleh banyak pengaduan.
- `complaints` 1:N `assignments`: satu pengaduan dapat memiliki catatan penugasan.
- `users` 1:N `assignments`: seorang petugas dapat menerima banyak tugas.
- `complaints` 1:N `complaint_history`: satu pengaduan dapat memiliki banyak catatan perubahan status.

Untuk tugas kelompok, kalian bisa membatasi satu petugas aktif per pengaduan agar implementasi awal lebih sederhana. Riwayat penugasan tetap bisa disimpan jika laporan perlu dialihkan kepada petugas lain.

Foto sebaiknya disimpan sebagai file di direktori penyimpanan aplikasi, sementara database menyimpan path atau nama file. Password harus disimpan dalam bentuk hash, bukan teks biasa.

# 7. Teknologi yang disarankan

Karena tugas menekankan arsitektur perangkat lunak dan demonstrasi running code, gunakan stack yang mendukung pemisahan lapisan dengan jelas.

| Komponen     | Rekomendasi                          |
| ------------ | ------------------------------------ |
| Frontend     | HTML, CSS, JavaScript, Bootstrap     |
| Backend      | PHP dengan struktur MVC atau modular |
| Database     | MySQL/MariaDB                        |
| Server lokal | XAMPP                                |
| Browser      | Chrome atau browser desktop lain     |
| Diagram      | Draw\.io untuk arsitektur dan UML    |

Stack ini cocok untuk prototipe kampus dan dapat dijalankan secara lokal. Kalian tidak perlu membuat frontend dan backend menjadi dua proyek terpisah jika belum dibutuhkan.

## Contoh struktur folder

```
pengaduan-fasilitas/
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── uploads/
│   └──
├── app/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── Views/
│   │   ├── auth/
│   │   ├── mahasiswa/
│   │   ├── admin/
│   │   └── petugas/
│   └── Middleware/
├── config/
│   └── database.php
└── database/
    └── schema.sql
```

Contoh alur kode untuk menyimpan laporan:

`ComplaintController` → `ComplaintService` → `ComplaintRepository` → `Database`.

- Controller menerima permintaan dari halaman.
- Service menjalankan validasi dan aturan bisnis.
- Repository menangani query SQL.
- Database menyimpan hasilnya.

Untuk keamanan, pastikan upload foto divalidasi tipe dan ukurannya, query menggunakan prepared statements, dan setiap halaman memeriksa session serta hak akses.

# 8. Aturan sistem yang perlu diterapkan

Berikut aturan inti yang perlu ditentukan sebelum mulai coding.

1. Mahasiswa hanya dapat melihat pengaduan miliknya sendiri.
2. Admin dapat melihat seluruh pengaduan dan menugaskan petugas.
3. Petugas hanya dapat melihat dan memperbarui tugas yang diberikan kepadanya.
4. Pengaduan baru otomatis berstatus `Menunggu`.
5. Status yang ditampilkan di halaman selalu berasal dari database.
6. Perubahan status dicatat dalam tabel riwayat.
7. Pengaduan berstatus `Selesai` tetap bisa dilihat dalam riwayat, tetapi tidak dapat sembarang diubah kembali.
8. Pengguna yang belum login tidak dapat mengakses dashboard.
9. Foto bukti wajib diisi jika memang ditetapkan sebagai data wajib pada spesifikasi formulir.
10. Semua perubahan status harus memeriksa kewenangan pengguna di sisi server.

# 9. Dokumen yang harus disiapkan untuk penilaian

Berdasarkan lembar tugas, penilaian mencakup demo aplikasi yang berjalan beserta code review bagian arsitektur, serta dokumentasi diagram arsitektur dan penjelasan letak materi wajib di kode.

Karena itu, siapkan tiga hasil utama berikut:

1\. Coding + Database

Aplikasi yang dapat login sesuai peran, membuat laporan, menugaskan petugas, memperbarui status, dan menampilkan riwayat.

2\. Laporan dokumentasi

Latar belakang, kebutuhan sistem, use case diagram, arsitektur berlapis, rancangan database, alur proses, dan penjelasan penerapan setiap layer.

3\. PPT presentasi

Masalah yang diselesaikan, demo alur pengaduan, arsitektur, struktur kode, database, dan bukti bahwa Session, Business Logic, serta Data Access Layer benar-benar diterapkan.

## 10. Urutan pengerjaan yang paling aman

Saya sarankan kelompok kalian mengerjakannya dalam urutan berikut:

1. Finalisasi kebutuhan dan desain: tetapkan aktor, fitur, status, use case, dan halaman.
2. Buat database: susun tabel, foreign key, dan data contoh.
3. Implementasikan autentikasi: login, logout, session, dan pembatasan akses.
4. Buat fitur mahasiswa: membuat pengaduan, unggah foto, daftar, detail, dan riwayat.
5. Buat fitur admin: memeriksa laporan dan menugaskan petugas.
6. Buat fitur petugas: melihat tugas dan memperbarui status.
7. Integrasikan semua layer: pastikan Controller, Service, Repository, dan Database bekerja sesuai tanggung jawabnya.
8. Uji skenario lengkap: mahasiswa mengirim laporan AC rusak, admin menugaskan petugas, petugas memperbaiki, lalu mahasiswa melihat status Selesai.
9. Siapkan demonstrasi dan dokumentasi: tunjukkan alur yang berjalan dan buka kode untuk menjelaskan letak setiap layer.

Kesimpulan: rancangan awal yang saya rekomendasikan adalah aplikasi web dengan tiga peran pengguna, 12 halaman utama, lima tabel inti, dan arsitektur berlapis. Prioritas terpenting bukan memperbanyak halaman, melainkan memastikan seluruh proses pengaduan berjalan dari awal sampai selesai dan pemisahan arsitektur dapat dibuktikan melalui kode.

---

# Implementasi aplikasi

Prototipe aplikasi berjalan sudah tersedia di folder ini. Aplikasi menggunakan PHP 8.1+, MySQL/MariaDB, PDO, dan arsitektur berlapis tanpa dependensi Composer.

## Persyaratan lokal

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, dan `fileinfo`.
- MySQL/MariaDB (dapat dijalankan melalui XAMPP).
- Browser modern.

## Menjalankan aplikasi dengan XAMPP

1. Salin folder proyek ke komputer dan hidupkan layanan MySQL dari XAMPP.
2. Buat database dengan mengimpor file `database/schema.sql` melalui phpMyAdmin. File tersebut membuat database `pengaduan_fasilitas` beserta tabel dan kategori awal.
3. Jika database lama sudah berisi data, cadangkan terlebih dahulu lalu jalankan `database/upgrade_features.sql` satu kali melalui phpMyAdmin. Migrasi menambah kolom dan tabel fitur serta memindahkan foto lama ke tabel foto baru. Jangan jalankan migrasi ini pada database baru yang sudah dibuat dari `schema.sql`.
4. Pastikan kredensial MySQL sesuai konfigurasi bawaan aplikasi (`127.0.0.1`, port `3306`, user `root`, kata sandi kosong). Jika berbeda, atur environment variable `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` sebelum menjalankan PHP.
5. Dari folder proyek, jalankan `php database/seed.php` untuk membuat akun demo dan memastikan kategori tersedia.
6. Jalankan server lokal dari folder proyek:

   ```powershell
   php -S 127.0.0.1:8000 -t public
   ```

7. Buka `http://127.0.0.1:8000` di browser.

Pastikan `storage/uploads` dapat ditulisi oleh proses PHP. Foto disimpan di luar folder `public` dan hanya dapat dibaca oleh pengguna yang berhak atas laporan tersebut. Maksimal 5 foto per tahap, masing-masing maksimal 5 MB; sesuaikan `upload_max_filesize=5M` dan `post_max_size=30M` di `php.ini` bila diperlukan.

## Akun demo lokal

Jalankan seeder terlebih dahulu. Kata sandi untuk ketiga akun adalah `DemoKampus123!`.

| Peran | Email |
| --- | --- |
| Admin | `admin@kampus.test` |
| Mahasiswa | `mahasiswa@kampus.test` |
| Petugas fasilitas | `petugas@kampus.test` |

Akun dan kata sandi ini hanya untuk demonstrasi lokal. Jangan gunakan kredensial demo di server publik.

## Alur demo lengkap

1. Masuk sebagai mahasiswa, buat pengaduan dengan kategori, lokasi, deskripsi, dan (opsional) foto bukti.
2. Masuk sebagai admin, cari/filter pengaduan, periksa detailnya, dan tetapkan petugas.
3. Petugas yang ditugaskan membuka daftar tugasnya dan mengubah status menjadi `Diproses`.
4. Setelah penanganan selesai, petugas mengubah status menjadi `Selesai` dan menambahkan catatan.
5. Mahasiswa membuka kembali laporan untuk melihat status dan riwayat perubahannya.

## Struktur dan tanggung jawab kode

- `public/index.php` — entry point, pengaturan halaman dan pemetaan permintaan.
- `app/Views/` — lapisan presentasi; hanya menampilkan data yang disiapkan controller.
- `app/Core/Auth.php` — sesi login dan pemeriksaan peran.
- `app/Services/` — aturan bisnis, validasi, penugasan, transisi status, serta validasi penyimpanan foto.
- `app/Repositories/` — semua kueri database dengan prepared statements.
- `app/Core/Database.php` — koneksi PDO ke MySQL/MariaDB.
- `database/schema.sql` — skema database dan kategori awal.
- `database/seed.php` — akun demo untuk pengembangan lokal.
- `storage/uploads/` — foto privat di luar web root.

## Pengamanan dan aturan status

Aplikasi memakai password hashing, regenerasi session saat login, CSRF token untuk seluruh form, prepared statements, escaping output, pembatasan akses berdasarkan peran dan kepemilikan/penugasan, serta validasi MIME dan ukuran foto. Riwayat status ditulis bersama perubahan status dalam transaksi database. Status bergerak dari `Menunggu` ke `Diproses`, lalu ke `Selesai`; hanya petugas yang sedang ditugaskan yang dapat mengonfirmasi penyelesaian. Laporan selesai tidak dapat diubah kembali.

## Fitur tambahan per peran

- **Mahasiswa:** memberi nilai 1–5 dan ulasan setelah laporan selesai, melampirkan hingga lima foto bukti, membatalkan laporan yang masih `Menunggu` (status `Dibatalkan` tetap tersimpan dalam riwayat), melihat grafik aktivitas 14 hari, dan mencetak detail laporan melalui dialog cetak browser untuk disimpan sebagai PDF.
- **Petugas:** waktu mulai dicatat otomatis saat status berubah menjadi `Diproses`, waktu selesai dicatat saat status menjadi `Selesai`, mengunggah hingga lima foto hasil perbaikan, melihat kalender tenggat yang ditentukan admin, menandai tugas terhambat dengan alasan tanpa mengganti status `Diproses`, dan melihat ringkasan tugas/status serta grafik tren di dashboard.
- **Admin:** mengelola akun petugas dan mahasiswa (membuat akun serta memperbarui nama, email, dan kata sandi), menetapkan prioritas dan tenggat saat menugaskan petugas, melihat grafik tren dan ringkasan status, mengunduh daftar laporan sebagai CSV, mencetak rekap untuk disimpan sebagai PDF, mengelola kategori (nonaktifkan kategori tanpa menghapus riwayat laporan), dan memfilter kinerja petugas berdasarkan rentang tanggal.

Tabel `complaint_photos` menyimpan beberapa lampiran bukti dan foto hasil perbaikan; `complaint_ratings` menyimpan penilaian mahasiswa. Kolom pada `complaints` menyimpan prioritas, tenggat, waktu mulai/selesai, dan alasan hambatan; `categories.is_active` menyimpan status kategori. ENUM status juga mencakup `Dibatalkan`. Gunakan `schema.sql` untuk instalasi baru atau `upgrade_features.sql` satu kali untuk database lama.