-- =====================================================
-- PAYROLL SYSTEM V2 - SUPER LENGKAP
-- SQL SETUP SCRIPT
-- =====================================================
CREATE DATABASE IF NOT EXISTS db_payroll_v2;
USE db_payroll_v2;

-- DEPARTEMEN
DROP TABLE IF EXISTS departemen;
CREATE TABLE departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL,
    nama_dept VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- KARYAWAN (Enhanced)
DROP TABLE IF EXISTS karyawan;
CREATE TABLE karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_hp VARCHAR(20),
    alamat TEXT,
    jabatan VARCHAR(50) NOT NULL,
    id_departemen INT,
    status ENUM('Aktif','Non-Aktif','Resign','PHK') DEFAULT 'Aktif',
    tgl_masuk DATE NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL,
    no_rekening VARCHAR(30),
    atas_nama VARCHAR(100),
    bank VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_departemen) REFERENCES departemen(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- KEHADIRAN (Enhanced)
DROP TABLE IF EXISTS kehadiran;
CREATE TABLE kehadiran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    hadir INT DEFAULT 0,
    sakit INT DEFAULT 0,
    izin INT DEFAULT 0,
    alpa INT DEFAULT 0,
    lembur INT DEFAULT 0,
    total_hari_kerja INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kehadiran (id_karyawan, periode_bulan, periode_tahun)
) ENGINE=InnoDB;

-- SETTING PAYROLL
DROP TABLE IF EXISTS setting_payroll;
CREATE TABLE setting_payroll (
    id INT PRIMARY KEY DEFAULT 1,
    umr DECIMAL(10,2) DEFAULT 5000000,
    tunjangan_transport DECIMAL(10,2) DEFAULT 500000,
    tunjangan_makan DECIMAL(10,2) DEFAULT 300000,
    tunjangan_kesehatan DECIMAL(10,2) DEFAULT 200000,
    bonus_lembur_per_jam DECIMAL(10,2) DEFAULT 25000,
    denda_alpa_per_hari DECIMAL(10,2) DEFAULT 100000,
    denda_terlambat_per_kali DECIMAL(10,2) DEFAULT 25000,
    pengganti_libur DECIMAL(10,2) DEFAULT 150000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- PENGGAJIAN (Super Complete)
DROP TABLE IF EXISTS penggajian;
CREATE TABLE penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    hadir INT DEFAULT 0,
    sakit INT DEFAULT 0,
    izin INT DEFAULT 0,
    alpa INT DEFAULT 0,
    lembur INT DEFAULT 0,
    gaji_pokok DECIMAL(12,2) NOT NULL,
    tunjangan_jabatan DECIMAL(12,2) DEFAULT 0,
    tunjangan_transport DECIMAL(12,2) DEFAULT 0,
    tunjangan_makan DECIMAL(12,2) DEFAULT 0,
    tunjangan_kesehatan DECIMAL(12,2) DEFAULT 0,
    tunjangan_masa_kerja DECIMAL(12,2) DEFAULT 0,
    bonus_kehadiran DECIMAL(12,2) DEFAULT 0,
    bonus_lembur DECIMAL(12,2) DEFAULT 0,
    total_penghasilan DECIMAL(12,2) DEFAULT 0,
    potongan_alpa DECIMAL(12,2) DEFAULT 0,
    potongan_izin DECIMAL(12,2) DEFAULT 0,
    potongan_bpjs_kesehatan DECIMAL(12,2) DEFAULT 0,
    potongan_bpjs_ketenagakerjaan DECIMAL(12,2) DEFAULT 0,
    potongan_pph21 DECIMAL(12,2) DEFAULT 0,
    potongan_lainnya DECIMAL(12,2) DEFAULT 0,
    total_potongan DECIMAL(12,2) DEFAULT 0,
    gaji_bersih DECIMAL(12,2) DEFAULT 0,
    status ENUM('draft','process','paid') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_penggajian (id_karyawan, periode_bulan, periode_tahun)
) ENGINE=InnoDB;

-- KOMPONEN GAJI
DROP TABLE IF EXISTS komponen_gaji;
CREATE TABLE komponen_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis ENUM('tunjangan','potongan') NOT NULL,
    tipe ENUM('persen','fixed') DEFAULT 'fixed',
    nilai DECIMAL(12,2) DEFAULT 0,
    persen TINYINT DEFAULT 0,
    aktif TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA
-- =====================================================
INSERT INTO departemen (kode, nama_dept, keterangan) VALUES
('HRD','Human Resource Development','Departemen SDM'),
('FIN','Finance & Accounting','Departemen Keuangan'),
('SALES','Sales & Marketing','Departemen Penjualan'),
('IT','Information Technology','Departemen IT'),
('PROD','Production','Departemen Produksi');

INSERT INTO karyawan (nik, nama, email, no_hp, alamat, jabatan, id_departemen, status, tgl_masuk, gaji_pokok, no_rekening, atas_nama, bank) VALUES
('NIK001','Ahmad Wijaya','ahmad@company.com','081234567890','Jl. Sudirman No.1','Manager',1,'Aktif','2020-01-15',15000000,'1234567890','Ahmad Wijaya','BCA'),
('NIK002','Siti Nurhaliza','siti@company.com','081234567891','Jl. Gatot Subroto No.2','Supervisor',2,'Aktif','2020-03-20',10000000,'2345678901','Siti Nurhaliza','Mandiri'),
('NIK003','Budi Santoso','budi@company.com','081234567892','Jl. Thamrin No.3','Staff',3,'Aktif','2021-06-01',7500000,'3456789012','Budi Santoso','BRI'),
('NIK004','Dewi Lestari','dewi@company.com','081234567893','Jl. Merdeka No.4','Senior Staff',4,'Aktif','2021-09-15',8500000,'4567890123','Dewi Lestari','BNI'),
('NIK005','Eko Prasetyo','eko@company.com','081234567894','Jl. Diponegoro No.5','Staff',5,'Aktif','2022-01-10',6500000,'5678901234','Eko Prasetyo','BTPN'),
('NIK006','Fitri Handayani','fitri@company.com','081234567895','Jl. A Yani No.6','Junior Staff',1,'Aktif','2023-03-01',5000000,'6789012345','Fitri Handayani','BCA'),
('NIK007','Gunawan Hidayat','gunawan@company.com','081234567896','Jl. Asia Afrika No.7','Assistant Manager',2,'Aktif','2020-07-20',12000000,'7890123456','Gunawan Hidayat','Mandiri');

INSERT INTO setting_payroll (id, umr, tunjangan_transport, tunjangan_makan, tunjangan_kesehatan, bonus_lembur_per_jam, denda_alpa_per_hari, denda_terlambat_per_kali, pengganti_libur) VALUES
(1, 5000000, 500000, 300000, 200000, 25000, 100000, 25000, 150000);
