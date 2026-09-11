<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { 
    Grid, 
    Smile, 
    FlipHorizontal, 
    Maximize, 
    Minimize, 
    Sliders, 
    Battery, 
    HardDrive, 
    Camera as CameraIcon,
    RefreshCw,
    Sparkles
} from 'lucide-vue-next';
import DeviceStatusBadge from './DeviceStatusBadge.vue';
import { useDeviceStore } from '@/stores/deviceStore';
import { getAssetUrl } from '@/utils/url';

const deviceStore = useDeviceStore();

const props = defineProps<{
    isLive?: boolean;
    overlayFrame?: string | null;
    aspectRatio?: number;
    slotLabel?: string;
    slotDimensions?: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);

const showGrid = ref(true);
const showFaceGuide = ref(true);
const showSafeArea = ref(true);
const showFrameGuide = ref(true);
const mirrorMode = ref(true);
const zoomLevel = ref(1.0);
const brightness = ref(100);
const isFullscreen = ref(false);

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

const camera = computed(() => deviceStore.camera);

const facingMode = ref<'user' | 'environment'>('user');
const isFlashActive = ref(false);
const hasActiveStream = ref(false);
const cameraError = ref<string | null>(null);

let stream: MediaStream | null = null;
let animationId: number | null = null;

onMounted(async () => {
    await initWebcamOrSimulated();
});

onUnmounted(() => {
    stopCameraStream();
});

async function initWebcamOrSimulated() {
    stopCameraStream();
    cameraError.value = null;

    // Pengecekan HTTPS — wajib untuk iPhone (Safari) dan browser modern di Android/VPS
    if (
        typeof window !== 'undefined' &&
        !window.isSecureContext &&
        location.hostname !== 'localhost' &&
        location.hostname !== '127.0.0.1'
    ) {
        const msg = `Akses kamera diblokir browser karena koneksi tidak aman (HTTP). Buka via https:// untuk mengizinkan kamera di iPhone / Android.`;
        cameraError.value = msg;
        hasActiveStream.value = false;
        runCanvasSimulation();
        return;
    }

    if (typeof navigator === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        cameraError.value = 'Browser tidak mendukung akses kamera. Gunakan Safari (iOS) atau Chrome (Android) versi terbaru.';
        hasActiveStream.value = false;
        runCanvasSimulation();
        return;
    }

    try {
        // Constraint tiers: prioritaskan facingMode untuk HP/iPhone, lalu fallback universal
        const constraintTiers: MediaStreamConstraints[] = [
            { video: { facingMode: facingMode.value, width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false },
            { video: { facingMode: facingMode.value, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
            { video: { facingMode: facingMode.value }, audio: false },
            { video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
            { video: true, audio: false },
        ];

        let activeStream: MediaStream | null = null;
        let lastError: any = null;
        for (const constraints of constraintTiers) {
            try {
                activeStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (activeStream) break;
            } catch (e: any) {
                lastError = e;
            }
        }

        if (activeStream) {
            stream = activeStream;
            if (videoRef.value) {
                videoRef.value.srcObject = stream;
                await videoRef.value.play();
                hasActiveStream.value = true;
                cameraError.value = null;
                return;
            }
        }

        // Tampilkan error yang informatif berdasarkan jenis kesalahan
        if (lastError?.name === 'NotAllowedError' || lastError?.name === 'PermissionDeniedError') {
            cameraError.value = 'Izin kamera ditolak. Ketuk ikon kamera / gembok di address bar browser lalu pilih "Izinkan".';
        } else if (lastError?.name === 'NotFoundError' || lastError?.name === 'DevicesNotFoundError') {
            cameraError.value = 'Kamera tidak ditemukan di perangkat ini.';
        } else if (lastError?.name === 'NotReadableError' || lastError?.name === 'TrackStartError') {
            cameraError.value = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu muat ulang halaman.';
        } else if (lastError) {
            cameraError.value = `Gagal membuka kamera: ${lastError.message || lastError.name || 'Error tidak diketahui'}`;
        }

    } catch (err: any) {
        cameraError.value = `Gagal membuka kamera: ${err?.message || 'Error tidak diketahui'}`;
        console.warn('Camera init error:', err);
    }

    hasActiveStream.value = false;
    runCanvasSimulation();
}

async function switchFacingMode() {
    facingMode.value = facingMode.value === 'user' ? 'environment' : 'user';
    mirrorMode.value = facingMode.value === 'user';
    await initWebcamOrSimulated();
}

function runCanvasSimulation() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let tick = 0;
    const render = () => {
        tick += 0.02;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Elegant studio backdrop gradient
        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        grad.addColorStop(0, '#1e1b4b');
        grad.addColorStop(0.5, '#312e81');
        grad.addColorStop(1, '#0f172a');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Simulated floating studio lighting effects
        for (let i = 0; i < 5; i++) {
            const x = canvas.width * 0.5 + Math.sin(tick + i) * 200;
            const y = canvas.height * 0.4 + Math.cos(tick * 0.8 + i) * 120;
            const rad = 140 + i * 20;

            const radial = ctx.createRadialGradient(x, y, 10, x, y, rad);
            radial.addColorStop(0, 'rgba(168, 85, 247, 0.25)');
            radial.addColorStop(1, 'rgba(168, 85, 247, 0)');
            ctx.fillStyle = radial;
            ctx.beginPath();
            ctx.arc(x, y, rad, 0, Math.PI * 2);
            ctx.fill();
        }

        // Silhouette / portrait avatar placeholder
        ctx.fillStyle = 'rgba(255, 255, 255, 0.15)';
        ctx.beginPath();
        // Head
        ctx.arc(canvas.width / 2, canvas.height * 0.38, 110, 0, Math.PI * 2);
        ctx.fill();
        // Shoulders
        ctx.beginPath();
        ctx.ellipse(canvas.width / 2, canvas.height * 0.72, 280, 160, 0, 0, Math.PI * 2);
        ctx.fill();

        // Subtitle
        ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
        ctx.font = '24px Poppins, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('LIVE VIEW STREAMING (READY)', canvas.width / 2, canvas.height * 0.9);

        animationId = requestAnimationFrame(render);
    };
    render();
}

function stopCameraStream() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    if (animationId) {
        cancelAnimationFrame(animationId);
        animationId = null;
    }
}

function toggleFullscreen() {
    const elem = document.documentElement;
    if (!isFullscreen.value) {
        if (elem.requestFullscreen) elem.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
        isFullscreen.value = false;
    }
}

function triggerFlash() {
    isFlashActive.value = true;
    setTimeout(() => {
        isFlashActive.value = false;
    }, 250);
}

function captureCurrentFrame(): string | null {
    triggerFlash();

    const combinedFilter = [
        activeFilter.value.cssFilter !== 'none' ? activeFilter.value.cssFilter : '',
        brightness.value !== 100 ? `brightness(${brightness.value}%)` : ''
    ].filter(Boolean).join(' ');

    // 1. Coba capture dari video stream (HP / Tablet / Webcam fisik)
    const video = videoRef.value;
    if (video && hasActiveStream.value && video.videoWidth > 0) {
        const offscreen = document.createElement('canvas');

        // Sesuaikan crop persis dengan aspect ratio slot target seperti yang tampak di live view
        const targetRatio = props.aspectRatio || (video.videoWidth / video.videoHeight);
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
        if (!ctx) return null;

        if (combinedFilter) {
            ctx.filter = combinedFilter;
        }

        if (mirrorMode.value) {
            ctx.translate(offscreen.width, 0);
            ctx.scale(-1, 1);
        }

        ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
        return offscreen.toDataURL('image/jpeg', 0.95);
    }

    // 2. Fallback: capture dari canvas simulation
    const canvas = canvasRef.value;
    if (canvas) {
        const offscreen = document.createElement('canvas');
        const targetRatio = props.aspectRatio || (canvas.width / canvas.height);
        const canvasRatio = canvas.width / canvas.height;

        let cropW = canvas.width;
        let cropH = canvas.height;
        let cropX = 0;
        let cropY = 0;

        if (canvasRatio > targetRatio) {
            cropH = canvas.height;
            cropW = Math.round(canvas.height * targetRatio);
            cropX = Math.round((canvas.width - cropW) / 2);
            cropY = 0;
        } else {
            cropW = canvas.width;
            cropH = Math.round(canvas.width / targetRatio);
            cropX = 0;
            cropY = Math.round((canvas.height - cropH) / 2);
        }

        offscreen.width = cropW;
        offscreen.height = cropH;
        const ctx = offscreen.getContext('2d');
        if (ctx) {
            if (combinedFilter) {
                ctx.filter = combinedFilter;
            }
            if (mirrorMode.value) {
                ctx.translate(offscreen.width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(canvas, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
            return offscreen.toDataURL('image/jpeg', 0.95);
        }
        return canvas.toDataURL('image/jpeg', 0.95);
    }

    return null;
}

defineExpose({
    captureCurrentFrame,
    triggerFlash,
    switchFacingMode,
    facingMode,
    hasActiveStream
});
</script>

<template>
    <div class="relative w-full h-full flex flex-col bg-black overflow-hidden rounded-2xl border border-white/10 shadow-2xl">
        <!-- FLASH OVERLAY -->
        <div v-if="isFlashActive" class="absolute inset-0 bg-white z-50 animate-flash"></div>

        <!-- TOP DEVICE STATUS BAR -->
        <div class="absolute top-0 inset-x-0 z-30 flex items-center justify-between px-6 py-4 bg-gradient-to-b from-black/80 via-black/40 to-transparent backdrop-blur-sm">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-300 bg-white/10 px-3 py-1.5 rounded-full border border-white/10">
                    <CameraIcon class="w-4 h-4 text-amber-400" />
                    <span class="font-medium">{{ camera.name }}</span>
                </div>
                <DeviceStatusBadge :status="camera.status" size="sm" />
            </div>

            <div class="flex items-center gap-4 text-xs font-mono text-slate-300">
                <div class="flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-full border border-white/10">
                    <Battery class="w-4 h-4 text-emerald-400" />
                    <span>{{ camera.battery_level }}%</span>
                </div>
                <div class="flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-full border border-white/10">
                    <HardDrive class="w-4 h-4 text-sky-400" />
                    <span>{{ camera.storage_remaining }}</span>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-slate-400">
                    <span>ISO {{ camera.iso }}</span>
                    <span>•</span>
                    <span>{{ camera.shutter_speed }}s</span>
                    <span>•</span>
                    <span>{{ camera.aperture }}</span>
                </div>
            </div>
        </div>

        <!-- MAIN LIVE VIEW AREA -->
        <div class="relative flex-1 w-full h-full flex items-center justify-center overflow-hidden">
            <!-- Real Video Element if Webcam Available -->
            <video
                ref="videoRef"
                autoplay
                playsinline
                muted
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-200"
                :class="{ '-scale-x-100': mirrorMode }"
                :style="{
                    transform: `${mirrorMode ? 'scaleX(-1)' : 'scaleX(1)'} scale(${zoomLevel})`,
                    filter: activeFilter.cssFilter !== 'none' ? `${activeFilter.cssFilter} brightness(${brightness}%)` : `brightness(${brightness}%)`
                }"
            ></video>

            <!-- Fallback Canvas if Simulated -->
            <canvas
                ref="canvasRef"
                width="1280"
                height="720"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-200"
                :class="{ '-scale-x-100': mirrorMode }"
                :style="{
                    transform: `${mirrorMode ? 'scaleX(-1)' : 'scaleX(1)'} scale(${zoomLevel})`,
                    filter: activeFilter.cssFilter !== 'none' ? `${activeFilter.cssFilter} brightness(${brightness}%)` : `brightness(${brightness}%)`
                }"
            ></canvas>

            <!-- CAMERA ERROR BANNER — tampil jika kamera gagal diakses -->
            <div
                v-if="cameraError && !hasActiveStream"
                class="absolute bottom-24 inset-x-4 z-40 flex items-start gap-3 bg-red-950/90 border border-red-500/50 text-red-200 text-xs rounded-xl px-4 py-3 shadow-2xl backdrop-blur-sm"
            >
                <span class="text-red-400 text-base shrink-0">⚠️</span>
                <div>
                    <p class="font-bold text-red-300 mb-0.5">Kamera Tidak Dapat Diakses</p>
                    <p class="leading-relaxed">{{ cameraError }}</p>
                    <button
                        @click="initWebcamOrSimulated"
                        class="mt-2 px-3 py-1 rounded-lg bg-red-500/30 hover:bg-red-500/50 border border-red-500/40 text-red-200 font-semibold transition-all text-xs"
                    >
                        🔄 Coba Lagi
                    </button>
                </div>
            </div>

            <!-- RULE OF THIRDS GRID OVERLAY -->
            <div v-if="showGrid" class="absolute inset-0 grid grid-cols-3 grid-rows-3 pointer-events-none z-10 border border-white/10">
                <div class="border-r border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-r border-b border-white/20"></div>
                <div class="border-b border-white/20"></div>
                <div class="border-r border-white/20"></div>
                <div class="border-r border-white/20"></div>
                <div></div>
            </div>

            <!-- FACE POSITIONING GUIDE OVERLAY -->
            <div v-if="showFaceGuide" class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                <div class="w-64 h-80 rounded-[50%] border-2 border-dashed border-amber-400/60 shadow-[0_0_25px_rgba(251,191,36,0.2)] flex flex-col items-center justify-start pt-6">
                    <Smile class="w-8 h-8 text-amber-400/80 mb-2" />
                    <span class="text-xs font-semibold text-amber-300/90 tracking-wider bg-black/40 px-3 py-1 rounded-full">
                        POSISIKAN WAJAH
                    </span>
                </div>
            </div>

            <!-- SAFE AREA MARGIN -->
            <div v-if="showSafeArea" class="absolute inset-8 pointer-events-none border border-dashed border-white/30 rounded-xl z-10">
                <span class="absolute top-2 left-2 text-[10px] tracking-widest text-white/50 uppercase">Safe Area (4R Print)</span>
            </div>

            <!-- PRO VIEWFINDER CORNER BRACKETS -->
            <div class="absolute inset-3 pointer-events-none z-10">
                <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-amber-400 rounded-tl-sm"></div>
                <div class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-amber-400 rounded-tr-sm"></div>
                <div class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-amber-400 rounded-bl-sm"></div>
                <div class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 border-amber-400 rounded-br-sm"></div>
            </div>

            <!-- SLOT FRAMING BADGE -->
            <div v-if="slotLabel" class="absolute top-3 left-3 z-20 pointer-events-none flex items-center gap-2 px-3 py-1 rounded-full bg-black/70 border border-amber-400/40 text-amber-300 text-xs font-bold shadow-lg">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>{{ slotLabel }}</span>
                <span v-if="slotDimensions" class="text-white/60 font-mono text-[10px]">({{ slotDimensions }})</span>
            </div>

            <!-- LIVE OVERLAY FRAME GUIDE ON CAMERA PREVIEW -->
            <div
                v-if="overlayFrame && showFrameGuide"
                class="absolute inset-0 pointer-events-none z-20 flex items-center justify-center overflow-hidden transition-opacity duration-300"
            >
                <img
                    :src="getAssetUrl(overlayFrame)"
                    alt="Frame Overlay Guide"
                    class="w-full h-full object-contain drop-shadow-[0_0_20px_rgba(0,0,0,0.8)]"
                />
            </div>

            <!-- SLOT OVERLAY INJECTION (via slot) -->
            <slot />
        </div>

        <!-- BEAUTY FILTER FLOATING BAR (Like BeautyPlus) -->
        <div class="absolute bottom-20 inset-x-0 z-30 flex items-center justify-center pointer-events-auto px-4">
            <div class="flex items-center gap-1.5 p-1.5 rounded-2xl bg-black/60 backdrop-blur-md border border-white/15 shadow-2xl overflow-x-auto max-w-full scrollbar-none">
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

        <!-- BOTTOM CONTROLS TOOLBAR -->
        <div class="absolute bottom-0 inset-x-0 z-30 flex items-center justify-between px-6 py-4 bg-gradient-to-t from-black/80 via-black/40 to-transparent backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <button
                    @click="showGrid = !showGrid"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5"
                    :class="showGrid ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                    title="Grid Garis Bantu"
                >
                    <Grid class="w-4 h-4" />
                    <span class="hidden sm:inline">Grid</span>
                </button>

                <button
                    @click="showFaceGuide = !showFaceGuide"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5"
                    :class="showFaceGuide ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                    title="Panduan Posisi Wajah"
                >
                    <Smile class="w-4 h-4" />
                    <span class="hidden sm:inline">Panduan Wajah</span>
                </button>

                <button
                    v-if="overlayFrame"
                    @click="showFrameGuide = !showFrameGuide"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5"
                    :class="showFrameGuide ? 'bg-amber-500/20 border-amber-500/40 text-amber-300 shadow-md' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                    title="Panduan Bingkai di Kamera"
                >
                    <Sparkles class="w-4 h-4" />
                    <span class="hidden sm:inline">Bingkai Guide</span>
                </button>

                <button
                    @click="mirrorMode = !mirrorMode"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5"
                    :class="mirrorMode ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                    title="Mirror Mode"
                >
                    <FlipHorizontal class="w-4 h-4" />
                    <span class="hidden sm:inline">Cermin</span>
                </button>

                <button
                    v-if="hasActiveStream"
                    @click="switchFacingMode"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5 bg-sky-500/20 border-sky-500/40 text-sky-300 hover:bg-sky-500/30"
                    title="Ganti Kamera Depan / Belakang"
                >
                    <RefreshCw class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ facingMode === 'user' ? 'Kamera Belakang' : 'Kamera Depan' }}</span>
                </button>
            </div>

            <div class="flex items-center gap-3">
                <!-- Zoom Slider -->
                <div class="hidden md:flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-xl border border-white/10 text-xs">
                    <span class="text-slate-400">Zoom:</span>
                    <input
                        type="range"
                        min="1"
                        max="2"
                        step="0.1"
                        v-model.number="zoomLevel"
                        class="w-20 accent-amber-500 cursor-pointer"
                    />
                    <span class="w-8 font-mono">{{ zoomLevel }}x</span>
                </div>

                <button
                    @click="toggleFullscreen"
                    class="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 text-slate-200 transition-all"
                    title="Layar Penuh"
                >
                    <Maximize v-if="!isFullscreen" class="w-4 h-4" />
                    <Minimize v-else class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>