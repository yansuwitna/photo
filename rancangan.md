Anda adalah seorang software architect, UI/UX designer, full-stack developer, desktop application developer, dan hardware integration engineer.

Saya ingin membuat aplikasi bernama:

**PHOTOBOOTH PRO**

Aplikasi ini merupakan sistem photo booth profesional yang dapat digunakan pada event sekolah, wedding, ulang tahun, gathering, booth promosi, event perusahaan, studio mini, dan berbagai acara lainnya.

Aplikasi harus memiliki tampilan yang sangat modern, unik, elegan, profesional, premium, touchscreen-friendly, dan mudah digunakan oleh operator maupun pengguna umum.

==================================================

1. TEKNOLOGI UTAMA
   ==================================================

Gunakan arsitektur hybrid desktop + web.

Backend:

* Laravel 12
* PHP 8.2+
* MySQL/MariaDB
* REST API
* WebSocket
* Queue jika diperlukan
* Laravel Storage

Frontend:

* Vue 3
* Inertia.js
* TypeScript
* Tailwind CSS
* Pinia
* Vite
* Lucide Icons
* Poppins atau Inter
* Animasi menggunakan CSS/Framer Motion equivalent yang kompatibel

Desktop:

* Tauri 2

Tujuan Tauri:

* Menjalankan aplikasi dalam mode kiosk/fullscreen
* Mengakses perangkat lokal
* Menjadi bridge antara UI Vue dengan perangkat keras
* Menjalankan Camera Bridge
* Menjalankan Printer Bridge
* Mengakses filesystem lokal
* Mengakses USB/perangkat yang diperlukan
* Menjalankan aplikasi tanpa membutuhkan browser biasa

JANGAN membuat sistem hanya sebagai website browser biasa karena aplikasi harus dapat berkomunikasi dengan kamera dan printer.

==================================================
2. ARSITEKTUR PERANGKAT KERAS
=============================

Buat arsitektur hardware abstraction layer.

Gunakan konsep:

Device Manager

Camera Manager
Printer Manager
Audio Manager
Display Manager
Storage Manager
Session Manager

Camera harus memiliki interface standar:

CameraInterface

dengan fungsi:

* connect()
* disconnect()
* getStatus()
* capture()
* startLiveView()
* stopLiveView()
* downloadPhoto()
* getBattery()
* getStorageStatus()
* setISO()
* setShutterSpeed()
* setAperture()
* setWhiteBalance()
* setFocusMode()
* setCaptureMode()

Jangan membuat Vue berkomunikasi langsung dengan kamera.

Vue berkomunikasi dengan:

Tauri
↓
Camera Bridge
↓
Camera SDK / native API / USB / supported protocol
↓
Camera

==================================================
3. SUPPORT KAMERA
=================

Sediakan adapter:

CanonAdapter
SonyAdapter
NikonAdapter
WebcamAdapter

Prioritaskan Canon dan Sony.

Arsitektur harus memungkinkan penggunaan:

* DSLR
* Mirrorless
* Webcam USB
* Kamera internal laptop
* Kamera eksternal

Untuk kamera profesional, gunakan pendekatan SDK/API resmi atau library native yang sesuai dengan sistem operasi.

JANGAN mengklaim semua kamera Canon/Sony dapat dikontrol dengan cara yang sama.

Buat Camera Capability Detection.

Contoh:

Canon EOS
Status: Connected
Live View: Supported
Remote Capture: Supported
Download: Supported
ISO Control: Supported

Sony Alpha
Status: Connected
Live View: Supported
Remote Capture: Supported
Download: Supported

Jika suatu kamera tidak mendukung fitur tertentu, tampilkan:

"Fitur tidak didukung oleh kamera ini."

Jangan membuat aplikasi crash.

==================================================
4. CAMERA BRIDGE
================

Buat service khusus:

camera-bridge

Tugasnya:

* mendeteksi kamera
* connect/disconnect
* mengambil foto
* menerima live view
* mengunduh hasil foto
* membaca status kamera
* membaca baterai
* membaca kapasitas storage
* menangani reconnect
* menangani camera busy
* menangani USB disconnect
* membuat log perangkat

Gunakan event:

camera.connected
camera.disconnected
camera.ready
camera.busy
camera.error
camera.photo_captured
camera.photo_downloaded
camera.liveview_started
camera.liveview_stopped

==================================================
5. LIVE VIEW
============

Photo booth harus mempunyai halaman:

CAMERA

Tampilkan live view kamera secara realtime.

Layout:

---

## | Battery | Storage | Camera | Connection     |

|                                              |
|                                              |
|                 LIVE VIEW                    |
|                                              |
|                                              |
------------------------------------------------

## | Template | Countdown | Capture | Settings  |

Live view harus mempunyai:

* fullscreen
* mirror mode
* crop
* zoom
* brightness indicator
* grid
* face positioning guide
* safe area
* countdown overlay

==================================================
6. MODE PHOTO BOOTH
===================

Sediakan mode:

1. Single Photo
2. 2 Photos
3. 3 Photos
4. 4 Photos
5. 6 Photos
6. Custom Sequence
7. GIF
8. Boomerang
9. Video
10. Green Screen jika memungkinkan

Operator dapat menentukan:

Jumlah foto:
1–10

Countdown:

3 detik
5 detik
10 detik

Setelah countdown:

3
2
1
SMILE!

Kemudian kamera mengambil foto.

==================================================
7. TEMPLATE SYSTEM
==================

Ini adalah salah satu fitur utama.

Buat Template Builder.

Template dapat terdiri dari:

* 1 foto
* 2 foto
* 3 foto
* 4 foto
* 6 foto
* 8 foto
* custom jumlah foto

Contoh template:

┌──────────────────────┐
│       EVENT NAME     │
│                      │
│   ┌──────────────┐   │
│   │    PHOTO 1   │   │
│   └──────────────┘   │
│                      │
│   ┌──────────────┐   │
│   │    PHOTO 2   │   │
│   └──────────────┘   │
│                      │
│      DATE / LOGO     │
└──────────────────────┘

Template harus mendukung:

* drag & drop
* resize
* rotate
* crop
* photo placeholder
* text
* logo
* QR Code
* frame
* background
* sticker
* gradient
* shape
* event name
* date
* custom font

Template dapat disimpan.

Field:

* nama template
* ukuran
* orientasi
* jumlah slot foto
* background
* overlay
* frame
* font
* elemen
* status aktif

==================================================
8. TEMPLATE PREVIEW
===================

Sebelum mengambil foto, user memilih:

"Choose Your Style"

Tampilkan template dalam bentuk kartu:

┌──────────────┐
│              │
│   TEMPLATE   │
│      A       │
│              │
└──────────────┘

┌──────────────┐
│              │
│   TEMPLATE   │
│      B       │
│              │
└──────────────┘

Template harus memiliki preview yang menarik.

Gunakan animasi hover/touch.

==================================================
9. PHOTO SESSION
================

Buat konsep:

Photo Session

Setiap sesi mempunyai:

* session_id
* event_id
* template_id
* camera_id
* start_time
* end_time
* number_of_photos
* photos
* final_output
* print_status
* payment_status

Flow:

SELECT TEMPLATE
↓
READY
↓
COUNTDOWN
↓
CAPTURE PHOTO 1
↓
COUNTDOWN
↓
CAPTURE PHOTO 2
↓
...
↓
PHOTO REVIEW
↓
COMPOSE TEMPLATE
↓
FINAL PREVIEW
↓
PRINT / SHARE

==================================================
10. PHOTO REVIEW
================

Setelah foto selesai:

"Your Photos"

Tampilkan seluruh foto.

User dapat:

* retake
* accept
* delete
* pilih foto
* ulangi session

Jika retake:

hanya foto yang dipilih yang diambil ulang.

==================================================
11. PHOTO COMPOSER
==================

Buat rendering engine untuk menggabungkan:

foto
+
template
+
text
+
logo
+
frame
+
sticker
+
QR code

menjadi satu output final.

Output:

PNG
JPEG

dan jika diperlukan:

PDF untuk print.

Gunakan resolusi tinggi.

Pastikan kualitas foto tidak turun secara signifikan.

==================================================
12. PRINTING SYSTEM
===================

Buat Printer Manager.

Support:

* Windows printer
* USB printer
* thermal printer
* dye sublimation printer
* photo printer

Printer interface:

PrinterInterface

fungsi:

* getPrinters()
* connect()
* getStatus()
* print()
* cancelPrint()
* getQueue()
* getPaperStatus()
* getErrorStatus()

Event:

printer.connected
printer.disconnected
printer.ready
printer.printing
printer.completed
printer.error
printer.paper_empty

Operator dapat memilih:

Printer
Ukuran kertas
Jumlah copy
Quality
Orientation

Contoh:

4R
5R
6R
A4
Custom

==================================================
13. PRINT FLOW
==============

Setelah user menekan:

PRINT PHOTO

tampilkan:

Preparing your photo...

kemudian:

Printing...

Progress:

████████████████░░░░ 80%

Kemudian:

PRINT COMPLETE

"Silakan ambil foto Anda."

Sediakan:

Print Again

dan:

Done

==================================================
14. PAYMENT
===========

Sediakan sistem pembayaran.

Metode:

* Cash
* QRIS
* Transfer
* Free
* Voucher
* Promo

Harga dapat ditentukan berdasarkan:

* template
* jumlah cetak
* jumlah sesi
* event
* promo

Contoh:

1 Session = Rp25.000
Additional Print = Rp10.000

==================================================
15. PROMO / DISCOUNT
====================

Buat promo engine.

Jenis:

* percentage
* fixed amount
* buy x get y
* event promo
* voucher
* member promo

Contoh:

PROMO MERDEKA

Diskon 20%

Aturan:

minimum transaksi
tanggal mulai
tanggal berakhir
jam aktif
jumlah penggunaan

==================================================
16. QR CODE
===========

Setiap session dapat menghasilkan QR Code.

QR dapat digunakan untuk:

* download foto
* melihat gallery
* share foto
* membuka digital copy

Contoh:

photobooth.local/session/ABC123

QR dicetak pada hasil foto jika template mengaktifkannya.

==================================================
17. TABLET / TOUCHSCREEN MODE
=============================

Aplikasi harus dapat digunakan pada:

* Windows PC
* touchscreen PC
* tablet
* Android tablet sebagai controller
* iPad sebagai controller jika memungkinkan melalui web/PWA
* smartphone operator

Buat:

Kiosk Mode

Customer Mode

Operator Mode

Admin Mode

Tablet tidak perlu mengendalikan kamera secara langsung.

Tablet berkomunikasi dengan Photo Booth Host melalui:

WebSocket / LAN.

Contoh:

Tablet
↓
WebSocket
↓
Photo Booth Host
↓
Camera Bridge
↓
Canon/Sony

==================================================
18. OPERATOR DASHBOARD
======================

Dashboard operator harus menampilkan:

Camera Status
Printer Status
Session
Template
Print Queue
Payment
Today's Transaction
Storage
Battery
Device Error

Status menggunakan indicator:

🟢 READY
🟡 BUSY
🔴 ERROR
⚪ DISCONNECTED

==================================================
19. ADMIN DASHBOARD
===================

Admin dapat mengelola:

Dashboard
Events
Templates
Template Builder
Cameras
Printers
Sessions
Customers
Transactions
Payments
Promos
Users
Roles
Settings
Device Logs
Activity Logs
Reports

==================================================
20. EVENT MANAGEMENT
====================

Buat Event.

Contoh:

Wedding Budi & Ayu
10 September 2026

Setiap event dapat mempunyai:

* nama event
* tanggal
* lokasi
* logo
* template khusus
* harga
* promo
* jumlah session
* custom background
* custom watermark

Ketika event aktif:

PHOTOBOOTH PRO

otomatis menggunakan konfigurasi event.

==================================================
21. GALLERY
===========

Buat gallery.

Admin dapat melihat:

* semua session
* foto original
* foto hasil
* tanggal
* event
* template
* payment
* print status

Gallery dapat menggunakan:

grid masonry modern.

==================================================
22. STORAGE
===========

Struktur:

storage/
events/
event-slug/
sessions/
SESSION-ID/
originals/
edited/
final/
thumbnails/

Nama file:

SESSIONID_001.jpg
SESSIONID_002.jpg
SESSIONID_FINAL.jpg

==================================================
23. AUTO CLEANUP
================

Sediakan konfigurasi:

Delete original after X days
Delete thumbnail after X days
Delete session after X days

Jangan menghapus otomatis tanpa setting yang jelas.

==================================================
24. DEVICE MANAGEMENT
=====================

Buat halaman:

DEVICE CENTER

Menampilkan:

CAMERA

Canon EOS R
CONNECTED
Battery 87%

PRINTER

DNP Printer
READY

DISPLAY

Touchscreen
CONNECTED

AUDIO

Speaker
CONNECTED

Tambahkan:

Test Camera
Test Printer
Test Speaker
Test Display

==================================================
25. AUDIO SYSTEM
================

Tambahkan Audio Manager.

Gunakan untuk:

* countdown sound
* shutter sound
* success sound
* error sound
* background music
* voice instruction

Support output:

* speaker
* headphone
* Bluetooth audio
* USB audio

Contoh:

"Siapkan posisi Anda."

"3..."

"2..."

"1..."

"SMILE!"

"Foto berhasil diambil."

==================================================
26. MULTI DEVICE
================

Sediakan konsep:

Photo Booth Host

dan:

Remote Controller.

Host menjalankan:

Laravel
Vue
Tauri
Camera Bridge
Printer Bridge

Remote device dapat membuka:

[http://HOST-IP:PORT/controller](http://HOST-IP:PORT/controller)

Tablet operator dapat:

* memilih template
* melihat status
* memulai session
* retake
* print
* melihat queue

==================================================
27. OFFLINE FIRST
=================

Photo booth harus tetap dapat bekerja walaupun internet mati.

Fungsi utama harus tetap berjalan:

* camera
* capture
* template
* composition
* print
* payment cash
* session
* local storage

Internet hanya dibutuhkan untuk fitur seperti:

* cloud backup
* online gallery
* QR download online
* cloud sync
* remote monitoring

==================================================
28. DATABASE
============

Buat database migration lengkap:

users
roles
events
templates
template_elements
cameras
printers
devices
sessions
session_photos
final_photos
print_jobs
payments
promos
promo_usages
settings
activity_logs
device_logs

Gunakan foreign key yang benar.

Gunakan UUID untuk session.

==================================================
29. SECURITY
============

Implementasikan:

RBAC
Authentication
CSRF
API authentication
Rate limiting
Validation
Activity logging
Device authentication
WebSocket authentication

Remote controller tidak boleh dapat mengakses fungsi admin.

==================================================
30. UI / UX
===========

DESAIN HARUS:

modern
premium
elegant
minimal
futuristic
professional

Inspirasi:

Apple
Sony
Canon
modern wedding studio
premium photo booth

Gunakan:

Poppins
rounded cards
glass effect secukupnya
soft shadows
large typography
smooth animation
micro interaction

Jangan membuat tampilan seperti dashboard admin biasa.

Customer screen harus terasa seperti produk premium.

==================================================
31. CUSTOMER UI
===============

Halaman awal:

---

```
    PHOTOBOOTH PRO

  Capture The Moment

    [ START ]
```

---

Setelah START:

"Choose Your Template"

Kemudian:

"Get Ready!"

Countdown besar:

3

2

1

SMILE!

Setelah selesai:

"Your Memories"

[ RETAKE ]

[ USE PHOTO ]

[ PRINT ]

==================================================
32. KIOSK MODE
==============

Saat aplikasi dijalankan:

* fullscreen
* hide browser chrome
* prevent accidental exit
* optional password untuk keluar kiosk
* auto start
* auto reconnect devices
* auto restore session

==================================================
33. ERROR HANDLING
==================

Jangan menampilkan error teknis kepada customer.

Jika kamera disconnect:

Customer:

"Kamera sedang dipersiapkan. Mohon tunggu sebentar."

Operator:

"Canon EOS disconnected."

dengan tombol:

Reconnect

Test Camera

Jika printer error:

Customer:

"Printer sedang dipersiapkan."

Operator mendapatkan detail error.

==================================================
34. LOGGING
===========

Catat semua aktivitas:

Camera connected
Camera disconnected
Photo captured
Photo downloaded
Template selected
Session created
Print started
Print completed
Print failed
Payment received
Device error

==================================================
35. REPORT
==========

Laporan:

* transaksi harian
* transaksi bulanan
* jumlah session
* jumlah print
* template terpopuler
* event terpopuler
* pendapatan
* promo
* printer error
* camera error

Export:

Excel
PDF
CSV

==================================================
36. PROJECT STRUCTURE
=====================

Buat struktur project yang bersih.

Pisahkan:

Laravel Backend

Vue Frontend

Tauri Desktop

Camera Bridge

Printer Bridge

Device Services

Shared Types

Jangan mencampur logic hardware ke controller Laravel.

==================================================
37. HARDWARE ABSTRACTION
========================

SANGAT PENTING:

Buat interface hardware.

Contoh:

CameraInterface

CanonAdapter
SonyAdapter
NikonAdapter
WebcamAdapter

PrinterInterface

WindowsPrinterAdapter
ThermalPrinterAdapter
DyeSubPrinterAdapter

AudioInterface

SystemAudioAdapter
BluetoothAudioAdapter
USBAudioAdapter

Dengan demikian perangkat dapat diganti tanpa mengubah UI.

==================================================
38. MOCK DEVICE
===============

Karena development tidak selalu memiliki kamera dan printer fisik, buat:

MockCamera
MockPrinter

Developer dapat menjalankan:

Demo Mode

sehingga:

capture

printer

camera status

dapat disimulasikan.

==================================================
39. INSTALLER
=============

Buat aplikasi installer untuk Windows.

Saat instalasi:

* install application
* register required services
* create local storage
* create database/config
* detect camera
* detect printer
* create desktop shortcut

==================================================
40. AUTO START
==============

Sediakan opsi:

Start Photo Booth automatically with Windows.

Jika aktif:

Windows start
↓
Photo Booth Pro
↓
Device detection
↓
Camera ready
↓
Printer ready
↓
Customer screen

==================================================
41. SETTINGS
============

Settings:

General
Camera
Printer
Audio
Display
Template
Payment
Storage
Network
WebSocket
Kiosk
Event
Backup
Cloud

==================================================
42. NETWORK ARCHITECTURE
========================

Host:

192.168.x.x

Port aplikasi dapat dikonfigurasi.

Remote:

Tablet
Smartphone
Laptop

Semua perangkat pada jaringan lokal dapat berkomunikasi.

Gunakan WebSocket untuk realtime status.

==================================================
43. REALTIME EVENTS
===================

Contoh:

tablet.startSession

host.camera.capture

camera.photoCaptured

host.template.render

host.print.start

printer.printing

printer.completed

tablet.sessionCompleted

==================================================
44. DESIGN SYSTEM
=================

Buat design system:

Primary
Secondary
Background
Surface
Text
Muted
Success
Warning
Danger

Komponen:

Button
Card
Modal
Toast
Drawer
Tabs
Stepper
Progress
DeviceStatus
PhotoSlot
TemplateCard
CameraPreview
PrintQueue
PaymentModal

Semua komponen responsive.

==================================================
45. RESPONSIVE
==============

Desktop:

1920x1080

Tablet:

1280x800
1024x768

Mobile controller:

390x844

UI harus tetap usable.

Untuk customer mode gunakan tombol besar karena pengguna mungkin berdiri beberapa meter dari layar.

==================================================
46. IMPLEMENTATION STRATEGY
===========================

Jangan langsung membuat seluruh aplikasi sekaligus.

Kerjakan secara bertahap:

PHASE 1
Project foundation

PHASE 2
Authentication & RBAC

PHASE 3
Template system

PHASE 4
Photo session

PHASE 5
Camera Bridge

PHASE 6
Printer Bridge

PHASE 7
Payment & promo

PHASE 8
Remote tablet controller

PHASE 9
Event management

PHASE 10
Reports

PHASE 11
Kiosk mode

PHASE 12
Installer

Setiap phase harus menghasilkan aplikasi yang dapat dijalankan.

==================================================
47. PRIORITAS PENGEMBANGAN
==========================

Prioritas paling penting:

1. Camera detection
2. Live view
3. Capture
4. Photo download
5. Template
6. Composition
7. Print
8. Session
9. Payment
10. Remote controller

==================================================
48. CODING RULE
===============

Saya bukan programmer profesional.

Oleh karena itu:

* jangan memberikan potongan kode yang tidak lengkap
* berikan implementasi lengkap
* sertakan semua file yang diperlukan
* jangan meninggalkan TODO penting
* jangan menggunakan pseudocode untuk fitur utama
* gunakan nama variabel yang mudah dipahami
* gunakan komentar kode dalam Bahasa Indonesia
* gunakan Bahasa Indonesia pada label aplikasi
* gunakan clean architecture
* gunakan service class
* gunakan interface
* gunakan repository jika diperlukan
* gunakan validation
* gunakan error handling
* gunakan logging

Jika sebuah library tidak kompatibel, pilih alternatif yang stabil.

Jangan memaksakan library yang sudah deprecated.

==================================================
49. HARDWARE COMPATIBILITY
==========================

Sediakan halaman:

Compatibility Center

yang menjelaskan:

Camera
SDK/API
Operating System
Live View
Remote Capture
Download
Settings Control

Printer
Driver
Paper Size
Status Monitoring

Jangan menganggap semua model kamera memiliki kemampuan yang sama.

Gunakan capability detection.

==================================================
50. HASIL AKHIR
===============

Saya ingin mendapatkan aplikasi PHOTOBOOTH PRO yang terasa seperti produk komersial profesional.

Bukan sekadar:

CRUD Laravel.

Aplikasi harus terasa seperti:

PROFESSIONAL EVENT PHOTO BOOTH SYSTEM.

Ketika customer berdiri di depan booth:

1. memilih template
2. menekan START
3. melihat live camera
4. countdown
5. kamera mengambil foto
6. foto berikutnya diambil
7. semua foto otomatis masuk template
8. hasil final ditampilkan
9. customer memilih PRINT
10. printer mencetak
11. QR Code dapat digunakan untuk mendapatkan versi digital
12. session selesai
13. sistem kembali otomatis ke halaman START

Operator dapat mengontrol semuanya melalui tablet.

Admin dapat mengelola event, template, kamera, printer, harga, promo, transaksi, dan laporan.

==================================================
PERINTAH UNTUK AI CODING AGENT
==============================

Mulai dengan membuat:

1. System Architecture
2. Folder Structure
3. Database ERD
4. Database Migration
5. Backend API
6. Vue UI
7. Tauri Integration
8. Device Abstraction Layer
9. Mock Camera
10. Mock Printer
11. Template Builder
12. Photo Session Engine

Setelah setiap tahap:

* jalankan test
* perbaiki error
* pastikan build berhasil
* pastikan tidak ada import yang rusak
* pastikan migration berhasil
* pastikan frontend berhasil build

Jangan berhenti hanya karena satu fitur hardware belum tersedia.

Untuk hardware yang belum tersedia, gunakan Mock Adapter.

Pastikan ketika hardware nyata tersedia, Mock Adapter dapat diganti dengan adapter kamera/printer nyata tanpa mengubah UI utama.

Buat dokumentasi:

INSTALL.md
HARDWARE.md
CAMERA.md
PRINTER.md
DEPLOYMENT.md
TROUBLESHOOTING.md

Tujuan akhir:

**Satu aplikasi photo booth profesional yang dapat mengendalikan kamera, menyusun template multi-photo, mencetak hasil foto, menerima pembayaran, mengelola event, dan dikontrol melalui touchscreen/tablet.**
