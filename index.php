<?php
/**
 * =====================================================
 * APLIKASI PAYROLL - ONE PAGE CODING
 * Full Stack Development with Native PHP
 * =====================================================
 */

// ==================== KONFIGURASI ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_payroll');

// ==================== KONEKSI DATABASE ====================
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die(json_encode(['status' => 'error', 'message' => 'Koneksi gagal: ' . $this->connection->connect_error]));
        }
        $this->connection->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// ==================== FUNGSI HELPER ====================
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function getNamaBulan($bulan) {
    $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $nama_bulan[$bulan] ?? '';
}

// ==================== HANDLING AJAX REQUEST ====================
$db = Database::getInstance();
$conn = $db->getConnection();

$response = ['status' => 'error', 'message' => 'Aksi tidak valid'];

// Handle AJAX Requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        // ==================== CRUD KARYAWAN ====================
        case 'get_karyawan':
            $result = $conn->query("SELECT * FROM karyawan ORDER BY id DESC");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'get_karyawan_by_id':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = ['status' => 'success', 'data' => $result->fetch_assoc()];
            $stmt->close();
            break;

        case 'simpan_karyawan':
            $nik = $conn->real_escape_string($_POST['nik']);
            $nama = $conn->real_escape_string($_POST['nama']);
            $jabatan = $conn->real_escape_string($_POST['jabatan']);
            $gaji_pokok = floatval($_POST['gaji_pokok']);
            
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE karyawan SET nik=?, nama=?, jabatan=?, gaji_pokok=? WHERE id=?");
                $stmt->bind_param("sssdi", $nik, $nama, $jabatan, $gaji_pokok, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO karyawan (nik, nama, jabatan, gaji_pokok) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssd", $nik, $nama, $jabatan, $gaji_pokok);
            }
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Data karyawan berhasil disimpan'];
            } else {
                $response = ['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error];
            }
            $stmt->close();
            break;

        case 'hapus_karyawan':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM karyawan WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Data karyawan berhasil dihapus'];
            } else {
                $response = ['status' => 'error', 'message' => 'Gagal menghapus: ' . $stmt->error];
            }
            $stmt->close();
            break;

        // ==================== CRUD KEHADIRAN ====================
        case 'get_kehadiran':
            $result = $conn->query("SELECT k.*, kr.nama, kr.nik 
                                    FROM kehadiran k 
                                    JOIN karyawan kr ON k.id_karyawan = kr.id 
                                    ORDER BY k.periode_tahun DESC, k.periode_bulan DESC");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'simpan_kehadiran':
            $id_karyawan = intval($_POST['id_karyawan']);
            $periode_bulan = intval($_POST['periode_bulan']);
            $periode_tahun = intval($_POST['periode_tahun']);
            $hadir = intval($_POST['hadir']);
            $izin = intval($_POST['izin']);
            $alpa = intval($_POST['alpa']);
            
            $sql = "INSERT INTO kehadiran (id_karyawan, periode_bulan, periode_tahun, hadir, izin, alpa) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE hadir=?, izin=?, alpa=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiiiiI", $id_karyawan, $periode_bulan, $periode_tahun, $hadir, $izin, $alpa, $hadir, $izin, $alpa);
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Data kehadiran berhasil disimpan'];
            } else {
                $response = ['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error];
            }
            $stmt->close();
            break;

        case 'hapus_kehadiran':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM kehadiran WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Data kehadiran berhasil dihapus'];
            } else {
                $response = ['status' => 'error', 'message' => 'Gagal menghapus: ' . $stmt->error];
            }
            $stmt->close();
            break;

        // ==================== PERHITUNGAN PAYROLL ====================
        case 'proses_payroll':
            $id_karyawan = intval($_POST['id_karyawan']);
            $periode_bulan = intval($_POST['periode_bulan']);
            $periode_tahun = intval($_POST['periode_tahun']);
            
            // Ambil data karyawan
            $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id = ?");
            $stmt->bind_param("i", $id_karyawan);
            $stmt->execute();
            $karyawan = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$karyawan) {
                $response = ['status' => 'error', 'message' => 'Karyawan tidak ditemukan'];
                break;
            }
            
            // Ambil data kehadiran
            $stmt = $conn->prepare("SELECT * FROM kehadiran WHERE id_karyawan = ? AND periode_bulan = ? AND periode_tahun = ?");
            $stmt->bind_param("iii", $id_karyawan, $periode_bulan, $periode_tahun);
            $stmt->execute();
            $kehadiran = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $hadir = $kehadiran ? $kehadiran['hadir'] : 0;
            $izin = $kehadiran ? $kehadiran['izin'] : 0;
            $alpa = $kehadiran ? $kehadiran['alpa'] : 0;
            
            $gaji_pokok = floatval($karyawan['gaji_pokok']);
            
            // Perhitungan Tunjangan
            $tunjangan_jabatan = match($karyawan['jabatan']) {
                'Direksi' => $gaji_pokok * 0.30,
                'Manager' => $gaji_pokok * 0.25,
                'Supervisor' => $gaji_pokok * 0.20,
                'Staff' => $gaji_pokok * 0.15,
                'Operator' => $gaji_pokok * 0.10,
                default => 0
            };
            
            // Tunjangan Kehadiran (Bonus Hadir)
            $tunjangan_kehadiran = $hadir * 50000; // Rp 50.000 per hari hadir
            
            // Bonus Lembur (jika hadir > 26 hari)
            $bonus_lembur = ($hadir > 26) ? ($hadir - 26) * 75000 : 0;
            
            $total_tunjangan = $tunjangan_jabatan + $tunjangan_kehadiran + $bonus_lembur;
            
            // Perhitungan Potongan
            $potongan_alpa = $alpa * 100000; // Rp 100.000 per hari alpa
            $potongan_izin = $izin * 50000; // Rp 50.000 per hari izin
            $potongan_bpjs = $gaji_pokok * 0.02; // BPJS 2% dari gaji pokok
            $potongan_pph21 = ($gaji_pokok + $tunjangan_jabatan) * 0.05; // PPh 21 5%
            
            $total_potongan = $potongan_alpa + $potongan_izin + $potongan_bpjs + $potongan_pph21;
            
            // Gaji Bersih
            $gaji_bruto = $gaji_pokok + $total_tunjangan;
            $gaji_bersih = $gaji_bruto - $total_potongan;
            
            // Simpan ke tabel penggajian
            $sql = "INSERT INTO penggajian (
                id_karyawan, periode_bulan, periode_tahun, gaji_pokok,
                tunjangan_kehadiran, tunjangan_jabatan, bonus_lembur, total_tunjangan,
                potongan_alpa, potongan_izin, potongan_bpjs, potongan_pph21, total_potongan,
                gaji_bruto, gaji_bersih, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid')
            ON DUPLICATE KEY UPDATE 
                gaji_pokok = VALUES(gaji_pokok),
                tunjangan_kehadiran = VALUES(tunjangan_kehadiran),
                tunjangan_jabatan = VALUES(tunjangan_jabatan),
                bonus_lembur = VALUES(bonus_lembur),
                total_tunjangan = VALUES(total_tunjangan),
                potongan_alpa = VALUES(potongan_alpa),
                potongan_izin = VALUES(potongan_izin),
                potongan_bpjs = VALUES(potongan_bpjs),
                potongan_pph21 = VALUES(potongan_pph21),
                total_potongan = VALUES(total_potongan),
                gaji_bruto = VALUES(gaji_bruto),
                gaji_bersih = VALUES(gaji_bersih),
                status = 'paid'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiddddddddd", 
                $id_karyawan, $periode_bulan, $periode_tahun, $gaji_pokok,
                $tunjangan_kehadiran, $tunjangan_jabatan, $bonus_lembur, $total_tunjangan,
                $potongan_alpa, $potongan_izin, $potongan_bpjs, $potongan_pph21, $total_potongan,
                $gaji_bruto, $gaji_bersih
            );
            
            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Payroll berhasil diproses',
                    'data' => [
                        'gaji_pokok' => $gaji_pokok,
                        'total_tunjangan' => $total_tunjangan,
                        'total_potongan' => $total_potongan,
                        'gaji_bersih' => $gaji_bersih
                    ]
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'Gagal menyimpan payroll: ' . $stmt->error];
            }
            $stmt->close();
            break;

        // ==================== LAPORAN PENGGAJIAN ====================
        case 'get_penggajian':
            $filter_bulan = isset($_POST['filter_bulan']) ? intval($_POST['filter_bulan']) : date('n');
            $filter_tahun = isset($_POST['filter_tahun']) ? intval($_POST['filter_tahun']) : date('Y');
            
            $sql = "SELECT p.*, k.nik, k.nama, k.jabatan 
                    FROM penggajian p 
                    JOIN karyawan k ON p.id_karyawan = k.id 
                    WHERE p.periode_bulan = ? AND p.periode_tahun = ?
                    ORDER BY k.nama ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $filter_bulan, $filter_tahun);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'get_slip_gaji':
            $id_karyawan = intval($_POST['id_karyawan']);
            $periode_bulan = intval($_POST['periode_bulan']);
            $periode_tahun = intval($_POST['periode_tahun']);
            
            $sql = "SELECT p.*, k.nik, k.nama, k.jabatan 
                    FROM penggajian p 
                    JOIN karyawan k ON p.id_karyawan = k.id 
                    WHERE p.id_karyawan = ? AND p.periode_bulan = ? AND p.periode_tahun = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $id_karyawan, $periode_bulan, $periode_tahun);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Ambil juga data kehadiran
                $stmt2 = $conn->prepare("SELECT * FROM kehadiran WHERE id_karyawan = ? AND periode_bulan = ? AND periode_tahun = ?");
                $stmt2->bind_param("iii", $id_karyawan, $periode_bulan, $periode_tahun);
                $stmt2->execute();
                $kehadiran = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                
                $response = [
                    'status' => 'success',
                    'data' => $row,
                    'kehadiran' => $kehadiran
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'Data slip gaji tidak ditemukan'];
            }
            $stmt->close();
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Payroll - PT. Maju Bersama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
                        secondary: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95' }
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            body * { visibility: hidden; }
            #printSlip, #printSlip * { visibility: visible; }
            #printSlip { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .modal { transition: opacity 0.3s ease, visibility 0.3s ease; }
        .modal.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .modal:not(.hidden) { opacity: 1; visibility: visible; pointer-events: auto; }
        .loader { border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-blue-50 min-h-screen">
    <!-- ==================== NAVBAR ==================== -->
    <nav class="bg-gradient-to-r from-blue-800 to-blue-900 text-white shadow-lg sticky top-0 z-50 no-print">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calculator text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Sistem Payroll</h1>
                        <p class="text-blue-200 text-xs">PT. Maju Bersama</p>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <span class="text-blue-200 text-sm">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('d F Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- ==================== TABS ==================== -->
    <div class="max-w-7xl mx-auto px-4 py-6 no-print">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="flex border-b border-gray-200 overflow-x-auto">
                <button onclick="switchTab('karyawan')" id="tab-karyawan" class="tab-btn px-6 py-4 font-medium text-sm flex items-center space-x-2 border-b-2 border-blue-600 text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-users"></i>
                    <span>Karyawan</span>
                </button>
                <button onclick="switchTab('kehadiran')" id="tab-kehadiran" class="tab-btn px-6 py-4 font-medium text-sm flex items-center space-x-2 border-b-2 border-transparent text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-calendar-check"></i>
                    <span>Kehadiran</span>
                </button>
                <button onclick="switchTab('payroll')" id="tab-payroll" class="tab-btn px-6 py-4 font-medium text-sm flex items-center space-x-2 border-b-2 border-transparent text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-calculator"></i>
                    <span>Payroll</span>
                </button>
                <button onclick="switchTab('laporan')" id="tab-laporan" class="tab-btn px-6 py-4 font-medium text-sm flex items-center space-x-2 border-b-2 border-transparent text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Laporan Gaji</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== CONTENT AREA ==================== -->
    <div class="max-w-7xl mx-auto px-4 pb-8">
        
        <!-- TAB KARYAWAN -->
        <div id="content-karyawan" class="tab-content active">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">
                        <i class="fas fa-users text-blue-600 mr-2"></i>Data Karyawan
                    </h2>
                    <button onclick="openModalKaryawan()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2 shadow-md">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Karyawan</span>
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                <th class="px-4 py-3 text-left text-sm font-semibold rounded-tl-lg">NIK</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Jabatan</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Gaji Pokok</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold rounded-tr-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-karyawan" class="divide-y divide-gray-200">
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB KEHADIRAN -->
        <div id="content-kehadiran" class="tab-content">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">
                        <i class="fas fa-calendar-check text-blue-600 mr-2"></i>Input Kehadiran
                    </h2>
                    <button onclick="openModalKehadiran()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2 shadow-md">
                        <i class="fas fa-plus"></i>
                        <span>Input Kehadiran</span>
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                <th class="px-4 py-3 text-left text-sm font-semibold rounded-tl-lg">NIK</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Periode</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Hadir</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Izin</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Alpa</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold rounded-tr-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-kehadiran" class="divide-y divide-gray-200">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB PAYROLL -->
        <div id="content-payroll" class="tab-content">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">
                        <i class="fas fa-calculator text-blue-600 mr-2"></i>Kalkulasi Payroll
                    </h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-cogs text-blue-600 mr-2"></i>Parameter Periode
                        </h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                                <select id="payroll-bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>><?php echo getNamaBulan($i); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                                <select id="payroll-tahun" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button onclick="loadKaryawanPayroll()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2">
                            <i class="fas fa-search"></i>
                            <span>Tampilkan Karyawan</span>
                        </button>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-green-600 mr-2"></i>Info Kalkulasi
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Gaji Pokok sesuai jabatan</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Tunjangan Jabatan (10-30%)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Bonus Kehadiran (@Rp 50.000)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Bonus Lembur (@Rp 75.000)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-minus-circle text-red-500"></i>
                                <span>Potongan Alpa (@Rp 100.000)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-minus-circle text-red-500"></i>
                                <span>Potongan Izin (@Rp 50.000)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-minus-circle text-red-500"></i>
                                <span>BPJS (2%) & PPh 21 (5%)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-list text-blue-600 mr-2"></i>Daftar Karyawan untuk Diproses
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                    <th class="px-4 py-3 text-left text-sm font-semibold">NIK</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold">Kehadiran</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">Gaji Pokok</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold rounded-tr-lg">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tabel-payroll" class="divide-y divide-gray-200">
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Pilih periode dan klik "Tampilkan Karyawan"</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB LAPORAN -->
        <div id="content-laporan" class="tab-content">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 no-print">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">
                        <i class="fas fa-file-invoice-dollar text-blue-600 mr-2"></i>Laporan Gaji
                    </h2>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4 mb-6 no-print">
                    <div class="flex-1 grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                            <select id="filter-bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>><?php echo getNamaBulan($i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                            <select id="filter-tahun" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="loadPenggajian()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2">
                                <i class="fas fa-search"></i>
                                <span>Tampilkan</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                <th class="px-4 py-3 text-left text-sm font-semibold rounded-tl-lg">NIK</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Jabatan</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Gaji Pokok</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Total Tunjangan</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Total Potongan</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Gaji Bersih</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold rounded-tr-lg no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-laporan" class="divide-y divide-gray-200">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Pilih periode dan klik "Tampilkan"</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary -->
                <div id="summary-laporan" class="mt-6 hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Periode</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-white rounded-lg shadow-sm">
                                <p class="text-sm text-gray-500">Total Karyawan</p>
                                <p id="summary-karyawan" class="text-2xl font-bold text-blue-600">0</p>
                            </div>
                            <div class="text-center p-4 bg-white rounded-lg shadow-sm">
                                <p class="text-sm text-gray-500">Total Gaji Pokok</p>
                                <p id="summary-gaji" class="text-2xl font-bold text-green-600">Rp 0</p>
                            </div>
                            <div class="text-center p-4 bg-white rounded-lg shadow-sm">
                                <p class="text-sm text-gray-500">Total Potongan</p>
                                <p id="summary-potongan" class="text-2xl font-bold text-red-600">Rp 0</p>
                            </div>
                            <div class="text-center p-4 bg-white rounded-lg shadow-sm">
                                <p class="text-sm text-gray-500">Total Gaji Bersih</p>
                                <p id="summary-bersih" class="text-2xl font-bold text-purple-600">Rp 0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL KARYAWAN ==================== -->
    <div id="modal-karyawan" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 id="modal-karyawan-title" class="text-lg font-semibold text-white">Tambah Karyawan</h3>
                    <button onclick="closeModalKaryawan()" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form id="form-karyawan" class="p-6 space-y-4">
                <input type="hidden" id="karyawan-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                    <input type="text" id="karyawan-nik" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: NIK001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="karyawan-nama" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan nama lengkap">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                    <select id="karyawan-jabatan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Direksi">Direksi</option>
                        <option value="Manager">Manager</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Staff">Staff</option>
                        <option value="Operator">Operator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok (Rp)</label>
                    <input type="number" id="karyawan-gaji" required min="0" step="1000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan gaji pokok">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModalKaryawan()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL KEHADIRAN ==================== -->
    <div id="modal-kehadiran" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 id="modal-kehadiran-title" class="text-lg font-semibold text-white">Input Kehadiran</h3>
                    <button onclick="closeModalKehadiran()" class="text-white/80 hover:text-white"><i class="fas fa-times text-xl"></i></button>
                </div>
            </div>
            <form id="form-kehadiran" class="p-6 space-y-4">
                <input type="hidden" id="kehadiran-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                    <select id="kehadiran-karyawan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="">-- Pilih Karyawan --</option></select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select id="kehadiran-bulan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php for($i = 1; $i <= 12; $i++): ?><option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>><?php echo getNamaBulan($i); ?></option><?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select id="kehadiran-tahun" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?><option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Hadir</label><input type="number" id="kehadiran-hadir" required min="0" max="31" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="0"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Izin</label><input type="number" id="kehadiran-izin" required min="0" max="31" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="0"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Alpa</label><input type="number" id="kehadiran-alpa" required min="0" max="31" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="0"></div>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModalKehadiran()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL SLIP GAJI ==================== -->
    <div id="modal-slip" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl my-8">
            <div id="printSlip">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 no-print">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white"><i class="fas fa-receipt mr-2"></i>Slip Gaji</h3>
                        <div class="flex space-x-2">
                            <button onclick="window.print()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors"><i class="fas fa-print mr-1"></i>Cetak</button>
                            <button onclick="closeModalSlip()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <div class="p-8" id="slip-content"></div>
            </div>
        </div>
    </div>

    <!-- ==================== TOAST NOTIFICATION ==================== -->
    <div id="toast" class="fixed bottom-4 right-4 transform translate-y-full opacity-0 transition-all duration-300 z-50">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
            <i id="toast-icon" class="fas fa-check-circle text-green-400"></i>
            <span id="toast-message">Message</span>
        </div>
    </div>

    <script>
        // ==================== UTILITY FUNCTIONS ====================
        function formatRupiah(angka) { return 'Rp ' + angka.toLocaleString('id-ID'); }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');
            toastMessage.textContent = message;
            toastIcon.className = type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-circle text-red-400';
            toast.classList.remove('translate-y-full', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => { toast.classList.remove('translate-y-0', 'opacity-100'); toast.classList.add('translate-y-full', 'opacity-0'); }, 3000);
        }

        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) { formData.append(key, data[key]); }
            try { const response = await fetch('index.php', { method: 'POST', body: formData }); return await response.json(); }
            catch (error) { return { status: 'error', message: 'Terjadi kesalahan koneksi' }; }
        }

        // ==================== TAB NAVIGATION ====================
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => { el.classList.remove('border-blue-600', 'text-blue-600'); el.classList.add('border-transparent', 'text-gray-500'); });
            document.getElementById('content-' + tabName).classList.add('active');
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-600', 'text-blue-600');
        }

        // ==================== KARYAWAN CRUD ====================
        async function loadKaryawan() {
            const result = await apiCall('get_karyawan');
            const tbody = document.getElementById('tabel-karyawan');
            if (result.status === 'success' && result.data.length > 0) {
                tbody.innerHTML = result.data.map(k => `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-3">${k.nama}</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs font-semibold rounded-full ${k.jabatan === 'Direksi' ? 'bg-purple-100 text-purple-700' : k.jabatan === 'Manager' ? 'bg-blue-100 text-blue-700' : k.jabatan === 'Supervisor' ? 'bg-green-100 text-green-700' : k.jabatan === 'Staff' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'}">${k.jabatan}</span></td>
                    <td class="px-4 py-3 text-right font-semibold">${formatRupiah(parseFloat(k.gaji_pokok))}</td>
                    <td class="px-4 py-3 text-center"><button onclick="editKaryawan(${k.id})" class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button><button onclick="hapusKaryawan(${k.id})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            } else { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data karyawan</td></tr>'; }
        }

        function openModalKaryawan() { document.getElementById('modal-karyawan-title').textContent = 'Tambah Karyawan'; document.getElementById('form-karyawan').reset(); document.getElementById('karyawan-id').value = ''; document.getElementById('modal-karyawan').classList.remove('hidden'); }

        async function editKaryawan(id) {
            const result = await apiCall('get_karyawan_by_id', { id });
            if (result.status === 'success') {
                const k = result.data;
                document.getElementById('modal-karyawan-title').textContent = 'Edit Karyawan';
                document.getElementById('karyawan-id').value = k.id;
                document.getElementById('karyawan-nik').value = k.nik;
                document.getElementById('karyawan-nama').value = k.nama;
                document.getElementById('karyawan-jabatan').value = k.jabatan;
                document.getElementById('karyawan-gaji').value = k.gaji_pokok;
                document.getElementById('modal-karyawan').classList.remove('hidden');
            }
        }

        function closeModalKaryawan() { document.getElementById('modal-karyawan').classList.add('hidden'); }
        async function hapusKaryawan(id) { if (confirm('Hapus karyawan ini?')) { const result = await apiCall('hapus_karyawan', { id }); showToast(result.message, result.status); if (result.status === 'success') loadKaryawan(); } }

        document.getElementById('form-karyawan').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = { id: document.getElementById('karyawan-id').value, nik: document.getElementById('karyawan-nik').value, nama: document.getElementById('karyawan-nama').value, jabatan: document.getElementById('karyawan-jabatan').value, gaji_pokok: document.getElementById('karyawan-gaji').value };
            const result = await apiCall('simpan_karyawan', data);
            showToast(result.message, result.status);
            if (result.status === 'success') { closeModalKaryawan(); loadKaryawan(); }
        });

        // ==================== KEHADIRAN CRUD ====================
        async function loadKaryawanSelect() {
            const result = await apiCall('get_karyawan');
            const select = document.getElementById('kehadiran-karyawan');
            if (result.status === 'success') { select.innerHTML = '<option value="">-- Pilih Karyawan --</option>' + result.data.map(k => `<option value="${k.id}">${k.nik} - ${k.nama}</option>`).join(''); }
        }

        async function loadKehadiran() {
            const result = await apiCall('get_kehadiran');
            const tbody = document.getElementById('tabel-kehadiran');
            if (result.status === 'success' && result.data.length > 0) {
                tbody.innerHTML = result.data.map(k => `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-3">${k.nama}</td>
                    <td class="px-4 py-3 text-center">${k.periode_bulan} / ${k.periode_tahun}</td>
                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">${k.hadir}</span></td>
                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">${k.izin}</span></td>
                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">${k.alpa}</span></td>
                    <td class="px-4 py-3 text-center"><button onclick="hapusKehadiran(${k.id})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            } else { tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data kehadiran</td></tr>'; }
        }

        function openModalKehadiran() { document.getElementById('modal-kehadiran-title').textContent = 'Input Kehadiran'; document.getElementById('form-kehadiran').reset(); document.getElementById('kehadiran-id').value = ''; loadKaryawanSelect(); document.getElementById('modal-kehadiran').classList.remove('hidden'); }
        function closeModalKehadiran() { document.getElementById('modal-kehadiran').classList.add('hidden'); }
        async function hapusKehadiran(id) { if (confirm('Hapus data kehadiran ini?')) { const result = await apiCall('hapus_kehadiran', { id }); showToast(result.message, result.status); if (result.status === 'success') loadKehadiran(); } }

        document.getElementById('form-kehadiran').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = { id: document.getElementById('kehadiran-id').value, id_karyawan: document.getElementById('kehadiran-karyawan').value, periode_bulan: document.getElementById('kehadiran-bulan').value, periode_tahun: document.getElementById('kehadiran-tahun').value, hadir: document.getElementById('kehadiran-hadir').value, izin: document.getElementById('kehadiran-izin').value, alpa: document.getElementById('kehadiran-alpa').value };
            const result = await apiCall('simpan_kehadiran', data);
            showToast(result.message, result.status);
            if (result.status === 'success') { closeModalKehadiran(); loadKehadiran(); }
        });

        // ==================== PAYROLL ====================
        async function loadKaryawanPayroll() {
            const bulan = document.getElementById('payroll-bulan').value;
            const tahun = document.getElementById('payroll-tahun').value;
            const result = await apiCall('get_karyawan');
            const tbody = document.getElementById('tabel-payroll');
            if (result.status === 'success' && result.data.length > 0) {
                const kehadiranResult = await apiCall('get_kehadiran');
                const kehadiranMap = {};
                if (kehadiranResult.status === 'success') { kehadiranResult.data.forEach(k => { if (k.periode_bulan == bulan && k.periode_tahun == tahun) { kehadiranMap[k.id_karyawan] = k; } }); }
                tbody.innerHTML = result.data.map(k => { const kh = kehadiranMap[k.id] || { hadir: 0, izin: 0, alpa: 0 }; return `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-3">${k.nama}</td>
                    <td class="px-4 py-3 text-center"><span class="text-xs">H: <b>${kh.hadir}</b></span> <span class="text-xs ml-2">I: <b>${kh.izin}</b></span> <span class="text-xs ml-2">A: <b>${kh.alpa}</b></span></td>
                    <td class="px-4 py-3 text-right font-semibold">${formatRupiah(parseFloat(k.gaji_pokok))}</td>
                    <td class="px-4 py-3 text-center"><button onclick="prosesPayroll(${k.id})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors text-sm"><i class="fas fa-calculator mr-1"></i> Proses</button></td>
                </tr>`; }).join('');
            } else { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada karyawan</td></tr>'; }
        }

        async function prosesPayroll(idKaryawan) {
            const bulan = document.getElementById('payroll-bulan').value;
            const tahun = document.getElementById('payroll-tahun').value;
            if (!confirm('Proses payroll untuk karyawan ini?')) return;
            const result = await apiCall('proses_payroll', { id_karyawan: idKaryawan, periode_bulan: bulan, periode_tahun: tahun });
            showToast(result.message, result.status);
            if (result.status === 'success') loadPenggajian();
        }

        // ==================== LAPORAN ====================
        async function loadPenggajian() {
            const bulan = document.getElementById('filter-bulan').value;
            const tahun = document.getElementById('filter-tahun').value;
            const result = await apiCall('get_penggajian', { filter_bulan: bulan, filter_tahun: tahun });
            const tbody = document.getElementById('tabel-laporan');
            if (result.status === 'success' && result.data.length > 0) {
                let summary = { count: 0, gaji: 0, tunjangan: 0, potongan: 0, bersih: 0 };
                tbody.innerHTML = result.data.map(p => {
                    summary.count++; summary.gaji += parseFloat(p.gaji_pokok); summary.tunjangan += parseFloat(p.total_tunjangan); summary.potongan += parseFloat(p.total_potongan); summary.bersih += parseFloat(p.gaji_bersih);
                    return `<tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-blue-600">${p.nik}</td><td class="px-4 py-3">${p.nama}</td><td class="px-4 py-3">${p.jabatan}</td>
                        <td class="px-4 py-3 text-right">${formatRupiah(parseFloat(p.gaji_pokok))}</td>
                        <td class="px-4 py-3 text-right text-green-600">${formatRupiah(parseFloat(p.total_tunjangan))}</td>
                        <td class="px-4 py-3 text-right text-red-600">${formatRupiah(parseFloat(p.total_potongan))}</td>
                        <td class="px-4 py-3 text-right font-bold text-purple-600">${formatRupiah(parseFloat(p.gaji_bersih))}</td>
                        <td class="px-4 py-3 text-center no-print"><button onclick="showSlipGaji(${p.id_karyawan}, ${p.periode_bulan}, ${p.periode_tahun})" class="text-blue-600 hover:text-blue-800"><i class="fas fa-file-alt"></i></button></td>
                    </tr>`; }).join('');
                document.getElementById('summary-laporan').classList.remove('hidden');
                document.getElementById('summary-karyawan').textContent = summary.count;
                document.getElementById('summary-gaji').textContent = formatRupiah(summary.gaji);
                document.getElementById('summary-potongan').textContent = formatRupiah(summary.potongan);
                document.getElementById('summary-bersih').textContent = formatRupiah(summary.bersih);
            } else { tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data penggajian</td></tr>'; document.getElementById('summary-laporan').classList.add('hidden'); }
        }

        async function showSlipGaji(idKaryawan, bulan, tahun) {
            const result = await apiCall('get_slip_gaji', { id_karyawan: idKaryawan, periode_bulan: bulan, periode_tahun: tahun });
            if (result.status === 'success') {
                const p = result.data; const k = result.kehadiran || { hadir: 0, izin: 0, alpa: 0 };
                const bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][bulan];
                document.getElementById('slip-content').innerHTML = `<div class="text-center mb-6 border-b-2 border-blue-600 pb-4">
                    <h2 class="text-2xl font-bold text-blue-800">SLIP GAJI</h2><p class="text-gray-600">PT. Maju Bersama</p><p class="text-gray-500 text-sm">Periode: ${bulanNama} ${tahun}</p>
                </div>
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div><h4 class="font-semibold text-gray-800 mb-2 border-b pb-1">Data Karyawan</h4><table class="w-full text-sm"><tr><td class="py-1 text-gray-600">NIK</td><td>: <b>${p.nik}</b></td></tr><tr><td class="py-1 text-gray-600">Nama</td><td>: <b>${p.nama}</b></td></tr><tr><td class="py-1 text-gray-600">Jabatan</td><td>: <b>${p.jabatan}</b></td></tr></table></div>
                    <div><h4 class="font-semibold text-gray-800 mb-2 border-b pb-1">Rekap Kehadiran</h4><table class="w-full text-sm"><tr><td class="py-1 text-gray-600">Hadir</td><td>: <b class="text-green-600">${k.hadir} hari</b></td></tr><tr><td class="py-1 text-gray-600">Izin</td><td>: <b class="text-yellow-600">${k.izin} hari</b></td></tr><tr><td class="py-1 text-gray-600">Alpa</td><td>: <b class="text-red-600">${k.alpa} hari</b></td></tr></table></div>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-green-50 rounded-lg p-4"><h4 class="font-semibold text-green-800 mb-3"><i class="fas fa-plus-circle mr-1"></i>PENGHASILAN</h4><table class="w-full text-sm"><tr class="border-b border-green-200"><td class="py-2">Gaji Pokok</td><td class="text-right">${formatRupiah(parseFloat(p.gaji_pokok))}</td></tr><tr class="border-b border-green-200"><td class="py-2">Tunjangan Jabatan</td><td class="text-right">${formatRupiah(parseFloat(p.tunjangan_jabatan))}</td></tr><tr class="border-b border-green-200"><td class="py-2">Tunjangan Kehadiran</td><td class="text-right">${formatRupiah(parseFloat(p.tunjangan_kehadiran))}</td></tr><tr class="border-b border-green-200"><td class="py-2">Bonus Lembur</td><td class="text-right">${formatRupiah(parseFloat(p.bonus_lembur))}</td></tr><tr class="font-bold text-green-700"><td class="py-2">TOTAL PENGHASILAN</td><td class="text-right">${formatRupiah(parseFloat(p.gaji_bruto))}</td></tr></table></div>
                    <div class="bg-red-50 rounded-lg p-4"><h4 class="font-semibold text-red-800 mb-3"><i class="fas fa-minus-circle mr-1"></i>POTONGAN</h4><table class="w-full text-sm"><tr class="border-b border-red-200"><td class="py-2">Potongan Alpa</td><td class="text-right">${formatRupiah(parseFloat(p.potongan_alpa))}</td></tr><tr class="border-b border-red-200"><td class="py-2">Potongan Izin</td><td class="text-right">${formatRupiah(parseFloat(p.potongan_izin))}</td></tr><tr class="border-b border-red-200"><td class="py-2">BPJS (2%)</td><td class="text-right">${formatRupiah(parseFloat(p.potongan_bpjs))}</td></tr><tr class="border-b border-red-200"><td class="py-2">PPh 21 (5%)</td><td class="text-right">${formatRupiah(parseFloat(p.potongan_pph21))}</td></tr><tr class="font-bold text-red-700"><td class="py-2">TOTAL POTONGAN</td><td class="text-right">${formatRupiah(parseFloat(p.total_potongan))}</td></tr></table></div>
                </div>
                <div class="mt-6 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 text-center">
                    <p class="text-sm mb-1">GAJI BERSIH YANG DITERIMA</p><p class="text-3xl font-bold">${formatRupiah(parseFloat(p.gaji_bersih))}</p>
                </div>
                <div class="mt-6 flex justify-between text-sm text-gray-600 no-print">
                    <div class="text-center"><p class="mb-16">Penerima</p><p class="font-semibold">${p.nama}</p></div>
                    <div class="text-center"><p class="mb-16">Finance</p><p class="font-semibold">(....................)</p></div>
                    <div class="text-center"><p class="mb-16">Director</p><p class="font-semibold">(....................)</p></div>
                </div>`;
                document.getElementById('modal-slip').classList.remove('hidden');
            } else { showToast(result.message, 'error'); }
        }

        function closeModalSlip() { document.getElementById('modal-slip').classList.add('hidden'); }

        // ==================== INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', () => { loadKaryawan(); loadKehadiran(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeModalKaryawan(); closeModalKehadiran(); closeModalSlip(); } });
        document.querySelectorAll('.modal').forEach(modal => { modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); }); });
    </script>
</body>
</html>
