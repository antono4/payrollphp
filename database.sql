-- =====================================================
-- DATABASE PAYROLL - SQL SETUP SCRIPT
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_payroll;
USE db_payroll;

-- TABEL KARYAWAN
DROP TABLE IF EXISTS karyawan;
CREATE TABLE karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jabatan ENUM('Direksi', 'Manager', 'Supervisor', 'Staff', 'Operator') NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- TABEL KEHADIRAN
DROP TABLE IF EXISTS kehadiran;
CREATE TABLE kehadiran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    hadir INT NOT NULL DEFAULT 0,
    izin INT NOT NULL DEFAULT 0,
    alpa INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kehadiran (id_karyawan, periode_bulan, periode_tahun)
) ENGINE=InnoDB;

-- TABEL PENGGAJIAN
DROP TABLE IF EXISTS penggajian;
CREATE TABLE penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL,
    tunjangan_kehadiran DECIMAL(12,2) NOT NULL DEFAULT 0,
    tunjangan_jabatan DECIMAL(12,2) NOT NULL DEFAULT 0,
    bonus_lembur DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_tunjangan DECIMAL(12,2) NOT NULL DEFAULT 0,
    potongan_alpa DECIMAL(12,2) NOT NULL DEFAULT 0,
    potongan_izin DECIMAL(12,2) NOT NULL DEFAULT 0,
    potongan_bpjs DECIMAL(12,2) NOT NULL DEFAULT 0,
    potongan_pph21 DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(12,2) NOT NULL DEFAULT 0,
    gaji_bruto DECIMAL(12,2) NOT NULL DEFAULT 0,
    gaji_bersih DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'process', 'paid') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_penggajian (id_karyawan, periode_bulan, periode_tahun)
) ENGINE=InnoDB;

-- DATA SAMPLE
INSERT INTO karyawan (nik, nama, jabatan, gaji_pokok) VALUES
('NIK001', 'Ahmad Wijaya', 'Manager', 15000000.00),
('NIK002', 'Siti Nurhaliza', 'Supervisor', 10000000.00),
('NIK003', 'Budi Santoso', 'Staff', 7500000.00),
('NIK004', 'Dewi Lestari', 'Operator', 5000000.00),
('NIK005', 'Eko Prasetyo', 'Operator', 5000000.00);
