<?php
/**
 * =====================================================
 * PAYROLL SYSTEM V2 - INSTALLER
 * Auto Setup Database & Configuration
 * =====================================================
 */

// Konfigurasi Default
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'db_payroll_v2'
];

$message = '';
$success = false;
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// ==================== PROSES INSTALL ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'db_payroll_v2';
    
    // Test koneksi
    $conn = @new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Gagal!</strong> Tidak dapat terhubung ke MySQL: ' . $conn->connect_error . '
        </div>';
    } else {
        // Buat database
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$conn->query($sql)) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Gagal!</strong> Tidak dapat membuat database: ' . $conn->error . '
            </div>';
        } else {
            $conn->select_db($db_name);
            
            // SQL Tables
            $tables_sql = "
-- DEPARTEMEN
CREATE TABLE IF NOT EXISTS departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL,
    nama_dept VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- KARYAWAN
CREATE TABLE IF NOT EXISTS karyawan (
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

-- KEHADIRAN
CREATE TABLE IF NOT EXISTS kehadiran (
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
CREATE TABLE IF NOT EXISTS setting_payroll (
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

-- PENGGAJIAN
CREATE TABLE IF NOT EXISTS penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    periode_bulan INT NOT NULL,
    periode_tahun INT NOT NULL,
    hadir INT DEFAULT 0, sakit INT DEFAULT 0, izin INT DEFAULT 0, alpa INT DEFAULT 0, lembur INT DEFAULT 0,
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
CREATE TABLE IF NOT EXISTS komponen_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis ENUM('tunjangan','potongan') NOT NULL,
    tipe ENUM('persen','fixed') DEFAULT 'fixed',
    nilai DECIMAL(12,2) DEFAULT 0,
    persen TINYINT DEFAULT 0,
    aktif TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";
            
            // Execute table creation
            $errors = [];
            $statements = array_filter(array_map('trim', explode(';', $tables_sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt) && !$conn->query($stmt)) {
                    $errors[] = $conn->error;
                }
            }
            
            // Insert Sample Data
            $sample_data = "
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
";
            
            $statements = array_filter(array_map('trim', explode(';', $sample_data)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $conn->query($stmt);
                }
            }
            
            // Update config in index.php
            $index_content = file_get_contents('index.php');
            $index_content = preg_replace(
                "/define\('DB_HOST',[^)]+\)/",
                "define('DB_HOST', '$db_host')",
                $index_content
            );
            $index_content = preg_replace(
                "/define\('DB_USER',[^)]+\)/",
                "define('DB_USER', '$db_user')",
                $index_content
            );
            $index_content = preg_replace(
                "/define\('DB_PASS',[^)]+\)/",
                "define('DB_PASS', '$db_pass')",
                $index_content
            );
            $index_content = preg_replace(
                "/define\('DB_NAME',[^)]+\)/",
                "define('DB_NAME', '$db_name')",
                $index_content
            );
            file_put_contents('index.php', $index_content);
            
            $success = true;
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Berhasil!</strong> Database dan sample data berhasil diinstal!
            </div>';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll System V2 - Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>tailwind.config={theme:{extend:{colors:{primary:{600:'#2563eb',700:'#1d4ed8'}}}}}</script>
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-950 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calculator text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Payroll System V2</h1>
                    <p class="text-blue-200">Installer & Setup Wizard</p>
                </div>
            </div>
        </div>
        
        <div class="p-8">
            <?php if ($success): ?>
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-4xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Instalasi Berhasil!</h2>
                    <p class="text-gray-600 mb-6">Sistem payroll telah siap digunakan.</p>
                    
                    <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
                        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-database mr-2"></i>Database:</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Departemen</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Karyawan</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Kehadiran</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Setting Payroll</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Penggajian</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Tabel Komponen Gaji</li>
                        </ul>
                    </div>
                    
                    <div class="bg-blue-50 rounded-xl p-4 mb-6 text-left">
                        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-user-friends mr-2"></i>Sample Data:</h3>
                        <p class="text-sm text-gray-600">7 karyawan dan 5 departemen telah ditambahkan.</p>
                    </div>
                    
                    <a href="index.php" class="inline-block bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
                        <i class="fas fa-arrow-right mr-2"></i>Buka Aplikasi
                    </a>
                    
                    <p class="text-xs text-gray-400 mt-4">
                        Hapus file installer.php setelah instalasi selesai untuk keamanan.
                    </p>
                </div>
            <?php else: ?>
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-server text-2xl text-blue-600"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Konfigurasi Database</h2>
                    <p class="text-gray-500 text-sm">Masukkan informasi koneksi MySQL Anda</p>
                </div>
                
                <?php echo $message; ?>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MySQL Host</label>
                        <input type="text" name="db_host" value="localhost" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username MySQL</label>
                        <input type="text" name="db_user" value="root" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password MySQL</label>
                        <input type="password" name="db_pass" placeholder="Kosongkan jika tidak ada"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Database</label>
                        <input type="text" name="db_name" value="db_payroll_v2" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Catatan:</strong> Database akan otomatis dibuat jika belum ada. 
                            Pastikan user MySQL memiliki hak CREATE DATABASE.
                        </p>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg flex items-center justify-center space-x-2">
                        <i class="fas fa-install"></i>
                        <span>Install Sekarang</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 text-center">
            <p class="text-xs text-gray-500">Payroll System V2 - © 2024</p>
        </div>
    </div>
</body>
</html>
