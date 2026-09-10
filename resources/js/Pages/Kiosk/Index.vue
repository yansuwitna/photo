<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import { Camera, Sparkles, ArrowRight, Play, Settings, Check } from 'lucide-vue-next';
import { useSessionStore } from '@/stores/sessionStore';
import { useAudioStore } from '@/stores/audioStore';
import { showError } from '@/utils/swal';

const page = usePage();
const sessionStore = useSessionStore();
const audioStore = useAudioStore();

const activeEvent = page.props.active_event as any;
const isStarting = ref(false);
const showBoothModal = ref(false);
const customBoothInput = ref('');

const presetBooths = ['STAND-01', 'STAND-02', 'STAND-03', 'STAND-04'];

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const boothParam = params.get('booth') || (params.get('stand') ? `STAND-0${params.get('stand')}` : null);
    if (boothParam) {
        sessionStore.setBoothId(boothParam);
    }
});

function selectBooth(id: string) {
    sessionStore.setBoothId(id);
    showBoothModal.value = false;
}

function saveCustomBooth() {
    if (customBoothInput.value.trim()) {
        sessionStore.setBoothId(customBoothInput.value.trim().toUpperCase());
        customBoothInput.value = '';
        showBoothModal.value = false;
    }
}

async function handleStart() {
    isStarting.value = true;
    audioStore.playBeep(880, 0.2, 'sine');
    audioStore.speakInstruction('Selamat datang di photo booth! Silakan pilih gaya foto Anda.');

    try {
        const res = await sessionStore.startSession(undefined, activeEvent?.id, sessionStore.boothId);
        if (res.success) {
            router.visit(`/session/${res.session.id}/camera`);
        }
    } catch (err) {
        showError('Gagal Memulai Sesi', 'Periksa koneksi kamera dan status server photobooth.');
    } finally {
        isStarting.value = false;
    }
}
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col items-center justify-between p-4 sm:p-8 md:p-12 text-center overflow-y-auto sm:overflow-hidden">
            <!-- Ambient Glowing Background Elements -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] sm:w-[600px] sm:h-[600px] rounded-full bg-gradient-to-tr from-amber-500/20 via-purple-600/15 to-transparent blur-3xl pointer-events-none"></div>

            <!-- Top Event Welcome Badge & Stand Indicator -->
            <div class="relative z-10 pt-2 sm:pt-4 animate-fade-in flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-5 py-1.5 sm:py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs sm:text-sm tracking-widest uppercase font-medium text-amber-300 shadow-xl">
                    <Sparkles class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400" />
                    <span>{{ activeEvent?.name || 'STUDIO PHOTOBOOTH PRO' }}</span>
                </div>

                <button 
                    @click="showBoothModal = true"
                    class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full bg-slate-900/80 hover:bg-slate-800 backdrop-blur-md border border-amber-500/30 text-[11px] sm:text-xs font-bold text-slate-200 shadow-lg transition-all active:scale-95 cursor-pointer"
                    title="Klik untuk mengubah Stand/Booth"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>STAND: <span class="text-amber-400 font-black">{{ sessionStore.boothId }}</span></span>
                    <Settings class="w-3.5 h-3.5 text-slate-400 ml-0.5 sm:ml-1" />
                </button>
            </div>

            <!-- Center Call to Action -->
            <div class="relative z-10 my-auto py-4 flex flex-col items-center max-w-2xl w-full">
                <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-slate-950 mb-4 sm:mb-8 shadow-[0_0_50px_rgba(245,158,11,0.5)] animate-bounce">
                    <Camera class="w-7 h-7 sm:w-10 sm:h-10" />
                </div>

                <h1 class="text-3xl sm:text-5xl md:text-7xl font-black tracking-tight text-white uppercase leading-tight sm:leading-none">
                    PHOTOBOOTH <span class="bg-gradient-to-r from-amber-400 via-amber-200 to-amber-500 bg-clip-text text-transparent">PRO</span>
                </h1>

                <p class="mt-2 sm:mt-4 text-base sm:text-xl md:text-2xl text-slate-300 font-light tracking-wide">
                    Capture The Moment
                </p>

                <p class="mt-1.5 sm:mt-2 text-xs sm:text-sm text-slate-400 max-w-md px-4">
                    Abadikan senyuman dan kenangan terbaik Anda dengan hasil cetak instan kualitas studio profesional.
                </p>

                <!-- GIANT TOUCH-FRIENDLY START BUTTON -->
                <button
                    @click="handleStart"
                    :disabled="isStarting"
                    class="mt-6 sm:mt-12 group relative inline-flex items-center justify-center px-8 py-4 sm:px-12 sm:py-6 rounded-full text-lg sm:text-2xl font-black tracking-wider uppercase text-slate-950 bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-400 hover:from-amber-300 hover:to-amber-200 shadow-[0_0_45px_rgba(245,158,11,0.6)] transform transition-all duration-300 hover:scale-105 active:scale-95 disabled:opacity-50 cursor-pointer"
                >
                    <span class="absolute -inset-1 rounded-full bg-amber-400/40 blur-lg group-hover:opacity-100 transition-opacity"></span>
                    <span class="relative flex items-center gap-2.5 sm:gap-4">
                        <Play class="w-5 h-5 sm:w-8 sm:h-8 fill-slate-950" />
                        <span>{{ isStarting ? 'MEMULAI...' : 'START' }}</span>
                        <ArrowRight class="w-5 h-5 sm:w-8 sm:h-8 group-hover:translate-x-1 transition-transform" />
                    </span>
                </button>
            </div>

            <!-- Bottom Instruction Footer -->
            <div class="relative z-10 pb-4 text-xs text-slate-400 tracking-wider">
                SENTUH TOMBOL START UNTUK MEMULAI SESI FOTO
            </div>

            <!-- MODAL PENGATURAN STAND / BOOTH -->
            <div 
                v-if="showBoothModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in"
            >
                <div class="bg-slate-900 border border-white/20 rounded-3xl max-w-md w-full p-6 shadow-2xl text-left">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                                <Settings class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-black text-white">Identitas Stand Foto</h3>
                                <p class="text-xs text-slate-400">Pilih stand tempat perangkat ini berada</p>
                            </div>
                        </div>
                        <button 
                            @click="showBoothModal = false"
                            class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="my-5 space-y-2">
                        <label class="text-xs font-bold text-slate-300 uppercase tracking-wider block mb-2">Pilih Stand Cepat:</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button
                                v-for="booth in presetBooths"
                                :key="booth"
                                @click="selectBooth(booth)"
                                class="p-3.5 rounded-2xl border text-xs font-black flex items-center justify-between transition-all"
                                :class="sessionStore.boothId === booth ? 'bg-amber-400 text-slate-950 border-amber-400 shadow-lg' : 'bg-slate-950 hover:bg-slate-800 text-white border-white/10'"
                            >
                                <span>{{ booth }}</span>
                                <Check v-if="sessionStore.boothId === booth" class="w-4 h-4 text-slate-950" />
                            </button>
                        </div>

                        <div class="pt-3">
                            <label class="text-xs font-bold text-slate-400 block mb-1.5">Atau Nama Stand Kustom:</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="customBoothInput"
                                    type="text"
                                    placeholder="Misal: STAND-VIP"
                                    class="flex-1 bg-slate-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400 uppercase"
                                    @keyup.enter="saveCustomBooth"
                                />
                                <button
                                    @click="saveCustomBooth"
                                    class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs"
                                >
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 leading-relaxed border-t border-white/10 pt-3">
                        💡 Semua foto dari stand ini akan otomatis dicetak oleh <span class="text-amber-400 font-bold">Auto-Print</span> yang disetel ke Stand ini.
                    </p>
                </div>
            </div>
        </div>
    </KioskLayout>
</template>