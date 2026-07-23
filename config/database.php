<?php
/**
 * =====================================================
 * FILE: config/database.php
 * FUNGSI: Menangani koneksi ke database MySQL
 *         menggunakan ekstensi mysqli (PHP Native).
 * =====================================================
 */

// Konfigurasi koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quizgen_ai');

/**
 * Fungsi untuk membuat koneksi database.
 * Menggunakan mysqli (bukan PDO) sesuai aturan PHP Native sederhana.
 *
 * @return mysqli
 */
function getConnection()
{
    // Membuat koneksi baru ke MySQL.
    // Objek mysqli inilah yang nantinya dipakai di semua file lain untuk
    // menjalankan query (SELECT/INSERT/UPDATE/DELETE) dengan prepared statement.
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Jika koneksi gagal, hentikan eksekusi dan tampilkan pesan error
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }

    // Set karakter set ke utf8mb4 agar mendukung berbagai karakter (termasuk emoji/simbol)
    $conn->set_charset("utf8mb4");

    return $conn;
}
