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