<?php
/**
 * =====================================================
 * APLIKASI PAYROLL SUPER LENGKAP - ONE PAGE CODING
 * Full Stack Development with Native PHP
 * Version 2.0 - Enhanced Edition
 * =====================================================
 */

// ==================== KONFIGURASI ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_payroll_v2');

// ==================== KONEKSI DATABASE ====================
class Database {
    private static $instance = null;
    private $connection;
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die(json_encode(['status' => 'error', 'message' => 'Koneksi gagal']));
        }
        $this->connection->set_charset("utf8mb4");
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function getConnection() { return $this->connection; }
}

function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }
function getNamaBulan($bulan) {
    $nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $nama[$bulan] ?? '';
}
function tanggalIndonesia($date) {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', strtotime($date)) . ' ' . $bulan[date('n', strtotime($date))] . ' ' . date('Y', strtotime($date));
}

// ==================== HANDLING AJAX ====================
$db = Database::getInstance();
$conn = $db->getConnection();
$response = ['status' => 'error', 'message' => 'Aksi tidak valid'];

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        // DEPARTEMEN
        case 'get_departemen':
            $result = $conn->query("SELECT * FROM departemen ORDER BY nama_dept ASC");
            $response = ['status' => 'success', 'data' => $result->fetch_all(MYSQLI_ASSOC)];
            break;
        case 'simpan_departemen':
            $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
            $stmt = $conn->prepare($id ? "UPDATE departemen SET nama_dept=?, kode=?, keterangan=? WHERE id=?" : "INSERT INTO departemen (nama_dept, kode, keterangan) VALUES (?, ?, ?)");
            $stmt->bind_param("sssi" . ($id ? "i" : ""), $_POST['nama'], $_POST['kode'], $_POST['keterangan'], $id);
            if (!$id) $stmt->bind_param("sss", $_POST['nama'], $_POST['kode'], $_POST['keterangan']);
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Berhasil disimpan'] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        case 'hapus_departemen':
            $stmt = $conn->prepare("DELETE FROM departemen WHERE id=?");
            $stmt->bind_param("i", intval($_POST['id']));
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Berhasil dihapus'] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        
        // KARYAWAN
        case 'get_karyawan':
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
            $dept = !empty($_POST['dept']) ? intval($_POST['dept']) : 0;
            $where = "WHERE (k.nama LIKE '%$search%' OR k.nik LIKE '%$search%')" . ($dept ? " AND k.id_departemen=$dept" : "");
            $result = $conn->query("SELECT k.*, d.nama_dept FROM karyawan k LEFT JOIN departemen d ON k.id_departemen=d.id $where ORDER BY k.id DESC");
            $response = ['status' => 'success', 'data' => $result->fetch_all(MYSQLI_ASSOC)];
            break;
        case 'get_karyawan_by_id':
            $stmt = $conn->prepare("SELECT k.*, d.nama_dept FROM karyawan k LEFT JOIN departemen d ON k.id_departemen=d.id WHERE k.id=?");
            $stmt->bind_param("i", intval($_POST['id']));
            $stmt->execute();
            $response = ['status' => 'success', 'data' => $stmt->get_result()->fetch_assoc()];
            $stmt->close();
            break;
        case 'simpan_karyawan':
            $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
            $f = ['nik'=>$_POST['nik'], 'nama'=>$_POST['nama'], 'email'=>$_POST['email'], 'no_hp'=>$_POST['no_hp'], 'alamat'=>$_POST['alamat'], 'jabatan'=>$_POST['jabatan'], 'id_departemen'=>$_POST['id_departemen'], 'status'=>$_POST['status'], 'tgl_masuk'=>$_POST['tgl_masuk'], 'gaji_pokok'=>floatval($_POST['gaji_pokok']), 'no_rekening'=>$_POST['no_rekening'], 'atas_nama'=>$_POST['atas_nama'], 'bank'=>$_POST['bank']];
            $sql = $id ? "UPDATE karyawan SET nik=?, nama=?, email=?, no_hp=?, alamat=?, jabatan=?, id_departemen=?, status=?, tgl_masuk=?, gaji_pokok=?, no_rekening=?, atas_nama=?, bank=? WHERE id=?" : "INSERT INTO karyawan (nik, nama, email, no_hp, alamat, jabatan, id_departemen, status, tgl_masuk, gaji_pokok, no_rekening, atas_nama, bank) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            if ($id) $stmt->bind_param("sssssssississi", $f['nik'], $f['nama'], $f['email'], $f['no_hp'], $f['alamat'], $f['jabatan'], $f['id_departemen'], $f['status'], $f['tgl_masuk'], $f['gaji_pokok'], $f['no_rekening'], $f['atas_nama'], $f['bank'], $id);
            else $stmt->bind_param("sssssssississ", $f['nik'], $f['nama'], $f['email'], $f['no_hp'], $f['alamat'], $f['jabatan'], $f['id_departemen'], $f['status'], $f['tgl_masuk'], $f['gaji_pokok'], $f['no_rekening'], $f['atas_nama'], $f['bank']);
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Berhasil disimpan'] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        case 'hapus_karyawan':
            $stmt = $conn->prepare("DELETE FROM karyawan WHERE id=?");
            $stmt->bind_param("i", intval($_POST['id']));
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Berhasil dihapus'] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        
        // STATISTIK
        case 'get_statistik':
            $bulan = date('n'); $tahun = date('Y');
            $stats = [];
            $r = $conn->query("SELECT COUNT(*) as t FROM karyawan WHERE status='Aktif'");
            $stats['total_karyawan'] = $r->fetch_assoc()['t'];
            $r = $conn->query("SELECT COALESCE(SUM(gaji_bersih),0) as t FROM penggajian WHERE periode_bulan=$bulan AND periode_tahun=$tahun");
            $stats['total_gaji'] = $r->fetch_assoc()['t'];
            $r = $conn->query("SELECT COALESCE(SUM(hadir),0) as t FROM kehadiran WHERE periode_bulan=$bulan AND periode_tahun=$tahun");
            $stats['total_kehadiran'] = $r->fetch_assoc()['t'];
            $r = $conn->query("SELECT COUNT(*) as t FROM karyawan WHERE MONTH(tgl_masuk)=$bulan AND YEAR(tgl_masuk)=$tahun");
            $stats['karyawan_baru'] = $r->fetch_assoc()['t'];
            $r = $conn->query("SELECT d.nama_dept, COUNT(k.id) as j FROM departemen d LEFT JOIN karyawan k ON d.id=k.id_departemen AND k.status='Aktif' GROUP BY d.id, d.nama_dept");
            $stats['per_dept'] = $r->fetch_all(MYSQLI_ASSOC);
            $r = $conn->query("SELECT jabatan, COUNT(*) as j FROM karyawan WHERE status='Aktif' GROUP BY jabatan");
            $stats['per_jabatan'] = $r->fetch_all(MYSQLI_ASSOC);
            $response = ['status' => 'success', 'data' => $stats];
            break;
        
        // KEHADIRAN
        case 'get_template_kehadiran':
            $bln = intval($_POST['periode_bulan']); $thn = intval($_POST['periode_tahun']);
            $r = $conn->query("SELECT id, nik, nama, jabatan FROM karyawan WHERE status='Aktif' ORDER BY nama");
            $karyawan = $r->fetch_all(MYSQLI_ASSOC);
            $stmt = $conn->prepare("SELECT id_karyawan, hadir, sakit, izin, alpa, lembur FROM kehadiran WHERE periode_bulan=? AND periode_tahun=?");
            $stmt->bind_param("ii", $bln, $thn);
            $stmt->execute();
            $kehadiran_map = [];
            while ($row = $stmt->get_result()->fetch_assoc()) $kehadiran_map[$row['id_karyawan']] = $row;
            $stmt->close();
            foreach ($karyawan as &$k) {
                if (isset($kehadiran_map[$k['id']])) $k = array_merge($k, $kehadiran_map[$k['id']]);
                else { $k['hadir']=0; $k['sakit']=0; $k['izin']=0; $k['alpa']=0; $k['lembur']=0; }
            }
            $response = ['status' => 'success', 'data' => $karyawan];
            break;
        case 'bulk_simpan_kehadiran':
            $data = json_decode($_POST['data'], true);
            $bln = intval($_POST['periode_bulan']); $thn = intval($_POST['periode_tahun']);
            foreach ($data as $item) {
                $stmt = $conn->prepare("INSERT INTO kehadiran (id_karyawan, periode_bulan, periode_tahun, hadir, sakit, izin, alpa, lembur, total_hari_kerja) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE hadir=?, sakit=?, izin=?, alpa=?, lembur=?, total_hari_kerja=?");
                $hadir=intval($item['hadir']); $sakit=intval($item['sakit']); $izin=intval($item['izin']); $alpa=intval($item['alpa']); $lembur=intval($item['lembur']);
                $total = $hadir + $sakit + $izin + $alpa;
                $stmt->bind_param("iiiiiiiii", intval($item['id_karyawan']), $bln, $thn, $hadir, $sakit, $izin, $alpa, $lembur, $total, $hadir, $sakit, $izin, $alpa, $lembur, $total);
                $stmt->execute(); $stmt->close();
            }
            $response = ['status' => 'success', 'message' => 'Data kehadiran disimpan'];
            break;
        
        // SETTING
        case 'get_setting_payroll':
            $r = $conn->query("SELECT * FROM setting_payroll WHERE id=1");
            $response = ['status' => 'success', 'data' => $r->fetch_assoc() ?: ['umr'=>5000000,'tunjangan_transport'=>500000,'tunjangan_makan'=>300000,'tunjangan_kesehatan'=>200000,'bonus_lembur_per_jam'=>25000,'denda_alpa_per_hari'=>100000]];
            break;
        case 'simpan_setting_payroll':
            $stmt = $conn->prepare("INSERT INTO setting_payroll (id, umr, tunjangan_transport, tunjangan_makan, tunjangan_kesehatan, bonus_lembur_per_jam, denda_alpa_per_hari, denda_terlambat_per_kali, pengganti_libur) VALUES (1,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE umr=?, tunjangan_transport=?, tunjangan_makan=?, tunjangan_kesehatan=?, bonus_lembur_per_jam=?, denda_alpa_per_hari=?, denda_terlambat_per_kali=?, pengganti_libur=?");
            $umr=floatval($_POST['umr']); $transport=floatval($_POST['tunjangan_transport']); $makan=floatval($_POST['tunjangan_makan']); $kesehatan=floatval($_POST['tunjangan_kesehatan']); $lembur=floatval($_POST['bonus_lembur_per_jam']); $alpa=floatval($_POST['denda_alpa_per_hari']); $terlambat=floatval($_POST['denda_terlambat_per_kali']); $libur=floatval($_POST['pengganti_libur']);
            $stmt->bind_param("dddddddddddddddd", $umr, $transport, $makan, $kesehatan, $lembur, $alpa, $terlambat, $libur, $umr, $transport, $makan, $kesehatan, $lembur, $alpa, $terlambat, $libur);
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Setting disimpan'] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        
        // PAYROLL
        case 'proses_payroll':
            $id_karyawan = intval($_POST['id_karyawan']);
            $bln = intval($_POST['periode_bulan']);
            $thn = intval($_POST['periode_tahun']);
            
            $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id=? AND status='Aktif'");
            $stmt->bind_param("i", $id_karyawan);
            $stmt->execute();
            $karyawan = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$karyawan) { $response = ['status' => 'error', 'message' => 'Karyawan tidak ditemukan']; break; }
            
            $stmt = $conn->prepare("SELECT * FROM kehadiran WHERE id_karyawan=? AND periode_bulan=? AND periode_tahun=?");
            $stmt->bind_param("iii", $id_karyawan, $bln, $thn);
            $stmt->execute();
            $kehadiran = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $hadir = $kehadiran['hadir'] ?? 0; $sakit = $kehadiran['sakit'] ?? 0; $izin = $kehadiran['izin'] ?? 0; $alpa = $kehadiran['alpa'] ?? 0; $lembur = $kehadiran['lembur'] ?? 0;
            $gaji_pokok = floatval($karyawan['gaji_pokok']);
            
            $r = $conn->query("SELECT * FROM setting_payroll WHERE id=1");
            $s = $r->fetch_assoc() ?: ['tunjangan_transport'=>500000,'tunjangan_makan'=>300000,'tunjangan_kesehatan'=>200000,'bonus_lembur_per_jam'=>25000,'denda_alpa_per_hari'=>100000];
            
            $tunjangan_jabatan = match($karyawan['jabatan']) {
                'Direksi'=>$gaji_pokok*0.35,'General Manager'=>$gaji_pokok*0.30,'Manager'=>$gaji_pokok*0.25,'Assistant Manager'=>$gaji_pokok*0.20,'Supervisor'=>$gaji_pokok*0.15,'Senior Staff'=>$gaji_pokok*0.10,'Staff'=>$gaji_pokok*0.08,'Junior Staff'=>$gaji_pokok*0.05, default=>0
            };
            $tunjangan_transport = floatval($s['tunjangan_transport']);
            $tunjangan_makan = floatval($s['tunjangan_makan']) * $hadir;
            $tunjangan_kesehatan = floatval($s['tunjangan_kesehatan']);
            $bonus_kehadiran = ($hadir >= 24) ? $gaji_pokok * 0.05 : 0;
            $bonus_lembur = $lembur * floatval($s['bonus_lembur_per_jam']) * 8;
            
            $tgl_masuk = new DateTime($karyawan['tgl_masuk']);
            $tgl_sekarang = new DateTime("$thn-$bln-01");
            $masa_kerja = $tgl_masuk->diff($tgl_sekarang)->m + ($tgl_masuk->diff($tgl_sekarang)->y * 12);
            $tunjangan_masa_kerja = ($masa_kerja >= 12) ? min($gaji_pokok * 0.02 * floor($masa_kerja / 12), $gaji_pokok * 0.10) : 0;
            
            $total_penghasilan = $gaji_pokok + $tunjangan_jabatan + $tunjangan_transport + $tunjangan_makan + $tunjangan_kesehatan + $bonus_kehadiran + $bonus_lembur + $tunjangan_masa_kerja;
            
            $potongan_alpa = $alpa * floatval($s['denda_alpa_per_hari']);
            $potongan_izin = ($izin > 2) ? (($izin - 2) * floatval($s['denda_alpa_per_hari']) * 0.5) : 0;
            $potongan_bpjs_kesehatan = $gaji_pokok * 0.01;
            $potongan_bpjs_ketenagakerjaan = $gaji_pokok * 0.02;
            $pkp_tahunan = ($gaji_pokok + $tunjangan_jabatan) * 12 - 54000000;
            $potongan_pph21 = ($pkp_tahunan > 0) ? ((($pkp_tahunan <= 60000000) ? $pkp_tahunan * 0.05 : (($pkp_tahunan <= 250000000) ? 3000000 + ($pkp_tahunan - 60000000) * 0.15 : (($pkp_tahunan <= 500000000) ? 31500000 + ($pkp_tahunan - 250000000) * 0.25 : 94750000 + ($pkp_tahunan - 500000000) * 0.30)))) / 12 : 0;
            $potongan_lainnya = $gaji_pokok * 0.01;
            $total_potongan = $potongan_alpa + $potongan_izin + $potongan_bpjs_kesehatan + $potongan_bpjs_ketenagakerjaan + $potongan_pph21 + $potongan_lainnya;
            $gaji_bersih = $total_penghasilan - $total_potongan;
            
            $stmt = $conn->prepare("INSERT INTO penggajian (id_karyawan, periode_bulan, periode_tahun, gaji_pokok, hadir, sakit, izin, alpa, lembur, tunjangan_jabatan, tunjangan_transport, tunjangan_makan, tunjangan_kesehatan, tunjangan_masa_kerja, bonus_kehadiran, bonus_lembur, total_penghasilan, potongan_alpa, potongan_izin, potongan_bpjs_kesehatan, potongan_bpjs_ketenagakerjaan, potongan_pph21, potongan_lainnya, total_potongan, gaji_bersih, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'paid',NOW()) ON DUPLICATE KEY UPDATE gaji_pokok=?, hadir=?, sakit=?, izin=?, alpa=?, lembur=?, tunjangan_jabatan=?, tunjangan_transport=?, tunjangan_makan=?, tunjangan_kesehatan=?, tunjangan_masa_kerja=?, bonus_kehadiran=?, bonus_lembur=?, total_penghasilan=?, potongan_alpa=?, potongan_izin=?, potongan_bpjs_kesehatan=?, potongan_bpjs_ketenagakerjaan=?, potongan_pph21=?, potongan_lainnya=?, total_potongan=?, gaji_bersih=?, status='paid', updated_at=NOW()");
            $stmt->bind_param("iiiiiiiiiiiiiiiiddddddddddddiiiiiiiiiiiidd", $id_karyawan, $bln, $thn, $gaji_pokok, $hadir, $sakit, $izin, $alpa, $lembur, $tunjangan_jabatan, $tunjangan_transport, $tunjangan_makan, $tunjangan_kesehatan, $tunjangan_masa_kerja, $bonus_kehadiran, $bonus_lembur, $total_penghasilan, $potongan_alpa, $potongan_izin, $potongan_bpjs_kesehatan, $potongan_bpjs_ketenagakerjaan, $potongan_pph21, $potongan_lainnya, $total_potongan, $gaji_bersih, $gaji_pokok, $hadir, $sakit, $izin, $alpa, $lembur, $tunjangan_jabatan, $tunjangan_transport, $tunjangan_makan, $tunjangan_kesehatan, $tunjangan_masa_kerja, $bonus_kehadiran, $bonus_lembur, $total_penghasilan, $potongan_alpa, $potongan_izin, $potongan_bpjs_kesehatan, $potongan_bpjs_ketenagakerjaan, $potongan_pph21, $potongan_lainnya, $total_potongan, $gaji_bersih);
            $response = $stmt->execute() ? ['status' => 'success', 'message' => 'Payroll berhasil diproses', 'data' => ['gaji_bersih'=>$gaji_bersih]] : ['status' => 'error', 'message' => 'Gagal'];
            $stmt->close();
            break;
        
        case 'proses_semua_payroll':
            $bln = intval($_POST['periode_bulan']); $thn = intval($_POST['periode_tahun']);
            $r = $conn->query("SELECT id FROM karyawan WHERE status='Aktif'");
            $success = 0;
            while ($k = $r->fetch_assoc()) {
                $_POST['id_karyawan'] = $k['id'];
                $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id=? AND status='Aktif'");
                $stmt->bind_param("i", $k['id']);
                $stmt->execute();
                $karyawan = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$karyawan) continue;
                $stmt = $conn->prepare("SELECT * FROM kehadiran WHERE id_karyawan=? AND periode_bulan=? AND periode_tahun=?");
                $stmt->bind_param("iii", $k['id'], $bln, $thn);
                $stmt->execute();
                $kehadiran = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $hadir = $kehadiran['hadir'] ?? 0; $sakit = $kehadiran['sakit'] ?? 0; $izin = $kehadiran['izin'] ?? 0; $alpa = $kehadiran['alpa'] ?? 0; $lembur = $kehadiran['lembur'] ?? 0;
                $gaji_pokok = floatval($karyawan['gaji_pokok']);
                $rr = $conn->query("SELECT * FROM setting_payroll WHERE id=1");
                $s = $rr->fetch_assoc() ?: ['tunjangan_transport'=>500000,'tunjangan_makan'=>300000,'tunjangan_kesehatan'=>200000,'bonus_lembur_per_jam'=>25000,'denda_alpa_per_hari'=>100000];
                $tj = match($karyawan['jabatan']) {'Direksi'=>$gaji_pokok*0.35,'General Manager'=>$gaji_pokok*0.30,'Manager'=>$gaji_pokok*0.25,'Assistant Manager'=>$gaji_pokok*0.20,'Supervisor'=>$gaji_pokok*0.15,'Senior Staff'=>$gaji_pokok*0.10,'Staff'=>$gaji_pokok*0.08,'Junior Staff'=>$gaji_pokok*0.05, default=>0};
                $tt = floatval($s['tunjangan_transport']); $tm = floatval($s['tunjangan_makan']) * $hadir; $tk = floatval($s['tunjangan_kesehatan']); $bl = ($hadir >= 24) ? $gaji_pokok * 0.05 : 0; $bonus_lembur = $lembur * floatval($s['bonus_lembur_per_jam']) * 8;
                $tgl_masuk = new DateTime($karyawan['tgl_masuk']); $tgl_sekarang = new DateTime("$thn-$bln-01"); $masa_kerja = $tgl_masuk->diff($tgl_sekarang)->m + ($tgl_masuk->diff($tgl_sekarang)->y * 12); $tmk = ($masa_kerja >= 12) ? min($gaji_pokok * 0.02 * floor($masa_kerja / 12), $gaji_pokok * 0.10) : 0;
                $tp = $gaji_pokok + $tj + $tt + $tm + $tk + $bl + $bonus_lembur + $tmk;
                $pa = $alpa * floatval($s['denda_alpa_per_hari']); $pi = ($izin > 2) ? (($izin - 2) * floatval($s['denda_alpa_per_hari']) * 0.5) : 0; $pbk = $gaji_pokok * 0.01; $pbt = $gaji_pokok * 0.02; $pp21 = ((($gaji_pokok + $tj) * 12 - 54000000) * 0.05) / 12; $pl = $gaji_pokok * 0.01; $tp2 = $pa + $pi + $pbk + $pbt + $pp21 + $pl; $gb = $tp - $tp2;
                $stmt = $conn->prepare("INSERT INTO penggajian (id_karyawan, periode_bulan, periode_tahun, gaji_pokok, hadir, sakit, izin, alpa, lembur, tunjangan_jabatan, tunjangan_transport, tunjangan_makan, tunjangan_kesehatan, tunjangan_masa_kerja, bonus_kehadiran, bonus_lembur, total_penghasilan, potongan_alpa, potongan_izin, potongan_bpjs_kesehatan, potongan_bpjs_ketenagakerjaan, potongan_pph21, potongan_lainnya, total_potongan, gaji_bersih, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'paid',NOW()) ON DUPLICATE KEY UPDATE gaji_pokok=?, hadir=?, sakit=?, izin=?, alpa=?, lembur=?, tunjangan_jabatan=?, tunjangan_transport=?, tunjangan_makan=?, tunjangan_kesehatan=?, tunjangan_masa_kerja=?, bonus_kehadiran=?, bonus_lembur=?, total_penghasilan=?, potongan_alpa=?, potongan_izin=?, potongan_bpjs_kesehatan=?, potongan_bpjs_ketenagakerjaan=?, potongan_pph21=?, potongan_lainnya=?, total_potongan=?, gaji_bersih=?, status='paid'");
                $stmt->bind_param("iiiiiiiiiiiiiiiiddddddddddddiiiiiiiiiiiidd", $k['id'], $bln, $thn, $gaji_pokok, $hadir, $sakit, $izin, $alpa, $lembur, $tj, $tt, $tm, $tk, $tmk, $bl, $bonus_lembur, $tp, $pa, $pi, $pbk, $pbt, $pp21, $pl, $tp2, $gb, $gaji_pokok, $hadir, $sakit, $izin, $alpa, $lembur, $tj, $tt, $tm, $tk, $tmk, $bl, $bonus_lembur, $tp, $pa, $pi, $pbk, $pbt, $pp21, $pl, $tp2, $gb);
                if ($stmt->execute()) $success++;
                $stmt->close();
            }
            $response = ['status' => 'success', 'message' => "Berhasil memproses $success karyawan"];
            break;
        
        // LAPORAN
        case 'get_penggajian':
            $bln = intval($_POST['filter_bulan']); $thn = intval($_POST['filter_tahun']);
            $dept = !empty($_POST['dept']) ? " AND k.id_departemen=" . intval($_POST['dept']) : "";
            $r = $conn->query("SELECT p.*, k.nik, k.nama, k.jabatan, k.no_rekening, k.atas_nama, k.bank, d.nama_dept FROM penggajian p JOIN karyawan k ON p.id_karyawan=k.id LEFT JOIN departemen d ON k.id_departemen=d.id WHERE p.periode_bulan=$bln AND p.periode_tahun=$thn$dept ORDER BY k.nama");
            $response = ['status' => 'success', 'data' => $r->fetch_all(MYSQLI_ASSOC)];
            break;
        case 'get_slip_gaji':
            $stmt = $conn->prepare("SELECT p.*, k.nik, k.nama, k.jabatan, k.alamat, k.no_rekening, k.atas_nama, k.bank, d.nama_dept FROM penggajian p JOIN karyawan k ON p.id_karyawan=k.id LEFT JOIN departemen d ON k.id_departemen=d.id WHERE p.id_karyawan=? AND p.periode_bulan=? AND p.periode_tahun=?");
            $stmt->bind_param("iii", intval($_POST['id_karyawan']), intval($_POST['periode_bulan']), intval($_POST['periode_tahun']));
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($p) {
                $stmt = $conn->prepare("SELECT * FROM kehadiran WHERE id_karyawan=? AND periode_bulan=? AND periode_tahun=?");
                $stmt->bind_param("iii", intval($_POST['id_karyawan']), intval($_POST['periode_bulan']), intval($_POST['periode_tahun']));
                $stmt->execute();
                $k = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $response = ['status' => 'success', 'data' => $p, 'kehadiran' => $k];
            } else { $response = ['status' => 'error', 'message' => 'Data tidak ditemukan']; }
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
    <title>Payroll System V2 - PT. Maju Bersama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'}}}}}</script>
    <style>
        @media print{body *{visibility:hidden}#printSlip,#printSlip *{visibility:visible}#printSlip{position:absolute;left:0;top:0;width:100%}.no-print{display:none!important}}}
        .tab-content{display:none}.tab-content.active{display:block}
        .modal{transition:opacity .3s,visibility .3s}.modal.hidden{opacity:0;visibility:hidden;pointer-events:none}.modal:not(.hidden){opacity:1;visibility:visible;pointer-events:auto}
        .sidebar-item:hover{background:rgba(255,255,255,0.1)}.sidebar-item.active{background:rgba(255,255,255,0.2);border-left:4px solid #fff}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(0,0,0,0.1)}.table-row:hover{background:#f8fafc}
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-gradient-to-b from-blue-900 to-blue-950 text-white min-h-screen fixed left-0 top-0 overflow-y-auto no-print z-50">
            <div class="p-6 border-b border-blue-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center"><i class="fas fa-calculator text-xl"></i></div>
                    <div><h1 class="text-lg font-bold">Payroll V2</h1><p class="text-blue-300 text-xs">PT. Maju Bersama</p></div>
                </div>
            </div>
            <nav class="p-4 space-y-1">
                <button onclick="switchTab('dashboard')" id="tab-dashboard" class="sidebar-item active w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-home w-6"></i><span>Dashboard</span></button>
                <button onclick="switchTab('departemen')" id="tab-departemen" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-building w-6"></i><span>Departemen</span></button>
                <button onclick="switchTab('karyawan')" id="tab-karyawan" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-users w-6"></i><span>Karyawan</span></button>
                <button onclick="switchTab('kehadiran')" id="tab-kehadiran" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-calendar-check w-6"></i><span>Kehadiran</span></button>
                <button onclick="switchTab('setting')" id="tab-setting" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-sliders-h w-6"></i><span>Pengaturan</span></button>
                <button onclick="switchTab('payroll')" id="tab-payroll" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-calculator w-6"></i><span>Proses Gaji</span></button>
                <button onclick="switchTab('laporan')" id="tab-laporan" class="sidebar-item w-full text-left px-4 py-3 rounded-lg flex items-center space-x-3"><i class="fas fa-chart-bar w-6"></i><span>Laporan</span></button>
            </nav>
            <div class="p-4 border-t border-blue-800 mt-auto"><p class="text-xs text-blue-300 text-center">v2.0 Super Lengkap © 2024</p></div>
        </aside>

        <!-- MAIN -->
        <main class="flex-1 ml-64">
            <header class="bg-white shadow-sm sticky top-0 z-40 no-print">
                <div class="flex items-center justify-between px-6 py-4">
                    <div><h2 id="page-title" class="text-2xl font-bold text-gray-800">Dashboard</h2><p class="text-gray-500 text-sm"><?php echo tanggalIndonesia(date('Y-m-d')); ?></p></div>
                    <div class="flex items-center space-x-4">
                        <div class="relative"><input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 border rounded-lg w-64"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">A</div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- DASHBOARD -->
                <div id="content-dashboard" class="tab-content active">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="stat-card bg-white rounded-xl p-6 shadow-sm cursor-pointer transition-all"><div class="flex items-center justify-between"><div><p class="text-gray-500 text-sm">Total Karyawan</p><p id="stat-karyawan" class="text-3xl font-bold text-blue-600 mt-1">0</p><p class="text-xs text-green-600 mt-1"><i class="fas fa-arrow-up"></i> Aktif</p></div><div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-users text-2xl text-blue-600"></i></div></div></div>
                        <div class="stat-card bg-white rounded-xl p-6 shadow-sm cursor-pointer transition-all"><div class="flex items-center justify-between"><div><p class="text-gray-500 text-sm">Total Gaji Bulan Ini</p><p id="stat-gaji" class="text-2xl font-bold text-green-600 mt-1">Rp 0</p></div><div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill text-2xl text-green-600"></i></div></div></div>
                        <div class="stat-card bg-white rounded-xl p-6 shadow-sm cursor-pointer transition-all"><div class="flex items-center justify-between"><div><p class="text-gray-500 text-sm">Total Kehadiran</p><p id="stat-kehadiran" class="text-3xl font-bold text-purple-600 mt-1">0</p></div><div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-calendar-check text-2xl text-purple-600"></i></div></div></div>
                        <div class="stat-card bg-white rounded-xl p-6 shadow-sm cursor-pointer transition-all"><div class="flex items-center justify-between"><div><p class="text-gray-500 text-sm">Karyawan Baru</p><p id="stat-baru" class="text-3xl font-bold text-orange-600 mt-1">0</p></div><div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center"><i class="fas fa-user-plus text-2xl text-orange-600"></i></div></div></div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white rounded-xl p-6 shadow-sm"><h3 class="text-lg font-semibold mb-4">Karyawan per Departemen</h3><div class="h-64"><canvas id="chart-dept"></canvas></div></div>
                        <div class="bg-white rounded-xl p-6 shadow-sm"><h3 class="text-lg font-semibold mb-4">Distribusi Jabatan</h3><div class="h-64"><canvas id="chart-jabatan"></canvas></div></div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Aksi Cepat</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <button onclick="switchTab('karyawan');openModalKaryawan();" class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-4 rounded-lg transition-colors flex flex-col items-center"><i class="fas fa-user-plus text-2xl mb-2"></i><span class="text-sm font-medium">Tambah Karyawan</span></button>
                            <button onclick="switchTab('kehadiran');" class="bg-green-50 hover:bg-green-100 text-green-700 p-4 rounded-lg transition-colors flex flex-col items-center"><i class="fas fa-calendar-plus text-2xl mb-2"></i><span class="text-sm font-medium">Input Kehadiran</span></button>
                            <button onclick="switchTab('payroll');" class="bg-purple-50 hover:bg-purple-100 text-purple-700 p-4 rounded-lg transition-colors flex flex-col items-center"><i class="fas fa-calculator text-2xl mb-2"></i><span class="text-sm font-medium">Proses Gaji</span></button>
                            <button onclick="switchTab('laporan');" class="bg-orange-50 hover:bg-orange-100 text-orange-700 p-4 rounded-lg transition-colors flex flex-col items-center"><i class="fas fa-file-alt text-2xl mb-2"></i><span class="text-sm font-medium">Lihat Laporan</span></button>
                        </div>
                    </div>
                </div>

                <!-- DEPARTEMEN -->
                <div id="content-departemen" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold"><i class="fas fa-building text-blue-600 mr-2"></i>Departemen</h2><button onclick="openModalDept()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-plus mr-2"></i>Tambah</button></div>
                        <table class="w-full"><thead><tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white"><th class="px-4 py-3 text-left rounded-tl-lg">Kode</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Keterangan</th><th class="px-4 py-3 text-center rounded-tr-lg">Aksi</th></tr></thead><tbody id="tabel-dept" class="divide-y"><tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Memuat...</td></tr></tbody></table>
                    </div>
                </div>

                <!-- KARYAWAN -->
                <div id="content-karyawan" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex flex-wrap justify-between items-center gap-4 mb-6"><h2 class="text-xl font-bold"><i class="fas fa-users text-blue-600 mr-2"></i>Karyawan</h2>
                            <div class="flex flex-wrap gap-3">
                                <select id="filter-dept" onchange="loadKaryawan()" class="px-3 py-2 border rounded-lg"><option value="">Semua Dept</option></select>
                                <input type="text" id="search-karyawan" onkeyup="loadKaryawan()" placeholder="Cari..." class="px-3 py-2 border rounded-lg">
                                <button onclick="openModalKaryawan()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-plus mr-2"></i>Tambah</button>
                            </div>
                        </div>
                        <table class="w-full"><thead><tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white"><th class="px-4 py-3 text-left rounded-tl-lg">NIK</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Dept</th><th class="px-4 py-3 text-left">Jabatan</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Gaji Pokok</th><th class="px-4 py-3 text-center rounded-tr-lg">Aksi</th></tr></thead><tbody id="tabel-karyawan" class="divide-y"><tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Memuat...</td></tr></tbody></table>
                    </div>
                </div>

                <!-- KEHADIRAN -->
                <div id="content-kehadiran" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex flex-wrap justify-between items-center gap-4 mb-6"><h2 class="text-xl font-bold"><i class="fas fa-calendar-check text-blue-600 mr-2"></i>Input Kehadiran</h2>
                            <div class="flex gap-3">
                                <select id="hadir-bulan" onchange="loadTemplateHadir()" class="px-3 py-2 border rounded-lg"><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>"<?php echo $i==date('n')?' selected':'';?>><?php echo getNamaBulan($i);?></option><?php endfor; ?></select>
                                <select id="hadir-tahun" onchange="loadTemplateHadir()" class="px-3 py-2 border rounded-lg"><?php for($y=date('Y')-2;$y<=date('Y');$y++): ?><option value="<?php echo $y;?>"<?php echo $y==date('Y')?' selected':'';?>><?php echo $y;?></option><?php endfor; ?></select>
                                <button onclick="simpanBulkHadir()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button>
                            </div>
                        </div>
                        <table class="w-full"><thead><tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white"><th class="px-4 py-3 text-left rounded-tl-lg">NIK</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Jabatan</th><th class="px-4 py-3 text-center">Hadir</th><th class="px-4 py-3 text-center">Sakit</th><th class="px-4 py-3 text-center">Izin</th><th class="px-4 py-3 text-center">Alpa</th><th class="px-4 py-3 text-center rounded-tr-lg">Lembur</th></tr></thead><tbody id="tabel-hadir"><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Pilih periode</td></tr></tbody></table>
                    </div>
                </div>

                <!-- SETTING -->
                <div id="content-setting" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold mb-6"><i class="fas fa-sliders-h text-blue-600 mr-2"></i>Pengaturan Payroll</h2>
                        <form id="form-setting" class="grid md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-medium mb-1">UMR/UMK</label><input type="number" id="s-umr" class="w-full px-3 py-2 border rounded-lg" placeholder="5000000"></div>
                            <div><label class="block text-sm font-medium mb-1">Tunjangan Transport</label><input type="number" id="s-transport" class="w-full px-3 py-2 border rounded-lg" placeholder="500000"></div>
                            <div><label class="block text-sm font-medium mb-1">Tunjangan Makan/Hari</label><input type="number" id="s-makan" class="w-full px-3 py-2 border rounded-lg" placeholder="300000"></div>
                            <div><label class="block text-sm font-medium mb-1">Tunjangan Kesehatan</label><input type="number" id="s-kesehatan" class="w-full px-3 py-2 border rounded-lg" placeholder="200000"></div>
                            <div><label class="block text-sm font-medium mb-1">Bonus Lembur/Jam</label><input type="number" id="s-lembur" class="w-full px-3 py-2 border rounded-lg" placeholder="25000"></div>
                            <div><label class="block text-sm font-medium mb-1">Denda Alpa/Hari</label><input type="number" id="s-alpa" class="w-full px-3 py-2 border rounded-lg" placeholder="100000"></div>
                            <div><label class="block text-sm font-medium mb-1">Denda Terlambat/Kali</label><input type="number" id="s-terlambat" class="w-full px-3 py-2 border rounded-lg" placeholder="25000"></div>
                            <div><label class="block text-sm font-medium mb-1">Lembur Libur/Hari</label><input type="number" id="s-libur" class="w-full px-3 py-2 border rounded-lg" placeholder="150000"></div>
                            <div class="md:col-span-2"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button></div>
                        </form>
                    </div>
                </div>

                <!-- PAYROLL -->
                <div id="content-payroll" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex flex-wrap justify-between items-center gap-4 mb-6"><h2 class="text-xl font-bold"><i class="fas fa-calculator text-blue-600 mr-2"></i>Proses Gaji</h2>
                            <div class="flex gap-3">
                                <select id="pay-bulan" class="px-3 py-2 border rounded-lg"><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>"<?php echo $i==date('n')?' selected':'';?>><?php echo getNamaBulan($i);?></option><?php endfor; ?></select>
                                <select id="pay-tahun" class="px-3 py-2 border rounded-lg"><?php for($y=date('Y')-2;$y<=date('Y');$y++): ?><option value="<?php echo $y;?>"<?php echo $y==date('Y')?' selected':'';?>><?php echo $y;?></option><?php endfor; ?></select>
                                <button onclick="prosesSemua()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-play mr-2"></i>Proses Semua</button>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-blue-50 rounded-xl p-6 border border-blue-200"><h3 class="font-bold text-blue-800 mb-4"><i class="fas fa-info-circle mr-2"></i>Komponen Tunjangan</h3><div class="space-y-2 text-sm"><p><i class="fas fa-check text-green-500 mr-2"></i>Gaji Pokok + Tunjangan Jabatan (5-35%)</p><p><i class="fas fa-check text-green-500 mr-2"></i>Tunjangan Transport & Makan</p><p><i class="fas fa-check text-green-500 mr-2"></i>Tunjangan Kesehatan & Masa Kerja</p><p><i class="fas fa-check text-green-500 mr-2"></i>Bonus Hadir ≥24 hari (5%)</p></div></div>
                            <div class="bg-red-50 rounded-xl p-6 border border-red-200"><h3 class="font-bold text-red-800 mb-4"><i class="fas fa-minus-circle mr-2"></i>Komponen Potongan</h3><div class="space-y-2 text-sm"><p><i class="fas fa-minus text-red-500 mr-2"></i>BPJS Kesehatan (1%)</p><p><i class="fas fa-minus text-red-500 mr-2"></i>BPJS Ketenagakerjaan (2%)</p><p><i class="fas fa-minus text-red-500 mr-2"></i>PPh 21 Progressive</p><p><i class="fas fa-minus text-red-500 mr-2"></i>Potongan Alpa & Izin</p></div></div>
                        </div>
                        <table class="w-full"><thead><tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white"><th class="px-4 py-3 text-left rounded-tl-lg">NIK</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-right">Gaji Pokok</th><th class="px-4 py-3 text-right rounded-tr-lg">Aksi</th></tr></thead><tbody id="tabel-payroll"><tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Klik Prosesi</td></tr></tbody></table>
                    </div>
                </div>

                <!-- LAPORAN -->
                <div id="content-laporan" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h2 class="text-xl font-bold mb-4"><i class="fas fa-chart-bar text-blue-600 mr-2"></i>Filter</h2>
                        <div class="grid md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Bulan</label><select id="lap-bulan" class="w-full px-3 py-2 border rounded-lg"><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>"<?php echo $i==date('n')?' selected':'';?>><?php echo getNamaBulan($i);?></option><?php endfor; ?></select></div>
                            <div><label class="block text-sm font-medium mb-1">Tahun</label><select id="lap-tahun" class="w-full px-3 py-2 border rounded-lg"><?php for($y=date('Y')-2;$y<=date('Y');$y++): ?><option value="<?php echo $y;?>"<?php echo $y==date('Y')?' selected':'';?>><?php echo $y;?></option><?php endfor; ?></select></div>
                            <div><label class="block text-sm font-medium mb-1">Departemen</label><select id="lap-dept" class="w-full px-3 py-2 border rounded-lg"><option value="">Semua</option></select></div>
                            <div class="flex items-end"><button onclick="loadLaporan()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-search mr-2"></i>Tampilkan</button></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <div class="overflow-x-auto"><table class="w-full"><thead><tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white"><th class="px-4 py-3 text-left rounded-tl-lg">NIK</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Jabatan</th><th class="px-4 py-3 text-right">Gaji Pokok</th><th class="px-4 py-3 text-right">Total Tunjangan</th><th class="px-4 py-3 text-right">Total Potongan</th><th class="px-4 py-3 text-right">Gaji Bersih</th><th class="px-4 py-3 text-center rounded-tr-lg no-print">Aksi</th></tr></thead><tbody id="tabel-laporan"><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Pilih filter</td></tr></tbody></table></div>
                    </div>
                    <div id="summary-lap" class="hidden bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl p-6 text-white">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="text-center"><p class="text-sm opacity-80">Karyawan</p><p id="sm-karyawan" class="text-2xl font-bold">0</p></div>
                            <div class="text-center"><p class="text-sm opacity-80">Gaji Pokok</p><p id="sm-gaji" class="text-2xl font-bold">Rp 0</p></div>
                            <div class="text-center"><p class="text-sm opacity-80">Total Tunjangan</p><p id="sm-tunjangan" class="text-2xl font-bold">Rp 0</p></div>
                            <div class="text-center"><p class="text-sm opacity-80">Total Potongan</p><p id="sm-potongan" class="text-2xl font-bold">Rp 0</p></div>
                            <div class="text-center"><p class="text-sm opacity-80">Gaji Bersih Total</p><p id="sm-bersih" class="text-2xl font-bold">Rp 0</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODALS -->
    <div id="modal-dept" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl"><h3 id="m-dept-title" class="text-lg font-semibold text-white">Tambah Departemen</h3></div>
            <form id="form-dept" class="p-6 space-y-4">
                <input type="hidden" id="dept-id"><div><label class="block text-sm font-medium mb-1">Kode</label><input type="text" id="dept-kode" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium mb-1">Nama</label><input type="text" id="dept-nama" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium mb-1">Keterangan</label><textarea id="dept-ket" class="w-full px-3 py-2 border rounded-lg" rows="2"></textarea></div>
                <div class="flex gap-3 pt-4"><button type="button" onclick="closeModal('dept')" class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button><button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button></div>
            </form>
        </div>
    </div>

    <div id="modal-karyawan" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl my-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl"><h3 id="m-karyawan-title" class="text-lg font-semibold text-white">Tambah Karyawan</h3></div>
            <form id="form-karyawan" class="p-6 space-y-4">
                <input type="hidden" id="k-id">
                <div class="grid md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium mb-1">NIK *</label><input type="text" id="k-nik" required class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Nama *</label><input type="text" id="k-nama" required class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" id="k-email" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">No. HP</label><input type="text" id="k-hp" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Departemen</label><select id="k-dept" class="w-full px-3 py-2 border rounded-lg"><option value="">-- Pilih --</option></select></div>
                    <div><label class="block text-sm font-medium mb-1">Jabatan *</label><select id="k-jabatan" required class="w-full px-3 py-2 border rounded-lg"><option value="">-- Pilih --</option><option>Direksi</option><option>General Manager</option><option>Manager</option><option>Assistant Manager</option><option>Supervisor</option><option>Senior Staff</option><option>Staff</option><option>Junior Staff</option><option>Internship</option></select></div>
                    <div><label class="block text-sm font-medium mb-1">Status</label><select id="k-status" class="w-full px-3 py-2 border rounded-lg"><option value="Aktif">Aktif</option><option value="Non-Aktif">Non-Aktif</option><option value="Resign">Resign</option></select></div>
                    <div><label class="block text-sm font-medium mb-1">Tgl Masuk *</label><input type="date" id="k-tgl" required class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Gaji Pokok *</label><input type="number" id="k-gaji" required min="0" class="w-full px-3 py-2 border rounded-lg"></div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Alamat</label><textarea id="k-alamat" class="w-full px-3 py-2 border rounded-lg" rows="2"></textarea></div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium mb-1">No. Rekening</label><input type="text" id="k-rek" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Atas Nama</label><input type="text" id="k-namarek" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium mb-1">Bank</label><input type="text" id="k-bank" class="w-full px-3 py-2 border rounded-lg"></div>
                </div>
                <div class="flex gap-3 pt-4"><button type="button" onclick="closeModal('karyawan')" class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button><button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button></div>
            </form>
        </div>
    </div>

    <div id="modal-slip" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl my-8">
            <div id="printSlip">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 rounded-t-xl no-print flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-white"><i class="fas fa-receipt mr-2"></i>Slip Gaji</h3>
                    <div class="flex gap-2"><button onclick="window.print()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg"><i class="fas fa-print mr-1"></i>Cetak</button><button onclick="closeModal('slip')" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg"><i class="fas fa-times"></i></button></div>
                </div>
                <div class="p-8" id="slip-content"></div>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="toast" class="fixed bottom-4 right-4 transform translate-y-full opacity-0 transition-all duration-300 z-50">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3"><i id="toast-icon" class="fas fa-check-circle text-green-400"></i><span id="toast-message">Message</span></div>
    </div>

    <script>
        const fmtRp = (n) => 'Rp ' + n.toLocaleString('id-ID');
        const api = async (a, d = {}) => { const fd = new FormData(); fd.append('action', a); for (const k in d) fd.append(k, d[k]); try { const r = await fetch('index.php', { method: 'POST', body: fd }); return await r.json(); } catch { return { status: 'error', message: 'Koneksi gagal' }; } };
        
        const toast = (m, t = 'success') => { document.getElementById('toast-message').textContent = m; document.getElementById('toast-icon').className = t === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-circle text-red-400'; const x = document.getElementById('toast'); x.classList.remove('translate-y-full', 'opacity-0'); x.classList.add('translate-y-0', 'opacity-100'); setTimeout(() => x.classList.remove('translate-y-0', 'opacity-100'), 3000); };
        
        const switchTab = (tab) => {
            document.querySelectorAll('.tab-content').forEach(e => e.classList.remove('active'));
            document.querySelectorAll('.sidebar-item').forEach(e => e.classList.remove('active'));
            document.getElementById('content-' + tab).classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
            const titles = { dashboard: 'Dashboard', departemen: 'Departemen', karyawan: 'Karyawan', kehadiran: 'Kehadiran', setting: 'Pengaturan', payroll: 'Proses Gaji', laporan: 'Laporan Gaji' };
            document.getElementById('page-title').textContent = titles[tab];
            if (tab === 'dashboard') loadDashboard();
            else if (tab === 'departemen') loadDept();
            else if (tab === 'karyawan') { loadDeptSelect(); loadKaryawan(); }
            else if (tab === 'kehadiran') loadTemplateHadir();
            else if (tab === 'setting') loadSetting();
            else if (tab === 'payroll') loadPayroll();
            else if (tab === 'laporan') { loadDeptSelect(); loadLaporan(); }
        };
        
        const closeModal = (n) => document.getElementById('modal-' + n).classList.add('hidden');
        
        // Dashboard
        let chartDept, chartJabatan;
        async function loadDashboard() {
            const r = await api('get_statistik');
            if (r.status === 'success') {
                const d = r.data;
                document.getElementById('stat-karyawan').textContent = d.total_karyawan;
                document.getElementById('stat-gaji').textContent = fmtRp(d.total_gaji);
                document.getElementById('stat-kehadiran').textContent = d.total_kehadiran;
                document.getElementById('stat-baru').textContent = d.karyawan_baru;
                if (chartDept) chartDept.destroy();
                chartDept = new Chart(document.getElementById('chart-dept'), { type: 'bar', data: { labels: d.per_dept.map(x => x.nama_dept), datasets: [{ label: 'Jumlah', data: d.per_dept.map(x => x.j), backgroundColor: '#3b82f6' }] }, options: { responsive: true, maintainAspectRatio: false } });
                if (chartJabatan) chartJabatan.destroy();
                chartJabatan = new Chart(document.getElementById('chart-jabatan'), { type: 'doughnut', data: { labels: d.per_jabatan.map(x => x.jabatan), datasets: [{ data: d.per_jabatan.map(x => x.j), backgroundColor: ['#8b5cf6','#3b82f6','#10b981','#f59e0b','#ef4444','#ec4899','#06b6d4','#84cc16','#f97316'] }] }, options: { responsive: true, maintainAspectRatio: false } });
            }
        }
        
        // Departemen
        async function loadDept() {
            const r = await api('get_departemen');
            const tbody = document.getElementById('tabel-dept');
            if (r.status === 'success' && r.data.length) tbody.innerHTML = r.data.map(d => `<tr class="table-row"><td class="px-4 py-3 font-medium">${d.kode}</td><td class="px-4 py-3">${d.nama_dept}</td><td class="px-4 py-3 text-gray-500">${d.keterangan || '-'}</td><td class="px-4 py-3 text-center"><button onclick="editDept(${d.id})" class="text-blue-600 mr-3"><i class="fas fa-edit"></i></button><button onclick="hapusDept(${d.id})" class="text-red-600"><i class="fas fa-trash"></i></button></td></tr>`).join('');
            else tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada</td></tr>';
        }
        function openModalDept() { document.getElementById('form-dept').reset(); document.getElementById('dept-id').value = ''; document.getElementById('m-dept-title').textContent = 'Tambah'; closeModal('dept'); document.getElementById('modal-dept').classList.remove('hidden'); }
        async function editDept(id) { const r = await api('get_departemen'); const d = r.data.find(x => x.id == id); document.getElementById('dept-id').value = d.id; document.getElementById('dept-kode').value = d.kode; document.getElementById('dept-nama').value = d.nama_dept; document.getElementById('dept-ket').value = d.keterangan; document.getElementById('m-dept-title').textContent = 'Edit'; document.getElementById('modal-dept').classList.remove('hidden'); }
        async function hapusDept(id) { if (confirm('Hapus?')) { const r = await api('hapus_departemen', { id }); toast(r.message, r.status); if (r.status === 'success') loadDept(); } }
        document.getElementById('form-dept').addEventListener('submit', async (e) => { e.preventDefault(); const r = await api('simpan_departemen', { id: document.getElementById('dept-id').value, kode: document.getElementById('dept-kode').value, nama: document.getElementById('dept-nama').value, keterangan: document.getElementById('dept-ket').value }); toast(r.message, r.status); if (r.status === 'success') { closeModal('dept'); loadDept(); } });
        
        // Karyawan
        async function loadDeptSelect() {
            const r = await api('get_departemen');
            const opts = r.data.map(d => `<option value="${d.id}">${d.nama_dept}</option>`).join('');
            document.getElementById('filter-dept').innerHTML = '<option value="">Semua Dept</option>' + opts;
            document.getElementById('k-dept').innerHTML = '<option value="">-- Pilih --</option>' + opts;
            document.getElementById('lap-dept').innerHTML = '<option value="">Semua</option>' + opts;
        }
        async function loadKaryawan() {
            const r = await api('get_karyawan', { search: document.getElementById('search-karyawan').value, dept: document.getElementById('filter-dept').value });
            const tbody = document.getElementById('tabel-karyawan');
            if (r.status === 'success' && r.data.length) tbody.innerHTML = r.data.map(k => `<tr class="table-row"><td class="px-4 py-3 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-3">${k.nama}</td><td class="px-4 py-3 text-gray-500">${k.nama_dept || '-'}</td><td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full ${k.jabatan.includes('Manager') ? 'bg-blue-100 text-blue-700' : k.jabatan.includes('Supervisor') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}">${k.jabatan}</span></td><td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full ${k.status === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${k.status}</span></td><td class="px-4 py-3 text-right font-medium">${fmtRp(parseFloat(k.gaji_pokok))}</td><td class="px-4 py-3 text-center"><button onclick="editKaryawan(${k.id})" class="text-blue-600 mr-3"><i class="fas fa-edit"></i></button><button onclick="hapusKaryawan(${k.id})" class="text-red-600"><i class="fas fa-trash"></i></button></td></tr>`).join('');
            else tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada</td></tr>';
        }
        function openModalKaryawan() { document.getElementById('form-karyawan').reset(); document.getElementById('k-id').value = ''; document.getElementById('k-tgl').value = new Date().toISOString().split('T')[0]; document.getElementById('m-karyawan-title').textContent = 'Tambah'; document.getElementById('modal-karyawan').classList.remove('hidden'); loadDeptSelect(); }
        async function editKaryawan(id) { const r = await api('get_karyawan_by_id', { id }); const k = r.data; document.getElementById('k-id').value = k.id; document.getElementById('k-nik').value = k.nik; document.getElementById('k-nama').value = k.nama; document.getElementById('k-email').value = k.email || ''; document.getElementById('k-hp').value = k.no_hp || ''; document.getElementById('k-dept').value = k.id_departemen || ''; document.getElementById('k-jabatan').value = k.jabatan; document.getElementById('k-status').value = k.status; document.getElementById('k-tgl').value = k.tgl_masuk; document.getElementById('k-gaji').value = k.gaji_pokok; document.getElementById('k-alamat').value = k.alamat || ''; document.getElementById('k-rek').value = k.no_rekening || ''; document.getElementById('k-namarek').value = k.atas_nama || ''; document.getElementById('k-bank').value = k.bank || ''; document.getElementById('m-karyawan-title').textContent = 'Edit'; document.getElementById('modal-karyawan').classList.remove('hidden'); }
        async function hapusKaryawan(id) { if (confirm('Hapus?')) { const r = await api('hapus_karyawan', { id }); toast(r.message, r.status); if (r.status === 'success') loadKaryawan(); } }
        document.getElementById('form-karyawan').addEventListener('submit', async (e) => { e.preventDefault(); const d = { id: document.getElementById('k-id').value, nik: document.getElementById('k-nik').value, nama: document.getElementById('k-nama').value, email: document.getElementById('k-email').value, no_hp: document.getElementById('k-hp').value, id_departemen: document.getElementById('k-dept').value, jabatan: document.getElementById('k-jabatan').value, status: document.getElementById('k-status').value, tgl_masuk: document.getElementById('k-tgl').value, gaji_pokok: document.getElementById('k-gaji').value, alamat: document.getElementById('k-alamat').value, no_rekening: document.getElementById('k-rek').value, atas_nama: document.getElementById('k-namarek').value, bank: document.getElementById('k-bank').value }; const r = await api('simpan_karyawan', d); toast(r.message, r.status); if (r.status === 'success') { closeModal('karyawan'); loadKaryawan(); } });
        
        // Kehadiran
        async function loadTemplateHadir() {
            const r = await api('get_template_kehadiran', { periode_bulan: document.getElementById('hadir-bulan').value, periode_tahun: document.getElementById('hadir-tahun').value });
            const tbody = document.getElementById('tabel-hadir');
            if (r.status === 'success' && r.data.length) tbody.innerHTML = r.data.map((k, i) => `<tr class="table-row"><td class="px-4 py-2 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-2">${k.nama}</td><td class="px-4 py-2 text-gray-500">${k.jabatan}</td><td class="px-4 py-2"><input type="number" id="h-${i}" value="${k.hadir}" min="0" max="31" class="w-16 px-2 py-1 border rounded text-center"></td><td class="px-4 py-2"><input type="number" id="s-${i}" value="${k.sakit}" min="0" max="31" class="w-16 px-2 py-1 border rounded text-center"></td><td class="px-4 py-2"><input type="number" id="iz-${i}" value="${k.izin}" min="0" max="31" class="w-16 px-2 py-1 border rounded text-center"></td><td class="px-4 py-2"><input type="number" id="a-${i}" value="${k.alpa}" min="0" max="31" class="w-16 px-2 py-1 border rounded text-center"></td><td class="px-4 py-2"><input type="number" id="l-${i}" value="${k.lembur}" min="0" max="100" class="w-16 px-2 py-1 border rounded text-center"></td><input type="hidden" id="kid-${i}" value="${k.id}"></tr>`).join('');
            else tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada karyawan aktif</td></tr>';
        }
        async function simpanBulkHadir() {
            const rows = document.querySelectorAll('#tabel-hadir tr.table-row');
            const data = [];
            rows.forEach((_, i) => data.push({ id_karyawan: document.getElementById('kid-' + i).value, hadir: parseInt(document.getElementById('h-' + i).value) || 0, sakit: parseInt(document.getElementById('s-' + i).value) || 0, izin: parseInt(document.getElementById('iz-' + i).value) || 0, alpa: parseInt(document.getElementById('a-' + i).value) || 0, lembur: parseInt(document.getElementById('l-' + i).value) || 0 }));
            const r = await api('bulk_simpan_kehadiran', { data: JSON.stringify(data), periode_bulan: document.getElementById('hadir-bulan').value, periode_tahun: document.getElementById('hadir-tahun').value });
            toast(r.message, r.status);
        }
        
        // Setting
        async function loadSetting() { const r = await api('get_setting_payroll'); if (r.status === 'success' && r.data) { document.getElementById('s-umr').value = r.data.umr || 5000000; document.getElementById('s-transport').value = r.data.tunjangan_transport || 500000; document.getElementById('s-makan').value = r.data.tunjangan_makan || 300000; document.getElementById('s-kesehatan').value = r.data.tunjangan_kesehatan || 200000; document.getElementById('s-lembur').value = r.data.bonus_lembur_per_jam || 25000; document.getElementById('s-alpa').value = r.data.denda_alpa_per_hari || 100000; document.getElementById('s-terlambat').value = r.data.denda_terlambat_per_kali || 25000; document.getElementById('s-libur').value = r.data.pengganti_libur || 150000; } }
        document.getElementById('form-setting').addEventListener('submit', async (e) => { e.preventDefault(); const r = await api('simpan_setting_payroll', { umr: document.getElementById('s-umr').value, tunjangan_transport: document.getElementById('s-transport').value, tunjangan_makan: document.getElementById('s-makan').value, tunjangan_kesehatan: document.getElementById('s-kesehatan').value, bonus_lembur_per_jam: document.getElementById('s-lembur').value, denda_alpa_per_hari: document.getElementById('s-alpa').value, denda_terlambat_per_kali: document.getElementById('s-terlambat').value, pengganti_libur: document.getElementById('s-libur').value }); toast(r.message, r.status); });
        
        // Payroll
        async function loadPayroll() {
            const r = await api('get_karyawan');
            const tbody = document.getElementById('tabel-payroll');
            if (r.status === 'success' && r.data.length) tbody.innerHTML = r.data.map(k => `<tr class="table-row"><td class="px-4 py-3 font-medium text-blue-600">${k.nik}</td><td class="px-4 py-3">${k.nama}</td><td class="px-4 py-3 text-right">${fmtRp(parseFloat(k.gaji_pokok))}</td><td class="px-4 py-3 text-center"><button onclick="prosesPayroll(${k.id})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-play mr-1"></i>Proses</button></td></tr>`).join('');
            else tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada</td></tr>';
        }
        async function prosesPayroll(id) { if (!confirm('Proses?')) return; const r = await api('proses_payroll', { id_karyawan: id, periode_bulan: document.getElementById('pay-bulan').value, periode_tahun: document.getElementById('pay-tahun').value }); toast(r.message, r.status); if (r.status === 'success') loadPayroll(); }
        async function prosesSemua() { if (!confirm('Proses semua?')) return; const r = await api('proses_semua_payroll', { periode_bulan: document.getElementById('pay-bulan').value, periode_tahun: document.getElementById('pay-tahun').value }); toast(r.message, r.status); if (r.status === 'success') loadPayroll(); }
        
        // Laporan
        async function loadLaporan() {
            const r = await api('get_penggajian', { filter_bulan: document.getElementById('lap-bulan').value, filter_tahun: document.getElementById('lap-tahun').value, dept: document.getElementById('lap-dept').value });
            const tbody = document.getElementById('tabel-laporan');
            let sm = { c: 0, g: 0, t: 0, p: 0, b: 0 };
            if (r.status === 'success' && r.data.length) {
                tbody.innerHTML = r.data.map(p => { sm.c++; sm.g += parseFloat(p.gaji_pokok); sm.t += parseFloat(p.total_penghasilan) - parseFloat(p.gaji_pokok); sm.p += parseFloat(p.total_potongan); sm.b += parseFloat(p.gaji_bersih); return `<tr class="table-row"><td class="px-4 py-3 font-medium text-blue-600">${p.nik}</td><td class="px-4 py-3">${p.nama}</td><td class="px-4 py-3">${p.jabatan}</td><td class="px-4 py-3 text-right">${fmtRp(parseFloat(p.gaji_pokok))}</td><td class="px-4 py-3 text-right text-green-600">${fmtRp(parseFloat(p.total_penghasilan) - parseFloat(p.gaji_pokok))}</td><td class="px-4 py-3 text-right text-red-600">${fmtRp(parseFloat(p.total_potongan))}</td><td class="px-4 py-3 text-right font-bold text-purple-600">${fmtRp(parseFloat(p.gaji_bersih))}</td><td class="px-4 py-3 text-center no-print"><button onclick="showSlip(${p.id_karyawan}, ${p.periode_bulan}, ${p.periode_tahun})" class="text-blue-600"><i class="fas fa-file-alt"></i></button></td></tr>`; }).join('');
                document.getElementById('summary-lap').classList.remove('hidden');
                document.getElementById('sm-karyawan').textContent = sm.c; document.getElementById('sm-gaji').textContent = fmtRp(sm.g); document.getElementById('sm-tunjangan').textContent = fmtRp(sm.t); document.getElementById('sm-potongan').textContent = fmtRp(sm.p); document.getElementById('sm-bersih').textContent = fmtRp(sm.b);
            } else { tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada</td></tr>'; document.getElementById('summary-lap').classList.add('hidden'); }
        }
        
        async function showSlip(id, bln, thn) {
            const r = await api('get_slip_gaji', { id_karyawan: id, periode_bulan: bln, periode_tahun: thn });
            if (r.status === 'success') {
                const p = r.data, k = r.kehadiran || { hadir: 0, sakit: 0, izin: 0, alpa: 0, lembur: 0 };
                const bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][bln];
                document.getElementById('slip-content').innerHTML = `<div class="text-center mb-6 border-b-2 border-blue-600 pb-4"><h2 class="text-2xl font-bold text-blue-800">SLIP GAJI</h2><p class="text-gray-600 font-semibold">PT. Maju Bersama</p><p class="text-gray-500">Periode: ${bulanNama} ${thn}</p></div>
                <div class="grid md:grid-cols-2 gap-6 mb-6"><div class="bg-gray-50 p-4 rounded-lg"><h4 class="font-semibold mb-2">Karyawan</h4><table class="w-full text-sm"><tr><td>NIK</td><td>: <b>${p.nik}</b></td></tr><tr><td>Nama</td><td>: <b>${p.nama}</b></td></tr><tr><td>Jabatan</td><td>: <b>${p.jabatan}</b></td></tr><tr><td>Dept</td><td>: <b>${p.nama_dept || '-'}</b></td></tr><tr><td>Rekening</td><td>: ${p.no_rekening || '-'}</td></tr></table></div><div class="bg-gray-50 p-4 rounded-lg"><h4 class="font-semibold mb-2">Kehadiran</h4><table class="w-full text-sm"><tr><td>Hadir</td><td class="text-green-600">: <b>${k.hadir} hari</b></td></tr><tr><td>Sakit</td><td>: ${k.sakit} hari</td></tr><tr><td>Izin</td><td>: ${k.izin} hari</td></tr><tr><td>Alpa</td><td class="text-red-600">: ${k.alpa} hari</td></tr><tr><td>Lembur</td><td>: ${k.lembur} jam</td></tr></table></div></div>
                <div class="grid md:grid-cols-2 gap-6"><div class="bg-green-50 p-4 rounded-lg border border-green-200"><h4 class="font-bold text-green-800 mb-3"><i class="fas fa-plus-circle mr-2"></i>PENGHASILAN</h4><table class="w-full text-sm"><tr class="border-b"><td>Gaji Pokok</td><td class="text-right">${fmtRp(parseFloat(p.gaji_pokok))}</td></tr><tr class="border-b"><td>Tunjangan Jabatan</td><td class="text-right">${fmtRp(parseFloat(p.tunjangan_jabatan))}</td></tr><tr class="border-b"><td>Tunjangan Transport</td><td class="text-right">${fmtRp(parseFloat(p.tunjangan_transport))}</td></tr><tr class="border-b"><td>Tunjangan Makan</td><td class="text-right">${fmtRp(parseFloat(p.tunjangan_makan))}</td></tr><tr class="border-b"><td>Tunjangan Kesehatan</td><td class="text-right">${fmtRp(parseFloat(p.tunjangan_kesehatan))}</td></tr><tr class="border-b"><td>Tunjangan Masa Kerja</td><td class="text-right">${fmtRp(parseFloat(p.tunjangan_masa_kerja))}</td></tr><tr class="border-b"><td>Bonus</td><td class="text-right">${fmtRp(parseFloat(p.bonus_kehadiran) + parseFloat(p.bonus_lembur))}</td></tr><tr class="font-bold text-green-800"><td>TOTAL</td><td class="text-right">${fmtRp(parseFloat(p.total_penghasilan))}</td></tr></table></div>
                <div class="bg-red-50 p-4 rounded-lg border border-red-200"><h4 class="font-bold text-red-800 mb-3"><i class="fas fa-minus-circle mr-2"></i>POTONGAN</h4><table class="w-full text-sm"><tr class="border-b"><td>Alpa</td><td class="text-right">${fmtRp(parseFloat(p.potongan_alpa))}</td></tr><tr class="border-b"><td>Izin</td><td class="text-right">${fmtRp(parseFloat(p.potongan_izin))}</td></tr><tr class="border-b"><td>BPJS Kesehatan</td><td class="text-right">${fmtRp(parseFloat(p.potongan_bpjs_kesehatan))}</td></tr><tr class="border-b"><td>BPJS Ketenagakerjaan</td><td class="text-right">${fmtRp(parseFloat(p.potongan_bpjs_ketenagakerjaan))}</td></tr><tr class="border-b"><td>PPh 21</td><td class="text-right">${fmtRp(parseFloat(p.potongan_pph21))}</td></tr><tr class="border-b"><td>Lainnya</td><td class="text-right">${fmtRp(parseFloat(p.potongan_lainnya))}</td></tr><tr class="font-bold text-red-800"><td>TOTAL</td><td class="text-right">${fmtRp(parseFloat(p.total_potongan))}</td></tr></table></div></div>
                <div class="mt-6 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 text-center"><p class="text-sm mb-1">GAJI BERSIH</p><p class="text-3xl font-bold">${fmtRp(parseFloat(p.gaji_bersih))}</p></div>
                <div class="mt-6 flex justify-between text-sm text-gray-600 no-print"><div class="text-center"><p class="mb-12">Penerima</p><p class="font-semibold">${p.nama}</p></div><div class="text-center"><p class="mb-12">Finance</p><p class="font-semibold">(............)</p></div><div class="text-center"><p class="mb-12">Director</p><p class="font-semibold">(............)</p></div></div>`;
                document.getElementById('modal-slip').classList.remove('hidden');
            } else toast(r.message, 'error');
        }
        
        // Init
        document.addEventListener('DOMContentLoaded', () => { loadDashboard(); loadDeptSelect(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal('dept'); closeModal('karyawan'); closeModal('slip'); });
        document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); }));
    </script>
</body>
</html>
