# QuizGen AI
### Automatic Question Generator From TXT (Rule-Based NLP)

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-mysqli-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![NLP](https://img.shields.io/badge/NLP-Rule--Based-orange)
![License](https://img.shields.io/badge/License-MIT-green)

Aplikasi web untuk membuat soal otomatis (Pilihan Ganda, Essay, Benar/Salah) dari file materi `.txt`, menggunakan pendekatan **Natural Language Processing (NLP) berbasis rule** — **tanpa API AI eksternal** (tanpa Gemini/OpenAI/dll). Seluruh proses berjalan 100% lokal di komputer kamu.

---

## Cara Menjalankan dari GitHub

```bash
# 1. Clone repository ini
git clone https://github.com/Rizkislametaryanto2402070/quizgen-ai.git

# 2. Pindahkan/salin folder hasil clone ke htdocs XAMPP
#    (atau clone langsung di dalam folder htdocs)

# 3. Ikuti langkah instalasi lengkap di bagian "2. Cara Instalasi (XAMPP)" di bawah
```



---

## 1. Teknologi yang Digunakan

| Komponen   | Teknologi                          |
|------------|-------------------------------------|
| Backend    | PHP Native (tanpa framework)        |
| Database   | MySQL (mysqli + prepared statement) |
| Frontend   | HTML5, CSS3, Bootstrap 5 (CDN)      |
| Interaksi  | JavaScript (Vanilla) + SweetAlert2  |
| Server     | XAMPP (Apache + MySQL)              |

Tidak ada Composer, tidak ada Node.js, tidak ada koneksi internet yang dibutuhkan saat aplikasi berjalan (kecuali untuk memuat CDN Bootstrap/Font/Icons/SweetAlert2 — jika ingin benar-benar offline total, file CDN tersebut bisa diunduh manual dan ditaruh di folder `assets/`).

---

## 2. Cara Instalasi (XAMPP)

### Langkah 1 — Salin folder project
Salin/extract folder `quizgen-ai` ke dalam folder `htdocs` XAMPP. Contoh lokasi umum:

```
C:\xampp\htdocs\quizgen-ai
```

### Langkah 2 — Aktifkan Apache & MySQL
Buka **XAMPP Control Panel**, lalu klik **Start** pada modul:
- Apache
- MySQL

### Langkah 3 — Buat Database
1. Buka browser, akses: `http://localhost/phpmyadmin`
2. Klik menu **Import**
3. Pilih file `database/quizgen_ai.sql` (ada di dalam folder project ini)
4. Klik **Go / Import**

Database `quizgen_ai` beserta tabel `users` dan `history` akan otomatis terbuat.

> **Catatan:** Jika MySQL kamu menggunakan password untuk user `root`, sesuaikan kredensial di file `config/database.php` (variabel `DB_USER` dan `DB_PASS`).

### Langkah 4 — Jalankan Aplikasi
Buka browser, akses:

```
http://localhost/quizgen-ai/
```

Kamu akan diarahkan otomatis ke halaman **Login**. Klik **Daftar di sini** untuk membuat akun baru, lalu login.

---

## 3. Struktur Folder

```
quizgen-ai/
├── index.php                     -> entry point (redirect ke login/dashboard)
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── user/
│   ├── dashboard.php             -> ringkasan statistik & riwayat terbaru
│   ├── upload.php                -> form upload file TXT
│   ├── generate.php              -> proses NLP + tampilan hasil soal
│   └── history.php               -> daftar semua riwayat generate soal
├── config/
│   ├── database.php              -> koneksi MySQL (mysqli)
│   ├── text_processor.php        -> PIPELINE NLP: preprocessing teks
│   └── question_generator.php    -> PIPELINE NLP: pembentukan soal
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── uploads/                      -> tempat penyimpanan file TXT yang diupload
├── assets/
│   ├── css/style.css
│   └── js/script.js
└── database/
    └── quizgen_ai.sql
```

---

## 4. Cara Kerja Sistem (Pipeline NLP Rule-Based)

Sistem ini disebut "AI tingkat dasar" karena menerapkan tahapan-tahapan **Natural Language Processing (NLP)** klasik secara berurutan, meskipun setiap tahap menggunakan logika rule-based sederhana (bukan machine learning/deep learning). Berikut tahapannya:

```
Upload File TXT
      ↓
[1] Baca File           -> file_get_contents()
      ↓
[2] Case Folding        -> ubah semua huruf jadi huruf kecil
      ↓
[3] Cleaning            -> hapus karakter tidak perlu
      ↓
[4] Sentence Splitting  -> pecah teks jadi kalimat-kalimat
      ↓
[5] Tokenizing          -> pecah kalimat jadi kata (token)
      ↓
[6] Stopword Removal    -> hapus kata umum (yang, dan, di, dll)
      ↓
[7] Keyword Extraction  -> hitung frekuensi kata penting
      ↓
[8] Sentence Selection  -> pilih kalimat berpola definitif
      ↓                   (adalah, merupakan, yaitu, berfungsi, dst)
[9] Rule-Based Question Generation
      ↓                   -> bentuk soal PG / Essay / Benar-Salah
Simpan ke Database & Tampilkan
```

### Lokasi setiap tahap di kode:

| Tahap NLP                  | Fungsi PHP                          | File                              |
|-----------------------------|--------------------------------------|------------------------------------|
| Baca File                   | `readTxtFile()`                     | `config/text_processor.php`        |
| Case Folding                 | `caseFolding()`                     | `config/text_processor.php`        |
| Cleaning                     | `cleanText()`                       | `config/text_processor.php`        |
| Sentence Splitting           | `splitSentences()`                  | `config/text_processor.php`        |
| Tokenizing                   | `tokenize()`                        | `config/text_processor.php`        |
| Stopword Removal             | `removeStopwords()`                 | `config/text_processor.php`        |
| Keyword Extraction           | `extractKeywords()`                 | `config/question_generator.php`    |
| Sentence Selection           | `selectCandidateSentences()`        | `config/question_generator.php`    |
| Question Generation (Essay)  | `generateEssayQuestion()`           | `config/question_generator.php`    |
| Question Generation (PG)     | `generateMultipleChoiceQuestion()`  | `config/question_generator.php`    |
| Question Generation (B/S)    | `generateTrueFalseQuestion()`       | `config/question_generator.php`    |
| Pipeline Utama Preprocessing | `preprocessText()`                  | `config/text_processor.php`        |
| Pipeline Utama Generate Soal | `generateQuestions()`               | `config/question_generator.php`    |

---

## 5. Contoh Materi yang Cocok

Sistem akan mendeteksi kalimat yang mengandung pola: **adalah, merupakan, yaitu, berfungsi, terdiri dari, disebut, memiliki, digunakan untuk**.

Contoh isi file `.txt` yang baik:

```
CPU merupakan otak komputer.
CPU berfungsi mengolah data.
CPU terdiri dari ALU dan CU.
RAM merupakan memori sementara.
RAM digunakan untuk menyimpan data sementara.
Motherboard adalah papan utama yang menghubungkan seluruh komponen komputer.
```

Semakin banyak kalimat yang memiliki pola di atas, semakin banyak soal yang bisa dihasilkan.

---

## 6. Batasan & Validasi

- Format file: hanya `.txt`
- Ukuran maksimal file: 5 MB
- Jumlah soal yang bisa dipilih: 5, 10, 15, atau 20
- Jenis soal: Pilihan Ganda, Essay, atau Benar/Salah
- Jika materi tidak mengandung kalimat berpola definitif, sistem akan menampilkan pesan peringatan dan tidak memaksa membuat soal asal-asalan.

---

## 7. Keamanan yang Diterapkan

- Password disimpan dengan `password_hash()` dan diverifikasi dengan `password_verify()`
- Seluruh query database menggunakan **Prepared Statement** (`mysqli`) untuk mencegah SQL Injection
- Validasi session login di setiap halaman user (`includes/header.php`)
- Validasi format & ukuran file saat upload
- File di folder `uploads/` tidak bisa dieksekusi sebagai script (dilindungi `.htaccess`)
- Output ke HTML di-escape dengan `htmlspecialchars()` untuk mencegah XSS

---

## 8. Catatan Tambahan

- Soal pilihan ganda menggunakan pengecoh (distractor) yang diambil dari keyword lain yang muncul di materi yang sama, supaya pengecoh tetap relevan dan tidak asal-asalan.
- Soal benar/salah dibuat dengan mengacak: sebagian statement ditampilkan apa adanya (jawaban "Benar"), sebagian lain disubstitusi kata kuncinya dengan keyword lain dari materi yang sama (jawaban "Salah").
- Setiap kali tombol **Generate Soal Sekarang** ditekan dengan file yang sama, hasil soal bisa sedikit berbeda karena ada proses pengacakan (shuffle) pada pemilihan kalimat kandidat dan pengecoh — ini wajar dan menunjukkan sistem tidak hanya mengambil kalimat pertama secara statis.

Selamat menggunakan QuizGen AI.

---

## 9. Catatan Redesign Tampilan (UI)

Tampilan visual project ini telah diperbarui tanpa mengubah fitur, logika bisnis, struktur halaman, maupun pipeline NLP. Perubahan hanya menyentuh file `assets/css/style.css`, import font di bagian `<head>`, beberapa kelas CSS pada markup HTML untuk animasi, dan teks UI yang sebelumnya memakai tanda seru/emoji.

### Sistem Warna
| Peran            | Warna     |
|------------------|-----------|
| Warna utama      | `#2C3E50` |
| Warna aksen      | `#E67E22` |
| Latar belakang   | `#F8F9FA` (dengan gradien radial halus) |

### Tipografi
- Judul (heading, label tebal, nama brand) menggunakan **Poppins**
- Isi teks (paragraf, tabel, form) menggunakan **Nunito**
- Kedua font dimuat dari Google Fonts pada file `includes/header.php`, `auth/login.php`, dan `auth/register.php`

### Tata Letak
- Seluruh jarak (margin/padding) antar elemen mengikuti skala kelipatan 8px, didefinisikan sebagai CSS variable: `--space-1` (8px) hingga `--space-6` (48px)
- Kartu statistik di dashboard memiliki sedikit pergeseran vertikal antar kolom (`margin-top` berbeda tiap kartu) untuk kesan tidak terlalu kaku/simetris
- Halaman login dan register memiliki dua bentuk geometris dekoratif di belakang kartu (lingkaran dan persegi miring) sebagai aksen non-simetris

### Animasi
- Semua tombol dan kartu memiliki transisi halus 0.3 detik saat hover (warna, bayangan, posisi)
- Konten utama setiap halaman muncul dengan animasi fade-in sekaligus bergerak naik sedikit saat halaman dimuat, memakai class `.fade-in`, `.fade-in-delay-1`, `.fade-in-delay-2`, dan `.fade-in-delay-3` agar elemen-elemen muncul secara bertahap
- Seluruh animasi memakai CSS murni (`@keyframes`), tidak ada JavaScript tambahan yang dipakai untuk efek ini

### Hal yang Sengaja Dipertahankan
- Seluruh nama class HTML utama (`.sidebar`, `.content-card`, `.stat-card`, `.question-item`, dll) tidak diubah, sehingga struktur halaman dan logika PHP/JavaScript yang merujuk ke elemen tersebut tetap berfungsi normal
- Tidak ada perubahan pada query database, validasi, maupun algoritma NLP (`text_processor.php` dan `question_generator.php`)
- Penamaan file dan struktur folder tidak berubah
