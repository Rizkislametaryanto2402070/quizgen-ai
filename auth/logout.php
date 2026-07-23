<?php
/**
 * =====================================================
 * FILE: auth/logout.php
 * FUNGSI: Menghapus session dan mengarahkan ke halaman login
 * =====================================================
 */
session_start();

// Hapus semua data session
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
