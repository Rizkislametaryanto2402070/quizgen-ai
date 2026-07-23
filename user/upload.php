<?php
/**
 * =====================================================
 * FILE: user/upload.php
 * FUNGSI: Halaman upload file TXT + form pilihan jumlah & jenis soal.
 * Proses upload divalidasi (format & ukuran), file disimpan,
 * lalu user diarahkan ke generate.php untuk diproses NLP-nya.
 * =====================================================
 */
$pageTitle = 'Upload TXT';
require_once __DIR__ . '/../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlahSoal = (int)($_POST['jumlah_soal'] ?? 0);
    $jenisSoal  = $_POST['jenis_soal'] ?? '';

    $allowedJumlah = [5, 10, 15, 20];
    $allowedJenis  = ['pilihan_ganda', 'essay', 'benar_salah'];

    // ===== VALIDASI UPLOAD FILE =====
    if (!isset($_FILES['materi']) || $_FILES['materi']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File belum dipilih atau terjadi kesalahan saat upload.';
    } else {
        $file = $_FILES['materi'];
        $fileName = $file['name'];
        $fileTmp  = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi format file: hanya .txt
        if ($fileExt !== 'txt') {
            $error = 'Format file harus .txt';
        }
        // Validasi ukuran file: maksimal 5 MB
        elseif ($fileSize > 5 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 5 MB.';
        }
        // Validasi jumlah soal & jenis soal yang dipilih
        elseif (!in_array($jumlahSoal, $allowedJumlah)) {
            $error = 'Jumlah soal tidak valid.';
        } elseif (!in_array($jenisSoal, $allowedJenis)) {
            $error = 'Jenis soal tidak valid.';
        } else {
            // Pastikan folder uploads/ benar-benar ada di server.
            // Jika belum ada (misal hilang saat extract ZIP), buat otomatis.
            $uploadsDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Buat nama file unik agar tidak tertimpa file lain.
            // uniqid('materi_', true) menghasilkan nama seperti "materi_65f1a2b3c4d5e6.12345678"
            // berdasarkan timestamp + angka acak, sehingga 2 user yang upload file
            // dengan nama sama tidak akan saling menimpa file di server.
            $newFileName = uniqid('materi_', true) . '.txt';
            $uploadPath  = $uploadsDir . '/' . $newFileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Simpan info ke session untuk dipakai di generate.php
                $_SESSION['upload_data'] = [
                    'file_path'      => $uploadPath,
                    'original_name'  => $fileName,
                    'jumlah_soal'    => $jumlahSoal,
                    'jenis_soal'     => $jenisSoal
                ];
                header('Location: generate.php');
                exit;
            } else {
                $error = 'Gagal menyimpan file ke server.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header fade-in">
    <h2>Upload Materi (.txt)</h2>
    <p class="text-muted">Upload file materi pembelajaran, sistem akan menganalisis teks secara otomatis menggunakan NLP rule-based.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content-card fade-in-delay-1">
    <form method="POST" action="upload.php" enctype="multipart/form-data" id="uploadForm">

        <div class="mb-4">
            <label class="form-label fw-semibold">File Materi (.txt, maksimal 5 MB)</label>
            <div class="upload-dropzone" id="dropzone">
                <i class="bi bi-cloud-arrow-up-fill"></i>
                <p class="mb-1 fw-semibold">Klik atau seret file TXT ke sini</p>
                <p class="text-muted small mb-0" id="fileNameLabel">Belum ada file dipilih</p>
                <input type="file" name="materi" id="materiInput" accept=".txt" required class="d-none">
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Jumlah Soal</label>
                <select name="jumlah_soal" class="form-select" required>
                    <option value="">-- Pilih Jumlah --</option>
                    <option value="5">5 Soal</option>
                    <option value="10" selected>10 Soal</option>
                    <option value="15">15 Soal</option>
                    <option value="20">20 Soal</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Jenis Soal</label>
                <select name="jenis_soal" class="form-select" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="pilihan_ganda">Pilihan Ganda</option>
                    <option value="essay">Essay</option>
                    <option value="benar_salah">Benar / Salah</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-4 px-4">
            <i class="bi bi-magic"></i> Generate Soal Sekarang
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
