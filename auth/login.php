<?php
/**
 * =====================================================
 * FILE: auth/login.php
 * FUNGSI: Halaman & proses login user
 * =====================================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $conn = getConnection();

        // Ambil data user berdasarkan username (prepared statement -> anti SQL Injection).
        // Tanda "?" adalah placeholder yang nilainya baru diisi lewat bind_param(),
        // sehingga input user TIDAK PERNAH digabung langsung ke teks query.
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        // "s" artinya parameter ke-1 bertipe string
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password menggunakan password_verify()
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];

                $stmt->close();
                $conn->close();

                header('Location: ../user/dashboard.php');
                exit;
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QuizGen AI</title>
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
                <p class="text-muted">Masuk untuk mulai membuat soal otomatis dari materi TXT</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">Masuk</button>
            </form>

            <p class="text-center mt-4 mb-0 text-muted">
                Belum punya akun? <a href="register.php" class="link-primary fw-semibold">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
