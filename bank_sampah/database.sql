-- Database: login_system
-- Table: users

CREATE DATABASE IF NOT EXISTS login_system;
USE login_system;

-- Hapus tabel jika sudah ada
DROP TABLE IF EXISTS users;

-- Buat tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    level VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Insert data user (password: admin123, user123, guest123)
INSERT INTO users (username, password, nama_lengkap, email, level) VALUES
('admin', 'admin123', 'Administrator', 'admin@example.com', 'admin'),
('user', 'user123', 'User Biasa', 'user@example.com', 'user'),
('guest', 'guest123', 'Tamu', 'guest@example.com', 'guest');

-- Verifikasi data
SELECT * FROM users;
