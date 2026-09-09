# PANDUAN DEPLOYMENT EVENT - PHOTOBOOTH PRO

Dokumen ini memuat panduan operasional saat menyiapkan stan PHOTOBOOTH PRO di lokasi acara (sekolah, pernikahan, gathering perusahaan, studio mini).

---

## 1. Arsitektur Jaringan On-Site (Offline-First)

PHOTOBOOTH PRO didesain **Offline-First**. Seluruh fungsi utama (kamera, live view, penyusunan template, cetak, kasir tunai) berjalan 100% tanpa sambungan internet.

```
                    ┌──────────────────────────────┐
                    │      ROUTER WI-FI LOKAL      │
                    │   (Tidak Butuh Kuota / Inet) │
                    └──────────────┬───────────────┘
                                   │
               ┌───────────────────┴───────────────────┐
               ▼                                       ▼
      [ PHOTO BOOTH HOST ]                    [ TABLET OPERATOR ]
   Laptop / Touchscreen PC                     iPad / Android Tab
   IP: 192.168.1.100                           IP: 192.168.1.105
   - Laravel Backend                           - Browser:
   - Vue 3 Kiosk UI (Layar Tamu)                 http://192.168.1.100:8000/controller
   - Terhubung Kamera & Printer via USB
```

---

## 2. Checklist Persiapan Stand Photo Booth

1. **Komputer Host**:
   - Sambungkan adaptor daya AC.
   - Sambungkan kabel USB Kamera DSLR/Mirrorless.
   - Sambungkan kabel USB Printer DNP.
   - Hubungkan ke Wi-Fi lokal stand (atau tethering router portabel).
2. **Kamera**:
   - Pasang di tripod kokoh pada ketinggian dada orang dewasa (~130-140 cm).
   - Pasang lampu studio (Ring light atau Softbox 5500K continuous).
   - Hidupkan kamera dalam mode Manual.
3. **Printer**:
   - Periksa sisa kertas dan ribbon. Pasang tray penampung foto.
4. **Menjalankan Sistem**:
   - Klik ganda pintasan **`PHOTOBOOTH PRO`** di Desktop.
   - Layar otomatis masuk ke mode Kiosk Fullscreen ("Capture The Moment [ START ]").
5. **Operator Tablet**:
   - Buka Chrome di tablet operator.
   - Akses: `http://[IP-HOST]:8000/controller`.
   - Operator siap memandu tamu atau mengontrol sesi secara nirkabel!