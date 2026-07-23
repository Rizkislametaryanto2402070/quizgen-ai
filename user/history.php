<?php
/**
 * =====================================================
 * FILE: user/history.php
 * FUNGSI: Menampilkan riwayat soal yang pernah di-generate user,
 *         dengan opsi untuk melihat detail soal (modal) dan
 *         menghapus riwayat.
 * =====================================================
 */
$pageTitle = 'Riwayat';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$conn = getConnection();
$userId = $_SESSION['user_id'];

// ===== PROSES HAPUS RIWAYAT =====
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM history WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $deleteId, $userId);
    $stmt->execute();
    $stmt->close();
    header('Location: history.php');
    exit;
}

// Ambil seluruh riwayat milik user, terbaru di atas
$stmt = $conn->prepare("SELECT id, file_name, jumlah_soal, jenis_soal, hasil_soal, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$histories = $stmt->get_result();
$stmt->close();
$conn->close();

$jenisLabel = [
    'pilihan_ganda' => 'Pilihan Ganda',
    'essay'         => 'Essay',
    'benar_salah'   => 'Benar / Salah'
];

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header fade-in">
    <h2>Riwayat Generate Soal</h2>
    <p class="text-muted">Semua soal yang pernah kamu buat tersimpan di sini.</p>
</div>

<div class="content-card fade-in-delay-1">
    <?php if ($histories->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Jumlah Soal</th>
                    <th>Jenis Soal</th>
                    <th>Tanggal</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $histories->fetch_assoc()): ?>
                <tr>
                    <td><i class="bi bi-file-text me-2 text-muted"></i><?= htmlspecialchars($h['file_name']) ?></td>
                    <td><span class="badge bg-primary-soft text-primary"><?= (int)$h['jumlah_soal'] ?> soal</span></td>
                    <td><?= htmlspecialchars($jenisLabel[$h['jenis_soal']] ?? $h['jenis_soal']) ?></td>
                    <td><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></td>
                    <td class="text-end">
                        <!-- Data JSON hasil soal "dititipkan" di atribut data-soal.
                             Nantinya assets/js/script.js akan membaca atribut ini,
                             mem-parsing JSON-nya, lalu menampilkannya di dalam modal
                             tanpa perlu reload halaman / request ulang ke server. -->
                        <button class="btn btn-sm btn-outline-primary view-detail-btn"
                                data-soal='<?= htmlspecialchars($h['hasil_soal'], ENT_QUOTES) ?>'
                                data-jenis="<?= htmlspecialchars($h['jenis_soal']) ?>"
                                data-file="<?= htmlspecialchars($h['file_name']) ?>">
                            <i class="bi bi-eye"></i> Lihat
                        </button>
                        <a href="history.php?delete=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-danger delete-btn">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada riwayat generate soal.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Soal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-patch-question-fill me-2"></i>Detail Soal - <span id="modalFileName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Diisi otomatis via JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
