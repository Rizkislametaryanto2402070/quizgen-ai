# Langkah-Langkah Presentasi — QuizGen AI
### Automatic Question Generator From TXT (Rule-Based NLP)

Dokumen ini adalah panduan urutan presentasi source code project **QuizGen AI**.
Ikuti urutan di bawah ini dari atas ke bawah agar penjelasan mengalir logis:
dari **konsep besar** → **arsitektur** → **database** → **alur autentikasi** →
**inti algoritma (NLP)** → **alur penggunaan aplikasi** → **demo langsung**.

---

## 0. Kalimat Pembuka (30 detik)

Sampaikan di awal, sebelum masuk ke kode:

> "Aplikasi yang saya buat bernama **QuizGen AI**, yaitu aplikasi web berbasis
> **PHP Native** yang bisa membuat soal (Pilihan Ganda, Essay, dan Benar/Salah)
> secara **otomatis** dari file materi berformat `.txt`. Yang membuat aplikasi
> ini menarik adalah, proses pembuatan soalnya **tidak menggunakan API AI
> eksternal** seperti ChatGPT atau Gemini, melainkan menggunakan pendekatan
> **Natural Language Processing (NLP) berbasis rule/aturan** yang saya bangun
> sendiri dari nol, sehingga seluruh proses berjalan 100% lokal tanpa koneksi
> internet."

Poin ini penting ditekankan karena ini **nilai jual utama** project: bukan sekadar
wrapper API, tapi implementasi NLP manual (cocok untuk mata kuliah AI/NLP/PKN
pemrograman berbasis logika, bukan sekadar integrasi pihak ketiga).

---

## 1. Tunjukkan `README.md` (2 menit)

**Alasan dipresentasikan pertama:** README berisi ringkasan teknologi, struktur
folder, dan diagram alur NLP — ini adalah "peta" yang membantu audiens/penguji
mengikuti penjelasan kode selanjutnya.

Yang perlu ditunjuk & dijelaskan singkat:
- Bagian **1. Teknologi yang Digunakan** → tegaskan: PHP Native (tanpa
  framework seperti Laravel/CodeIgniter), MySQL dengan `mysqli`, tanpa Composer,
  tanpa Node.js.
- Bagian **3. Struktur Folder** → jelaskan pembagian folder `auth/`, `user/`,
  `config/`, `includes/` secara singkat (akan dibahas detail satu per satu).
- Bagian **4. Cara Kerja Sistem (Pipeline NLP Rule-Based)** → ini bagian paling
  penting. Tunjukkan diagram 9 tahap (Baca File → ... → Question Generation).
  **Ini adalah "peta jalan" yang akan Anda telusuri satu per satu di kode.**

---

## 2. Struktur Database — `database/quizgen_ai.sql` (2 menit)

**Alasan dipresentasikan kedua:** sebelum membaca kode PHP, audiens perlu tahu
data apa saja yang disimpan aplikasi.

Jelaskan 2 tabel:
1. **`users`** — menyimpan akun (username, email, password yang di-hash).
2. **`history`** — menyimpan riwayat hasil generate soal. Soroti kolom
   `hasil_soal` bertipe `LONGTEXT` yang menyimpan soal dalam format **JSON**
   — jelaskan alasannya (setiap jenis soal punya struktur data yang berbeda,
   jadi JSON lebih fleksibel daripada bikin banyak kolom terpisah).
3. Sebutkan relasi **foreign key** `history.user_id → users.id` dengan
   `ON DELETE CASCADE` (jika user dihapus, riwayatnya ikut terhapus).

---

## 3. Koneksi Database — `config/database.php` (1 menit)

File paling sederhana, cocok jadi pemanasan sebelum masuk ke alur yang lebih
kompleks.

- Jelaskan fungsi `getConnection()` memakai **mysqli** (bukan PDO) sesuai
  konsep "PHP Native murni".
- Tunjukkan `set_charset("utf8mb4")` agar mendukung berbagai karakter.

---

## 4. Alur Autentikasi — `auth/register.php` → `auth/login.php` → `auth/logout.php` (4 menit)

**Alasan dipresentasikan sebelum fitur utama:** ini adalah pintu masuk aplikasi
(entry point flow) dan menunjukkan penerapan keamanan dasar — biasanya jadi
bahan pertanyaan penguji soal keamanan.

Urutan penjelasan:
1. **`index.php`** — tunjukkan dulu file ini (sangat singkat), jelaskan bahwa
   ini "gerbang" aplikasi: redirect otomatis ke dashboard atau login tergantung
   status session.
2. **`auth/register.php`**
   - Validasi input (username/email/password wajib diisi, format email valid,
     password minimal 6 karakter, konfirmasi password harus sama).
   - Tunjukkan `password_hash()` — **ini poin keamanan penting**: password
     tidak pernah disimpan dalam bentuk asli.
   - Tunjukkan penggunaan **prepared statement** (`?` sebagai placeholder +
     `bind_param()`) — jelaskan ini mencegah **SQL Injection**.
3. **`auth/login.php`**
   - Ambil user berdasarkan username, lalu `password_verify()` untuk mencocokkan
     password yang diinput dengan hash di database.
   - Simpan `user_id` dan `username` ke `$_SESSION` setelah berhasil login.
4. **`auth/logout.php`** — cukup sebutkan singkat: `session_destroy()` menghapus
   sesi login.
5. **`includes/header.php`** — tunjukkan blok pengecekan
   `if (!isset($_SESSION['user_id']))` di bagian atas file. Jelaskan ini adalah
   **guard/proteksi halaman**: setiap halaman di folder `user/` otomatis
   menolak akses jika belum login, karena semuanya memanggil `header.php`
   di baris pertama.

---

## 5. INTI PROJECT — Pipeline NLP Rule-Based (10–12 menit)

Ini adalah **bagian paling penting** dari seluruh presentasi. Alokasikan waktu
paling banyak di sini. Jelaskan dengan urutan sesuai pipeline di README:

### 5a. `config/text_processor.php` — Tahap Preprocessing

Jelaskan fungsi satu per satu **sesuai urutan pipeline**, bukan sesuai urutan
baris kode kebetulan sama:

| Fungsi | Tahap NLP | Penjelasan singkat ke audiens |
|---|---|---|
| `readTxtFile()` | 1. Baca File | Membaca isi file `.txt` mentah |
| `caseFolding()` | 2. Case Folding | Ubah semua huruf jadi huruf kecil, agar "CPU" dan "cpu" dianggap sama |
| `cleanText()` | 3. Cleaning | Buang karakter aneh, rapikan spasi, tapi tanda baca akhir kalimat (`.!?`) sengaja dipertahankan |
| `splitSentences()` | 4. Sentence Splitting | Pecah teks panjang jadi array kalimat, berdasarkan tanda titik/seru/tanya |
| `tokenize()` | 5. Tokenizing | Pecah satu kalimat jadi kata per kata (token) |
| `removeStopwords()` | 6. Stopword Removal | Buang kata umum Bahasa Indonesia ("yang", "dan", "di", dst) yang tidak informatif |
| `preprocessText()` | Pipeline utama | Menggabungkan tahap 1–4 menjadi satu alur otomatis |

**Tips presentasi:** pakai contoh kalimat sederhana secara lisan, misalnya:
`"CPU Merupakan Otak Komputer."` → setelah case folding + cleaning + tokenize
+ stopword removal → `["cpu", "merupakan", "otak", "komputer"]`. Ini akan
sangat membantu audiens memahami tanpa harus membaca kode baris demi baris.

### 5b. `config/question_generator.php` — Tahap Analisis & Pembentukan Soal

| Fungsi | Tahap NLP | Penjelasan singkat |
|---|---|---|
| `extractKeywords()` | 7. Keyword Extraction | Hitung kata apa saja yang paling sering muncul di seluruh materi (term frequency) |
| `selectCandidateSentences()` | 8. Sentence Selection | Pilih kalimat yang mengandung pola definitif: "adalah", "merupakan", "yaitu", "berfungsi", "terdiri dari", "disebut", "memiliki", "digunakan untuk" |
| `extractSubjectAndRemainder()` | Fungsi bantu | Pecah kalimat kandidat jadi bagian **subjek** (sebelum pola) dan **keterangan** (sesudah pola) |
| `generateEssayQuestion()` | 9a. Question Generation (Essay) | Bentuk pertanyaan essay berdasarkan jenis pola yang ditemukan |
| `generateMultipleChoiceQuestion()` | 9b. Question Generation (PG) | Bentuk soal pilihan ganda + **pengecoh (distractor)** yang diambil dari keyword lain di materi yang sama (agar tetap relevan) |
| `generateTrueFalseQuestion()` | 9c. Question Generation (B/S) | 50% soal ditampilkan apa adanya (jawaban Benar), 50% disubstitusi kata kuncinya (jawaban Salah) |
| `generateQuestions()` | Pipeline utama | Menggabungkan Keyword Extraction → Sentence Selection → Question Generation sesuai jenis soal yang dipilih user |

**Poin yang sering ditanyakan penguji, siapkan jawabannya:**
- *"Kenapa hasilnya bisa berbeda-beda tiap generate?"* → Karena ada
  `shuffle()` pada kalimat kandidat dan pengecoh, agar soal tidak monoton
  mengambil kalimat pertama saja.
- *"Kalau materinya tidak ada kata 'adalah/merupakan', gimana?"* →
  Sistem akan menampilkan pesan bahwa tidak ditemukan kalimat berpola
  definitif (lihat validasi di `generate.php`), bukan memaksakan soal asal.
- *"Ini termasuk AI/Machine Learning atau bukan?"* → Ini termasuk
  **rule-based NLP** (bagian dari ilmu NLP/AI klasik, berbasis aturan
  linguistik), **bukan machine learning/deep learning** karena tidak ada
  proses training model.

---

## 6. Alur Penggunaan Aplikasi (5 menit)

Setelah inti algoritma dijelaskan, lanjutkan ke bagaimana algoritma tersebut
"dipakai" dari sisi user, urut sesuai alur pemakaian:

1. **`user/dashboard.php`** — halaman awal setelah login, tunjukkan query
   `COUNT()` dan `SUM()` untuk statistik total generate & total soal.
2. **`user/upload.php`** — form upload file `.txt` + pilihan jumlah & jenis
   soal. Tunjukkan validasi: ekstensi harus `.txt`, ukuran maksimal 5MB, nama
   file dibuat unik dengan `uniqid()` agar tidak saling menimpa antar user.
3. **`user/generate.php`** — **titik pertemuan** antara `text_processor.php`
   dan `question_generator.php`. Tunjukkan baris pemanggilan
   `preprocessText()` lalu `generateQuestions()` — ini membuktikan ke audiens
   bahwa dua file sebelumnya benar-benar dipakai di sini, bukan cuma
   dijelaskan teori. Hasilnya disimpan ke tabel `history` dalam format JSON.
4. **`user/history.php`** — tampilkan daftar riwayat, jelaskan tombol
   **"Lihat"** memakai `data-soal` (atribut HTML) untuk mengirim data JSON ke
   JavaScript tanpa reload halaman.
5. **`assets/js/script.js`** — jelaskan singkat 2 bagian utama: drag & drop
   upload file (dropzone), dan modal detail riwayat yang mem-parsing JSON lalu
   menampilkannya secara dinamis dengan `JSON.parse()`.
6. **`assets/css/style.css`** — cukup sebutkan sekilas: desain custom (bukan
   template Bootstrap polos), memakai sistem warna, tipografi (Poppins +
   Nunito), dan animasi `fade-in` — tidak perlu dibaca baris per baris saat
   presentasi, cukup tunjukkan hasil visualnya di demo.

---

## 7. Demo Langsung (5–7 menit)

Setelah source code selesai dijelaskan, lakukan demo end-to-end:

1. Buka `http://localhost/quizgen-ai/` → tunjukkan redirect otomatis ke login.
2. Register akun baru → login.
3. Masuk ke halaman **Upload**, gunakan file `contoh_materi.txt` yang sudah
   disediakan di project (atau salah satu file di folder `uploads/` sebagai
   contoh materi yang sudah pernah diupload).
4. Pilih jumlah soal (misal 5) dan jenis soal (misal Pilihan Ganda).
5. Klik **Generate Soal Sekarang** → tunjukkan hasil soal yang muncul,
   jelaskan singkat bahwa opsi jawaban & pengecoh terbentuk otomatis dari
   materi.
6. Buka halaman **Riwayat** → klik tombol **Lihat** pada salah satu riwayat,
   tunjukkan modal detail soal muncul tanpa reload halaman.
7. (Opsional) Generate ulang dengan file yang sama untuk menunjukkan hasil
   soal bisa sedikit berbeda tiap kali (karena proses `shuffle`).

---

## 8. Ringkasan Penutup (30 detik)

Tutup presentasi dengan menekankan kembali 3 poin utama:

1. Seluruh proses NLP (case folding → tokenizing → keyword extraction →
   question generation) dibangun **manual berbasis rule**, bukan memanggil
   API AI pihak ketiga.
2. Keamanan dasar diterapkan: password di-hash, prepared statement anti SQL
   Injection, validasi session, dan escaping output anti XSS.
3. Aplikasi berjalan 100% lokal dengan PHP Native + MySQL, tanpa dependency
   tambahan (Composer/Node.js), sehingga mudah dijalankan di XAMPP mana pun.

---

## Estimasi Total Waktu

| Bagian | Estimasi Waktu |
|---|---|
| Pembuka | 0.5 menit |
| README & arsitektur | 2 menit |
| Database | 2 menit |
| Koneksi database | 1 menit |
| Autentikasi (register/login/logout/header guard) | 4 menit |
| **Inti NLP (text_processor + question_generator)** | **10–12 menit** |
| Alur penggunaan aplikasi (dashboard/upload/generate/history/JS/CSS) | 5 menit |
| Demo langsung | 5–7 menit |
| Penutup | 0.5 menit |
| **Total** | **± 30–35 menit** |

Sesuaikan durasi tiap bagian dengan waktu presentasi yang diberikan — jika
waktu terbatas, bagian yang **boleh dipersingkat**: struktur database, CSS,
dan JS. Bagian yang **wajib dijaga porsi waktunya**: pipeline NLP di
`text_processor.php` dan `question_generator.php`, karena ini adalah inti
nilai project.
