<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
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
    Sparkles
} from 'lucide-vue-next';
import { useDeviceStore } from '@/stores/deviceStore';
import { useAudioStore } from '@/stores/audioStore';

const deviceStore = useDeviceStore();
const audioStore = useAudioStore();

const testResult = ref<string>('');
const isTestingCamera = ref(false);
const isTestingPrinter = ref(false);

async function handleTestCamera() {
    isTestingCamera.value = true;
    testResult.value = 'Menjalankan simulasi remote capture kamera...';
    const res = await deviceStore.testCamera();
    testResult.value = res.message || 'Uji kamera berhasil.';
    isTestingCamera.value = false;
}

async function handleTestPrinter() {
    isTestingPrinter.value = true;
    testResult.value = 'Mengirim pekerjaan cetak uji coba ke printer...';
    const res = await deviceStore.testPrinter();
    testResult.value = res.message || 'Uji printer berhasil.';
    isTestingPrinter.value = false;
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
    </AdminLayout>
</template>