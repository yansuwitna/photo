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
    RefreshCw
} from 'lucide-vue-next';
import DeviceStatusBadge from './DeviceStatusBadge.vue';
import { useDeviceStore } from '@/stores/deviceStore';

const deviceStore = useDeviceStore();

const props = defineProps<{
    isLive?: boolean;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);

const showGrid = ref(true);
const showFaceGuide = ref(true);
const showSafeArea = ref(true);
const mirrorMode = ref(true);
const zoomLevel = ref(1.0);
const brightness = ref(100);
const isFullscreen = ref(false);

const camera = computed(() => deviceStore.camera);

let stream: MediaStream | null = null;
let animationId: number | null = null;

onMounted(async () => {
    await initWebcamOrSimulated();
});

onUnmounted(() => {
    stopCameraStream();
});

async function initWebcamOrSimulated() {
    try {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1920 }, height: { ideal: 1080 }, facingMode: 'user' },
                audio: false
            });
            if (videoRef.value) {
                videoRef.value.srcObject = stream;
                videoRef.value.play();
                return;
            }
        }
    } catch (err) {
        console.info('No direct browser webcam, using high-fidelity studio liveview canvas.');
    }
    // Fallback: animated photobooth studio preview canvas
    runCanvasSimulation();
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
        stream.getTracks().forEach(t => t.stop());
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
</script>

<template>
    <div class="relative w-full h-full flex flex-col bg-black overflow-hidden rounded-2xl border border-white/10 shadow-2xl">
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
                ref={videoRef}
                autoplay
                playsinline
                muted
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-200"
                :class="{ '-scale-x-100': mirrorMode }"
                :style="{ transform: `${mirrorMode ? 'scaleX(-1)' : 'scaleX(1)'} scale(${zoomLevel})`, filter: `brightness(${brightness}%)` }"
            ></video>

            <!-- Fallback Canvas if Simulated -->
            <canvas
                ref={canvasRef}
                width="1280"
                height="720"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-200"
                :class="{ '-scale-x-100': mirrorMode }"
                :style="{ transform: `${mirrorMode ? 'scaleX(-1)' : 'scaleX(1)'} scale(${zoomLevel})`, filter: `brightness(${brightness}%)` }"
            ></canvas>

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

            <!-- SLOT OVERLAY INJECTION (via slot) -->
            <slot />
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
                    @click="mirrorMode = !mirrorMode"
                    class="p-2.5 rounded-xl border transition-all text-xs flex items-center gap-1.5"
                    :class="mirrorMode ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-white/10 border-white/10 text-slate-300 hover:bg-white/20'"
                    title="Mirror Mode"
                >
                    <FlipHorizontal class="w-4 h-4" />
                    <span class="hidden sm:inline">Cermin</span>
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