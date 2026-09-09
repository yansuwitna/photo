# PANDUAN INTEGRASI KAMERA - PHOTOBOOTH PRO

Dokumen ini memuat panduan lengkap untuk menghubungkan dan mengonfigurasi kamera DSLR, Mirrorless, maupun Webcam pada PHOTOBOOTH PRO.

---

## 1. Adapter Kamera yang Tersedia

| Adapter | Rekomendasi Hardware | Protokol / SDK |
| :--- | :--- | :--- |
| **CanonAdapter** | Canon EOS R50, R6, R8, 200D II, 80D, 1500D | Canon EDSDK via USB Tethering |
| **SonyAdapter** | Sony Alpha A7 IV, A7 III, A6700, ZV-E10 | Sony Camera Remote SDK (USB PC Remote) |
| **NikonAdapter** | Nikon Z5, Z6, Z50, D7500 | Nikon SDK / WIA Driver |
| **WebcamAdapter** | Logitech Brio 4K, C920, Cam Link USB | DirectShow / WebRTC MediaDevices |
| **MockCamera** | Mode Demo / Pengujian Tanpa Hardware Fisik | GD Image Studio Synthesis |

---

## 2. Pengaturan Kamera Fisik Sebelum Digunakan

### Untuk Kamera Canon EOS:
1. Pasang kabel USB kecepatan tinggi (disarankan USB 3.0 / USB-C langsung ke port motherboard, hindari USB hub pasif).
2. Atur mode kamera ke **Manual (M)**:
   - Shutter Speed: `1/125` atau `1/160` detik (menghindari getaran goyang).
   - Aperture: `f/4.0` - `f/5.6` (menjaga ketajaman grup tamu).
   - ISO: `400` atau `800` (sesuaikan dengan continuous lighting booth).
   - White Balance: `Daylight` atau `Flash` (5500K tetap).
   - Fokus: `One Shot AF` dengan deteksi mata (Eye AF).
3. Matikan fitur **Auto Power Off / Sleep** pada menu kamera.
4. Gunakan Dummy Battery (AC Adapter kit) agar kamera menyala terus-menerus selama acara.

### Untuk Kamera Sony Alpha:
1. Buka menu kamera: `Network` -> `PC Remote Function` -> **PC Remote: ON**.
2. Metode koneksi: **USB**.
3. Pastikan mode fokus disetel ke **AF-S** atau **AF-C Wide**.

---

## 3. Deteksi Kapabilitas Kamera (Capability Detection)
PHOTOBOOTH PRO melakukan pemeriksaan fitur secara otomatis:
- Jika kamera mendukung kontrol ISO, slider ISO akan muncul di panel operator.
- Jika kamera tidak mendukung (misalnya USB Webcam), aplikasi secara anggun menyembunyikan pengaturan tersebut dengan pesan: *"Fitur tidak didukung oleh kamera ini."* tanpa mengalami crash.