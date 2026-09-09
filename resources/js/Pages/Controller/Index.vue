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
    Home
} from 'lucide-vue-next';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import { useDeviceStore } from '@/stores/deviceStore';
import axios from 'axios';

const props = defineProps<{
    activeSession?: any;
    templates: any[];
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
const selectedTemplateId = ref<number>(props.templates[0]?.id || 1);

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
        }
    } catch (e) {}
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
        alert('Gagal memulai sesi dari controller');
    } finally {
        isStarting.value = false;
    }
}

async function triggerRemoteCapture(slot: number) {
    if (!activeSession.value) return;
    try {
        const res = await axios.post(`/api/session/${activeSession.value.id}/capture`, {
            slot_index: slot,
        });
        if (res.data.success) {
            activeSession.value = res.data.session;
        }
    } catch (e) {
        alert('Gagal remote capture');
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
            alert('Perintah cetak berhasil dikirim!');
            await refreshData();
        } else {
            alert('Gagal cetak: ' + res.data.message);
        }
    } catch (e: any) {
        alert('Error cetak: ' + (e.response?.data?.message || 'Koneksi terputus'));
    } finally {
        isPrinting.value = false;
    }
}

async function runCameraTest() {
    const res = await deviceStore.testCamera();
    alert(res.message || 'Uji kamera selesai');
}

async function runPrinterTest() {
    const res = await deviceStore.testPrinter();
    alert(res.message || 'Uji printer selesai');
}

function getAssetUrl(path?: string) {
    if (!path) return '';
    return '/' + path.replace('public/', 'storage/');
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
                    @click="router.visit('/')"
                    class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 text-xs font-semibold flex items-center gap-1.5"
                >
                    <Home class="w-3.5 h-3.5" />
                    <span>Kiosk Layar</span>
                </button>
            </div>
        </header>

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
                                class="relative aspect-[3/4] rounded-xl bg-black/50 border border-white/10 overflow-hidden flex flex-col justify-between p-2"
                            >
                                <span class="text-[10px] font-bold text-slate-400">Foto {{ slot }}</span>
                                
                                <template v-if="activeSession.photos?.find((p: any) => p.slot_index === slot)">
                                    <img
                                        :src="getAssetUrl(activeSession.photos.find((p: any) => p.slot_index === slot).original_path)"
                                        class="absolute inset-0 w-full h-full object-cover"
                                    />
                                    <button
                                        @click="triggerRemoteCapture(slot)"
                                        class="relative z-10 mt-auto py-1 px-2 rounded-lg bg-amber-500/90 text-slate-950 font-bold text-[10px] flex items-center justify-center gap-1 shadow"
                                    >
                                        <RotateCcw class="w-3 h-3" />
                                        <span>Retake</span>
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        @click="triggerRemoteCapture(slot)"
                                        class="my-auto py-2 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 text-[11px] font-bold flex flex-col items-center justify-center gap-1"
                                    >
                                        <Camera class="w-4 h-4 text-amber-400" />
                                        <span>Capture</span>
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
    </div>
</template>