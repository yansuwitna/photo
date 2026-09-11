<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { 
    Camera, 
    RotateCcw, 
    Check, 
    CheckCircle2, 
    X, 
    Sparkles, 
    RefreshCw, 
    ArrowRight, 
    FlipHorizontal,
    Smile,
    Clock,
    Layers,
    Sliders
} from 'lucide-vue-next';
import { useAudioStore } from '@/stores/audioStore';
import { getAssetUrl } from '@/utils/url';
import axios from 'axios';
import { showError } from '@/utils/swal';

const props = withDefaults(
    defineProps<{
        show: boolean;
        session: any;
        slotIndex: number;
        totalSlots?: number;
        cameraName?: string;
    }>(),
    {
        totalSlots: 3,
        cameraName: 'Kamera Booth Utama',
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'captured', data: { session: any; photo: any; slotIndex: number; isComplete: boolean }): void;
    (e: 'compose'): void;
    (e: 'nextSlot', nextSlot: number): void;
}>();

const audioStore = useAudioStore();

// Phase: 'viewfinder' | 'countdown' | 'capturing' | 'result'
const phase = ref<'viewfinder' | 'countdown' | 'capturing' | 'result'>('countdown');
const timerDuration = ref<number>(3);
const countdown = ref<number>(3);
const isCountingDown = ref(false);
const isFlashing = ref(false);
const isProcessing = ref(false);

const capturedPhoto = ref<any>(null);
const capturedPhotoUrl = ref<string | null>(null);

// Video Stream & Canvas
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mirrorMode = ref(true);
const hasActiveStream = ref(false);

let mediaStream: MediaStream | null = null;
let countdownInterval: any = null;
let animId: number | null = null;

const currentSlot = ref(props.slotIndex);
const totalSlotCount = computed(() => props.totalSlots || props.session?.total_photos_required || 3);

// Template data from active session
const template = computed(() => props.session?.template);

// Active slot element from template
const activeSlotElement = computed(() => {
    const tpl = template.value;
    if (!tpl?.elements || !Array.isArray(tpl.elements)) return null;
    return tpl.elements.find(
        (el: any) => el.type === 'photo_slot' && Number(el.slot_index) === Number(currentSlot.value)
    ) || null;
});

// Calculate actual slot dimensions and aspect ratio to match the real physical print frame
const slotFrameDimensions = computed(() => {
    const tpl = template.value;
    const tWidth = Number(tpl?.width) || (tpl?.paper_size === 'Strip 2x6' ? 600 : 1200);
    const tHeight = Number(tpl?.height) || 1800;
    const el = activeSlotElement.value;

    if (el && Number(el.width) > 0 && Number(el.height) > 0) {
        const pixelW = Math.round((Number(el.width) / 100) * tWidth);
        const pixelH = Math.round((Number(el.height) / 100) * tHeight);
        const ratio = pixelW / pixelH;
        return {
            pixelWidth: pixelW,
            pixelHeight: pixelH,
            aspectRatio: ratio,
            borderRadius: Number(el.border_radius) || 10,
            borderWidth: Number(el.border_width) || 0,
            borderColor: el.border_color || '#f59e0b',
            rotation: Number(el.rotation) || 0,
            label: el.label || `Foto ${currentSlot.value}`,
            ratioFormatted: ratio >= 1 ? `${ratio.toFixed(2)}:1 (Landscape)` : `1:${(1 / ratio).toFixed(2)} (Portrait)`,
        };
    }

    // Accurate fallback based on template category / paper_size
    const count = Number(tpl?.photo_count) || totalSlotCount.value || 3;
    const isStrip = tpl?.paper_size === 'Strip 2x6' || tWidth === 600 || tpl?.frame_style === 'strip';

    if (isStrip) {
        // Strip 600x1800 px
        if (count === 2) {
            return { pixelWidth: 480, pixelHeight: 702, aspectRatio: 480 / 702, borderRadius: 12, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1:1.46 (Portrait)' };
        } else if (count === 4) {
            return { pixelWidth: 480, pixelHeight: 351, aspectRatio: 480 / 351, borderRadius: 8, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1.37:1 (Landscape)' };
        } else {
            // Default Strip 3 Foto (480x450 px)
            return { pixelWidth: 480, pixelHeight: 450, aspectRatio: 480 / 450, borderRadius: 10, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1.07:1 (Standard)' };
        }
    } else {
        // Full 4R 1200x1800 px
        if (count === 1) {
            return { pixelWidth: 1008, pixelHeight: 1404, aspectRatio: 1008 / 1404, borderRadius: 16, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1:1.39 (Portrait)' };
        } else if (count === 2) {
            return { pixelWidth: 960, pixelHeight: 702, aspectRatio: 960 / 702, borderRadius: 12, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1.37:1 (Landscape)' };
        } else if (count === 4) {
            return { pixelWidth: 504, pixelHeight: 702, aspectRatio: 504 / 702, borderRadius: 12, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1:1.39 (Portrait)' };
        } else {
            // Full 6 Foto (504x432 px)
            return { pixelWidth: 504, pixelHeight: 432, aspectRatio: 504 / 432, borderRadius: 8, borderWidth: 0, borderColor: '#f59e0b', rotation: 0, label: `Foto ${currentSlot.value}`, ratioFormatted: '1.17:1 (Standard)' };
        }
    }
});

watch(
    () => props.slotIndex,
    (newSlot) => {
        currentSlot.value = newSlot;
    }
);

watch(
    () => props.show,
    async (isOpen) => {
        if (isOpen) {
            currentSlot.value = props.slotIndex;
            // IMMEDIATELY reset state so previous photo NEVER shows at the beginning!
            capturedPhotoUrl.value = null;
            capturedPhoto.value = null;
            phase.value = 'countdown';
            countdown.value = timerDuration.value;
            isCountingDown.value = true;
            isProcessing.value = false;
            isFlashing.value = false;

            await nextTick();
            await initCamera();
            startCountdown();
        } else {
            cleanup();
        }
    }
);

onMounted(async () => {
    if (props.show) {
        capturedPhotoUrl.value = null;
        capturedPhoto.value = null;
        phase.value = 'countdown';
        await initCamera();
        startCountdown();
    }
});

onUnmounted(() => {
    cleanup();
});

function cleanup() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    stopCameraStream();
    isCountingDown.value = false;
    isProcessing.value = false;
    isFlashing.value = false;
}

// Video Element Setter via callback ref to guarantee binding
function setVideoRef(el: any) {
    if (el) {
        videoRef.value = el as HTMLVideoElement;
        if (mediaStream) {
            if (el.srcObject !== mediaStream) {
                el.srcObject = mediaStream;
            }
            el.play().catch(() => {});
        }
    }
}

// Camera initialization
async function initCamera() {
    stopCameraStream();
    try {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            const constraintTiers: MediaStreamConstraints[] = [
                { video: { width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false },
                { video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
                { video: true, audio: false }
            ];

            let stream: MediaStream | null = null;
            for (const constraints of constraintTiers) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    if (stream) break;
                } catch (e) {}
            }

            if (stream) {
                mediaStream = stream;
                if (videoRef.value) {
                    videoRef.value.srcObject = mediaStream;
                    await videoRef.value.play();
                }
                hasActiveStream.value = true;
                return;
            }
        }
    } catch (err) {
        console.warn('Webcam tidak tersedia, menggunakan simulasi studio photobooth.', err);
    }

    hasActiveStream.value = false;
    runStudioSimulation();
}

function stopCameraStream() {
    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }
    if (animId) {
        cancelAnimationFrame(animId);
        animId = null;
    }
}

function runStudioSimulation() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let tick = 0;
    const render = () => {
        tick += 0.025;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Gradient studio
        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        grad.addColorStop(0, '#0f172a');
        grad.addColorStop(0.5, '#1e1b4b');
        grad.addColorStop(1, '#020617');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Light warm glow
        const x = canvas.width * 0.5 + Math.sin(tick) * 70;
        const y = canvas.height * 0.45 + Math.cos(tick) * 40;
        const radial = ctx.createRadialGradient(x, y, 10, x, y, 180);
        radial.addColorStop(0, 'rgba(245, 158, 11, 0.35)');
        radial.addColorStop(1, 'rgba(245, 158, 11, 0)');
        ctx.fillStyle = radial;
        ctx.beginPath();
        ctx.arc(x, y, 180, 0, Math.PI * 2);
        ctx.fill();

        // Silhouette / portrait
        ctx.fillStyle = 'rgba(255, 255, 255, 0.15)';
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height * 0.38, 80, 0, Math.PI * 2);
        ctx.fill();

        ctx.beginPath();
        ctx.ellipse(canvas.width / 2, canvas.height * 0.8, 190, 110, 0, 0, Math.PI * 2);
        ctx.fill();

        animId = requestAnimationFrame(render);
    };
    render();
}

// Start countdown sequence
function startCountdown() {
    if (isProcessing.value) return;

    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }

    capturedPhotoUrl.value = null;
    capturedPhoto.value = null;
    phase.value = 'countdown';
    countdown.value = timerDuration.value;
    isCountingDown.value = true;

    // Audio instruction: "Foto ke-[slot]. Siapkan pose terbaik Anda!"
    audioStore.speakInstruction(`Foto ke-${currentSlot.value}. Siapkan pose terbaik Anda!`);

    countdownInterval = setInterval(async () => {
        countdown.value -= 1;

        if (countdown.value > 0) {
            audioStore.playCountdown(countdown.value);
        } else if (countdown.value === 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            isCountingDown.value = false;

            // At 0: play smile
            audioStore.playSmile();

            // After 450ms: Shutter snap + Flash + Capture execution
            setTimeout(async () => {
                await executeShutterAndCapture();
            }, 450);
        }
    }, 1000);
}

// Execute Shutter & Capture with EXACT FRAME CROPPING
async function executeShutterAndCapture() {
    audioStore.playShutter();

    // Trigger visual flash
    isFlashing.value = true;
    setTimeout(() => {
        isFlashing.value = false;
    }, 350);

    phase.value = 'capturing';
    isProcessing.value = true;

    try {
        let imageData: string | null = null;

        // 1. Capture webcam frame if available with EXACT aspect ratio crop matching the frame
        const video = videoRef.value;
        if (video && hasActiveStream.value && video.videoWidth > 0) {
            const offscreen = document.createElement('canvas');
            const targetRatio = slotFrameDimensions.value.aspectRatio;
            const videoRatio = video.videoWidth / video.videoHeight;

            let cropW = video.videoWidth;
            let cropH = video.videoHeight;
            let cropX = 0;
            let cropY = 0;

            if (videoRatio > targetRatio) {
                cropH = video.videoHeight;
                cropW = Math.round(video.videoHeight * targetRatio);
                cropX = Math.round((video.videoWidth - cropW) / 2);
                cropY = 0;
            } else {
                cropW = video.videoWidth;
                cropH = Math.round(video.videoWidth / targetRatio);
                cropX = 0;
                cropY = Math.round((video.videoHeight - cropH) / 2);
            }

            offscreen.width = cropW;
            offscreen.height = cropH;
            const ctx = offscreen.getContext('2d');
            if (ctx) {
                if (mirrorMode.value) {
                    ctx.translate(offscreen.width, 0);
                    ctx.scale(-1, 1);
                }
                ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
                imageData = offscreen.toDataURL('image/jpeg', 0.95);
            }
        } else if (canvasRef.value) {
            // 2. Fallback capture from simulation canvas
            const simCanvas = canvasRef.value;
            const offscreen = document.createElement('canvas');
            offscreen.width = slotFrameDimensions.value.pixelWidth || 640;
            offscreen.height = slotFrameDimensions.value.pixelHeight || 480;
            const ctx = offscreen.getContext('2d');
            if (ctx) {
                if (mirrorMode.value) {
                    ctx.translate(offscreen.width, 0);
                    ctx.scale(-1, 1);
                }
                ctx.drawImage(simCanvas, 0, 0, offscreen.width, offscreen.height);
                imageData = offscreen.toDataURL('image/jpeg', 0.95);
            }
        }

        const payload: Record<string, any> = {
            slot_index: currentSlot.value,
        };
        if (imageData) {
            payload.image_data = imageData;
        }

        const res = await axios.post(`/api/session/${props.session.id}/capture`, payload);

        if (res.data.success) {
            capturedPhoto.value = res.data.photo;
            const photoPath = res.data.photo?.original_path || res.data.photo?.thumbnail_path;
            
            // Critical cache buster: ensures browser immediately fetches and displays the new retaken photo!
            capturedPhotoUrl.value = getAssetUrl(photoPath) + '?v=' + Date.now();

            phase.value = 'result';
            audioStore.playSuccess();

            emit('captured', {
                session: res.data.session,
                photo: res.data.photo,
                slotIndex: currentSlot.value,
                isComplete: Boolean(res.data.is_complete),
            });
        } else {
            showError('Gagal Mengambil Foto', res.data.message || 'Kamera tidak merespon.');
            phase.value = 'viewfinder';
        }
    } catch (err: any) {
        console.error('Capture error', err);
        showError('Gagal Remote Capture', err.response?.data?.message || 'Terjadi kesalahan sistem saat mengambil foto.');
        phase.value = 'viewfinder';
    } finally {
        isProcessing.value = false;
    }
}

// Retake this slot: cleanly resets photo and re-attaches stream
async function handleRetake() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }

    // Immediately clear photo so old picture is gone
    capturedPhotoUrl.value = null;
    capturedPhoto.value = null;
    phase.value = 'countdown';
    countdown.value = timerDuration.value;
    isCountingDown.value = true;
    isProcessing.value = false;
    isFlashing.value = false;

    await nextTick();
    if (!mediaStream || !hasActiveStream.value) {
        await initCamera();
    } else if (videoRef.value) {
        if (videoRef.value.srcObject !== mediaStream) {
            videoRef.value.srcObject = mediaStream;
        }
        videoRef.value.play().catch(() => {});
    }

    startCountdown();
}

// Next slot
function handleGoToNextSlot() {
    const nextSlot = currentSlot.value + 1;
    if (nextSlot <= totalSlotCount.value) {
        currentSlot.value = nextSlot;
        emit('nextSlot', nextSlot);
        handleRetake();
    } else {
        emit('close');
    }
}

function handleClose() {
    cleanup();
    emit('close');
}

function handleCompose() {
    emit('compose');
    handleClose();
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-md p-3 sm:p-6 select-none animate-fade-in"
    >
        <div class="relative w-full max-w-3xl bg-slate-900 border border-white/15 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[94vh]">
            <!-- FLASH OVERLAY (FLICKER EFFECT) -->
            <div v-if="isFlashing" class="absolute inset-0 bg-white z-50 animate-flash pointer-events-none"></div>

            <!-- TOP HEADER -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-slate-950/70 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-400 shadow-sm">
                        <Camera class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs sm:text-sm font-black text-white">VIEWFINDER KIOSK</span>
                            <span class="px-2 py-0.5 rounded-full bg-red-600 text-white text-[9px] font-black uppercase flex items-center gap-1 shadow">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                <span>LIVE</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ cameraName }} • Template: <span class="text-slate-200 font-semibold">{{ template?.name || 'Photobooth' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Slot Indicator Badge -->
                    <div class="px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>FOTO SLOT {{ currentSlot }} / {{ totalSlotCount }}</span>
                    </div>

                    <!-- Close Button -->
                    <button
                        @click="handleClose"
                        class="p-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-slate-400 hover:text-white transition-colors"
                        title="Tutup Viewfinder"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- MAIN STAGE / VIEWFINDER AREA (SEUKURAN FRAME AKTUAL) -->
            <div class="relative flex-1 w-full bg-slate-950/95 overflow-hidden flex flex-col items-center justify-center p-3 sm:p-4 min-h-[380px] sm:min-h-[460px] max-h-[64vh]">
                
                <!-- ACTUAL FRAME SIZED VIEWFINDER CONTAINER -->
                <div
                    class="relative flex items-center justify-center transition-all duration-300 shadow-[0_0_60px_rgba(0,0,0,0.95)] max-h-[50vh] sm:max-h-[54vh] max-w-[94%] h-full"
                    :style="{
                        aspectRatio: `${slotFrameDimensions.aspectRatio}`,
                        width: 'auto',
                    }"
                >
                    <!-- THE FRAME BOX WITH EXACT ASPECT RATIO & BORDERS -->
                    <div
                        class="relative w-full h-full bg-black overflow-hidden border-2 border-amber-400 shadow-2xl flex items-center justify-center ring-4 ring-black/80"
                        :style="{
                            borderRadius: `${slotFrameDimensions.borderRadius}px`,
                            borderColor: slotFrameDimensions.borderWidth ? slotFrameDimensions.borderColor : '#f59e0b',
                            borderWidth: `${Math.max(2, slotFrameDimensions.borderWidth)}px`,
                            transform: slotFrameDimensions.rotation ? `rotate(${slotFrameDimensions.rotation}deg)` : undefined,
                        }"
                    >
                        <!-- CASE A: RESULT VIEW ("Langsung Menampilkan Hasilnya") -->
                        <template v-if="phase === 'result' && capturedPhotoUrl">
                            <div class="relative w-full h-full flex items-center justify-center bg-black animate-fade-in">
                                <img
                                    :key="capturedPhotoUrl"
                                    :src="capturedPhotoUrl"
                                    alt="Hasil Jepretan Foto"
                                    class="w-full h-full object-cover select-none"
                                />

                                <!-- Success Badge on top of image -->
                                <div class="absolute top-3 left-3 z-10 px-3 py-1.5 rounded-full bg-emerald-500 text-slate-950 font-black text-xs flex items-center gap-1.5 shadow-lg backdrop-blur-xs">
                                    <CheckCircle2 class="w-3.5 h-3.5 stroke-[2.5]" />
                                    <span>Foto Slot {{ currentSlot }} OK</span>
                                </div>

                                <!-- Resolution info pill -->
                                <div class="absolute bottom-3 right-3 z-10 px-2.5 py-1 rounded-lg bg-black/80 text-slate-200 font-mono text-[10px] border border-white/20">
                                    {{ capturedPhoto?.width || slotFrameDimensions.pixelWidth }} × {{ capturedPhoto?.height || slotFrameDimensions.pixelHeight }} px
                                </div>
                            </div>
                        </template>

                        <!-- CASE B: LIVE VIEWFINDER & COUNTDOWN -->
                        <template v-else>
                            <!-- Live Video Element (Cropped naturally to frame's exact aspect ratio) -->
                            <video
                                v-show="hasActiveStream"
                                :ref="setVideoRef"
                                autoplay
                                playsinline
                                muted
                                class="w-full h-full object-cover transition-transform duration-150"
                                :class="{ '-scale-x-100': mirrorMode }"
                            ></video>

                            <!-- Fallback Studio Canvas Simulation -->
                            <canvas
                                v-show="!hasActiveStream"
                                ref="canvasRef"
                                width="640"
                                height="480"
                                class="w-full h-full object-cover"
                                :class="{ '-scale-x-100': mirrorMode }"
                            ></canvas>

                            <!-- PRO VIEWFINDER CORNER BRACKETS [ ] ON ACTUAL FRAME CORNERS -->
                            <div class="absolute inset-2.5 pointer-events-none z-10">
                                <div class="absolute top-0 left-0 w-5 h-5 border-t-2 border-l-2 border-amber-400 rounded-tl-sm shadow-sm"></div>
                                <div class="absolute top-0 right-0 w-5 h-5 border-t-2 border-r-2 border-amber-400 rounded-tr-sm shadow-sm"></div>
                                <div class="absolute bottom-0 left-0 w-5 h-5 border-b-2 border-l-2 border-amber-400 rounded-bl-sm shadow-sm"></div>
                                <div class="absolute bottom-0 right-0 w-5 h-5 border-b-2 border-r-2 border-amber-400 rounded-br-sm shadow-sm"></div>
                            </div>

                            <!-- Subtle Center Crosshair -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10 opacity-30">
                                <div class="w-6 h-0.5 bg-amber-400"></div>
                                <div class="h-6 w-0.5 bg-amber-400 absolute"></div>
                            </div>

                            <!-- Subtle Rule-of-Thirds Grid -->
                            <div class="absolute inset-0 grid grid-cols-3 grid-rows-3 pointer-events-none z-10 opacity-15">
                                <div class="border-r border-b border-white"></div>
                                <div class="border-r border-b border-white"></div>
                                <div class="border-b border-white"></div>
                                <div class="border-r border-b border-white"></div>
                                <div class="border-r border-b border-white"></div>
                                <div class="border-b border-white"></div>
                                <div class="border-r border-white"></div>
                                <div class="border-r border-white"></div>
                                <div></div>
                            </div>

                            <!-- Slot Label Watermark inside Frame -->
                            <div class="absolute top-2.5 right-2.5 z-20 px-2 py-0.5 rounded-full bg-black/60 text-amber-300 text-[9px] font-black border border-amber-400/40 shadow">
                                Slot #{{ currentSlot }}
                            </div>

                            <!-- COUNTDOWN OVERLAY DIRECTLY OVER ACTUAL FRAME -->
                            <div
                                v-if="phase === 'countdown'"
                                class="absolute inset-0 z-30 flex flex-col items-center justify-center pointer-events-none select-none"
                            >
                                <div class="relative flex items-center justify-center">
                                    <!-- Outer pulse rings -->
                                    <div
                                        v-if="countdown > 0"
                                        class="absolute w-36 h-36 sm:w-44 sm:h-44 rounded-full border-2 border-amber-400/40 animate-ping"
                                    ></div>
                                    <div
                                        v-if="countdown > 0"
                                        class="absolute w-28 h-28 sm:w-36 sm:h-36 rounded-full border-2 border-dashed border-white/30 animate-spin"
                                        style="animation-duration: 5s;"
                                    ></div>

                                    <!-- Countdown Number Display -->
                                    <span
                                        :key="countdown"
                                        class="font-black tracking-tighter text-amber-300 drop-shadow-[0_4px_30px_rgba(0,0,0,0.95)] transform transition-transform"
                                        :class="countdown === 0 ? 'text-3xl sm:text-4xl text-white animate-bounce drop-shadow-[0_0_25px_rgba(245,158,11,1)]' : 'text-7xl sm:text-8xl animate-scale-up'"
                                    >
                                        {{ countdown === 0 ? '📸 SENYUM!' : countdown }}
                                    </span>
                                </div>

                                <div class="mt-4 px-3.5 py-1 rounded-full bg-black/70 border border-white/15 text-white text-[11px] font-semibold tracking-wide drop-shadow-md">
                                    {{ countdown === 0 ? 'Tahan pose terbaik...' : '👀 Tatap kamera booth...' }}
                                </div>
                            </div>

                            <!-- PROCESSING / CAPTURING SPINNER -->
                            <div
                                v-else-if="phase === 'capturing' || isProcessing"
                                class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-black/70 backdrop-blur-xs select-none"
                            >
                                <div class="w-12 h-12 rounded-full border-4 border-amber-400 border-t-transparent animate-spin mb-2.5"></div>
                                <span class="text-xs font-black text-white uppercase tracking-wider">Menyimpan Foto Frame...</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- INFO BADGE: UKURAN FRAME AKTUAL YANG DIGUNAKAN -->
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-[11px] text-slate-400 bg-black/60 px-3.5 py-1.5 rounded-full border border-white/10 shadow-sm select-none">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span class="font-bold text-white">Frame Aktual:</span>
                    <span class="font-mono text-amber-300 font-bold">{{ slotFrameDimensions.pixelWidth }} × {{ slotFrameDimensions.pixelHeight }} px</span>
                    <span class="text-slate-500">•</span>
                    <span>Rasio <strong class="text-slate-200">{{ slotFrameDimensions.ratioFormatted }}</strong></span>
                    <span class="text-slate-500">•</span>
                    <span class="text-slate-300">{{ slotFrameDimensions.label }}</span>
                </div>
            </div>

            <!-- BOTTOM CONTROLS & ACTION BAR -->
            <div class="px-5 py-3.5 border-t border-white/10 bg-slate-950/80 z-20 flex flex-wrap items-center justify-between gap-3">
                <!-- If Showing Result: Actions to Retake or Proceed -->
                <template v-if="phase === 'result'">
                    <div class="flex items-center gap-2">
                        <button
                            @click="handleRetake"
                            class="py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all active:scale-95"
                        >
                            <RotateCcw class="w-4 h-4 text-amber-400" />
                            <span>Ambil Ulang (Retake)</span>
                        </button>

                        <button
                            @click="mirrorMode = !mirrorMode"
                            class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white border border-white/10 transition-all"
                            title="Mirror Camera"
                        >
                            <FlipHorizontal class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <!-- Next Slot Button if slots remaining -->
                        <button
                            v-if="currentSlot < totalSlotCount"
                            @click="handleGoToNextSlot"
                            class="py-2.5 px-5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-black text-xs flex items-center gap-2 shadow-lg transition-all active:scale-95"
                        >
                            <span>Lanjut ke Foto {{ currentSlot + 1 }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>

                        <!-- If all slots complete: Compose Button -->
                        <button
                            v-else
                            @click="handleCompose"
                            class="py-2.5 px-5 rounded-xl bg-gradient-to-r from-emerald-400 to-emerald-300 hover:from-emerald-300 hover:to-emerald-200 text-slate-950 font-black text-xs flex items-center gap-2 shadow-lg transition-all active:scale-95"
                        >
                            <Sparkles class="w-4 h-4" />
                            <span>Susun Layout (Selesai)</span>
                        </button>

                        <button
                            @click="handleClose"
                            class="py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white font-bold text-xs transition-all"
                        >
                            Tutup
                        </button>
                    </div>
                </template>

                <!-- If In Viewfinder or Countdown Mode -->
                <template v-else>
                    <div class="flex items-center gap-2">
                        <!-- Countdown Duration selector: 1s | 3s | 5s -->
                        <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl border border-white/10 text-xs">
                            <span class="text-[10px] text-slate-400 px-1.5 font-bold uppercase">Timer:</span>
                            <button
                                v-for="t in [1, 3, 5]"
                                :key="t"
                                @click="timerDuration = t"
                                class="px-2 py-0.5 rounded-lg text-xs font-bold transition-all"
                                :class="timerDuration === t ? 'bg-amber-400 text-slate-950 shadow-sm' : 'text-slate-300 hover:text-white'"
                            >
                                {{ t }}s
                            </button>
                        </div>

                        <!-- Mirror Mode Toggle -->
                        <button
                            @click="mirrorMode = !mirrorMode"
                            class="py-1.5 px-2.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 transition-all"
                            :class="mirrorMode ? 'bg-amber-400/20 border-amber-400/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300'"
                            title="Mirror Mode"
                        >
                            <FlipHorizontal class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline">Cermin</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <!-- Trigger Immediate Capture or Restart Countdown -->
                        <button
                            v-if="!isCountingDown && !isProcessing"
                            @click="startCountdown"
                            class="py-2.5 px-6 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs flex items-center gap-2 shadow-lg transition-all active:scale-95"
                        >
                            <Camera class="w-4 h-4 stroke-[2.5]" />
                            <span>Mulai Hitungan Mundur</span>
                        </button>

                        <button
                            v-else-if="isCountingDown"
                            @click="executeShutterAndCapture"
                            class="py-2.5 px-5 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-black text-xs flex items-center gap-2 shadow-lg transition-all active:scale-95"
                        >
                            <Camera class="w-4 h-4 stroke-[2.5]" />
                            <span>Jepret Sekarang (Lewati)</span>
                        </button>

                        <button
                            @click="handleClose"
                            class="py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white font-bold text-xs transition-all"
                        >
                            Batal
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes flash {
    0% { opacity: 0.95; }
    100% { opacity: 0; }
}
.animate-flash {
    animation: flash 0.35s ease-out forwards;
}

@keyframes scaleUp {
    0% { transform: scale(0.5); opacity: 0; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-scale-up {
    animation: scaleUp 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes fadeIn {
    0% { opacity: 0; transform: scale(0.98); }
    100% { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
