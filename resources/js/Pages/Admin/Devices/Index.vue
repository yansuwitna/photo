<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import CameraTestModal from '@/Components/CameraTestModal.vue';
import DeviceLockModal from '@/Components/DeviceLockModal.vue';
import { 
    Camera, 
    Printer, 
    Monitor, 
    Volume2, 
    Play, 
    CheckCircle2, 
    RefreshCw,
    Sliders,
    Layers,
    Sparkles,
    Lock,
    Unlock,
    Save
} from 'lucide-vue-next';
import { useDeviceStore } from '@/stores/deviceStore';
import { useAudioStore } from '@/stores/audioStore';
import { showToast, showSuccess, showError } from '@/utils/swal';

const props = defineProps<{
    cameras?: any[];
    printers?: any[];
    activeCamera?: any;
    activePrinter?: any;
    activePaperSize?: string;
    isLocked?: boolean;
    devices?: any[];
}>();

const deviceStore = useDeviceStore();
const audioStore = useAudioStore();

const testResult = ref<string>('');
const isTestingCamera = ref(false);
const isTestingPrinter = ref(false);

const isLocked = ref(props.isLocked ?? false);
const selectedCameraId = ref(props.activeCamera?.id ?? props.cameras?.[0]?.id ?? 1);
const selectedPrinterId = ref(props.activePrinter?.id ?? props.printers?.[0]?.id ?? 1);
const selectedPaperSize = ref(props.activePaperSize ?? props.activePrinter?.default_paper_size ?? '4R');
const showUnlockModal = ref(false);
const isSavingDevices = ref(false);
const isSyncingPrinters = ref(false);

const showCameraModal = ref(false);
const cameraTestResult = ref<{
    image_url?: string;
    camera?: string;
    width?: number;
    height?: number;
    metadata?: any;
} | null>(null);

async function handleSaveDeviceSettings() {
    if (isLocked.value) {
        showError('Pengaturan Terkunci', 'Silakan buka kunci terlebih dahulu untuk mengubah perangkat.');
        return;
    }
    isSavingDevices.value = true;
    try {
        const res = await deviceStore.selectDevices(
            selectedCameraId.value,
            selectedPrinterId.value,
            selectedPaperSize.value
        );
        if (res.success) {
            showSuccess('Pengaturan Disimpan', 'Kamera dan printer aktif berhasil diperbarui.');
            await deviceStore.fetchStatus();
        } else {
            showError('Gagal Menyimpan', res.message);
        }
    } catch (e: any) {
        showError('Gagal Menyimpan', e?.message || 'Terjadi kesalahan sistem.');
    } finally {
        isSavingDevices.value = false;
    }
}

async function handleToggleLock() {
    if (isLocked.value) {
        showUnlockModal.value = true;
    } else {
        const res = await deviceStore.lock();
        if (res.success) {
            isLocked.value = true;
            showSuccess('Pengaturan Terkunci', 'Pengaturan kamera & printer dikunci untuk keamanan event.');
        } else {
            showError('Gagal Mengunci', res.message);
        }
    }
}

function onUnlocked() {
    isLocked.value = false;
}

async function handleSyncPrinters() {
    isSyncingPrinters.value = true;
    try {
        const res = await deviceStore.syncPrinters();
        if (res.success) {
            showSuccess('Sinkronisasi Sukses', 'Daftar printer Windows berhasil diperbarui.');
        } else {
            showError('Gagal Sinkronisasi', res.message);
        }
    } catch (e: any) {
        showError('Error Sinkronisasi', e?.message || 'Gagal');
    } finally {
        isSyncingPrinters.value = false;
    }
}

async function handleTestCamera() {
    isTestingCamera.value = true;
    showCameraModal.value = true;
    testResult.value = 'Menjalankan simulasi remote capture kamera...';
    try {
        const res = await deviceStore.testCamera();
        if (res.success) {
            cameraTestResult.value = {
                image_url: res.image_url,
                camera: res.camera || deviceStore.camera.name,
                width: res.width,
                height: res.height,
                metadata: res.metadata,
            };
            testResult.value = res.message || 'Uji kamera berhasil.';
            showToast(res.message || 'Uji kamera berhasil.', 'success');
        } else {
            testResult.value = res.message || 'Uji kamera gagal.';
            showToast(res.message || 'Uji kamera gagal.', 'error');
            showCameraModal.value = false;
        }
    } catch (e: any) {
        showToast(e?.message || 'Uji kamera gagal.', 'error');
        showCameraModal.value = false;
    } finally {
        isTestingCamera.value = false;
    }
}

async function handleTestPrinter() {
    isTestingPrinter.value = true;
    testResult.value = 'Mengirim pekerjaan cetak uji coba ke printer...';
    const res = await deviceStore.testPrinter();
    testResult.value = res.message || (res.success ? 'Uji printer berhasil.' : 'Uji printer gagal.');
    isTestingPrinter.value = false;
    if (res.success) {
        showToast(res.message || 'Uji printer berhasil.', 'success');
    } else {
        showToast(res.message || 'Uji printer gagal.', 'error');
    }
}

function testSound(type: string) {
    switch (type) {
        case 'countdown':
            audioStore.playCountdown(3);
            break;
        case 'shutter':
            audioStore.playShutter();
            break;
        case 'smile':
            audioStore.playSmile();
            break;
        case 'success':
            audioStore.playSuccess();
            break;
    }
}

function testDisplay() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        document.documentElement.requestFullscreen();
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">DEVICE CENTER (Pusat Perangkat)</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Pusat kendali, diagnosis, dan pengujian perangkat keras photo booth
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/devices/compatibility"
                        class="py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-slate-300 text-xs font-semibold border border-white/10"
                    >
                        Matriks Kompatibilitas Hardware &rarr;
                    </Link>
                </div>
            </div>

            <div v-if="testResult" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center justify-between">
                <span>{{ testResult }}</span>
                <button @click="testResult = ''" class="text-amber-400 font-bold hover:underline">Tutup</button>
            </div>

            <!-- PANEL PENGATURAN & KUNCI PERANGKAT -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 shadow-2xl relative overflow-hidden">
                <div
                    class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r transition-all"
                    :class="isLocked ? 'from-emerald-500 via-amber-400 to-emerald-500' : 'from-blue-500 via-sky-400 to-blue-500'"
                ></div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-white/10 mb-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center border shadow-lg transition-all"
                            :class="isLocked ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-sky-500/20 text-sky-400 border-sky-500/30'"
                        >
                            <Lock v-if="isLocked" class="w-6 h-6" />
                            <Unlock v-else class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-base md:text-lg font-black text-white">PENGATURAN & KUNCI PERANGKAT</h3>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border flex items-center gap-1.5"
                                    :class="isLocked ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border-amber-500/40'"
                                >
                                    <span class="w-2 h-2 rounded-full" :class="isLocked ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                                    <span>{{ isLocked ? 'TERKUNCI (Aman untuk Event)' : 'TERBUKA (Bisa Diubah)' }}</span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Pilih kamera dan printer aktif (bisa printer biasa seperti Epson L-series atau dye-sublimation). Kunci agar operator tidak dapat mengubah sembarangan saat acara.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            @click="handleSyncPrinters"
                            :disabled="isSyncingPrinters || isLocked"
                            class="py-2.5 px-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-slate-300 text-xs font-semibold flex items-center gap-1.5 border border-white/10 transition-all disabled:opacity-50"
                            title="Deteksi printer yang terpasang di sistem Windows"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isSyncingPrinters }" />
                            <span>Deteksi Printer Windows</span>
                        </button>

                        <button
                            @click="handleToggleLock"
                            class="py-2.5 px-4 rounded-xl text-xs font-black flex items-center gap-2 shadow-lg transition-all active:scale-95"
                            :class="isLocked ? 'bg-amber-400 hover:bg-amber-300 text-slate-950' : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'"
                        >
                            <Unlock v-if="isLocked" class="w-4 h-4" />
                            <Lock v-else class="w-4 h-4" />
                            <span>{{ isLocked ? 'Buka Kunci Pengaturan' : 'Kunci Pengaturan Sekarang' }}</span>
                        </button>
                    </div>
                </div>

                <!-- FORM PILIHAN PERANGKAT -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- 1. KAMERA -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Kamera Aktif:</span>
                            <span v-if="isLocked" class="text-[10px] text-amber-400 font-normal flex items-center gap-1">
                                <Lock class="w-3 h-3" /> Terkunci
                            </span>
                        </label>
                        <select
                            v-model="selectedCameraId"
                            :disabled="isLocked"
                            class="w-full px-3.5 py-3 rounded-2xl bg-black/60 border text-xs font-semibold focus:outline-none transition-all"
                            :class="isLocked ? 'border-white/5 text-slate-400 cursor-not-allowed bg-slate-950/60' : 'border-white/20 text-white focus:border-amber-400 hover:border-white/30'"
                        >
                            <option v-for="cam in (deviceStore.cameras?.length ? deviceStore.cameras : props.cameras)" :key="cam.id" :value="cam.id">
                                {{ cam.name }} ({{ cam.brand }})
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Pilihan kamera DSLR, Mirrorless, atau USB Cam.
                        </p>
                    </div>

                    <!-- 2. PRINTER (MENDUKUNG PRINTER BIASA) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Printer Aktif (Bisa Printer Biasa):</span>
                            <span v-if="isLocked" class="text-[10px] text-amber-400 font-normal flex items-center gap-1">
                                <Lock class="w-3 h-3" /> Terkunci
                            </span>
                        </label>
                        <select
                            v-model="selectedPrinterId"
                            :disabled="isLocked"
                            class="w-full px-3.5 py-3 rounded-2xl bg-black/60 border text-xs font-semibold focus:outline-none transition-all"
                            :class="isLocked ? 'border-white/5 text-slate-400 cursor-not-allowed bg-slate-950/60' : 'border-white/20 text-white focus:border-amber-400 hover:border-white/30'"
                        >
                            <option v-for="pr in (deviceStore.printers?.length ? deviceStore.printers : props.printers)" :key="pr.id" :value="pr.id">
                                {{ pr.name }} • {{ pr.adapter === 'windows' ? 'Printer Biasa (Windows Driver)' : 'Dye-Sub Pro' }}
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Mendukung printer biasa (Epson L1210 / Inkjet) & printer foto khusus.
                        </p>
                    </div>

                    <!-- 3. UKURAN KERTAS & SIMPAN -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Ukuran Kertas Default:</span>
                            <span v-if="isLocked" class="text-[10px] text-amber-400 font-normal flex items-center gap-1">
                                <Lock class="w-3 h-3" /> Terkunci
                            </span>
                        </label>
                        <div class="flex gap-2">
                            <select
                                v-model="selectedPaperSize"
                                :disabled="isLocked"
                                class="flex-1 px-3.5 py-3 rounded-2xl bg-black/60 border text-xs font-semibold focus:outline-none transition-all"
                                :class="isLocked ? 'border-white/5 text-slate-400 cursor-not-allowed bg-slate-950/60' : 'border-white/20 text-white focus:border-amber-400 hover:border-white/30'"
                            >
                                <option value="4R">4R (4x6 inci / 10x15 cm) - Standar Foto</option>
                                <option value="A4">A4 (21 x 29.7 cm - Kertas Biasa)</option>
                                <option value="Strip 2x6">Strip 2x6 inci (Photostrip)</option>
                                <option value="5R">5R (5 x 7 inci)</option>
                            </select>

                            <button
                                @click="handleSaveDeviceSettings"
                                :disabled="isLocked || isSavingDevices"
                                class="py-3 px-5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs shadow-lg transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 shrink-0"
                            >
                                <Save class="w-4 h-4" />
                                <span>{{ isSavingDevices ? 'Menyimpan...' : 'Simpan' }}</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Sesuaikan dengan kertas yang terpasang di tray printer.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 4 HARDWARE TILES -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. CAMERA CENTER -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center border border-amber-400/30">
                                    <Camera class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-amber-400 uppercase">Hardware 01</span>
                                    <h3 class="text-lg font-black text-white">KAMERA (DSLR / Mirrorless / USB)</h3>
                                </div>
                            </div>
                            <DeviceStatusBadge :status="deviceStore.camera.status" />
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Model Terdeteksi</span>
                                <span class="font-bold text-white">{{ deviceStore.camera.name }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Tingkat Baterai</span>
                                <span class="font-bold text-emerald-400 font-mono">{{ deviceStore.camera.battery_level }}%</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Status Penyimpanan</span>
                                <span class="font-bold text-sky-400 font-mono">{{ deviceStore.camera.storage_remaining }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">ISO / Shutter / Aperture</span>
                                <span class="font-mono text-amber-300">ISO {{ deviceStore.camera.iso }} • {{ deviceStore.camera.shutter_speed }} • {{ deviceStore.camera.aperture }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/10 flex gap-3">
                        <button
                            @click="handleTestCamera"
                            :disabled="isTestingCamera"
                            class="flex-1 py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-lg transition-all active:scale-95"
                        >
                            {{ isTestingCamera ? 'Menguji...' : 'Test Kamera' }}
                        </button>
                    </div>
                </div>

                <!-- 2. PRINTER CENTER -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-sky-400/20 text-sky-400 flex items-center justify-center border border-sky-400/30">
                                    <Printer class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-sky-400 uppercase">Hardware 02</span>
                                    <h3 class="text-lg font-black text-white">PRINTER FOTO PRO</h3>
                                </div>
                            </div>
                            <DeviceStatusBadge :status="deviceStore.printer.status" />
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Model Printer</span>
                                <span class="font-bold text-white">{{ deviceStore.printer.name }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Sisa Kertas</span>
                                <span class="font-bold text-white font-mono">{{ deviceStore.printer.paper_remaining }} Lembar</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Kondisi Kertas</span>
                                <span class="font-bold text-emerald-400">{{ deviceStore.printer.paper_status }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Format Kertas Bawaan</span>
                                <span class="font-mono text-slate-300">4R (10x15 cm) Dye-Sublimation</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/10 flex gap-3">
                        <button
                            @click="handleTestPrinter"
                            :disabled="isTestingPrinter"
                            class="flex-1 py-3 rounded-xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-bold text-xs shadow-lg transition-all active:scale-95"
                        >
                            {{ isTestingPrinter ? 'Mencetak...' : 'Test Printer' }}
                        </button>
                    </div>
                </div>

                <!-- 3. AUDIO SYSTEM -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-purple-400/20 text-purple-400 flex items-center justify-center border border-purple-400/30">
                                    <Volume2 class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-purple-400 uppercase">Hardware 03</span>
                                    <h3 class="text-lg font-black text-white">SISTEM AUDIO & SUARA</h3>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400">
                                READY
                            </span>
                        </div>

                        <p class="text-xs text-slate-400 mb-4">
                            Uji coba suara instruksi, suara hitung mundur, klik shutter, dan suara selebrasi sukses.
                        </p>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="testSound('countdown')"
                                class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-slate-200"
                            >
                                🔔 Beep Countdown
                            </button>
                            <button
                                @click="testSound('smile')"
                                class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-slate-200"
                            >
                                🗣️ Suara "SMILE!"
                            </button>
                            <button
                                @click="testSound('shutter')"
                                class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-slate-200"
                            >
                                📸 Shutter Mekanis
                            </button>
                            <button
                                @click="testSound('success')"
                                class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-slate-200"
                            >
                                🎉 Chime Sukses
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/10">
                        <button
                            @click="audioStore.speakInstruction('Uji coba speaker berhasil.')"
                            class="w-full py-3 rounded-xl bg-purple-500 hover:bg-purple-400 text-white font-bold text-xs shadow-lg transition-all active:scale-95"
                        >
                            Test Speaker / Audio
                        </button>
                    </div>
                </div>

                <!-- 4. DISPLAY & TOUCHSCREEN -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-400/20 text-emerald-400 flex items-center justify-center border border-emerald-400/30">
                                    <Monitor class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-emerald-400 uppercase">Hardware 04</span>
                                    <h3 class="text-lg font-black text-white">DISPLAY & TOUCHSCREEN</h3>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400">
                                CONNECTED
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Resolusi Monitor</span>
                                <span class="font-bold text-white font-mono">1920 x 1080 (Full HD)</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Dukungan Layar Sentuh</span>
                                <span class="font-bold text-emerald-400">Aktif (Touchscreen Friendly)</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-400">Kiosk Fullscreen</span>
                                <span class="font-bold text-amber-300">Siap Kiosk Mode</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/10">
                        <button
                            @click="testDisplay"
                            class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-all active:scale-95"
                        >
                            Test Display / Toggle Fullscreen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL HASIL UJI KAMERA -->
        <CameraTestModal
            :show="showCameraModal"
            :isLoading="isTestingCamera"
            :imageUrl="cameraTestResult?.image_url"
            :cameraName="cameraTestResult?.camera || deviceStore.camera.name"
            :width="cameraTestResult?.width"
            :height="cameraTestResult?.height"
            :metadata="cameraTestResult?.metadata"
            @close="showCameraModal = false"
            @retake="handleTestCamera"
        />

        <!-- MODAL BUKA KUNCI PERANGKAT -->
        <DeviceLockModal
            :show="showUnlockModal"
            @close="showUnlockModal = false"
            @unlocked="onUnlocked"
        />
    </AdminLayout>
</template>