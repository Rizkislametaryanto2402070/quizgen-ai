<?php
/**
 * =====================================================
 * FILE: config/question_generator.php
 * MODUL: Natural Language Processing (NLP) - Question Generation
 * =====================================================
 *
 * File ini bertanggung jawab atas seluruh tahap ANALISIS dan
 * PEMBENTUKAN SOAL setelah teks diproses oleh text_processor.php.
 *
 * Pipeline NLP berbasis rule yang diimplementasikan di sini:
 *   1. Keyword Extraction        -> menghitung frekuensi kata penting
 *   2. Sentence Selection        -> memilih kalimat layak dijadikan soal
 *   3. Rule-Based Question Generation
 *        - Pilihan Ganda (Multiple Choice)
 *        - Essay
 *        - Benar / Salah (True False)
 *
 * Pendekatan yang digunakan adalah RULE-BASED NLP:
 * sistem mengenali pola kalimat definitif (mengandung kata kunci
 * seperti "merupakan", "adalah", "terdiri dari", dst), lalu
 * mengekstrak entitas penting dari kalimat tersebut untuk
 * dibentuk menjadi pertanyaan secara otomatis.
 */

require_once __DIR__ . '/text_processor.php';


/**
 * =====================================================
 * STEP 1 - KEYWORD EXTRACTION (Berdasarkan Frekuensi Kata)
 * =====================================================
 * Tahap NLP: Keyword Extraction
 * Tujuan: menemukan kata-kata yang paling sering muncul dalam
 * keseluruhan materi (term frequency), karena kata yang sering
 * disebut biasanya merupakan istilah/topik penting dalam materi.
 *
 * Proses:
 *   - Setiap kalimat di-tokenize
 *   - Token melewati Stopword Removal
 *   - Token yang tersisa dihitung frekuensinya
 *   - Diurutkan dari yang paling sering muncul
 *
 * @param array $sentences daftar kalimat (huruf kecil, sudah bersih)
 * @return array asosiatif [kata => frekuensi], terurut menurun
 */
function extractKeywords($sentences)
{
    $wordFrequency = [];

    foreach ($sentences as $sentence) {
        // Tokenizing -> pecah kalimat menjadi kata
        $tokens = tokenize($sentence);

        // Stopword Removal -> buang kata umum yang tidak penting
        $keywords = removeStopwords($tokens);

        // Hitung frekuensi tiap kata
        foreach ($keywords as $word) {
            if (!isset($wordFrequency[$word])) {
                $wordFrequency[$word] = 0;
            }
            $wordFrequency[$word]++;
        }
    }

    // Urutkan dari frekuensi tertinggi ke terendah
    arsort($wordFrequency);

    return $wordFrequency;
}


/**
 * =====================================================
 * STEP 2 - SENTENCE SELECTION
 * =====================================================
 * Tahap NLP: Sentence Selection
 * Tujuan: memilih kalimat-kalimat yang LAYAK dijadikan soal,
 * yaitu kalimat yang mengandung pola/pattern definitif seperti:
 * "adalah", "merupakan", "yaitu", "berfungsi", "terdiri dari",
 * "disebut", "memiliki", "digunakan untuk".
 *
 * Kalimat yang tidak mengandung pola ini dianggap tidak cocok
 * untuk dijadikan soal (noise) dan akan diabaikan.
 *
 * @param array $sentences daftar kalimat hasil preprocessing
 * @return array daftar kalimat kandidat soal beserta pattern yang cocok
 */
function selectCandidateSentences($sentences)
{
    // Pola kalimat definitif yang dicari (rule-based pattern)
    $patterns = [
        'adalah',
        'merupakan',
        'yaitu',
        'berfungsi',
        'terdiri dari',
        'disebut',
        'memiliki',
        'digunakan untuk'
    ];

    $candidates = [];

    foreach ($sentences as $sentence) {
        foreach ($patterns as $pattern) {
            // Cek apakah kalimat mengandung salah satu pola
            if (strpos($sentence, $pattern) !== false) {
                $candidates[] = [
                    'sentence' => $sentence,
                    'pattern'  => $pattern
                ];
                break; // satu kalimat cukup cocok dengan 1 pattern saja
            }
        }
    }

    return $candidates;
}


/**
 * =====================================================
 * FUNGSI BANTU - EKSTRAKSI SUBJEK & PREDIKAT DARI KALIMAT
 * =====================================================
 * Memecah kalimat kandidat menjadi bagian SUBJEK (sebelum pattern)
 * dan KETERANGAN (setelah pattern), untuk dijadikan dasar
 * pembentukan pertanyaan maupun jawaban.
 *
 * Contoh:
 * "cpu merupakan otak komputer"
 * pattern  = "merupakan"
 * subject  = "cpu"
 * remainder= "otak komputer"
 *
 * @param string $sentence kalimat kandidat
 * @param string $pattern pola yang ditemukan
 * @return array ['subject' => ..., 'remainder' => ...]
 */
function extractSubjectAndRemainder($sentence, $pattern)
{
    $parts = explode($pattern, $sentence, 2);

    $subject   = isset($parts[0]) ? trim($parts[0]) : '';
    $remainder = isset($parts[1]) ? trim($parts[1]) : '';

    return [
        'subject'   => $subject,
        'remainder' => $remainder
    ];
}


/**
 * =====================================================
 * FUNGSI BANTU - UBAH HURUF AWAL JADI HURUF BESAR
 * =====================================================
 * Membantu merapikan tampilan teks subjek/kalimat agar
 * terlihat seperti kalimat baku saat ditampilkan sebagai soal.
 *
 * @param string $text
 * @return string
 */
function capitalizeFirst($text)
{
    $text = trim($text);
    if ($text === '') {
        return $text;
    }
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}


/**
 * =====================================================
 * STEP 3a - RULE-BASED QUESTION GENERATION: ESSAY
 * =====================================================
 * Membentuk soal essay dari kalimat kandidat.
 * Pola pertanyaan dibentuk berdasarkan jenis pattern yang ditemukan.
 *
 * @param string $subject subjek kalimat (sebelum pattern)
 * @param string $pattern pola kalimat yang cocok
 * @param string $remainder bagian kalimat setelah pattern
 * @return string pertanyaan essay
 */
function generateEssayQuestion($subject, $pattern, $remainder)
{
    $subject = capitalizeFirst($subject);

    switch ($pattern) {
        case 'berfungsi':
        case 'digunakan untuk':
            return "Jelaskan fungsi dari {$subject}!";
        case 'terdiri dari':
            return "Sebutkan dan jelaskan bagian-bagian dari {$subject}!";
        case 'memiliki':
            return "Jelaskan apa yang dimiliki oleh {$subject}!";
        case 'disebut':
            return "Apa yang dimaksud dengan {$subject}? Jelaskan!";
        default: // adalah, merupakan, yaitu
            return "Jelaskan pengertian dari {$subject}!";
    }
}


/**
 * =====================================================
 * STEP 3b - RULE-BASED QUESTION GENERATION: PILIHAN GANDA
 * =====================================================
 * Membentuk soal pilihan ganda.
 * - Pertanyaan dibentuk dari subjek + pattern kalimat
 * - Jawaban benar diambil dari subjek (atau remainder, tergantung pola)
 * - Pengecoh (distractor) diambil dari KEYWORD lain hasil
 *   Keyword Extraction, agar pengecoh tetap relevan dengan materi.
 *
 * @param string $subject
 * @param string $pattern
 * @param string $remainder
 * @param array  $allKeywords daftar keyword global (untuk pengecoh)
 * @return array|null struktur soal pilihan ganda
 */
function generateMultipleChoiceQuestion($subject, $pattern, $remainder, $allKeywords)
{
    $subjectClean = capitalizeFirst($subject);

    if ($subject === '' || $remainder === '') {
        return null;
    }

    // Tentukan bentuk pertanyaan & jawaban benar berdasarkan pattern
    if ($pattern === 'terdiri dari' || $pattern === 'memiliki') {
        $question   = "{$subjectClean} {$pattern} ....";
        $correctAns = $remainder;
    } else {
        $question   = "Apa yang dimaksud dengan {$subjectClean}?";
        $correctAns = $remainder;
    }

    // Ambil 1-3 kata kunci utama dari remainder sebagai jawaban singkat
    $correctKeyword = getMainKeywordFromText($remainder, $allKeywords);
    if ($correctKeyword === '') {
        $correctKeyword = $remainder;
    }

    // Bentuk daftar pengecoh (distractor) dari keyword lain (selain jawaban benar & subjek).
    // Ide dasarnya: pengecoh diambil dari kata-kata yang MEMANG muncul di materi yang sama,
    // sehingga pilihan jawaban salah tetap terasa relevan/masuk akal, bukan kata sembarangan.
    $distractorPool = [];
    foreach (array_keys($allKeywords) as $kw) {
        if ($kw !== $correctKeyword && $kw !== $subject && mb_strlen($kw) > 2) {
            $distractorPool[] = $kw;
        }
    }

    // Acak pengecoh agar variatif setiap generate
    shuffle($distractorPool);
    $distractors = array_slice($distractorPool, 0, 3);

    // Jika keyword materi tidak cukup untuk 3 pengecoh, lengkapi dengan pengecoh umum
    $genericDistractors = ['data', 'sistem', 'proses', 'jaringan', 'program', 'aplikasi'];
    $i = 0;
    while (count($distractors) < 3 && $i < count($genericDistractors)) {
        if (!in_array($genericDistractors[$i], $distractors) && $genericDistractors[$i] !== $correctKeyword) {
            $distractors[] = $genericDistractors[$i];
        }
        $i++;
    }

    // Gabungkan jawaban benar dengan pengecoh, lalu acak urutan opsi
    $options = $distractors;
    $options[] = $correctKeyword;
    shuffle($options);

    // Format opsi menjadi A, B, C, D
    $labels = ['A', 'B', 'C', 'D'];
    $formattedOptions = [];
    $correctLabel = '';

    foreach ($options as $index => $opt) {
        if (!isset($labels[$index])) {
            continue;
        }
        $formattedOptions[$labels[$index]] = capitalizeFirst($opt);
        if ($opt === $correctKeyword) {
            $correctLabel = $labels[$index];
        }
    }

    return [
        'question'       => $question,
        'options'        => $formattedOptions,
        'correct_answer' => $correctLabel,
        'correct_text'   => capitalizeFirst($correctKeyword)
    ];
}


/**
 * =====================================================
 * FUNGSI BANTU - AMBIL KATA KUNCI UTAMA DARI SEPOTONG TEKS
 * =====================================================
 * Digunakan untuk menyederhanakan "remainder" kalimat menjadi
 * satu kata kunci utama (agar opsi jawaban tidak terlalu panjang).
 * Caranya: cari token dalam teks yang juga ada di daftar
 * keyword global, ambil yang frekuensinya paling tinggi.
 *
 * @param string $text
 * @param array $allKeywords
 * @return string
 */
function getMainKeywordFromText($text, $allKeywords)
{
    $tokens = tokenize($text);
    $tokens = removeStopwords($tokens);

    $bestWord = '';
    $bestScore = -1;

    foreach ($tokens as $token) {
        if (isset($allKeywords[$token]) && $allKeywords[$token] > $bestScore) {
            $bestScore = $allKeywords[$token];
            $bestWord = $token;
        }
    }

    // Jika tidak ada token yang cocok dengan keyword global, ambil token pertama
    if ($bestWord === '' && count($tokens) > 0) {
        $bestWord = $tokens[0];
    }

    return $bestWord;
}


/**
 * =====================================================
 * STEP 3c - RULE-BASED QUESTION GENERATION: BENAR / SALAH
 * =====================================================
 * Membentuk soal benar/salah dari kalimat kandidat.
 * Sebagian soal ditampilkan APA ADANYA (jawaban: Benar),
 * dan sebagian lain dimodifikasi/disangkal agar jawabannya Salah,
 * dengan cara mengganti kata kunci utama dengan keyword lain.
 *
 * @param string $sentence kalimat kandidat (asli)
 * @param string $subject
 * @param array  $allKeywords
 * @return array struktur soal benar/salah
 */
function generateTrueFalseQuestion($sentence, $subject, $allKeywords)
{
    // 50% kemungkinan soal dibuat benar, 50% dibuat salah (agar variatif).
    // rand(0,1) menghasilkan angka acak 0 atau 1 -> jika hasilnya 1, statement akan
    // "disangkal" dengan mengganti kata kunci utamanya (lihat blok if di bawah).
    $makeFalse = (rand(0, 1) === 1);

    $statement = capitalizeFirst($sentence);
    $isTrue = true;

    if ($makeFalse) {
        // Cari keyword pengganti yang berbeda dari subjek, untuk membuat statement palsu
        $candidates = array_keys($allKeywords);
        $candidates = array_filter($candidates, function ($kw) use ($subject) {
            return $kw !== $subject && mb_strlen($kw) > 2;
        });
        $candidates = array_values($candidates);

        if (count($candidates) > 0 && $subject !== '') {
            $replacement = $candidates[array_rand($candidates)];
            $modified = preg_replace('/\b' . preg_quote($subject, '/') . '\b/', $replacement, $sentence, 1);

            // Pastikan benar-benar berubah sebelum dianggap valid sebagai statement salah
            if ($modified !== $sentence) {
                $statement = capitalizeFirst($modified);
                $isTrue = false;
            }
        }
    }

    return [
        'statement' => $statement,
        'answer'    => $isTrue ? 'Benar' : 'Salah'
    ];
}


/**
 * =====================================================
 * FUNGSI UTAMA - PIPELINE PEMBENTUKAN SOAL
 * =====================================================
 * Menggabungkan seluruh tahap NLP menjadi satu pipeline akhir:
 *
 *   Sentences -> Keyword Extraction -> Sentence Selection
 *             -> Rule-Based Question Generation
 *
 * @param array  $sentences daftar kalimat hasil preprocessing
 * @param int    $jumlahSoal jumlah soal yang diminta user
 * @param string $jenisSoal 'pilihan_ganda' | 'essay' | 'benar_salah'
 * @return array daftar soal yang sudah terbentuk
 */
function generateQuestions($sentences, $jumlahSoal, $jenisSoal)
{
    // STEP 1: Keyword Extraction (frekuensi kata di seluruh materi)
    $allKeywords = extractKeywords($sentences);

    // STEP 2: Sentence Selection (pilih kalimat berpola definitif)
    $candidates = selectCandidateSentences($sentences);

    // Jika kalimat kandidat lebih sedikit dari jumlah soal yang diminta,
    // maka soal yang dihasilkan akan menyesuaikan jumlah kandidat yang ada.
    shuffle($candidates); // acak urutan agar soal tidak selalu sama setiap generate

    $questions = [];
    $count = 0;

    foreach ($candidates as $candidate) {
        if ($count >= $jumlahSoal) {
            break;
        }

        $sentence = $candidate['sentence'];
        $pattern  = $candidate['pattern'];

        // Ekstraksi subjek & keterangan dari kalimat
        $parts     = extractSubjectAndRemainder($sentence, $pattern);
        $subject   = $parts['subject'];
        $remainder = $parts['remainder'];

        if ($subject === '') {
            continue; // lewati jika tidak ada subjek yang jelas
        }

        // STEP 3: Rule-Based Question Generation sesuai jenis soal yang dipilih
        switch ($jenisSoal) {
            case 'essay':
                $questions[] = [
                    'type'     => 'essay',
                    'question' => generateEssayQuestion($subject, $pattern, $remainder)
                ];
                $count++;
                break;

            case 'benar_salah':
                $tfResult = generateTrueFalseQuestion($sentence, $subject, $allKeywords);
                $questions[] = [
                    'type'      => 'benar_salah',
                    'statement' => $tfResult['statement'],
                    'answer'    => $tfResult['answer']
                ];
                $count++;
                break;

            case 'pilihan_ganda':
            default:
                $mcq = generateMultipleChoiceQuestion($subject, $pattern, $remainder, $allKeywords);
                if ($mcq !== null) {
                    $questions[] = [
                        'type'           => 'pilihan_ganda',
                        'question'       => $mcq['question'],
                        'options'        => $mcq['options'],
                        'correct_answer' => $mcq['correct_answer'],
                        'correct_text'   => $mcq['correct_text']
                    ];
                    $count++;
                }
                break;
        }
    }

    return $questions;
}
