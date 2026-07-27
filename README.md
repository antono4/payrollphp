# 💰 Payroll System V2 - Super Lengkap

![Version](https://img.shields.io/badge/version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/license-MIT-green)

Sistem Penggajian Karyawan lengkap dengan arsitektur **One Page Coding** - semua dalam satu file PHP! 

---

## ✨ Fitur Utama

### 🏠 Dashboard
- Statistik real-time (total karyawan, gaji bulan ini, kehadiran)
- Grafik interaktif (Chart.js)
- Aksi cepat untuk navigasi

### 🏢 Manajemen Departemen
- CRUD lengkap (Tambah, Edit, Hapus)
- Kode departemen & keterangan

### 👥 Manajemen Karyawan
- Data lengkap: NIK, Nama, Email, HP, Alamat
- Department & Jabatan
- Status karyawan (Aktif/Non-Aktif/Resign/PHK)
- Info bank (No. Rekening, Atas Nama, Bank)
- Tanggal masuk & Gaji Pokok
- Filter & pencarian

### 📅 Input Kehadiran
- Input bulk untuk semua karyawan
- Tracking: Hadir, Sakit, Izin, Alpa, Lembur
- Filter periode (Bulan/Tahun)

### ⚙️ Pengaturan Payroll
- UMR/UMK Daerah
- Tunjangan Transport
- Tunjangan Makan (per hari)
- Tunjangan Kesehatan
- Bonus Lembur (per jam)
- Denda Alpa & Terlambat

### 💵 Proses Gaji
- Proses individual atau bulk
- Otomatis hitung semua komponen

### 📊 Laporan Gaji
- Filter berdasarkan departemen & periode
- Ringkasan total per periode
- Export ke Excel (coming soon)

### 🧾 Slip Gaji
- Detail lengkap semua komponen
- Print-ready dengan `window.print()`

---

## 💰 Kalkulasi Gaji

### Komponen Penghasilan
| Komponen | Perhitungan |
|----------|------------|
| Gaji Pokok | Sesuai jabatan |
| Tunjangan Jabatan | 5-35% dari gaji pokok |
| Tunjangan Transport | Per bulan |
| Tunjangan Makan | Per hari hadir |
| Tunjangan Kesehatan | Per bulan |
| Tunjangan Masa Kerja | Maks 10% (per 12 bulan) |
| Bonus Hadir | 5% jika hadir ≥24 hari |
| Bonus Lembur | Per jam × rate |

### Komponen Potongan
| Komponen | Perhitungan |
|----------|------------|
| Potongan Alpa | Per hari × rate |
| Potongan Izin | Per hari × rate (diatas 2 hari) |
| BPJS Kesehatan | 1% dari gaji pokok |
| BPJS Ketenagakerjaan | 2% dari gaji pokok |
| PPh 21 | Progressive (5%/15%/25%/30%) |
| Simpanan Wajib | 1% dari gaji pokok |

---

## 📋 Requirement

- **PHP** 7.4 atau lebih tinggi
- **MySQL** 5.7 atau lebih tinggi
- **Web Server** (Apache/Nginx) atau PHP Built-in Server
- Ekstensi PHP: `mysqli`, `mbstring`

---

## 🚀 Instalasi

### Metode 1: Auto Installer (Recommended)

1. Download/Clone repository ini
2. Extract ke folder web server (htdocs/www)
3. Buka browser: `http://localhost/payroll-app/installer.php`
4. Ikuti wizard instalasi
5. Selesai! Buka `index.php`

```bash
# Clone repository
git clone https://github.com/antono4/payrollphp.git

# Atau download ZIP dan extract
```

### Metode 2: Manual

1. Buat database MySQL:
```sql
CREATE DATABASE db_payroll_v2;
```

2. Import `database.sql`:
```bash
mysql -u root -p db_payroll_v2 < database.sql
```

3. Edit konfigurasi di `index.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'db_payroll_v2');
```

4. Jalankan aplikasi:
```bash
php -S localhost:8000
# Atau letakkan di folder web server
```

---

## 📁 Struktur File

```
payroll-app/
├── index.php         # Aplikasi utama (One Page Coding)
├── installer.php     # Wizard instalasi otomatis
├── database.sql      # SQL setup (backup)
├── README.md         # Dokumentasi
└── .gitignore       # Git ignore
```

---

## 🎨 Screenshots

### Dashboard
```
┌─────────────────────────────────────────────────────────────┐
│ 🏠 Dashboard                                                │
├─────────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐    │
│ │Karyawan  │ │Total Gaji │ │Kehadiran │ │Karyawan  │    │
│ │   7      │ │ Rp150jt   │ │   150    │ │  Baru 1  │    │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘    │
│                                                             │
│ ┌─────────────────────┐ ┌─────────────────────┐             │
│ │ Grafik Dept        │ │ Grafik Jabatan     │             │
│ │ ████████████      │ │      (○)           │             │
│ └─────────────────────┘ └─────────────────────┘             │
└─────────────────────────────────────────────────────────────┘
```

### Slip Gaji
```
┌─────────────────────────────────────────────────────────────┐
│                    SLIP GAJI                               │
│              PT. Maju Bersama                               │
│           Periode: Juli 2024                               │
├────────────────────────┬──────────────────────────────────┤
│ DATA KARYAWAN           │ KEHADIRAN                         │
│ NIK: NIK001            │ Hadir: 24 hari                   │
│ Nama: Ahmad Wijaya     │ Sakit: 0                         │
│ Jabatan: Manager       │ Izin: 1                          │
│ Dept: HRD              │ Alpa: 0                          │
├────────────────────────┴──────────────────────────────────┤
│ PENGHASILAN                           POTONGAN               │
│ Gaji Pokok        Rp 15.000.000    Alpa         Rp 0      │
│ Tunj. Jabatan     Rp  3.750.000    Izin         Rp 50rb  │
│ Tunj. Transport   Rp    500.000    BPJS Kesehatan Rp 150rb  │
│ Tunj. Makan       Rp  7.200.000    BPJS TK      Rp 300rb  │
│ Tunj. Kesehatan   Rp    200.000    PPh 21       Rp 937rb  │
│ Bonus Hadir       Rp    750.000    Lainnya       Rp 150rb  │
│ ─────────────────────────────     ─────────────────────    │
│ TOTAL           Rp 27.400.000    TOTAL        Rp 1.587rb │
├─────────────────────────────────────────────────────────────┤
│                 GAJI BERSIH: Rp 25.812.963                 │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Konfigurasi

### Mengubah Setting Payroll
1. Login ke aplikasi
2. Menu **Pengaturan**
3. Ubah nilai sesuai kebutuhan
4. Klik **Simpan**

### Menambah Jabatan
Edit di `index.php` pada bagian combobox jabatan:
```html
<select id="k-jabatan">
    <option value="Direksi">Direksi</option>
    <option value="General Manager">General Manager</option>
    <!-- Tambahkan di sini -->
</select>
```

---

## 📝 API Reference (AJAX Actions)

| Action | Deskripsi |
|--------|-----------|
| `get_departemen` | Ambil semua departemen |
| `get_karyawan` | Ambil karyawan (dengan filter) |
| `get_statistik` | Ambil statistik dashboard |
| `get_template_kehadiran` | Ambil template input kehadiran |
| `bulk_simpan_kehadiran` | Simpan semua kehadiran |
| `proses_payroll` | Proses gaji satu karyawan |
| `proses_semua_payroll` | Proses semua karyawan |
| `get_penggajian` | Ambil data penggajian |
| `get_slip_gaji` | Ambil detail slip gaji |

---

## 🔒 Keamanan

- Prepared statements untuk semua query SQL
- Input sanitization
- CSRF protection (via token)
- XSS prevention (htmlspecialchars)
- SQL Injection prevention

**⚠️ Jangan lupa:**
- Hapus `installer.php` setelah instalasi selesai
- Ganti password database secara berkala
- Aktifkan HTTPS di production

---

## 🛠️ Troubleshooting

### Error: "Koneksi gagal"
- Pastikan MySQL server berjalan
- Periksa username & password
- Pastikan port MySQL benar (default: 3306)

### Error: "Table doesn't exist"
- Jalankan installer.php lagi
- Atau import database.sql manual

### Error: "Permission denied"
- Pastikan folder writable untuk web server
- chmod 755 untuk folder, 644 untuk file

---

## 📜 License

MIT License - Bebas digunakan untuk personal maupun commercial.

---

## 🤝 Contributing

Kontribusi sangat diterima! Silakan:
1. Fork repository
2. Buat feature branch
3. Commit changes
4. Push ke branch
5. Buat Pull Request

---

## 📞 Support

- **Issue:** https://github.com/antono4/payrollphp/issues
- **Email:** support@company.com

---

<div align="center">
  <p>Made with ❤️ for Indonesian Businesses</p>
  <p>© 2024 Payroll System V2</p>
</div>
