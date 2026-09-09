<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Camera, Printer, Check, X, AlertTriangle, ShieldCheck } from 'lucide-vue-next';

const cameraMatrix = [
    {
        brand: 'Canon EOS (DSLR & Mirrorless)',
        models: 'EOS R6, R5, R8, R50, 200D II, 80D, 90D, 1500D, 3000D',
        sdk: 'Canon EDSDK (Official C++ / Rust Bridge)',
        os: 'Windows 10/11 x64',
        live_view: true,
        remote_capture: true,
        download: true,
        settings_control: true,
        notes: 'Dukungan penuh auto-download dan live stream video 30 FPS.'
    },
    {
        brand: 'Sony Alpha (Mirrorless)',
        models: 'A7 IV, A7 III, A6700, A6400, ZV-E10, ZV-1',
        sdk: 'Sony Camera Remote SDK (APIv2)',
        os: 'Windows 10/11 x64',
        live_view: true,
        remote_capture: true,
        download: true,
        settings_control: true,
        notes: 'Koneksi USB-C PC Remote mode.'
    },
    {
        brand: 'Nikon Z / D-Series',
        models: 'Z5, Z6, Z50, D7500, D5600',
        sdk: 'Nikon SDK / gphoto2 / WIA Driver',
        os: 'Windows 10/11 x64',
        live_view: true,
        remote_capture: true,
        download: true,
        settings_control: true,
        notes: 'Pastikan driver USB terpasang dengan mode PTP.'
    },
    {
        brand: 'Webcam USB / Cam Link',
        models: 'Logitech Brio, C920, C922, Elgato Cam Link 4K',
        sdk: 'DirectShow / MediaFoundation / WebRTC',
        os: 'Windows / Linux / macOS',
        live_view: true,
        remote_capture: true,
        download: true,
        settings_control: false,
        notes: 'Pengaturan ISO/Shutter dilakukan via driver kamera atau otomatis.'
    },
    {
        brand: 'Simulated Mock Camera',
        models: 'Virtual Studio Generator',
        sdk: 'Native PHP / GD Virtual Adapter',
        os: 'Semua Sistem Operasi',
        live_view: true,
        remote_capture: true,
        download: true,
        settings_control: true,
        notes: 'Digunakan untuk demo, testing, dan pengembangan tanpa kamera fisik.'
    }
];

const printerMatrix = [
    {
        brand: 'DNP Photo Printer',
        models: 'DS-RX1HS, DS620A, DS820A, QW410',
        driver: 'DNP Official Windows Driver / Hot Folder',
        sizes: '4R (10x15), 5R, 6R, Strip 2x6',
        monitoring: 'Mendukung pembacaan sisa kertas ribbon secara presisi.',
    },
    {
        brand: 'Citizen Photo Printer',
        models: 'CY-02, OP900II, CX-02',
        driver: 'Citizen Windows Spooler Driver',
        sizes: '4R, 5R, 6R',
        monitoring: 'Deteksi status paper jam dan status siap cetak.',
    },
    {
        brand: 'Epson SureLab',
        models: 'SL-D1070, SL-D870, SL-D700',
        driver: 'Epson SureLab Windows Driver',
        sizes: '4R, 5R, 6R, A4, Roll Paper',
        monitoring: 'Ink status & paper roll monitoring.',
    },
    {
        brand: 'POS Thermal Printer',
        models: '80mm Thermal Receipt / Sticker Printer',
        driver: 'ESC/POS Driver',
        sizes: '80mm Continuous Strip',
        monitoring: 'Deteksi ketiadaan kertas thermal roll.',
    }
];
</script>

<template>
    <AdminLayout>
        <div class="space-y-8">
            <div>
                <h2 class="text-2xl font-black text-white">Pusat Kompatibilitas Hardware</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Daftar resmi kapabilitas kamera, printer foto, dan driver yang didukung oleh PHOTOBOOTH PRO
                </p>
            </div>

            <!-- CAMERA COMPATIBILITY TABLE -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10">
                <div class="flex items-center gap-3 mb-4">
                    <Camera class="w-6 h-6 text-amber-400" />
                    <div>
                        <h3 class="font-bold text-white text-base">Matriks Kompatibilitas Kamera</h3>
                        <p class="text-xs text-slate-400">Dukungan fitur Live View, Remote Capture, dan Kontrol Parameter</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/10">
                                <th class="pb-3 font-semibold">MERK / TIPE</th>
                                <th class="pb-3 font-semibold">MODEL POPULER</th>
                                <th class="pb-3 font-semibold">SDK / API</th>
                                <th class="pb-3 font-semibold text-center">LIVE VIEW</th>
                                <th class="pb-3 font-semibold text-center">REMOTE CAPTURE</th>
                                <th class="pb-3 font-semibold text-center">DOWNLOAD</th>
                                <th class="pb-3 font-semibold text-center">KONTROL ISO/SHUTTER</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-200">
                            <tr v-for="(c, idx) in cameraMatrix" :key="idx" class="hover:bg-white/5">
                                <td class="py-3.5 font-bold text-white">{{ c.brand }}</td>
                                <td class="py-3.5 text-slate-300">{{ c.models }}</td>
                                <td class="py-3.5 font-mono text-[11px] text-slate-400">{{ c.sdk }}</td>
                                <td class="py-3.5 text-center">
                                    <Check v-if="c.live_view" class="w-4 h-4 text-emerald-400 mx-auto" />
                                    <X v-else class="w-4 h-4 text-rose-400 mx-auto" />
                                </td>
                                <td class="py-3.5 text-center">
                                    <Check v-if="c.remote_capture" class="w-4 h-4 text-emerald-400 mx-auto" />
                                    <X v-else class="w-4 h-4 text-rose-400 mx-auto" />
                                </td>
                                <td class="py-3.5 text-center">
                                    <Check v-if="c.download" class="w-4 h-4 text-emerald-400 mx-auto" />
                                    <X v-else class="w-4 h-4 text-rose-400 mx-auto" />
                                </td>
                                <td class="py-3.5 text-center">
                                    <Check v-if="c.settings_control" class="w-4 h-4 text-emerald-400 mx-auto" />
                                    <span v-else class="text-[10px] text-slate-500 font-bold">Auto</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRINTER COMPATIBILITY TABLE -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10">
                <div class="flex items-center gap-3 mb-4">
                    <Printer class="w-6 h-6 text-sky-400" />
                    <div>
                        <h3 class="font-bold text-white text-base">Matriks Kompatibilitas Printer Foto</h3>
                        <p class="text-xs text-slate-400">Dye-sublimation, Inkjet Lab, dan Thermal</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/10">
                                <th class="pb-3 font-semibold">MERK</th>
                                <th class="pb-3 font-semibold">MODEL YANG DIDUKUNG</th>
                                <th class="pb-3 font-semibold">DRIVER / PROTOKOL</th>
                                <th class="pb-3 font-semibold">UKURAN KERTAS</th>
                                <th class="pb-3 font-semibold">MONITORING STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-200">
                            <tr v-for="(p, idx) in printerMatrix" :key="idx" class="hover:bg-white/5">
                                <td class="py-3.5 font-bold text-white">{{ p.brand }}</td>
                                <td class="py-3.5 text-slate-300">{{ p.models }}</td>
                                <td class="py-3.5 font-mono text-[11px] text-slate-400">{{ p.driver }}</td>
                                <td class="py-3.5 text-amber-300 font-medium">{{ p.sizes }}</td>
                                <td class="py-3.5 text-slate-400 text-[11px]">{{ p.monitoring }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>