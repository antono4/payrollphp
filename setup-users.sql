-- =====================================================
-- PAYROLL SYSTEM V2.1 - SETUP USERS TABLE
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- =====================================================

-- Buat tabel users jika belum ada
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    level ENUM('admin','manager','finance','staff') NOT NULL DEFAULT 'staff',
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    id_departemen INT,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_departemen) REFERENCES departemen(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Hapus user lama jika ada (untuk reset)
DELETE FROM users WHERE username IN ('admin', 'manager', 'finance', 'staff');

-- Insert user demo (password untuk semua: password)
-- Hash bcrypt untuk 'password' adalah: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (username, password, nama_lengkap, email, level, status, id_departemen) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@company.com', 'admin', 'aktif', NULL),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager HRD', 'manager@company.com', 'manager', 'aktif', 1),
('finance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Manager', 'finance@company.com', 'finance', 'aktif', 2),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Umum', 'staff@company.com', 'staff', 'aktif', NULL);

-- Verifikasi
SELECT 'Setup berhasil!' as status;
SELECT id, username, nama_lengkap, email, level, status FROM users;
