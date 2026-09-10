<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import LiveTemplateCanvas from '@/Components/LiveTemplateCanvas.vue';
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
    CreditCard,
    FlipHorizontal
} from 'lucide-vue-next';
import { useAudioStore } from '@/stores/audioStore';
import { useSessionStore } from '@/stores/sessionStore';
import { getAssetUrl } from '@/utils/url';
import FrameSelectorModal, { type FrameItem } from '@/Components/FrameSelectorModal.vue';
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
const countdown = ref(3);
const isCountingDown = ref(false);
const currentSlotIndex = ref(1);
const totalSlots = computed(() => props.template?.photo_count || 4);
const templateWidth = computed(() => props.template?.width || 1200);
const templateHeight = computed(() => props.template?.height || 1800);
const finalPhotoTimestamp = ref(Date.now());

// Map foto per slot untuk live template canvas: { 1: url, 2: url, ... }
const capturedPhotosMap = ref<Record<number, string>>({});
const isFlashingSlot = ref<number | null>(null);
const liveCanvasRef = ref<any>(null);
const mirrorMode = ref(true);

// Frame Overlay & Background Colors
const showFrameModal = ref(false);
const activeFrame = computed(() => {
    return (currentSession.value.metadata as any)?.custom_overlay_image || currentSession.value.template?.overlay_image || null;
});

const frameColors = [
    { name: 'Putih Bersih', hex: '#ffffff', isDark: false },
    { name: 'Hitam Elegan', hex: '#0f172a', isDark: true },
    { name: 'Pink Pastel', hex: '#fce7f3', isDark: false },
    { name: 'Baby Blue', hex: '#e0f2fe', isDark: false },
    { name: 'Butter Cream', hex: '#fef9c3', isDark: false },
    { name: 'Sage Green', hex: '#dcfce7', isDark: false },
    { name: 'Lilac Ungu', hex: '#f3e8ff', isDark: false },
    { name: 'Abu Modern', hex: '#334155', isDark: true },
];

const activeBgColor = computed(() => {
    return (currentSession.value.metadata as any)?.custom_background_color || props.template.background_color || '#ffffff';
});

// Beauty Filter Presets ala BeautyPlus
export interface CameraFilter {
    id: string;
    name: string;
    icon: string;
    cssFilter: string;
}

const cameraFilters: CameraFilter[] = [
    { id: 'normal', name: 'Asli', icon: '✨', cssFilter: 'none' },
    { id: 'korean-glow', name: 'Korean Glow', icon: '🌸', cssFilter: 'brightness(1.08) contrast(0.98) saturate(1.12)' },
    { id: 'bw-noir', name: 'B&W Klasik', icon: '🖤', cssFilter: 'grayscale(100%) contrast(1.25) brightness(1.02)' },
    { id: 'vintage-film', name: 'Vintage 90s', icon: '🎞️', cssFilter: 'sepia(0.35) contrast(1.15) brightness(1.05) saturate(1.1)' },
    { id: 'rosy-blush', name: 'Rosy Pink', icon: '🎀', cssFilter: 'contrast(1.06) saturate(1.25) hue-rotate(-10deg) brightness(1.04)' },
    { id: 'cyber-cool', name: 'Cyber Cool', icon: '⚡', cssFilter: 'contrast(1.2) saturate(1.3) hue-rotate(15deg)' },
];
const activeFilterId = ref('normal');
const activeFilter = computed(() => cameraFilters.find(f => f.id === activeFilterId.value) || cameraFilters[0]);

// Printing & Payment Modals
const showPrintModal = ref(false);
const printProgress = ref(0);
const isPrinting = ref(false);
const isPrintComplete = ref(false);
const printCopies = ref(1);
const showPaymentModal = ref(false);

function getNextAvailableSlot(): number {
    for (let i = 1; i <= totalSlots.value; i++) {
        if (!capturedPhotosMap.value[i]) return i;
    }
    return 1;
}

onMounted(() => {
    // Inisialisasi foto yang sudah ada dari database sesi
    const photos = currentSession.value.photos || [];
    photos.forEach((p: SessionPhoto) => {
        if (p.slot_index && p.is_accepted) {
            capturedPhotosMap.value[Number(p.slot_index)] = p.thumbnail_path || p.original_path;
        }
    });

    if (currentSession.value.final_photo_path) {
        step.value = 'final';
    } else if (Object.keys(capturedPhotosMap.value).length >= totalSlots.value) {
        step.value = 'review';
    } else {
        step.value = 'ready';
        currentSlotIndex.value = getNextAvailableSlot();
    }
});

// START CAPTURE SEQUENCE
async function startCapture(slot?: number) {
    if (isCountingDown.value) return;

    const targetSlot = slot ?? currentSlotIndex.value;
    currentSlotIndex.value = targetSlot;
    isCountingDown.value = true;
    countdown.value = 3; // 3 detik per foto ala Korean Life4Cuts
    step.value = 'countdown';

    audioStore.speakInstruction(`Foto ke-${targetSlot}. Siapkan pose Anda!`);

    const interval = setInterval(async () => {
        countdown.value -= 1;

        if (countdown.value > 0) {
            audioStore.playCountdown(countdown.value);
        } else if (countdown.value === 0) {
            clearInterval(interval);
            isCountingDown.value = false;
            step.value = 'capturing';

            audioStore.playSmile();

            // Flash & snap
            setTimeout(async () => {
                audioStore.playShutter();
                await executeCameraCapture(targetSlot);
            }, 500);
        }
    }, 1000);
}

// EXECUTE CAMERA CAPTURE & LOCK TO SLOT
async function executeCameraCapture(slot: number) {
    try {
        let imageData: string | null = null;
        if (liveCanvasRef.value?.captureActiveSlot) {
            imageData = liveCanvasRef.value.captureActiveSlot(slot);
        }

        if (imageData) {
            // Freeze dan kunci foto di slot ini secara instan di UI
            capturedPhotosMap.value[slot] = imageData;
            isFlashingSlot.value = slot;
            setTimeout(() => {
                isFlashingSlot.value = null;
            }, 350);
        }

        const payload: Record<string, any> = {
            slot_index: slot,
        };
        if (imageData) {
            payload.image_data = imageData;
        }

        const res = await axios.post(`/api/session/${currentSession.value.id}/capture`, payload);

        if (res.data.success) {
            currentSession.value = res.data.session;
            if (res.data.photo) {
                // Keep local base64 or update with server asset
                capturedPhotosMap.value[slot] = imageData || res.data.photo.thumbnail_path || res.data.photo.original_path;
            }
            audioStore.playSuccess();

            if (res.data.is_complete) {
                step.value = 'review';
                audioStore.speakInstruction('Luar biasa! Semua foto lengkap. Periksa hasil strip foto Anda.');
            } else {
                // Beri jeda 1.8 detik agar pengguna melihat foto terkunci di slot, lalu lanjut ke slot berikutnya
                const nextSlot = getNextAvailableSlot();
                currentSlotIndex.value = nextSlot;
                step.value = 'ready';
                audioStore.playBeep(784, 0.1, 'sine');
                audioStore.speakInstruction(`Bagus! Bersiap untuk foto ke-${nextSlot}.`);

                setTimeout(() => {
                    startCapture(nextSlot);
                }, 1800);
            }
        }
    } catch (err) {
        console.error('Capture error', err);
        alert('Gagal mengambil foto. Silakan coba lagi.');
        step.value = 'ready';
    }
}

// RETAKE SPECIFIC SLOT (Ambil Ulang Foto di Slot Tertentu)
function handleRetake(slotIndex: number) {
    const s = Number(slotIndex);
    delete capturedPhotosMap.value[s];
    currentSlotIndex.value = s;
    step.value = 'ready';
    audioStore.speakInstruction(`Mengambil ulang foto ke-${s}. Siapkan pose Anda!`);
    setTimeout(() => {
        startCapture(s);
    }, 600);
}

// RETAKE ALL PHOTOS
function handleRestartAll() {
    capturedPhotosMap.value = {};
    currentSlotIndex.value = 1;
    step.value = 'ready';
    startCapture(1);
}

// Ganti Background Color Frame
async function handleSelectBgColor(hex: string) {
    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/set-frame`, {
            background_color: hex,
        });
        if (res.data.success) {
            currentSession.value = res.data.session;
            audioStore.playBeep(784, 0.08, 'sine');
        }
    } catch (e) {
        console.error('Gagal mengganti warna background frame', e);
    }
}

// Ganti Overlay Frame
async function handleSelectFrame(frame: FrameItem) {
    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/set-frame`, {
            frame_path: frame.path,
        });
        if (res.data.success) {
            currentSession.value = res.data.session;
            audioStore.playBeep(880, 0.1, 'sine');
        }
    } catch (e) {
        alert('Gagal memilih bingkai');
    }
}

async function handleRemoveFrame() {
    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/set-frame`, {
            frame_path: null,
        });
        if (res.data.success) {
            currentSession.value = res.data.session;
        }
    } catch (e) {
        alert('Gagal melepas bingkai');
    }
}

// COMPOSE FINAL PHOTO (300 DPI Rendering)
async function handleProceedToCompose() {
    step.value = 'composing';
    audioStore.speakInstruction('Sedang menyusun template dan merender resolusi tinggi 300 DPI.');

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/compose`);
        if (res.data.success) {
            currentSession.value = res.data.session || {
                ...currentSession.value,
                final_photo_path: res.data.file_path,
                final_thumbnail_path: res.data.thumbnail_path,
            };
            finalPhotoTimestamp.value = Date.now();
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
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col overflow-hidden p-4 sm:p-6 md:p-8">
            <!-- ========================================================== -->
            <!-- 1. LIVE INTERACTIVE CANVAS CAPTURE (Slot-by-Slot Realtime) -->
            <!-- ========================================================== -->
            <template v-if="step === 'ready' || step === 'countdown' || step === 'capturing'">
                <div class="relative flex-1 w-full h-full flex flex-col items-center justify-between gap-4">
                    <!-- Top Info Header -->
                    <div class="flex items-center justify-between w-full max-w-4xl px-2">
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
                            <span class="text-xs md:text-sm font-black text-amber-300 tracking-wider uppercase">
                                KAMERA AKTIF DI SLOT #{{ currentSlotIndex }} (DARI {{ totalSlots }} FOTO)
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 font-medium hidden sm:block">
                            Template: <span class="font-bold text-white">{{ template.name }}</span>
                        </div>
                    </div>

                    <!-- LIVE TEMPLATE CANVAS CONTAINER (Slot 1 active -> locked -> Slot 2 active...) -->
                    <div class="flex-1 w-full flex items-center justify-center overflow-hidden py-1">
                        <LiveTemplateCanvas
                            ref="liveCanvasRef"
                            :template="template"
                            :currentSlotIndex="currentSlotIndex"
                            :capturedPhotos="capturedPhotosMap"
                            :isCountingDown="isCountingDown"
                            :countdown="countdown"
                            :activeFrame="activeFrame"
                            :backgroundColor="activeBgColor"
                            :activeFilterCss="activeFilter.cssFilter"
                            :mirrorMode="mirrorMode"
                            :isInteractiveReview="false"
                            :isFlashingSlot="isFlashingSlot"
                            @retake="handleRetake"
                        />
                    </div>

                    <!-- BEAUTY FILTER CHIPS (Float right above bottom toolbar) -->
                    <div class="flex items-center justify-center w-full px-2">
                        <div class="flex items-center gap-1.5 p-1.5 rounded-2xl bg-black/70 backdrop-blur-md border border-white/15 shadow-xl overflow-x-auto max-w-full scrollbar-none">
                            <button
                                v-for="filter in cameraFilters"
                                :key="filter.id"
                                @click="activeFilterId = filter.id"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all whitespace-nowrap"
                                :class="activeFilterId === filter.id 
                                    ? 'bg-gradient-to-r from-amber-400 to-amber-300 text-slate-950 shadow-md scale-105' 
                                    : 'text-slate-300 hover:text-white hover:bg-white/10'"
                            >
                                <span>{{ filter.icon }}</span>
                                <span>{{ filter.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- BOTTOM CONTROLS & BIG TRIGGER SHUTTER -->
                    <div class="w-full flex items-center justify-between max-w-4xl mx-auto pt-2 border-t border-white/10">
                        <div class="text-left">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">
                                Giliran Foto: Slot {{ currentSlotIndex }}
                            </span>
                            <span class="text-sm font-semibold text-slate-300 hidden sm:block">
                                {{ step === 'countdown' ? 'Tahan pose & senyum...' : 'Posisi kamera ada di kotak slot aktif' }}
                            </span>
                        </div>

                        <!-- SHUTTER BUTTON -->
                        <button
                            v-if="step === 'ready'"
                            @click="startCapture(currentSlotIndex)"
                            class="px-8 md:px-12 py-4 md:py-5 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-400 hover:from-amber-300 hover:to-yellow-200 text-slate-950 font-black text-lg md:text-xl tracking-wider shadow-[0_0_40px_rgba(245,158,11,0.6)] flex items-center gap-3 transition-all transform active:scale-95"
                        >
                            <Camera class="w-6 h-6 stroke-[2.5]" />
                            <span>{{ currentSlotIndex === 1 ? 'MULAI AMBIL FOTO' : `AMBIL FOTO ${currentSlotIndex}` }}</span>
                        </button>

                        <div
                            v-else-if="step === 'countdown'"
                            class="px-8 py-4 rounded-full bg-amber-400/20 border border-amber-400/50 text-amber-300 font-black text-lg flex items-center gap-2"
                        >
                            <span>POSE! ( {{ countdown }} )</span>
                        </div>

                        <!-- MIRROR TOGGLE -->
                        <button
                            @click="mirrorMode = !mirrorMode"
                            class="py-3 px-4 rounded-2xl border transition-all text-xs font-bold flex items-center gap-2"
                            :class="mirrorMode ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                            title="Mirror Mode (Cermin)"
                        >
                            <FlipHorizontal class="w-4 h-4" />
                            <span class="hidden sm:inline">Cermin</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- ========================================================== -->
            <!-- 2. PHOTO REVIEW MODE (Interactive Completed Canvas)       -->
            <!-- ========================================================== -->
            <template v-else-if="step === 'review'">
                <div class="flex-1 flex flex-col justify-between max-w-5xl mx-auto w-full h-full overflow-hidden">
                    <!-- Header -->
                    <div class="text-center mb-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold uppercase tracking-wider mb-1">
                            <Sparkles class="w-3.5 h-3.5" />
                            <span>STRIP FOTO ANDA SELESAI</span>
                        </div>
                        <h2 class="text-2xl md:text-4xl font-black text-white tracking-tight">
                            YOUR MEMORIES
                        </h2>
                        <p class="text-xs md:text-sm text-slate-400 mt-0.5">
                            Sentuh foto jika ingin mengambil ulang, atau ganti warna bingkai di bawah.
                        </p>
                    </div>

                    <!-- Interactive Live Template Canvas with all locked photos -->
                    <div class="flex-1 w-full flex items-center justify-center overflow-hidden py-2">
                        <LiveTemplateCanvas
                            ref="liveCanvasRef"
                            :template="template"
                            :currentSlotIndex="currentSlotIndex"
                            :capturedPhotos="capturedPhotosMap"
                            :activeFrame="activeFrame"
                            :backgroundColor="activeBgColor"
                            :activeFilterCss="activeFilter.cssFilter"
                            :mirrorMode="mirrorMode"
                            :isInteractiveReview="true"
                            @retake="handleRetake"
                        />
                    </div>

                    <!-- FRAME COLOR PALETTE (Pilih Warna Bingkai Ala Life4Cuts) -->
                    <div class="py-2.5 px-4 rounded-2xl bg-black/50 border border-white/10 flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white">Warna Bingkai Strip:</span>
                            <span class="text-[10px] text-slate-400">Pilih warna border</span>
                        </div>

                        <div class="flex items-center gap-2 overflow-x-auto py-0.5">
                            <button
                                v-for="color in frameColors"
                                :key="color.hex"
                                @click="handleSelectBgColor(color.hex)"
                                class="w-7 h-7 rounded-full border-2 transition-all flex items-center justify-center shadow-md hover:scale-115 active:scale-90"
                                :class="activeBgColor === color.hex ? 'border-amber-400 ring-2 ring-amber-400/50 scale-110' : 'border-white/30'"
                                :style="{ backgroundColor: color.hex }"
                                :title="color.name"
                            >
                                <Check v-if="activeBgColor === color.hex" class="w-3.5 h-3.5 stroke-[3]" :class="color.isDark ? 'text-white' : 'text-slate-900'" />
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="pt-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                        <button
                            @click="handleRestartAll"
                            class="py-3.5 px-5 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-300 font-semibold text-xs flex items-center gap-2 border border-white/10 transition-all active:scale-95"
                        >
                            <RotateCcw class="w-4 h-4" />
                            <span>Ulangi Semua Sesi</span>
                        </button>

                        <!-- GANTI / PILIH BINGKAI BUTTON -->
                        <button
                            @click="showFrameModal = true"
                            class="py-3.5 px-5 rounded-2xl bg-gradient-to-r from-amber-500/20 to-purple-500/20 hover:from-amber-500/30 hover:to-purple-500/30 text-amber-300 font-bold text-xs flex items-center gap-2 border border-amber-400/40 shadow-lg transition-all active:scale-95"
                        >
                            <Sparkles class="w-4 h-4 text-amber-400" />
                            <span>{{ activeFrame ? 'Ganti Bingkai Overlay' : '+ Bingkai Overlay' }}</span>
                        </button>

                        <button
                            @click="handleProceedToCompose"
                            class="py-4 px-8 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-base shadow-[0_0_35px_rgba(16,185,129,0.5)] flex items-center gap-3 transition-all active:scale-95"
                        >
                            <Check class="w-5 h-5 stroke-[3]" />
                            <span>PAKAI FOTO & CETAK</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- 3. COMPOSING RENDERING SPINNER                 -->
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
                    <h2 class="text-3xl font-black text-white">Menyusun Strip Desain...</h2>
                    <p class="text-sm text-slate-400 mt-2 max-w-sm">
                        Menggabungkan foto, frame, watermark, dan QR code beresolusi 300 DPI siap cetak.
                    </p>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- 4. FINAL PREVIEW & PRINT / QR DOWNLOAD         -->
            <!-- ============================================== -->
            <template v-else-if="step === 'final'">
                <div class="flex-1 flex flex-col md:flex-row items-center justify-between gap-8 max-w-6xl mx-auto w-full h-full overflow-hidden">
                    <!-- Left: Final Composite Preview Card -->
                    <div class="flex-1 h-full max-h-[78vh] flex items-center justify-center">
                        <div 
                            class="relative max-h-full rounded-3xl overflow-hidden border-2 border-white/20 shadow-[0_0_50px_rgba(0,0,0,0.8)] bg-slate-950 group"
                            :style="{ aspectRatio: `${templateWidth} / ${templateHeight}` }"
                        >
                            <img
                                :src="getAssetUrl(currentSession.final_photo_path) + '?v=' + finalPhotoTimestamp"
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

        <!-- FRAME SELECTOR MODAL -->
        <FrameSelectorModal
            :show="showFrameModal"
            :currentFramePath="activeFrame"
            @close="showFrameModal = false"
            @select="handleSelectFrame"
            @remove="handleRemoveFrame"
        />
    </KioskLayout>
</template>