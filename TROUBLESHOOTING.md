# PANDUAN TROUBLESHOOTING - PHOTOBOOTH PRO

Dokumen ini merangkum pemecahan masalah (troubleshooting) atas kendala operasional yang mungkin terjadi selama event berlangsung.

---

## 1. Kamera Tidak Terdeteksi / Status "DISCONNECTED"

**Penyebab & Solusi**:
1. **Kabel USB longgar atau rusak**:
   - Gunakan kabel USB pendek berkualitas tinggi (maksimal 2-3 meter tanpa repeater).
   - Pastikan dicolokkan ke port USB 3.0 (warna biru) di bagian belakang PC/laptop.
2. **Software lain sedang mengunci kamera**:
   - Pastikan aplikasi seperti *EOS Utility*, *Lightroom*, atau *Sony Imaging Edge* telah ditutup tuntas (periksa System Tray / Task Manager).
3. **Auto Power Off kamera aktif**:
   - Matikan timer auto-sleep pada menu pengaturan kamera fisik.
4. **Beralih ke Mock Kamera saat darurat**:
   - Buka Panel Admin -> Pusat Perangkat -> ganti adapter kamera ke `Mock Camera` agar sesi foto tetap dapat berlangsung jika kamera fisik mengalami kendala baterai.

---

## 2. Printer Macet / Paper Jam / Paper Empty

**Penyebab & Solusi**:
1. **Lampu indikator merah pada printer menyala**:
   - Buka penutup depan printer DNP, periksa apakah ribbon telah habis atau kusut.
   - Pasang rol kertas baru dan potong 2 lembar pertama sesuai instruksi tray DNP.
2. **Antrian cetak tertahan di Windows**:
   - Buka `Printers & Scanners` di Windows, klik `Open Queue` pada printer DNP, lalu pilih `Restart all jobs` atau `Cancel all documents`.
3. **Reset hitungan kertas**:
   - Di panel admin, perbarui sisa kertas ke jumlah isi ulang (misal 400 lembar).

---

## 3. Lupa PIN Keluar Kiosk

- **PIN Administrator**: `1234`
- **PIN Operator**: `0000`
- Jika ingin mengubah PIN, buka menu **Panel Admin -> Pengaturan Sistem -> PIN Keluar Kiosk Mode**.

---

## 4. Layar Live View Gelap / Blank

1. Periksa penutup lensa kamera (lens cap) apakah sudah dibuka.
2. Pada kamera DSLR (seperti Canon 80D / 200D), pastikan baterai kamera terisi minimal di atas 30% karena live view membutuhkan daya kontinu untuk mengangkat cermin reflex.
3. Gunakan continuous lighting yang cukup terang di area pemotretan.