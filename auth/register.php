<?php
/**
 * =====================================================
 * FILE: auth/register.php
 * FUNGSI: Halaman & proses registrasi akun baru
 * =====================================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

// Jika form di-submit (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input dasar
    if ($username === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $conn = getConnection();

        // Cek apakah username atau email sudah terdaftar (prepared statement)
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = 'Username atau email sudah terdaftar.';
        } else {
            // Hash password sebelum disimpan ke database.
            // password_hash() mengubah password asli menjadi teks acak satu arah (hash)
            // sehingga meskipun database bocor, password asli user tidak langsung terbaca.
            // PASSWORD_DEFAULT -> PHP otomatis memilih algoritma hashing terbaik (saat ini bcrypt).
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insertStmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($insertStmt->execute()) {
                $success = 'Registrasi berhasil. Silakan login untuk melanjutkan.';
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data. Coba lagi.';
            }
            $insertStmt->close();
        }
        $checkStmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuizGen AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="text-center mb-4">
                <i class="bi bi-stars auth-icon"></i>
                <h3 class="fw-bold mt-2">QuizGen AI</h3>
                <p class="text-muted">Buat akun baru untuk mulai membuat soal otomatis</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">Daftar Sekarang</button>
            </form>

            <p class="text-center mt-4 mb-0 text-muted">
                Sudah punya akun? <a href="login.php" class="link-primary fw-semibold">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
