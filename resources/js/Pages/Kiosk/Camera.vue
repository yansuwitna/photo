<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import CameraPreview from '@/Components/CameraPreview.vue';
import CountdownOverlay from '@/Components/CountdownOverlay.vue';
import PhotoSlotRenderer from '@/Components/PhotoSlotRenderer.vue';
import PrintModal from '@/Components/PrintModal.vue';
import PaymentModal from '@/Components/PaymentModal.vue';
import type { BoothSession, Template, SessionPhoto } from '@/types';
import { 
    Camera, 
    RotateCcw, 
    Check, 
    Printer, 
    QrCode, 
    Sparkles, 
    Layers, 
    Plus, 
    Minus,
    CreditCard
} from 'lucide-vue-next';
import { useAudioStore } from '@/stores/audioStore';
import { useSessionStore } from '@/stores/sessionStore';
import axios from 'axios';

const props = defineProps<{
    session: BoothSession;
    template: Template;
}>();

const audioStore = useAudioStore();
const sessionStore = useSessionStore();

// Local Reactive States
const currentSession = ref<BoothSession>(props.session);
const step = ref<'ready' | 'countdown' | 'capturing' | 'review' | 'composing' | 'final'>('ready');
const countdown = ref(5);
const isCountingDown = ref(false);
const currentSlotIndex = ref(1);
const totalSlots = computed(() => props.template?.photo_count || 3);

// Printing & Payment Modals
const showPrintModal = ref(false);
const printProgress = ref(0);
const isPrinting = ref(false);
const isPrintComplete = ref(false);
const printCopies = ref(1);
const showPaymentModal = ref(false);

onMounted(() => {
    // Tentukan step awal berdasarkan data sesi
    const photos = currentSession.value.photos || [];
    if (currentSession.value.final_photo_path) {
        step.value = 'final';
    } else if (photos.length >= totalSlots.value) {
        step.value = 'review';
    } else {
        step.value = 'ready';
        currentSlotIndex.value = photos.length + 1;
    }
});

// START PHOTO SEQUENCE
async function startCapture(slot?: number) {
    const targetSlot = slot ?? currentSlotIndex.value;
    currentSlotIndex.value = targetSlot;
    isCountingDown.value = true;
    countdown.value = currentSession.value.event?.countdown_seconds || 5;
    step.value = 'countdown';

    audioStore.speakInstruction('Siapkan posisi terbaik Anda!');

    const interval = setInterval(async () => {
        countdown.value -= 1;

        if (countdown.value > 0 && countdown.value <= 3) {
            audioStore.playCountdown(countdown.value);
        } else if (countdown.value === 0) {
            clearInterval(interval);
            isCountingDown.value = false;
            step.value = 'capturing';

            audioStore.playSmile();

            // Ambil foto setelah senyum
            setTimeout(async () => {
                audioStore.playShutter();
                await executeCameraCapture(targetSlot);
            }, 600);
        }
    }, 1000);
}

async function executeCameraCapture(slot: number) {
    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/capture`, {
            slot_index: slot,
        });

        if (res.data.success) {
            currentSession.value = res.data.session;
            audioStore.playSuccess();

            if (res.data.is_complete) {
                step.value = 'review';
                audioStore.speakInstruction('Foto selesai diambil. Periksa hasil foto Anda.');
            } else {
                // Beri jeda 2.5 detik lalu lanjut foto berikutnya
                currentSlotIndex.value = res.data.captured_count + 1;
                step.value = 'ready';
                setTimeout(() => {
                    startCapture(currentSlotIndex.value);
                }, 2200);
            }
        }
    } catch (err) {
        alert('Gagal mengambil foto dari kamera. Coba lagi.');
        step.value = 'ready';
    }
}

// RETAKE SPECIFIC SLOT
function handleRetake(slotIndex: number) {
    audioStore.playBeep(659.25, 0.1, 'triangle');
    startCapture(slotIndex);
}

// COMPOSE FINAL PHOTO
async function handleProceedToCompose() {
    step.value = 'composing';
    audioStore.speakInstruction('Sedang menyusun template dan merender resolusi tinggi.');

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/compose`);
        if (res.data.success) {
            currentSession.value = res.data.session || {
                ...currentSession.value,
                final_photo_path: res.data.file_path,
                final_thumbnail_path: res.data.thumbnail_path,
            };
            step.value = 'final';
            audioStore.playSuccess();
        }
    } catch (err) {
        alert('Gagal menyusun template.');
        step.value = 'review';
    }
}

// PRINT FLOW
async function triggerPrint() {
    // Jika belum lunas dan event mensyaratkan bayar
    if (currentSession.value.payment_status === 'unpaid' && (currentSession.value.event?.default_price || 0) > 0) {
        showPaymentModal.value = true;
        return;
    }

    showPrintModal.value = true;
    isPrinting.value = true;
    isPrintComplete.value = false;
    printProgress.value = 15;

    const progInterval = setInterval(() => {
        if (printProgress.value < 90) {
            printProgress.value += 15;
        }
    }, 450);

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/print`, {
            copies: printCopies.value,
        });

        clearInterval(progInterval);
        printProgress.value = 100;

        if (res.data.success) {
            isPrinting.value = false;
            isPrintComplete.value = true;
            audioStore.playPrintDone();
            audioStore.speakInstruction('Pencetakan selesai. Silakan ambil foto Anda.');
        } else {
            showPrintModal.value = false;
            alert(res.data.message || 'Printer error');
        }
    } catch (err: any) {
        clearInterval(progInterval);
        showPrintModal.value = false;
        alert('Gagal mencetak: ' + (err.response?.data?.message || 'Koneksi printer terputus'));
    }
}

function handleDoneSession() {
    showPrintModal.value = false;
    router.visit(`/session/${currentSession.value.id}/success`);
}

function getAssetUrl(path?: string) {
    if (!path) return '';
    return '/' + path.replace('public/', 'storage/');
}
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col overflow-hidden p-6 md:p-8">
            <!-- ============================================== -->
            <!-- 1. LIVE VIEW & CAPTURE MODE (Ready / Countdown) -->
            <!-- ============================================== -->
            <template v-if="step === 'ready' || step === 'countdown' || step === 'capturing'">
                <div class="relative flex-1 w-full h-full flex flex-col items-center justify-between">
                    <!-- CAMERA LIVE VIEW CONTAINER -->
                    <div class="relative w-full flex-1 max-h-[75vh] flex items-center justify-center">
                        <CameraPreview :isLive="true">
                            <!-- COUNTDOWN OVERLAY -->
                            <CountdownOverlay
                                :countdown="countdown"
                                :isCountingDown="isCountingDown"
                                :slotIndex="currentSlotIndex"
                                :totalSlots="totalSlots"
                            />
                        </CameraPreview>
                    </div>

                    <!-- BOTTOM CONTROLS & TRIGGER BUTTON -->
                    <div class="w-full pt-6 flex items-center justify-between max-w-4xl mx-auto">
                        <div class="text-left">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">
                                SLOT {{ currentSlotIndex }} DARI {{ totalSlots }}
                            </span>
                            <h3 class="text-xl font-bold text-white">
                                {{ step === 'ready' ? 'Siap Mengambil Foto' : 'Senyum ke Kamera!' }}
                            </h3>
                        </div>

                        <!-- BIG SHUTTER BUTTON -->
                        <button
                            v-if="step === 'ready'"
                            @click="startCapture(currentSlotIndex)"
                            class="px-10 py-5 rounded-full bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-400 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-black text-xl tracking-wider shadow-[0_0_40px_rgba(245,158,11,0.6)] flex items-center gap-3 transition-all transform active:scale-95"
                        >
                            <Camera class="w-7 h-7 stroke-[2.5]" />
                            <span>AMBIL FOTO {{ currentSlotIndex }}</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- 2. PHOTO REVIEW MODE (Multi-slot Review & Retake)-->
            <!-- ============================================== -->
            <template v-else-if="step === 'review'">
                <div class="flex-1 flex flex-col justify-between max-w-6xl mx-auto w-full">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold uppercase tracking-wider mb-2">
                            <Sparkles class="w-3.5 h-3.5" />
                            <span>Langkah 2 dari 3</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight">
                            YOUR MEMORIES
                        </h2>
                        <p class="text-sm md:text-base text-slate-400 mt-2">
                            Periksa hasil foto Anda. Anda dapat mengambil ulang foto tertentu jika kurang pas.
                        </p>
                    </div>

                    <!-- Slots Grid with Retake -->
                    <div class="flex-1 overflow-y-auto px-2 py-4 flex items-center justify-center">
                        <PhotoSlotRenderer
                            :photos="currentSession.photos || []"
                            :totalSlots="totalSlots"
                            :template="template"
                            :allowRetake="true"
                            @retake="handleRetake"
                        />
                    </div>

                    <!-- Bottom Actions -->
                    <div class="pt-6 border-t border-white/10 flex items-center justify-between">
                        <button
                            @click="startCapture(1)"
                            class="py-4 px-6 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-300 font-semibold text-sm flex items-center gap-2 border border-white/10 transition-all active:scale-95"
                        >
                            <RotateCcw class="w-4 h-4" />
                            <span>Ulangi Semua Sesi</span>
                        </button>

                        <button
                            @click="handleProceedToCompose"
                            class="py-4 px-10 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-lg shadow-[0_0_35px_rgba(16,185,129,0.5)] flex items-center gap-3 transition-all active:scale-95"
                        >
                            <Check class="w-6 h-6 stroke-[3]" />
                            <span>PAKAI FOTO & CETAK</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- 3. COMPOSING RENDERING SPINNER -->
            <!-- ============================================== -->
            <template v-else-if="step === 'composing'">
                <div class="flex-1 flex flex-col items-center justify-center text-center">
                    <div class="relative w-32 h-32 mb-8">
                        <div class="absolute inset-0 rounded-full border-4 border-amber-400/20"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-amber-400 border-t-transparent animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <Layers class="w-12 h-12 text-amber-400 animate-pulse" />
                        </div>
                    </div>
                    <h2 class="text-3xl font-black text-white">Menyusun Template Desain...</h2>
                    <p class="text-sm text-slate-400 mt-2 max-w-sm">
                        Menggabungkan foto, watermark, logo event, dan QR code beresolusi 300 DPI siap cetak.
                    </p>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- 4. FINAL PREVIEW & PRINT / QR DOWNLOAD -->
            <!-- ============================================== -->
            <template v-else-if="step === 'final'">
                <div class="flex-1 flex flex-col md:flex-row items-center justify-between gap-8 max-w-6xl mx-auto w-full h-full overflow-hidden">
                    <!-- Left: Final Composite Preview Card -->
                    <div class="flex-1 h-full max-h-[78vh] flex items-center justify-center">
                        <div class="relative max-h-full aspect-[2/3] rounded-3xl overflow-hidden border-2 border-white/20 shadow-[0_0_50px_rgba(0,0,0,0.8)] bg-slate-950 group">
                            <img
                                :src="getAssetUrl(currentSession.final_photo_path)"
                                alt="Final Photobooth Output"
                                class="w-full h-full object-contain"
                            />
                        </div>
                    </div>

                    <!-- Right: Print Options & QR Download Panel -->
                    <div class="w-full md:w-96 rounded-3xl bg-slate-900/90 border border-white/10 p-6 flex flex-col justify-between shadow-2xl">
                        <div>
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Hasil Siap Cetak</span>
                            <h3 class="text-2xl font-black text-white mt-1">Cetak & Unduh</h3>
                            <p class="text-xs text-slate-400 mt-1">Dapatkan salinan fisik dan versi digital instan</p>

                            <!-- Number of Copies Picker -->
                            <div class="mt-6 p-4 rounded-2xl bg-black/40 border border-white/10">
                                <label class="text-xs font-semibold text-slate-300 block mb-3">Jumlah Lembar Cetak:</label>
                                <div class="flex items-center justify-between">
                                    <button
                                        @click="printCopies = Math.max(1, printCopies - 1)"
                                        class="w-12 h-12 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-lg flex items-center justify-center active:scale-90 transition-all"
                                    >
                                        <Minus class="w-5 h-5" />
                                    </button>
                                    <span class="text-3xl font-black text-amber-400 font-mono">{{ printCopies }}</span>
                                    <button
                                        @click="printCopies = Math.min(10, printCopies + 1)"
                                        class="w-12 h-12 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-lg flex items-center justify-center active:scale-90 transition-all"
                                    >
                                        <Plus class="w-5 h-5" />
                                    </button>
                                </div>
                                <div class="text-[11px] text-slate-400 text-center mt-3">
                                    Ukuran Kertas: {{ template?.paper_size || '4R' }} Glossy Premium
                                </div>
                            </div>

                            <!-- QR Code Mobile Download Info -->
                            <div class="mt-4 p-4 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-white p-1 flex items-center justify-center shadow">
                                    <QrCode class="w-12 h-12 text-slate-950" />
                                </div>
                                <div class="text-xs">
                                    <p class="font-bold text-white">Digital Copy Tersedia</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Kode: {{ currentSession.digital_code }}</p>
                                    <p class="text-[10px] text-amber-300 mt-0.5">Scan di hasil cetak untuk unduh</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 space-y-3">
                            <button
                                @click="triggerPrint"
                                class="w-full py-5 rounded-2xl bg-gradient-to-r from-amber-400 to-yellow-400 hover:from-amber-300 hover:to-yellow-300 text-slate-950 font-black text-lg shadow-[0_0_35px_rgba(245,158,11,0.5)] flex items-center justify-center gap-3 transition-all active:scale-95"
                            >
                                <Printer class="w-6 h-6 stroke-[2.5]" />
                                <span>CETAK FOTO SEKARANG</span>
                            </button>

                            <button
                                @click="handleDoneSession"
                                class="w-full py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 font-semibold text-xs transition-all text-center"
                            >
                                Lewati & Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- PRINT PROGRESS MODAL -->
        <PrintModal
            :show="showPrintModal"
            :progress="printProgress"
            :isPrinting="isPrinting"
            :isCompleted="isPrintComplete"
            :copies="printCopies"
            @print-again="triggerPrint"
            @done="handleDoneSession"
        />

        <!-- PAYMENT MODAL -->
        <PaymentModal
            :show="showPaymentModal"
            :sessionId="currentSession.id"
            :basePrice="currentSession.event?.default_price || 25000"
            :extraPrintPrice="currentSession.event?.extra_print_price || 10000"
            :copies="printCopies"
            @close="showPaymentModal = false"
            @paid="triggerPrint"
        />
    </KioskLayout>
</template>