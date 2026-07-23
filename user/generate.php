<?php
/**
 * =====================================================
 * FILE: user/generate.php
 * FUNGSI: Menjalankan seluruh pipeline NLP rule-based untuk
 *         menghasilkan soal dari file TXT yang sudah diupload,
 *         lalu menyimpan hasilnya ke tabel history.
 * =====================================================
 */
$pageTitle = 'Hasil Generate';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/text_processor.php';
require_once __DIR__ . '/../config/question_generator.php';

// Pastikan ada data upload yang tersimpan di session
if (!isset($_SESSION['upload_data'])) {
    header('Location: upload.php');
    exit;
}

$uploadData = $_SESSION['upload_data'];
$filePath   = $uploadData['file_path'];
$jumlahSoal = $uploadData['jumlah_soal'];
$jenisSoal  = $uploadData['jenis_soal'];
$originalName = $uploadData['original_name'];

// =====================================================
// PIPELINE NLP RULE-BASED
// =====================================================
// 1. Preprocessing: Baca File -> Case Folding -> Cleaning -> Sentence Splitting
$preprocessed = preprocessText($filePath);
$sentences = $preprocessed['sentences'];

$questions = [];
$generateError = '';

if (count($sentences) === 0) {
    $generateError = 'File TXT tidak mengandung kalimat yang dapat diproses.';
} else {
    // 2. Keyword Extraction + Sentence Selection + Rule-Based Question Generation
    $questions = generateQuestions($sentences, $jumlahSoal, $jenisSoal);

    if (count($questions) === 0) {
        $generateError = 'Tidak ditemukan kalimat dengan pola definitif (adalah, merupakan, yaitu, dll) pada materi. Coba gunakan materi yang lebih deskriptif.';
    } else {
        // Simpan hasil ke database (tabel history) dalam format JSON.
        // Alasan pakai JSON: setiap jenis soal (PG/Essay/Benar-Salah) punya struktur
        // data berbeda, jadi lebih fleksibel disimpan sebagai satu kolom JSON (LONGTEXT)
        // daripada membuat banyak kolom terpisah yang sebagian besar akan kosong.
        $conn = getConnection();
        $userId = $_SESSION['user_id'];
        // JSON_UNESCAPED_UNICODE -> agar huruf non-ASCII (misal huruf berimbuhan/aksen) tidak
        // diubah menjadi kode escape unicode, sehingga tetap enak dibaca di database.
        $hasilJson = json_encode($questions, JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("INSERT INTO history (user_id, file_name, jumlah_soal, jenis_soal, hasil_soal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $userId, $originalName, $jumlahSoal, $jenisSoal, $hasilJson);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}

// Hapus data sesi upload agar tidak diproses ulang jika halaman direfresh
unset($_SESSION['upload_data']);

$jenisLabel = [
    'pilihan_ganda' => 'Pilihan Ganda',
    'essay'         => 'Essay',
    'benar_salah'   => 'Benar / Salah'
];

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header fade-in">
    <h2>Hasil Generate Soal</h2>
    <p class="text-muted">
        File: <strong><?= htmlspecialchars($originalName) ?></strong> &middot;
        Jenis: <strong><?= htmlspecialchars($jenisLabel[$jenisSoal] ?? $jenisSoal) ?></strong> &middot;
        Diminta: <strong><?= (int)$jumlahSoal ?> soal</strong>
    </p>
</div>

<?php if ($generateError): ?>
    <div class="alert alert-warning fade-in-delay-1">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($generateError) ?>
    </div>
    <a href="upload.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Coba Upload Lagi</a>
<?php else: ?>

    <div class="alert alert-success fade-in-delay-1">
        <i class="bi bi-check-circle me-1"></i> Berhasil membuat <strong><?= count($questions) ?></strong> soal dari materi yang diupload.
    </div>

    <div class="content-card fade-in-delay-2">
        <?php foreach ($questions as $index => $q): ?>
            <div class="question-item">
                <div class="question-number">Soal <?= $index + 1 ?></div>

                <?php if ($q['type'] === 'pilihan_ganda'): ?>
                    <p class="question-text"><?= htmlspecialchars($q['question']) ?></p>
                    <div class="options-list">
                        <?php foreach ($q['options'] as $label => $optionText): ?>
                            <div class="option-row <?= $label === $q['correct_answer'] ? 'correct-option' : '' ?>">
                                <span class="option-label"><?= htmlspecialchars($label) ?></span>
                                <span><?= htmlspecialchars($optionText) ?></span>
                                <?php if ($label === $q['correct_answer']): ?>
                                    <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($q['type'] === 'essay'): ?>
                    <p class="question-text"><?= htmlspecialchars($q['question']) ?></p>
                    <span class="badge bg-secondary">Soal Essay</span>

                <?php elseif ($q['type'] === 'benar_salah'): ?>
                    <p class="question-text"><?= htmlspecialchars($q['statement']) ?></p>
                    <span class="badge <?= $q['answer'] === 'Benar' ? 'bg-success' : 'bg-danger' ?>">
                        Jawaban: <?= htmlspecialchars($q['answer']) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 mt-3">
        <a href="upload.php" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Generate Soal Baru</a>
        <a href="history.php" class="btn btn-primary"><i class="bi bi-clock-history"></i> Lihat Riwayat</a>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
