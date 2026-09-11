# PANDUAN INSTALASI - PHOTOBOOTH PRO

Dokumen ini menjelaskan langkah demi langkah untuk menginstal dan menjalankan **PHOTOBOOTH PRO** baik di komputer lokal (Windows/Linux) maupun di Server VPS (Linux Ubuntu/Debian) dengan domain HTTPS.

---

## 1. Persyaratan Sistem

- **Sistem Operasi**: Windows 10/11 x64 ATAU Linux (Ubuntu 22.04/24.04 LTS, Debian, Arch, Kali).
- **PHP**: Versi 8.2+ dengan ekstensi wajib aktif:
  - `pdo_mysql`, `gd`, `fileinfo`, `exif`, `curl`, `mbstring`, `zip`, `xml`, `intl`.
- **Web Server / Database**: Nginx / Apache & MySQL 8.0+ atau MariaDB 10.5+.
- **Node.js**: Versi 18+ atau 20+ (NPM terpasang).
- **Composer**: Versi 2.x.
- **Koneksi / SSL**: Wajib menggunakan **HTTPS** (atau `localhost` / `127.0.0.1`) agar browser mengizinkan akses kamera (`getUserMedia`).
- **Resolusi Layar**: Minimal 1920x1080 untuk Kiosk Display (Layar sentuh / Touchscreen didukung penuh).

---

## 2. Instalasi Cepat di Windows (Otomatis)

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

## 3. Instalasi di VPS Linux (Ubuntu / Debian / Production)

### Langkah 1: Persiapan Server & Dependensi PHP
```bash
# Update repository
sudo apt update && sudo apt upgrade -y

# Pasang dependensi PHP & ekstensi pendukung (contoh PHP 8.2/8.3)
sudo apt install -y php-cli php-fpm php-mysql php-gd php-curl \
  php-mbstring php-xml php-zip php-exif php-intl composer \
  nodejs npm nginx mariadb-server git
```

### Langkah 2: Kloning & Dependensi Backend
```bash
cd /var/www
# atau direktori pilihan Anda
git clone https://github.com/yansuwitna/photo.git
cd photo

composer install --no-dev --optimize-autoloader
```

### Langkah 3: Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan parameter `.env`:
```env
APP_NAME="PHOTOBOOTH PRO"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://photo.domainanda.sch.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=photobooth_pro
DB_USERNAME=nama_user_db
DB_PASSWORD=password_db

FILESYSTEM_DISK=public
SESSION_DRIVER=database
SESSION_TABLE=http_sessions
```

### Langkah 4: Setup Database & Storage Symlink
```bash
# Buat database jika belum ada
sudo mysql -e "CREATE DATABASE photobooth_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrasi & seeder
php artisan migrate --force
php artisan db:seed --force

# Hubungkan symlink storage publik
php artisan storage:link
```

### Langkah 5: Pengaturan Izin Folder (PENTING untuk Foto & Upload)
Pastikan web server (`www-data`) memiliki izin penuh untuk menulis ke folder penyimpanan sesi:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Langkah 6: Kompilasi Aset Frontend
```bash
npm install
npm run build
```

### Langkah 7: Konfigurasi Nginx & Cloudflare / HTTPS
Akses kamera browser memerlukan konteks aman (**HTTPS**).

Contoh konfigurasi Nginx (`/etc/nginx/sites-available/photo`):
```nginx
server {
    listen 80;
    server_name photo.domainanda.sch.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name photo.domainanda.sch.id;

    root /var/www/photo/public;
    index index.php index.html;

    # SSL Certs (Let's Encrypt / Cloudflare Origin)
    ssl_certificate /etc/ssl/certs/fullchain.pem;
    ssl_certificate_key /etc/ssl/private/privkey.pem;

    # Header izin kamera untuk kiosk dan perangkat mobile
    add_header Permissions-Policy "camera=*, microphone=*, display-capture=*";
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Sesuaikan versi PHP
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Aktifkan dan restart:
```bash
sudo ln -s /etc/nginx/sites-available/photo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx php8.2-fpm
```

---

## 4. Instalasi Manual di Lingkungan Development

### Langkah 1: Dependensi Backend
```bash
composer install
```

### Langkah 2: Konfigurasi Lingkungan (`.env`)
```bash
cp .env.example .env
php artisan key:generate
```

### Langkah 3: Database & Symlink
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### Langkah 4: Dependensi Frontend & Kompilasi
```bash
npm install
npm run build
```

---

## 5. Menjalankan Aplikasi

### Di Windows:
Jalankan skrip launcher:
```cmd
start-photobooth.bat
```

### Di Linux / Manual:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 6. Akses Halaman & Kredensial Default

Buka URL pada browser:
- **Layar Kiosk Customer**: `https://photo.domainanda.sch.id/` (atau `http://localhost:8000/`)
- **Remote Operator Tablet**: `https://photo.domainanda.sch.id/controller`
- **Panel Admin**: `https://photo.domainanda.sch.id/admin`
  - **Email**: `admin@photobooth.pro`
  - **Password**: `password`
  - **PIN Cepat Kiosk**: `1234`
  - **PIN Operator**: `0000`

---

## 7. Catatan Pemecahan Masalah (Troubleshooting)

1. **Kamera Tidak Muncul / "Akses Kamera Diperlukan"**:
   - Pastikan URL dibuka menggunakan protokol **`https://`** atau `http://localhost`. Browser menolak akses kamera pada protokol HTTP non-lokal.
   - Periksa izin kamera pada browser di bilah URL (ikon gembok / slider izin situs).

2. **Gagal Mengambil Foto / 500 Internal Server Error saat Capture**:
   - Periksa izin folder penyimpanan di VPS:
     ```bash
     sudo chown -R www-data:www-data storage bootstrap/cache
     sudo chmod -R 775 storage bootstrap/cache
     ```
   - Pastikan ekstensi PHP `php-gd` sudah terpasang dan aktif (`php -m | grep gd`).
   - Bersihkan cache Laravel:
     ```bash
     php artisan optimize:clear
     ```

3. **Foto Final / Thumbnail Tidak Tampil**:
   - Pastikan symlink publik telah terhubung:
     ```bash
     php artisan storage:link
     ```