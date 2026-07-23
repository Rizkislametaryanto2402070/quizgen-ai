-- =====================================================
-- DATABASE: quizgen_ai
-- Project   : QuizGen AI - Automatic Question Generator From TXT
-- Deskripsi : Struktur database untuk menyimpan data user
--             dan riwayat hasil generate soal.
-- =====================================================

CREATE DATABASE IF NOT EXISTS quizgen_ai;
USE quizgen_ai;

-- =====================================================
-- TABEL: users
-- Menyimpan data akun pengguna (untuk login & register)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL, -- disimpan dalam bentuk hash (password_hash)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (username),
    UNIQUE KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: history
-- Menyimpan riwayat hasil generate soal per user.
-- Soal hasil generate disimpan dalam format JSON
-- agar fleksibel menampung Pilihan Ganda, Essay,
-- dan Benar/Salah dalam satu kolom.
-- =====================================================
CREATE TABLE IF NOT EXISTS history (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,        -- nama file TXT asli yang diupload
    jumlah_soal INT(11) NOT NULL,           -- jumlah soal yang diminta user
    jenis_soal VARCHAR(50) NOT NULL,        -- pilihan_ganda / essay / benar_salah
    hasil_soal LONGTEXT NOT NULL,           -- hasil soal dalam format JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY (user_id),
    CONSTRAINT fk_history_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
