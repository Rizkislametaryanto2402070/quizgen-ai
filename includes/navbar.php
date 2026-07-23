<?php
/**
 * =====================================================
 * FILE: includes/navbar.php
 * FUNGSI: Sidebar navigasi yang dipakai di semua halaman user.
 * =====================================================
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="d-flex">
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-stars"></i>
            <span>QuizGen AI</span>
        </div>

        <div class="sidebar-user">
            <i class="bi bi-person-circle"></i>
            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="upload.php" class="<?= $currentPage === 'upload.php' ? 'active' : '' ?>">
                    <i class="bi bi-cloud-upload-fill"></i> Upload TXT
                </a>
            </li>
            <li>
                <a href="history.php" class="<?= $currentPage === 'history.php' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Riwayat
                </a>
            </li>
            <li>
                <a href="../auth/logout.php" id="logoutBtn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- MAIN CONTENT WRAPPER -->
    <main class="main-content">
