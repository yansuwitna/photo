<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import type { BoothSession } from '@/types';
import { CheckCircle2, Download, Printer, Home, Sparkles, QrCode } from 'lucide-vue-next';
import confetti from 'canvas-confetti';
import QRCode from 'qrcode';

const props = defineProps<{
    session: BoothSession;
}>();

const qrDataUrl = ref('');
const resetCountdown = ref(20);
let timer: any = null;

onMounted(async () => {
    // Ledakan confetti selebrasi
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
    });

    // Generate QR Code untuk download
    const downloadUrl = window.location.origin + `/download/${props.session.digital_code}`;
    try {
        qrDataUrl.value = await QRCode.toDataURL(downloadUrl, {
            width: 260,
            margin: 1,
            color: { dark: '#020617', light: '#ffffff' }
        });
    } catch (e) {
        console.warn('QR error:', e);
    }

    // Auto reset timer countdown
    timer = setInterval(() => {
        resetCountdown.value -= 1;
        if (resetCountdown.value <= 0) {
            handleDone();
        }
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

function handleDone() {
    if (timer) clearInterval(timer);
    router.visit('/');
}

function handleReprint() {
    router.visit(`/session/${props.session.id}/camera`);
}

function getAssetUrl(path?: string) {
    if (!path) return '';
    return '/' + path.replace('public/', 'storage/');
}
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col items-center justify-between p-6 md:p-10 max-w-5xl mx-auto text-center overflow-hidden">
            <!-- Header Celebration -->
            <div class="flex flex-col items-center pt-2">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border-2 border-emerald-400 text-emerald-400 flex items-center justify-center mb-3 shadow-[0_0_30px_rgba(16,185,129,0.4)] animate-bounce">
                    <CheckCircle2 class="w-10 h-10" />
                </div>
                <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight">
                    TERIMA KASIH!
                </h2>
                <p class="text-base text-slate-300 mt-1">
                    Silakan ambil foto cetak Anda di tray printer.
                </p>
            </div>

            <!-- Content Card: Photo Thumbnail + QR Code -->
            <div class="my-auto flex flex-col md:flex-row items-center justify-center gap-8 bg-slate-900/80 border border-white/10 p-8 rounded-3xl backdrop-blur-md shadow-2xl">
                <!-- Preview Thumbnail -->
                <div class="w-48 sm:w-56 aspect-[2/3] rounded-2xl overflow-hidden border border-white/20 shadow-xl bg-black">
                    <img
                        :src="getAssetUrl(session.final_photo_path)"
                        alt="Foto Final"
                        class="w-full h-full object-contain"
                    />
                </div>

                <!-- QR Code Box -->
                <div class="flex flex-col items-center max-w-xs">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                        <Sparkles class="w-3.5 h-3.5" />
                        <span>VERSI DIGITAL</span>
                    </span>

                    <div class="p-3 bg-white rounded-2xl shadow-xl border-2 border-amber-400/40">
                        <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code Unduh" class="w-48 h-48" />
                        <div v-else class="w-48 h-48 flex items-center justify-center text-slate-950">
                            <QrCode class="w-32 h-32" />
                        </div>
                    </div>

                    <p class="text-xs font-bold text-white mt-3">Scan dengan Kamera HP</p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Atau akses kode: <span class="font-mono font-bold text-amber-300">{{ session.digital_code }}</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons & Auto Reset Notice -->
            <div class="w-full max-w-lg flex flex-col items-center gap-4 pb-2">
                <div class="flex items-center gap-4 w-full">
                    <button
                        @click="handleReprint"
                        class="w-1/2 py-4 px-6 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/15 flex items-center justify-center gap-2 transition-all active:scale-95"
                    >
                        <Printer class="w-5 h-5" />
                        <span>Cetak Lagi</span>
                    </button>

                    <button
                        @click="handleDone"
                        class="w-1/2 py-4 px-6 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95"
                    >
                        <Home class="w-5 h-5" />
                        <span>Selesai</span>
                    </button>
                </div>

                <div class="text-xs text-slate-500 font-mono">
                    Otomatis kembali ke layar awal dalam {{ resetCountdown }} detik
                </div>
            </div>
        </div>
    </KioskLayout>
</template>