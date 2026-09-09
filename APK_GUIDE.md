# 📱 PANDUAN LENGKAP APK & MOBILE PHOTOBOOTH PRO

Dokumen ini menjelaskan cara menggunakan dan mengompilasi aplikasi **PHOTOBOOTH PRO** pada perangkat Android (Tablet dan Smartphone) untuk kedua skenario penggunaan:
1. **Skenario A:** Tablet Android sebagai **Remote Operator Controller & Kiosk Display** (terhubung ke server booth lokal).
2. **Skenario B:** **Standalone Mobile Photobooth** (menggunakan kamera bawaan tablet/HP Android langsung).

---

## 🌟 2 Skenario Penggunaan Mobile

### Skenario A: Remote Operator & Kiosk Display (Standar Lab Event)
- **Komputer Server (Laptop / Mini PC):**
  - Terhubung dengan Kamera DSLR/Mirrorless (Canon / Sony / Nikon via USB).
  - Terhubung dengan Printer Dye-Sublimation (DNP DS-RX1 / DS620 / Epson) via USB.
  - Menjalankan backend server: `php artisan serve --host=0.0.0.0 --port=8000`.
- **Tablet Android:**
  - Terhubung ke WiFi yang sama dengan PC Server.
  - Membuka halaman Kiosk (`http://<IP_PC>:8000/`) untuk tamu menyentuh layar, ATAU membuka Remote Controller (`http://<IP_PC>:8000/controller`) untuk operator memicu jepretan, retake, dan cetak nirkabel.

### Skenario B: Standalone Mobile Photobooth (Kamera HP/Tablet)
- Menggunakan sensor kamera depan (selfie) atau kamera belakang HP/Tablet Android secara langsung.
- Dilengkapi fitur:
  - **Switch Camera:** Beralih instan antara kamera depan (*selfie*) dan kamera belakang.
  - **Mirror Mode:** Efek cermin alami saat berpose di depan layar.
  - **Screen Flash Simulation:** Layar otomatis menyala putih terang sekejap saat tombol shutter aktif untuk efek pencahayaan studio.
  - **300 DPI Rendering:** Foto resolusi tinggi dari kamera HP langsung dikomposisikan ke dalam template pilihan secara otomatis di server.

---

## 🚀 2 Cara Menggunakan di Android

### Cara 1: Progressive Web App (PWA) — Paling Cepat & Tanpa Kompilasi!
Anda **tidak perlu** menginstal Android Studio atau mengompilasi APK secara manual untuk merasakan pengalaman aplikasi native:

1. Pastikan HP/Tablet Android terhubung ke satu jaringan WiFi/Hotspot dengan PC Server booth.
2. Buka **Google Chrome** di HP/Tablet Android Anda.
3. Ketikkan alamat server booth, contoh:
   ```text
   http://192.168.1.100:8000
   ```
   *(Ganti `192.168.1.100` dengan IP lokal komputer Anda).*
4. Di Google Chrome, tekan menu titik tiga (⋮) di pojok kanan atas, lalu pilih **"Tambahkan ke Layar Utama"** atau **"Install App"** (*Instal Aplikasi*).
5. Ikon **PHOTOBOOTH PRO** akan langsung muncul di halaman beranda / App Drawer Android Anda!
6. Saat dibuka, aplikasi akan berjalan **100% Fullscreen (Kiosk Mode)** tanpa bilah alamat (*address bar*) browser, persis seperti aplikasi APK native!

---

### Cara 2: Kompilasi Menjadi APK Native (Capacitor + Android Studio)
Project ini sudah dilengkapi struktur native Android resmi berbasis **Capacitor** di folder `android/`.

#### Langkah Kompilasi APK:
1. Pastikan Anda telah menginstal **Android Studio** di komputer Anda.
2. Buka **Android Studio**, lalu klik **Open an Existing Project**.
3. Pilih folder project Android:
   ```text
   D:\PROGRAMER\WEB\photo\android
   ```
4. Tunggu beberapa saat hingga proses **Gradle Sync** selesai secara otomatis.
5. Untuk membuat file APK:
   - Klik menu atas: **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
6. Setelah proses selesai, klik tulisan **"locate"** pada notifikasi di pojok kanan bawah.
7. File APK Anda akan berada di:
   ```text
   D:\PROGRAMER\WEB\photo\android\app\build\outputs\apk\debug\app-debug.apk
   ```
8. Salin file `.apk` tersebut ke tablet atau HP Android Anda, lalu pasang (*Install*).

---

## 🛠️ Konfigurasi Gateway Mobile

Saat membuka aplikasi di Android untuk pertama kalinya, Anda akan melihat antarmuka **PHOTOBOOTH PRO Mobile Gateway**:
1. **Masukkan Alamat IP Server:** Masukkan alamat IP komputer booth Anda, misalnya: `http://192.168.1.100:8000`.
2. **Pilih Mode:**
   - **🖥️ Buka Layar Kiosk Booth (`/`):** Layar sentuh untuk tamu memilih template dan berfoto.
   - **📱 Buka Remote Controller Operator (`/controller`):** Kontrol nirkabel jarak jauh untuk operator.
   - **⚙️ Buka Dashboard Admin (`/login`):** Konfigurasi event, template builder, dan device center.
3. Centang opsi **"Otomatis buka mode terakhir kali dipilih"** agar saat aplikasi dibuka berikutnya, sistem langsung masuk ke mode yang Anda tentukan tanpa perlu konfigurasi ulang.

---

## 🔒 Izin Perangkat (*Permissions*) yang Disediakan
Aplikasi APK telah dikonfigurasi dengan izin lengkap di [`AndroidManifest.xml`](file:///D:/PROGRAMER/WEB/photo/android/app/src/main/AndroidManifest.xml):
- `CAMERA`: Mengakses kamera tablet/HP depan dan belakang.
- `RECORD_AUDIO` & `MODIFY_AUDIO_SETTINGS`: Memainkan efek suara Web Audio API.
- `INTERNET`: Komunikasi dengan server booth lokal maupun cloud.
- `ACCESS_NETWORK_STATE` & `ACCESS_WIFI_STATE`: Deteksi status koneksi WiFi lokal.
- `usesCleartextTraffic="true"`: Memungkinkan koneksi lancar ke IP lokal (`http://192.168.x.x`) tanpa kendala sertifikat SSL.
