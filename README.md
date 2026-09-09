# 📸 PHOTOBOOTH PRO

> **Sistem Photobooth Hybrid Desktop + Web Modern untuk Pernikahan, Event Sekolah, Ulang Tahun, dan Gathering Perusahaan.**

Aplikasi sistem booth foto komprehensif yang dibangun menggunakan **Laravel 12**, **Vue 3**, **Inertia.js**, **Tailwind CSS**, dan arsitektur abstraksi perangkat keras (**Hardware Abstraction Layer**) untuk kamera DSLR/Mirrorless/Webcam serta printer Dye-Sub/Thermal.

---

## 🚀 Fitur Utama

- 📷 **Multi-Brand Camera Support:** Canon EOS (EDSDK/gPhoto2), Sony Alpha (Camera Remote SDK), Nikon (Type00xx SDK), Webcam (V4L2/DirectShow/HTML5 MediaDevices), serta Mock Studio Camera.
- 🖨️ **Pro Dye-Sub & Thermal Printing:** DNP (DS620, RX1HS, DS-RX1, QW410), Citizen (CY-02, CX-02, OP900II), Epson (L8050, L18050, WF Series), thermal ESC/POS, dan Mock Printer.
- 🎨 **WYSIWYG Template Builder:** Visual editor drag-and-drop untuk layout Photo Strip (2x6"), Grid 4-Kotak (4x6"), dan Single Portrait (4x6" / 5x7") dengan live preview, overlay PNG transparansi, dan teks dinamis (tanggal, nama event).
- 🖼️ **300 DPI High-Resolution Composite Renderer:** Pengomposisian foto multi-slot berstandar cetak lab foto menggunakan Intervention Image v3.
- 🔊 **Web Audio Synthesizer:** Generator audio interaktif berbasis Web Audio API tanpa file eksternal (Beep countdown, shutter camera FX, suara "SMILE!", dan fanfare selebrasi).
- 📱 **Remote Operator Tablet (`/controller`):** Kontrol nirkabel dari tablet atau smartphone operator di jaringan lokal (trigger foto, retake, pause, print ulang).
- 📲 **Instant QR Code & Guest Download (`/download/{code}`):** Tamu dapat langsung memindai QR code di layar akhir untuk mengunduh foto digital resolusi tinggi mereka.
- 💳 **Kasir & Promosi Terpadu:** Dukungan pembayaran Tunai, QRIS, Kartu Debit/Kredit, serta sistem kupon potongan harga otomatis.
- 🔒 **Kiosk Mode Proteksi PIN:** Layar sentuh fullscreen interaktif dengan proteksi PIN untuk mengakses menu konfigurasi admin (`1234` / `0000`).
- 📊 **Laporan & Analytics:** Metrik sesi lengkap, tingkat keberhasilan cetak, estimasi sisa kertas, dan ekspor data ke file CSV.

---

## 🛠️ Tech Stack

| Lapisan | Teknologi |
| :--- | :--- |
| **Backend Framework** | [Laravel 12](https://laravel.com) (PHP 8.2+) |
| **Frontend UI** | [Vue 3](https://vuejs.org) + [TypeScript](https://www.typescriptlang.org) |
| **Monolith Bridge** | [Inertia.js v2](https://inertiajs.com) |
| **CSS Framework** | [Tailwind CSS v3](https://tailwindcss.com) + Poppins Typography |
| **State Management** | [Pinia](https://pinia.vuejs.org) |
| **Icons & Visuals** | [Lucide Vue Next](https://lucide.dev) + Canvas Confetti |
| **Image Engine** | [Intervention Image v3](https://image.intervention.io) (GD Driver) |
| **QR Code Engine** | [BaconQrCode](https://github.com/Bacon/BaconQrCode) |
| **Desktop Bridge** | [Tauri v2](https://v2.tauri.app) (Rust) & Windows Chromium Kiosk Launcher |
| **Database** | MySQL 8.x (`photobooth_pro`) |

---

## 📂 Struktur Direktori

```text
D:\PROGRAMER\WEB\photo\
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/           # Dashboard, Event, Template, Devices, Gallery, Promos, Reports, Settings
│   │   ├── Api/             # Session API, Device API, Promo API
│   │   ├── Auth/            # Auth Controller (Login, Logout)
│   │   ├── Kiosk/           # Kiosk Controller (Index, TemplateSelect, Camera, Success)
│   │   ├── ControllerPageController.php  # Remote Tablet Operator Controller
│   │   └── DownloadController.php        # Halaman Download Foto Tamu
│   ├── Models/              # BoothSession, Event, Template, Device, Camera, Printer, Payment, Promo, dll.
│   └── Services/
│       ├── Composer/        # PhotoComposer (300 DPI Rendering Engine)
│       ├── Hardware/        # HAL (CameraManager, PrinterManager, DeviceManager, AudioManager)
│       │   ├── Adapters/    # Canon, Sony, Nikon, Webcam, Mock, DNP, Epson, Citizen, Thermal
│       │   └── Contracts/   # CameraInterface, PrinterInterface, AudioInterface
│       ├── Payment/         # PaymentService (Cash, QRIS, Card)
│       ├── Promo/           # PromoService (Kupon diskon & validasi)
│       ├── Session/         # SessionManager (Lifecycle sesi photobooth)
│       └── Storage/         # StorageManager (Manajemen berkas foto terstruktur)
├── database/
│   ├── migrations/          # 12 Migrasi database lengkap
│   └── seeders/             # DatabaseSeeder (Akun demo, event default, 3 template, hardware demo)
├── resources/
│   ├── css/app.css          # Tailwind CSS + custom glassmorphism & kiosk theme
│   └── js/
│       ├── Components/      # CameraPreview, CountdownOverlay, DeviceStatusBadge, Modals, dll.
│       ├── Layouts/         # KioskLayout, AdminLayout
│       ├── Pages/           # Kiosk, Controller, Admin, Download, Auth
│       ├── stores/          # sessionStore, deviceStore, audioStore
│       └── app.ts           # Inertia application entry point
├── routes/
│   └── web.php              # Definisi 44 rute lengkap aplikasi
├── src-tauri/               # Konfigurasi dan bridge Rust untuk Tauri 2
├── tests/
│   └── test_lifecycle.php   # Script verifikasi otomatis siklus hidup sesi
├── start-photobooth.bat     # Launcher satu-klik untuk Windows Kiosk
├── install-service.ps1      # Skrip PowerShell auto-installer Windows Service
└── docs/                    # Dokumentasi Teknis Lengkap
    ├── INSTALL.md           # Panduan instalasi dan deployment
    ├── HARDWARE.md          # Spesifikasi hardware yang didukung
    ├── CAMERA.md            # Panduan setup kamera (DSLR/Mirrorless/Webcam)
    ├── PRINTER.md           # Panduan setup printer (Dye-Sub/Epson/Thermal)
    ├── DEPLOYMENT.md        # Panduan deployment offline event & onsite checklist
    └── TROUBLESHOOTING.md   # Panduan penanganan kendala umum
```

---

## ⚡ Panduan Cepat Menjalankan Aplikasi

### 1. Prasyarat
- PHP 8.2+ dengan ekstensi `pdo_mysql`, `gd`, `fileinfo`, `mbstring` aktif.
- MySQL Server (misal: Laragon / XAMPP).
- Node.js 18+ & npm.

### 2. Setup Database & Environment
```bash
# Pastikan database 'photobooth_pro' sudah ada di MySQL
mysql -u root -e "CREATE DATABASE IF NOT EXISTS photobooth_pro;"

# Install dependensi (sudah terpasang di repo ini)
composer install
npm install

# Jalankan migrasi dan seeding data awal
php artisan migrate --seed

# Buat symlink storage
php artisan storage:link
```

### 3. Menjalankan Server & Kiosk
Cukup klik dua kali berkas:
```cmd
start-photobooth.bat
```
Atau jalankan secara manual di dua terminal:
```bash
# Terminal 1 - Backend Server
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 - Frontend Dev Server (jika dalam mode pengembangan)
npm run dev
```

---

## 🔑 Kredensial Default

| Peran | Email / Akses | Password | PIN Kiosk |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@photobooth.pro` | `password` | `1234` |
| **Operator** | `operator@photobooth.pro` | `password` | `0000` |
| **Kupon Promo Demo** | Kode: `MERDEKA20` | Diskon: Rp 10.000 | - |

---

## 🌐 Alamat & URL Akses

| Antarmuka | URL | Deskripsi |
| :--- | :--- | :--- |
| **Kiosk Booth** | `http://localhost:8000` | Antarmuka layar sentuh utama untuk tamu foto |
| **Remote Controller** | `http://<IP-LAN>:8000/controller` | Kontrol nirkabel jarak jauh untuk tablet operator |
| **Admin Panel** | `http://localhost:8000/login` | Dashboard, Template Builder, Hardware, Laporan |
| **Device Center** | `http://localhost:8000/admin/devices` | Monitoring & pengujian kamera/printer |
| **Download Tamu** | `http://<IP-LAN>:8000/download/{code}` | Halaman unduh foto digital tamu via QR code |

---

## 📚 Dokumentasi Rinci

- [Panduan Instalasi & Konfigurasi](file:///D:/PROGRAMER/WEB/photo/INSTALL.md)
- [Spesifikasi & Panduan Hardware](file:///D:/PROGRAMER/WEB/photo/HARDWARE.md)
- [Panduan Pengaturan Kamera](file:///D:/PROGRAMER/WEB/photo/CAMERA.md)
- [Panduan Pengaturan Printer](file:///D:/PROGRAMER/WEB/photo/PRINTER.md)
- [Panduan Deployment Event Lapangan](file:///D:/PROGRAMER/WEB/photo/DEPLOYMENT.md)
- [Troubleshooting & Solusi Kendala](file:///D:/PROGRAMER/WEB/photo/TROUBLESHOOTING.md)

---

## 📄 Lisensi
Hak Cipta © 2026 PHOTOBOOTH PRO Team. Dibuat secara profesional dan siap pakai untuk kebutuhan komersial.
