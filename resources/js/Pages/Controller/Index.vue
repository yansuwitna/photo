<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    Camera, 
    Printer, 
    RotateCcw, 
    Play, 
    RefreshCw, 
    Layers, 
    CheckCircle2, 
    AlertCircle, 
    Battery, 
    HardDrive,
    Tablet,
    Settings,
    Home,
    Sparkles,
    Lock,
    Unlock,
    Save,
    ChevronDown,
    ChevronUp
} from 'lucide-vue-next';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import CameraTestModal from '@/Components/CameraTestModal.vue';
import DeviceLockModal from '@/Components/DeviceLockModal.vue';
import ControllerCaptureModal from '@/Components/ControllerCaptureModal.vue';
import { useDeviceStore } from '@/stores/deviceStore';
import { getAssetUrl } from '@/utils/url';
import axios from 'axios';
import { showSuccess, showError, showInfo } from '@/utils/swal';

const props = defineProps<{
    activeSession?: any;
    templates: any[];
    cameras?: any[];
    printers?: any[];
    activeCamera?: any;
    activePrinter?: any;
    activePaperSize?: string;
    isLocked?: boolean;
    todayStats: {
        total_sessions: number;
        total_prints: number;
        revenue: number;
    };
}>();

const deviceStore = useDeviceStore();

const activeSession = ref<any>(props.activeSession);
const isStarting = ref(false);
const isPrinting = ref(false);
const isCapturingSlot = ref<number | null>(null);
const isComposing = ref(false);
const selectedTemplateId = ref<number>(props.templates[0]?.id || 1);

// State Pengaturan Kamera & Printer
const isLocked = ref(props.isLocked ?? false);
const selectedCameraId = ref(props.activeCamera?.id ?? props.cameras?.[0]?.id ?? 1);
const selectedPrinterId = ref(props.activePrinter?.id ?? props.printers?.[0]?.id ?? 1);
const selectedPaperSize = ref(props.activePaperSize ?? props.activePrinter?.default_paper_size ?? '4R');
const showUnlockModal = ref(false);
const isSavingDevices = ref(false);
const showDeviceSettings = ref(false);

// State untuk Modal Hasil Uji Kamera
const showCameraModal = ref(false);
const isTestingCamera = ref(false);
const cameraTestResult = ref<any>(null);

// State untuk Modal Viewfinder Kiosk Capture
const showCaptureModal = ref(false);
const currentCaptureSlot = ref<number>(1);
const photoTimestamp = ref(Date.now());

onMounted(() => {
    deviceStore.fetchStatus();
    setInterval(async () => {
        await refreshData();
    }, 5000);
});

async function refreshData() {
    await deviceStore.fetchStatus();
    try {
        const res = await axios.get('/api/controller/status');
        if (res.data) {
            activeSession.value = res.data.active_session;
            if (res.data.is_locked !== undefined) {
                isLocked.value = res.data.is_locked;
            }
            if (res.data.active_camera) {
                selectedCameraId.value = res.data.active_camera.id;
            }
            if (res.data.active_printer) {
                selectedPrinterId.value = res.data.active_printer.id;
            }
            if (res.data.active_paper_size) {
                selectedPaperSize.value = res.data.active_paper_size;
            } else if (res.data.active_printer) {
                selectedPaperSize.value = res.data.active_printer.default_paper_size || '4R';
            }
        }
    } catch (e) {}
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

async function handleSaveDeviceSettings() {
    if (isLocked.value) {
        showError('Pengaturan Terkunci', 'Silakan buka kunci terlebih dahulu.');
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

async function startNewSession() {
    isStarting.value = true;
    try {
        const res = await axios.post('/api/session/start', {
            template_id: selectedTemplateId.value,
        });
        if (res.data.success) {
            activeSession.value = res.data.session;
        }
    } catch (e) {
        showError('Gagal Memulai Sesi', 'Tidak dapat memulai sesi dari controller.');
    } finally {
        isStarting.value = false;
    }
}

function triggerRemoteCapture(slot: number) {
    if (!activeSession.value) return;
    currentCaptureSlot.value = slot;
    showCaptureModal.value = true;
}

function onModalCaptured(data: { session: any; photo: any; slotIndex: number; isComplete: boolean }) {
    activeSession.value = data.session;
    photoTimestamp.value = Date.now();
}

async function onModalCompose() {
    await autoCompose();
}

function onModalNextSlot(nextSlot: number) {
    currentCaptureSlot.value = nextSlot;
}

async function autoCompose() {
    if (!activeSession.value) return;
    isComposing.value = true;
    try {
        const res = await axios.post(`/api/session/${activeSession.value.id}/compose`);
        if (res.data.success) {
            activeSession.value = res.data.session;
            showSuccess('Layout Selesai', 'Foto strip berhasil disusun dan siap dicetak.');
        }
    } catch (e) {
        console.error('Auto compose error:', e);
    } finally {
        isComposing.value = false;
    }
}

async function triggerRemotePrint() {
    if (!activeSession.value) return;
    isPrinting.value = true;
    try {
        const res = await axios.post(`/api/session/${activeSession.value.id}/print`, {
            copies: 1,
        });
        if (res.data.success) {
            showSuccess('Perintah Cetak Terkirim', 'Printer sedang memproses pencetakan foto.');
            await refreshData();
        } else {
            showError('Gagal Cetak', res.data.message || 'Printer mengalami kendala.');
        }
    } catch (e: any) {
        showError('Error Cetak', e.response?.data?.message || 'Koneksi ke printer terputus.');
    } finally {
        isPrinting.value = false;
    }
}

async function runCameraTest() {
    isTestingCamera.value = true;
    showCameraModal.value = true;
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
        } else {
            showError('Uji Kamera Gagal', res.message || 'Gagal mengambil foto uji kamera.');
            showCameraModal.value = false;
        }
    } catch (e: any) {
        showError('Uji Kamera Gagal', e?.message || 'Terjadi kesalahan sistem.');
        showCameraModal.value = false;
    } finally {
        isTestingCamera.value = false;
    }
}

async function runPrinterTest() {
    const res = await deviceStore.testPrinter();
    showInfo('Uji Printer', res.message || 'Uji printer selesai.');
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col p-4 md:p-6 select-none font-sans">
        <!-- TOP HEADER -->
        <header class="flex items-center justify-between pb-4 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-400">
                    <Tablet class="w-5 h-5" />
                </div>
                <div>
                    <h1 class="text-base md:text-lg font-black text-white">REMOTE OPERATOR CONTROLLER</h1>
                    <p class="text-xs text-slate-400">Panel Kontrol Nirkabel Tablet / Operator Booth</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="refreshData"
                    class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300"
                    title="Refresh"
                >
                    <RefreshCw class="w-4 h-4" />
                </button>
                <button
                    @click="router.visit('/print-station')"
                    class="py-2 px-3 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-1.5 transition-all"
                    title="Buka Web Print Station untuk cetak otomatis di PC ini"
                >
                    <Printer class="w-3.5 h-3.5 text-amber-400" />
                    <span>Print Station</span>
                </button>
                <button
                    @click="router.visit('/')"
                    class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 text-xs font-semibold flex items-center gap-1.5"
                >
                    <Home class="w-3.5 h-3.5" />
                    <span>Kiosk Layar</span>
                </button>
            </div>
        </header>

        <!-- PENGATURAN KAMERA & PRINTER OPERATOR -->
        <div class="my-3 p-3.5 rounded-2xl bg-slate-900 border border-white/10 flex flex-col gap-3 shadow-lg">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <!-- Lock Status Pill -->
                    <span
                        class="px-2.5 py-1 rounded-xl font-bold flex items-center gap-1.5 border"
                        :class="isLocked ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30'"
                    >
                        <Lock v-if="isLocked" class="w-3.5 h-3.5" />
                        <Unlock v-else class="w-3.5 h-3.5" />
                        <span>{{ isLocked ? 'Perangkat Terkunci' : 'Perangkat Terbuka' }}</span>
                    </span>

                    <!-- Active Camera Pill -->
                    <span class="px-2.5 py-1 rounded-xl bg-white/5 border border-white/10 text-slate-300 flex items-center gap-1.5">
                        <Camera class="w-3.5 h-3.5 text-amber-400" />
                        <span class="text-white font-semibold">{{ deviceStore.camera.name }}</span>
                    </span>

                    <!-- Active Printer Pill -->
                    <span class="px-2.5 py-1 rounded-xl bg-white/5 border border-white/10 text-slate-300 flex items-center gap-1.5">
                        <Printer class="w-3.5 h-3.5 text-sky-400" />
                        <span class="text-white font-semibold">{{ deviceStore.printer.name }}</span>
                        <span class="text-[10px] text-slate-400">({{ selectedPaperSize }})</span>
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="showDeviceSettings = !showDeviceSettings"
                        class="py-1.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-xs text-slate-300 font-semibold flex items-center gap-1.5 border border-white/10 transition-all"
                    >
                        <Settings class="w-3.5 h-3.5" />
                        <span>{{ showDeviceSettings ? 'Tutup Pengaturan' : 'Ubah Kamera & Printer' }}</span>
                        <ChevronUp v-if="showDeviceSettings" class="w-3 h-3" />
                        <ChevronDown v-else class="w-3 h-3" />
                    </button>

                    <button
                        @click="handleToggleLock"
                        class="py-1.5 px-3 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow active:scale-95"
                        :class="isLocked ? 'bg-amber-400 hover:bg-amber-300 text-slate-950' : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'"
                    >
                        <Unlock v-if="isLocked" class="w-3.5 h-3.5" />
                        <Lock v-else class="w-3.5 h-3.5" />
                        <span>{{ isLocked ? 'Buka Kunci (PIN)' : 'Kunci Sekarang' }}</span>
                    </button>
                </div>
            </div>

            <!-- Expandable Drawer for Changing Camera & Printer -->
            <div v-if="showDeviceSettings" class="pt-3 border-t border-white/10 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Pilih Kamera:</label>
                    <select
                        v-model="selectedCameraId"
                        :disabled="isLocked"
                        class="w-full px-3 py-2 rounded-xl bg-black/50 border text-xs font-medium focus:outline-none transition-all"
                        :class="isLocked ? 'border-white/5 text-slate-500 cursor-not-allowed' : 'border-white/10 text-white focus:border-amber-400'"
                    >
                        <option v-for="c in (deviceStore.cameras?.length ? deviceStore.cameras : props.cameras)" :key="c.id" :value="c.id">
                            {{ c.name }} ({{ c.brand }})
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Pilih Printer (Bisa Printer Biasa):</label>
                    <select
                        v-model="selectedPrinterId"
                        :disabled="isLocked"
                        class="w-full px-3 py-2 rounded-xl bg-black/50 border text-xs font-medium focus:outline-none transition-all"
                        :class="isLocked ? 'border-white/5 text-slate-500 cursor-not-allowed' : 'border-white/10 text-white focus:border-amber-400'"
                    >
                        <option v-for="p in (deviceStore.printers?.length ? deviceStore.printers : props.printers)" :key="p.id" :value="p.id">
                            {{ p.name }} • {{ p.adapter === 'windows' ? 'Printer Biasa' : 'Dye-Sub' }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Ukuran Kertas:</label>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedPaperSize"
                            :disabled="isLocked"
                            class="flex-1 px-3 py-2 rounded-xl bg-black/50 border text-xs font-medium focus:outline-none transition-all"
                            :class="isLocked ? 'border-white/5 text-slate-500 cursor-not-allowed' : 'border-white/10 text-white focus:border-amber-400'"
                        >
                            <option value="4R">4R (4x6 inci / Foto)</option>
                            <option value="A4">A4 (Kertas Biasa)</option>
                            <option value="Strip 2x6">Strip 2x6</option>
                            <option value="5R">5R</option>
                        </select>

                        <button
                            @click="handleSaveDeviceSettings"
                            :disabled="isLocked || isSavingDevices"
                            class="py-2 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow flex items-center gap-1.5 transition-all disabled:opacity-40"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ isSavingDevices ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- DEVICE STATUS TILES -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <!-- Camera Tile -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Kamera</span>
                    <DeviceStatusBadge :status="deviceStore.camera.status" size="sm" :showText="false" />
                </div>
                <div class="my-2">
                    <p class="text-xs font-bold text-white truncate">{{ deviceStore.camera.name }}</p>
                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                        Baterai: {{ deviceStore.camera.battery_level }}% • {{ deviceStore.camera.storage_remaining }}
                    </p>
                </div>
                <button
                    @click="runCameraTest"
                    class="py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-slate-300"
                >
                    Uji Jepret Kamera
                </button>
            </div>

            <!-- Printer Tile -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Printer</span>
                    <DeviceStatusBadge :status="deviceStore.printer.status" size="sm" :showText="false" />
                </div>
                <div class="my-2">
                    <p class="text-xs font-bold text-white truncate">{{ deviceStore.printer.name }}</p>
                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                        Sisa Kertas: {{ deviceStore.printer.paper_remaining }} lbr ({{ deviceStore.printer.paper_status }})
                    </p>
                </div>
                <button
                    @click="runPrinterTest"
                    class="py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-slate-300"
                >
                    Uji Print Test Page
                </button>
            </div>

            <!-- Total Sesi Hari Ini -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase">Sesi Hari Ini</span>
                <p class="text-2xl font-black text-amber-400 font-mono my-1">
                    {{ todayStats.total_sessions }}
                </p>
                <span class="text-[10px] text-slate-400">Total lembar cetak: {{ todayStats.total_prints }}</span>
            </div>

            <!-- Pendapatan -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase">Pendapatan Hari Ini</span>
                <p class="text-2xl font-black text-emerald-400 font-mono my-1">
                    Rp {{ (todayStats.revenue || 0).toLocaleString('id-ID') }}
                </p>
                <span class="text-[10px] text-emerald-300/80">Transaksi Lunas</span>
            </div>
        </div>

        <!-- MAIN OPERATOR WORKSPACE -->
        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- LEFT PANEL: START SESSION & TEMPLATES -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3">Mulai Sesi Baru</h3>
                    <label class="text-xs text-slate-400 block mb-2">Pilih Template Foto:</label>
                    <select
                        v-model="selectedTemplateId"
                        class="w-full px-3 py-2.5 rounded-xl bg-black/50 border border-white/10 text-white text-xs font-medium mb-4 focus:outline-none focus:border-amber-400"
                    >
                        <option v-for="t in templates" :key="t.id" :value="t.id">
                            {{ t.name }} ({{ t.photo_count }} Foto - {{ t.paper_size }})
                        </option>
                    </select>

                    <button
                        @click="startNewSession"
                        :disabled="isStarting"
                        class="w-full py-4 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-all"
                    >
                        <Play class="w-5 h-5 fill-slate-950" />
                        <span>{{ isStarting ? 'MEMULAI...' : 'MULAI SESI FOTO' }}</span>
                    </button>
                </div>

                <div class="mt-6 p-4 rounded-2xl bg-black/40 border border-white/5 text-xs text-slate-400 leading-relaxed">
                    <p class="font-bold text-slate-300 mb-1">Tips Operator:</p>
                    Gunakan tombol "Ambil Ulang" jika tamu berkedip atau ingin berpose kembali tanpa membatalkan foto yang lain.
                </div>
            </div>

            <!-- CENTER & RIGHT: ACTIVE SESSION MONITOR -->
            <div class="md:col-span-2 p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col">
                <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                    <div>
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Status Sesi Aktif</span>
                        <h2 class="text-lg font-black text-white">
                            {{ activeSession ? activeSession.session_code : 'Tidak Ada Sesi Berjalan' }}
                        </h2>
                    </div>

                    <div v-if="activeSession" class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/10 border border-white/15 text-slate-200 uppercase">
                            Step: {{ activeSession.current_step }}
                        </span>
                    </div>
                </div>

                <!-- If Session is Active -->
                <template v-if="activeSession">
                    <!-- Session Photos Grid -->
                    <div class="flex-1 overflow-y-auto mb-4">
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            <div
                                v-for="slot in (activeSession.total_photos_required || 3)"
                                :key="slot"
                                class="relative aspect-[3/4] rounded-xl bg-black/50 border border-white/10 overflow-hidden flex flex-col justify-between p-2 group"
                            >
                                <div class="flex items-center justify-between z-10">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-black/60 text-slate-300">
                                        Foto {{ slot }}
                                    </span>
                                    <span
                                        v-if="isCapturingSlot === slot"
                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 animate-pulse"
                                    >
                                        Memproses...
                                    </span>
                                </div>
                                
                                <template v-if="activeSession.photos?.find((p: any) => p.slot_index === slot)">
                                    <img
                                        :src="getAssetUrl(activeSession.photos.find((p: any) => p.slot_index === slot).original_path) + '?v=' + photoTimestamp"
                                        class="absolute inset-0 w-full h-full object-cover"
                                    />
                                    <button
                                        @click="triggerRemoteCapture(slot)"
                                        :disabled="isCapturingSlot !== null || isComposing"
                                        class="relative z-10 mt-auto py-1.5 px-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-[10px] flex items-center justify-center gap-1.5 shadow-md transition-all active:scale-95 disabled:opacity-50"
                                    >
                                        <RefreshCw v-if="isCapturingSlot === slot" class="w-3 h-3 animate-spin" />
                                        <RotateCcw v-else class="w-3 h-3" />
                                        <span>{{ isCapturingSlot === slot ? 'Memproses...' : 'Retake' }}</span>
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        @click="triggerRemoteCapture(slot)"
                                        :disabled="isCapturingSlot !== null || isComposing"
                                        class="my-auto py-3 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 text-[11px] font-bold flex flex-col items-center justify-center gap-1.5 transition-all active:scale-95 disabled:opacity-50"
                                    >
                                        <RefreshCw v-if="isCapturingSlot === slot" class="w-5 h-5 text-amber-400 animate-spin" />
                                        <Camera v-else class="w-5 h-5 text-amber-400" />
                                        <span>{{ isCapturingSlot === slot ? 'Mengambil...' : 'Capture' }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="pt-4 border-t border-white/10 flex items-center justify-between gap-3">
                        <button
                            v-if="activeSession.final_photo_path"
                            @click="triggerRemotePrint"
                            :disabled="isPrinting"
                            class="flex-1 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-all disabled:opacity-50"
                        >
                            <Printer class="w-4 h-4 stroke-[2.5]" />
                            <span>{{ isPrinting ? 'Mencetak...' : 'Cetak Foto (Printer)' }}</span>
                        </button>

                        <button
                            v-else-if="activeSession.photos?.length >= activeSession.total_photos_required"
                            @click="autoCompose"
                            :disabled="isComposing"
                            class="flex-1 py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-all disabled:opacity-50"
                        >
                            <Sparkles class="w-4 h-4 stroke-[2.5]" />
                            <span>{{ isComposing ? 'Menyusun Layout...' : 'Susun Hasil Foto (Compose)' }}</span>
                        </button>

                        <button
                            @click="router.visit(`/session/${activeSession.id}/camera`)"
                            class="py-3.5 px-6 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/10"
                        >
                            Buka Tampilan Kiosk
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-else>
                    <div class="flex-1 flex flex-col items-center justify-center text-center p-8 text-slate-500">
                        <Camera class="w-12 h-12 mb-3 text-slate-600" />
                        <p class="text-sm font-semibold text-slate-400">Belum Ada Sesi Aktif</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs">
                            Pilih template di sebelah kiri dan tekan tombol "Mulai Sesi Foto" untuk memulai.
                        </p>
                    </div>
                </template>
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
            @retake="runCameraTest"
        />

        <!-- MODAL BUKA KUNCI PERANGKAT -->
        <DeviceLockModal
            :show="showUnlockModal"
            @close="showUnlockModal = false"
            @unlocked="onUnlocked"
        />

        <!-- MODAL VIEWFINDER KIOSK CAPTURE -->
        <ControllerCaptureModal
            v-if="activeSession"
            :show="showCaptureModal"
            :session="activeSession"
            :slotIndex="currentCaptureSlot"
            :totalSlots="activeSession.total_photos_required || 3"
            :cameraName="deviceStore.camera.name"
            @close="showCaptureModal = false"
            @captured="onModalCaptured"
            @compose="onModalCompose"
            @nextSlot="onModalNextSlot"
        />
    </div>
</template>