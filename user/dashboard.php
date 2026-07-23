<?php
/**
 * =====================================================
 * FILE: user/dashboard.php
 * FUNGSI: Halaman utama setelah login, menampilkan ringkasan
 *         statistik penggunaan (jumlah riwayat generate soal).
 * =====================================================
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$conn = getConnection();
$userId = $_SESSION['user_id'];

// Hitung total riwayat generate soal milik user ini
$stmt = $conn->prepare("SELECT COUNT(*) AS total, SUM(jumlah_soal) AS total_soal FROM history WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$totalGenerate = $row['total'] ?? 0;
$totalSoal = $row['total_soal'] ?? 0;
$stmt->close();

// Ambil 5 riwayat terakhir untuk ditampilkan di dashboard
$stmt2 = $conn->prepare("SELECT file_name, jumlah_soal, jenis_soal, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$recentHistory = $stmt2->get_result();
$stmt2->close();
$conn->close();

// Label jenis soal yang lebih ramah ditampilkan
$jenisLabel = [
    'pilihan_ganda' => 'Pilihan Ganda',
    'essay'         => 'Essay',
    'benar_salah'   => 'Benar / Salah'
];

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header fade-in">
    <h2>Dashboard</h2>
    <p class="text-muted">Selamat datang kembali, <?= htmlspecialchars($_SESSION['username']) ?>.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card fade-in">
            <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-cloud-upload-fill"></i></div>
            <div>
                <h3><?= (int)$totalGenerate ?></h3>
                <p>Total Generate</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card fade-in-delay-1">
            <div class="stat-icon bg-success-soft text-success"><i class="bi bi-patch-question-fill"></i></div>
            <div>
                <h3><?= (int)$totalSoal ?></h3>
                <p>Total Soal Dibuat</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card fade-in-delay-2">
            <div class="stat-icon bg-warning-soft text-warning"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <h3>NLP Rule-Based</h3>
                <p>Metode Generate Soal</p>
            </div>
        </div>
    </div>
</div>

<div class="content-card fade-in-delay-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0">Riwayat Terbaru</h5>
        <a href="upload.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Generate Soal Baru</a>
    </div>

    <?php if ($recentHistory->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Jumlah Soal</th>
                    <th>Jenis Soal</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $recentHistory->fetch_assoc()): ?>
                <tr>
                    <td><i class="bi bi-file-text me-2 text-muted"></i><?= htmlspecialchars($h['file_name']) ?></td>
                    <td><span class="badge bg-primary-soft text-primary"><?= (int)$h['jumlah_soal'] ?> soal</span></td>
                    <td><?= htmlspecialchars($jenisLabel[$h['jenis_soal']] ?? $h['jenis_soal']) ?></td>
                    <td><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada riwayat. Mulai dengan membuat soal pertama dari materi yang kamu miliki.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
