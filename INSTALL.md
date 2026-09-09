# PANDUAN INSTALASI - PHOTOBOOTH PRO

Dokumen ini menjelaskan langkah demi langkah untuk menginstal dan menjalankan **PHOTOBOOTH PRO** pada komputer host berbasis Windows 10/11 x64.

---

## 1. Persyaratan Sistem

- **Sistem Operasi**: Windows 10 atau Windows 11 (64-bit disarankan)
- **PHP**: Versi 8.2+ dengan ekstensi `pdo_mysql`, `gd`, `fileinfo`, `exif`, `curl`, `mbstring` aktif.
- **Web Server / Database**: MySQL / MariaDB (misalnya melalui XAMPP atau Laragon).
- **Node.js**: Versi 18+ atau 20+ (NPM terpasang).
- **Composer**: Versi 2.x.
- **Resolusi Layar**: Minimal 1920x1080 (Layar sentuh / Touchscreen didukung penuh).

---

## 2. Instalasi Cepat (Otomatis)

Tersedia skrip instalasi otomatis via PowerShell:
```powershell
powershell -ExecutionPolicy Bypass -File .\install-service.ps1
```
Skrip di atas akan secara otomatis:
1. Memeriksa dan membuat database MySQL `photobooth_pro`.
2. Menjalankan migration dan database seeder lengkap.
3. Menghubungkan storage symlink (`public/storage`).
4. Mengompilasi aset frontend (Vue 3, Inertia, Tailwind CSS) via Vite.
5. Menjalankan uji hardware abstraction layer.
6. Membuat pintasan desktop `PHOTOBOOTH PRO`.

---

## 3. Instalasi Manual (Langkah demi Langkah)

### Langkah 1: Kloning & Dependensi Backend
```bash
composer install
```

### Langkah 2: Konfigurasi Lingkungan (`.env`)
Pastikan parameter database pada `.env` telah disesuaikan:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=photobooth_pro
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
SESSION_TABLE=http_sessions
```

### Langkah 3: Inisialisasi Database & Seeder
Buat database di MySQL:
```sql
CREATE DATABASE photobooth_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Jalankan migrasi dan seeder:
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### Langkah 4: Instalasi Dependensi Frontend & Kompilasi
```bash
npm install
npm run build
```

---

## 4. Menjalankan Aplikasi

Jalankan skrip launcher:
```cmd
start-photobooth.bat
```
Atau jalankan server Laravel:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Buka URL pada browser:
- **Layar Kiosk Customer**: `http://localhost:8000/`
- **Remote Operator Tablet**: `http://localhost:8000/controller` (atau ganti `localhost` dengan IP LAN komputer host, contoh: `http://192.168.1.100:8000/controller`)
- **Panel Admin**: `http://localhost:8000/admin`
  - Email: `admin@photobooth.pro`
  - Password: `password`
  - PIN Cepat Kiosk: `1234`
  - PIN Operator: `0000`