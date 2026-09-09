# ARSITEKTUR PERANGKAT KERAS (HARDWARE ABSTRACTION LAYER)

PHOTOBOOTH PRO dirancang menggunakan arsitektur perangkat keras modular (Hardware Abstraction Layer). Logika bisnis tidak terikat langsung pada tipe kamera atau printer fisik tertentu.

---

## 1. Konsep Perangkat Keras

```
UI Vue 3 (Touchscreen / Kiosk)
            │
    Inertia.js / REST API
            │
    Device Manager (Backend)
 ┌──────────┴──────────┬──────────────┬──────────────┐
 ▼                     ▼              ▼              ▼
Camera Manager  Printer Manager  Audio Manager  Display Manager
 │                     │              │              │
 ├─ CanonAdapter       ├─ Windows     ├─ Web Audio   └─ Fullscreen
 ├─ SonyAdapter        ├─ DyeSub DNP  ├─ Speech ID      Kiosk Mode
 ├─ NikonAdapter       ├─ Thermal     └─ Shutter FX
 ├─ WebcamAdapter      └─ MockPrinter
 └─ MockCamera
```

---

## 2. Abstraksi Kamera (`CameraInterface`)
Semua adapter kamera mengimplementasikan fungsi standar:
- `connect()`: Melakukan jabat tangan (handshake) dengan kamera via USB/PTP.
- `disconnect()`: Memutus koneksi kamera secara aman.
- `getStatus()`: Mengambil status baterai, sisa penyimpanan, dan status siap.
- `capture($destinationPath)`: Memicu pengambilan foto beresolusi tinggi.
- `startLiveView()` / `stopLiveView()`: Menjalankan streaming live view pada layar.
- `getCapabilities()`: Mendeteksi fitur kamera (Live View, ISO control, dsb.).

Jika suatu fitur tidak didukung oleh kamera tertentu (misalnya webcam tidak memiliki pengaturan ISO manual), sistem akan memberikan respons graceful tanpa menyebabkan aplikasi crash.

---

## 3. Abstraksi Printer (`PrinterInterface`)
- `getPrinters()`: Mendeteksi printer yang terpasang di Windows.
- `print($filePath, $copies, $paperSize)`: Mengirim tugas cetak ke antrian spooler printer foto.
- `getPaperStatus()`: Membaca sisa lembar kertas foto (dye sublimation ribbon/paper roll).
- `cancelPrint($jobId)`: Membatalkan antrian cetak jika diperlukan.

---

## 4. Sistem Audio (`AudioManager` & Web Audio API)
Sistem audio bekerja langsung di browser dan sistem host tanpa memerlukan file audio eksternal tambahan:
1. **Beep Countdown**: Frekuensi bertingkat pada detik ke-3, 2, 1.
2. **Shutter Sound**: Simulasi mekanis klik shutter kamera melalui audio filter.
3. **Voice Instruction**: Sintesis suara berbahasa Indonesia ("Siapkan posisi Anda", "SMILE!", "Silakan ambil foto Anda").
4. **Celebration Fanfare**: Suara akor selebrasi saat pemotretan dan cetak selesai.