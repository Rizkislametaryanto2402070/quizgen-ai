<?php
/**
 * =====================================================
 * FILE: config/text_processor.php
 * MODUL: Natural Language Processing (NLP) - Text Preprocessing
 * =====================================================
 *
 * File ini bertanggung jawab atas seluruh tahap PREPROCESSING TEKS
 * sebelum teks dianalisis lebih jauh oleh question_generator.php.
 *
 * Pipeline NLP berbasis rule yang diimplementasikan di sini:
 *   1. Case Folding          -> menyamakan huruf menjadi huruf kecil
 *   2. Cleaning              -> membersihkan karakter tidak perlu
 *   3. Sentence Splitting    -> memecah teks menjadi kalimat-kalimat
 *   4. Tokenizing             -> memecah kalimat menjadi token/kata
 *   5. Stopword Removal      -> membuang kata-kata umum yang tidak penting
 *
 * Setiap tahap dipisah dalam fungsi tersendiri agar terlihat jelas
 * bahwa proses ini adalah implementasi NLP tingkat dasar,
 * bukan sekadar pengolahan string biasa.
 */


/**
 * =====================================================
 * STEP 1 - BACA FILE TXT
 * =====================================================
 * Membaca isi file TXT menggunakan file_get_contents()
 * tanpa library tambahan (sesuai requirement project).
 *
 * @param string $filePath path file TXT yang sudah diupload
 * @return string isi mentah file
 */
function readTxtFile($filePath)
{
    if (!file_exists($filePath)) {
        return '';
    }

    // Membaca seluruh isi file sebagai string
    $content = file_get_contents($filePath);

    return $content;
}


/**
 * =====================================================
 * STEP 2 - CASE FOLDING
 * =====================================================
 * Tahap NLP: Case Folding
 * Tujuan: menyamakan semua huruf menjadi huruf kecil (lowercase)
 * agar proses pencocokan kata (keyword matching, stopword removal,
 * dan pencarian pola kalimat) tidak terpengaruh oleh perbedaan
 * huruf besar/kecil.
 *
 * Contoh: "CPU Merupakan Otak Komputer" -> "cpu merupakan otak komputer"
 *
 * @param string $text teks mentah
 * @return string teks dalam huruf kecil
 */
function caseFolding($text)
{
    return mb_strtolower($text, 'UTF-8');
}


/**
 * =====================================================
 * STEP 3 - CLEANING TEXT
 * =====================================================
 * Membersihkan teks dari karakter yang tidak diperlukan
 * seperti tab, baris kosong berlebih, dan karakter aneh
 * hasil copy-paste, namun tetap mempertahankan tanda baca
 * akhir kalimat (. ! ?) karena dibutuhkan pada tahap
 * Sentence Splitting.
 *
 * @param string $text teks hasil case folding
 * @return string teks yang sudah dibersihkan
 */
function cleanText($text)
{
    // Mengganti tab dan baris baru ganda dengan spasi tunggal
    // (\r\n / \r = enter gaya Windows/Mac, \t = tab)
    $text = str_replace(["\r\n", "\r", "\t"], " ", $text);
    $text = preg_replace('/\n+/', ' ', $text);

    // Menghapus karakter yang bukan huruf, angka, spasi, atau tanda baca akhir kalimat.
    // Penjelasan pola regex [^a-z0-9\s\.\,\!\?\-] :
    //   ^          -> artinya "BUKAN" karakter berikut ini (negasi)
    //   a-z0-9     -> huruf kecil dan angka (huruf besar sudah hilang karena caseFolding)
    //   \s         -> spasi/whitespace
    //   \.\,\!\?\- -> tanda titik, koma, seru, tanya, dan strip tetap dipertahankan
    // Karakter di luar daftar ini (simbol aneh hasil copy-paste) akan diganti spasi.
    $text = preg_replace('/[^a-z0-9\s\.\,\!\?\-]/u', ' ', $text);

    // Menghapus spasi berlebih (lebih dari satu spasi menjadi satu spasi)
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}


/**
 * =====================================================
 * STEP 4 - SENTENCE SPLITTING (Pemisahan Kalimat)
 * =====================================================
 * Memisahkan teks panjang menjadi larik (array) kalimat
 * berdasarkan tanda baca akhir kalimat (. ! ?).
 * Ini adalah langkah awal sebelum proses Sentence Selection
 * pada question_generator.php.
 *
 * @param string $text teks yang sudah dibersihkan
 * @return array daftar kalimat
 */
function splitSentences($text)
{
    // Pecah berdasarkan tanda titik, tanda seru, atau tanda tanya.
    // Penjelasan pola regex (?<=[\.\!\?])\s+ :
    //   (?<=...)  -> "lookbehind", artinya: pecah teks TEPAT SETELAH karakter ini,
    //                 tapi tanda bacanya sendiri tidak ikut terhapus dari hasil split
    //   [\.\!\?]  -> salah satu dari titik, seru, atau tanya
    //   \s+       -> diikuti satu atau lebih spasi (baru dianggap batas kalimat baru)
    $rawSentences = preg_split('/(?<=[\.\!\?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    $sentences = [];
    foreach ($rawSentences as $sentence) {
        // Hilangkan tanda baca akhir kalimat dan spasi berlebih
        $sentence = trim($sentence);
        $sentence = rtrim($sentence, ".!? ");

        // Hanya simpan kalimat yang punya minimal 3 kata (membuang kalimat terlalu pendek/noise)
        if (str_word_count($sentence) >= 3) {
            $sentences[] = $sentence;
        }
    }

    return $sentences;
}


/**
 * =====================================================
 * STEP 5 - TOKENIZING
 * =====================================================
 * Tahap NLP: Tokenizing
 * Tujuan: memecah sebuah kalimat menjadi unit kata (token).
 * Token inilah yang akan dianalisis pada tahap Stopword Removal
 * dan Keyword Extraction.
 *
 * Contoh:
 * "cpu merupakan otak komputer" -> ["cpu", "merupakan", "otak", "komputer"]
 *
 * @param string $sentence satu kalimat (sudah dalam lowercase)
 * @return array daftar token/kata
 */
function tokenize($sentence)
{
    // Hapus tanda baca tersisa agar token bersih
    $sentence = preg_replace('/[\.\,\!\?\-]/', ' ', $sentence);

    // Pecah berdasarkan spasi menjadi array kata (token)
    $tokens = preg_split('/\s+/', trim($sentence), -1, PREG_SPLIT_NO_EMPTY);

    return $tokens;
}


/**
 * =====================================================
 * STEP 6 - STOPWORD REMOVAL
 * =====================================================
 * Tahap NLP: Stopword Removal
 * Tujuan: membuang kata-kata umum (stopword) dalam Bahasa Indonesia
 * yang sering muncul namun tidak memiliki nilai informasi penting,
 * seperti "yang", "di", "dan", "adalah", dsb.
 *
 * Tujuannya agar tahap Keyword Extraction selanjutnya hanya
 * menghitung frekuensi kata-kata yang benar-benar bermakna
 * (kata benda/istilah penting), bukan kata penghubung.
 *
 * @param array $tokens daftar token hasil tokenizing
 * @return array token yang sudah bersih dari stopword
 */
function removeStopwords($tokens)
{
    // Daftar stopword sederhana Bahasa Indonesia (rule-based, statis)
    $stopwords = [
        'yang', 'untuk', 'pada', 'ke', 'para', 'namun', 'menurut', 'antara',
        'dia', 'dua', 'ia', 'seperti', 'jika', 'sehingga', 'kembali', 'dan',
        'tidak', 'ini', 'karena', 'kepada', 'oleh', 'saat', 'harus', 'sementara',
        'setelah', 'belum', 'kami', 'sekitar', 'bagi', 'serta', 'di', 'dari',
        'telah', 'sebagai', 'masih', 'hal', 'ketika', 'adalah', 'merupakan',
        'yaitu', 'dengan', 'akan', 'juga', 'atau', 'dalam', 'itu', 'ada',
        'bisa', 'dapat', 'maka', 'agar', 'sangat', 'lebih', 'suatu', 'para',
        'beberapa', 'lain', 'banyak', 'semua', 'setiap', 'tersebut', 'tanpa',
        'sebuah', 'satu', 'pun', 'yakni', 'maupun', 'apabila', 'kita',
        'mereka', 'saya', 'anda', 'kalian', 'nya', 'lah', 'kah', 'pun'
    ];

    $filtered = [];
    foreach ($tokens as $token) {
        // Hanya simpan token yang BUKAN stopword dan punya panjang lebih dari 2 huruf
        if (!in_array($token, $stopwords) && mb_strlen($token) > 2) {
            $filtered[] = $token;
        }
    }

    return $filtered;
}


/**
 * =====================================================
 * FUNGSI UTAMA - PIPELINE PREPROCESSING
 * =====================================================
 * Menggabungkan seluruh tahap preprocessing menjadi satu pipeline:
 * Baca File -> Case Folding -> Cleaning -> Sentence Splitting
 *
 * Hasil dari fungsi ini adalah array kalimat bersih yang siap
 * diproses lebih lanjut oleh question_generator.php
 * (Tokenizing, Stopword Removal, Keyword Extraction, dst).
 *
 * @param string $filePath path file TXT
 * @return array berisi 'sentences' (array kalimat) dan 'raw_text'
 */
function preprocessText($filePath)
{
    // 1. Baca file mentah
    $rawText = readTxtFile($filePath);

    // 2. Case Folding -> ubah ke huruf kecil
    $foldedText = caseFolding($rawText);

    // 3. Cleaning -> bersihkan karakter tidak perlu
    $cleanedText = cleanText($foldedText);

    // 4. Sentence Splitting -> pecah jadi kalimat-kalimat
    $sentences = splitSentences($cleanedText);

    return [
        'raw_text'  => $rawText,
        'sentences' => $sentences
    ];
}
