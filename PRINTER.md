# PANDUAN INTEGRASI PRINTER - PHOTOBOOTH PRO

Dokumen ini memberikan panduan integrasi printer foto berkecepatan tinggi pada PHOTOBOOTH PRO.

---

## 1. Printer yang Didukung

1. **DNP Dye-Sublimation**: DS-RX1HS, DS620A, QW410. (Sangat disarankan untuk event profesional karena kecepatan 12-14 detik per lembar 4R).
2. **Citizen Photo Printer**: CY-02, CX-02.
3. **Epson SureLab**: SL-D1070, SL-D870, SL-D700.
4. **Thermal Receipt / Sticker Printer**: POS 80mm roll format.
5. **Windows Standard Spooler**: Semua printer foto inkjet yang terdaftar di driver Windows.
6. **MockPrinter**: Printer virtual untuk simulasi cetak di tahap pengujian.

---

## 2. Ukuran Kertas Cetak yang Didukung

- **4R (10 x 15 cm)**: Format standar paling populer. Resolusi cetak 1200 x 1800 px pada 300 DPI.
- **Photo Strip (2 x 6 inch / 5 x 15 cm)**: Format strip 2-potong khas photo booth pernikahan.
- **5R (13 x 18 cm)**: Format studio sedang.
- **6R (15 x 20 cm)**: Format potret besar.
- **A4**: Untuk cetak kolase atau sertifikat berbingkai.

---

## 3. Pemantauan Sisa Kertas & Ribbon

Printer Dye-Sublimation seperti DNP DS-RX1HS memiliki ribbon berkapasitas 700 lembar (strip) atau 350-400 lembar (4R).
- PHOTOBOOTH PRO mencatat pengurangan lembar secara berkala pada setiap cetakan.
- Status Kertas:
  - 🟢 **Normal**: Sisa > 20 lembar.
  - 🟡 **Kertas Menipis**: Sisa <= 20 lembar (peringatan untuk operator).
  - 🔴 **Kertas Habis**: Cetak dinonaktifkan sementara dan dialog penggantian kertas muncul di panel operator.

---

## 4. Konfigurasi Multi-Stand & Multi-Printer (1 Stand 1 Printer)

Jika Anda menggunakan lebih dari 1 stand foto di lokasi event, dan setiap stand memiliki printer masing-masing:

### A. Konsep Kerja
1. **Layar Kiosk (Pengunjung)**: Mengambil foto, memilih frame, dan submit sesi. Sesi dan job cetak otomatis diberi tag identitas stand (`booth_id`: `STAND-01`, `STAND-02`, dst.). Layar Kiosk langsung kembali ke menu awal tanpa menunggu printer fisik selesai bergulir.
2. **Auto-Print Station (Background Worker)**:
   - Dibuka di latar belakang pada laptop stand yang terhubung printer fisik.
   - URL: `http://localhost:8000/print-station?booth=STAND-01`
   - Dijalankan dengan Chrome/Edge `--kiosk-printing` agar pencetakan berlangsung secara senyap (*silent print*) tanpa pop-up dialog print Windows.
   - Hanya menyedot dan mencetak antrean foto yang ditujukan khusus untuk stand tersebut.

### B. Cara Menjalankan Cepat
* **Laptop Stand 1 (Host Server + Printer Stand 1)**:
  Jalankan file [start-stand1.bat](file:///D:/PROGRAMER/WEB/photo/start-stand1.bat)
* **Laptop Stand 2 / Client (Terhubung Printer Stand 2)**:
  Jalankan file [start-stand-client.bat](file:///D:/PROGRAMER/WEB/photo/start-stand-client.bat)
  Masukkan IP server (misal `192.168.1.100`) dan pilih `STAND-02`.
* **Ubah Stand Manual via UI**:
  Pada layar awal Kiosk, klik tombol badge **STAND: STAND-01** di bagian atas untuk mengganti ke stand lain kapan saja.