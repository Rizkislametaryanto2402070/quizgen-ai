<?php
/**
 * =====================================================
 * FILE: index.php
 * FUNGSI: Entry point project. Mengarahkan user secara otomatis
 *         ke dashboard (jika sudah login) atau ke halaman login
 *         (jika belum login).
 * =====================================================
 */
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
