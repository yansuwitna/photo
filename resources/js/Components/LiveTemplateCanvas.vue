<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import type { Template, TemplateElement } from '@/types';
import { Camera, QrCode, RotateCcw, Lock } from 'lucide-vue-next';
import { getAssetUrl } from '@/utils/url';
import type { PhotoboothSticker } from '@/types/stickers';

const props = withDefaults(
    defineProps<{
        template: Template;
        currentSlotIndex: number;
        capturedPhotos?: Record<number, string>;
        isCountingDown?: boolean;
        countdown?: number;
        activeFrame?: string | null;
        backgroundColor?: string;
        frameTheme?: string | null;
        mirrorMode?: boolean;
        isInteractiveReview?: boolean;
        isFlashingSlot?: number | null;
        activeSticker?: PhotoboothSticker | null;
    }>(),
    {
        capturedPhotos: () => ({}),
        isCountingDown: false,
        countdown: 3,
        activeFrame: null,
        backgroundColor: '#ffffff',
        frameTheme: 'classic_white',
        mirrorMode: true,
        isInteractiveReview: false,
        isFlashingSlot: null,
        activeSticker: null,
    }
);

const emit = defineEmits<{
    (e: 'retake', slotIndex: number): void;
    (e: 'stream-ready', stream: MediaStream): void;
    (e: 'camera-status', status: { hasStream: boolean; error: string | null; cameras: MediaDeviceInfo[] }): void;
}>();

// Video & Stream References
const masterVideoRef = ref<HTMLVideoElement | null>(null);
const activeVideoEl = ref<HTMLVideoElement | null>(null);
const simulatedCanvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);
const hasActiveStream = ref(false);
const isConnectingCamera = ref(false);
const cameraError = ref<string | null>(null);
const availableCameras = ref<MediaDeviceInfo[]>([]);
const selectedCameraId = ref<string>('');
let animId: number | null = null;

const templateWidth = computed(() => props.template?.width || 1200);
const templateHeight = computed(() => props.template?.height || 1800);

// Robust Elements Resolution: Auto-generates fallback layout if template.elements is empty
const elements = computed<TemplateElement[]>(() => {
    if (props.template?.elements && props.template.elements.length > 0) {
        return props.template.elements;
    }

    const count = Number(props.template?.photo_count) || 4;
    const isGrid = props.template?.frame_style === 'grid';
    const fallback: TemplateElement[] = [];

    // Title text
    fallback.push({
        id: 901,
        template_id: props.template?.id || 1,
        type: 'text',
        content: '{event_name}',
        x: 5,
        y: 2.5,
        width: 90,
        height: 4,
        font_family: 'Poppins',
        font_size: 26,
        font_color: '#0f172a',
        font_weight: 'bold',
        text_align: 'center',
        z_index: 2,
    });

    if (count === 1) {
        fallback.push({
            id: 1,
            template_id: props.template?.id || 1,
            type: 'photo_slot',
            slot_index: 1,
            label: 'Foto 1',
            x: 8,
            y: 8,
            width: 84,
            height: 78,
            border_width: 0,
            border_radius: 16,
            z_index: 1,
        });
    } else if (count === 2) {
        fallback.push(
            {
                id: 1,
                template_id: props.template?.id || 1,
                type: 'photo_slot',
                slot_index: 1,
                label: 'Foto 1',
                x: 10,
                y: 8,
                width: 80,
                height: 39,
                border_width: 0,
                border_radius: 12,
                z_index: 1,
            },
            {
                id: 2,
                template_id: props.template?.id || 1,
                type: 'photo_slot',
                slot_index: 2,
                label: 'Foto 2',
                x: 10,
                y: 50,
                width: 80,
                height: 39,
                border_width: 0,
                border_radius: 12,
                z_index: 1,
            }
        );
    } else if (count === 3) {
        for (let i = 1; i <= 3; i++) {
            fallback.push({
                id: i,
                template_id: props.template?.id || 1,
                type: 'photo_slot',
                slot_index: i,
                label: `Foto ${i}`,
                x: 10,
                y: 8 + (i - 1) * 27,
                width: 80,
                height: 25,
                border_width: 0,
                border_radius: 10,
                z_index: 1,
            });
        }
    } else if (count === 4) {
        if (isGrid) {
            const coords = [
                { x: 6, y: 9 },
                { x: 52, y: 9 },
                { x: 6, y: 50 },
                { x: 52, y: 50 },
            ];
            coords.forEach((c, idx) => {
                fallback.push({
                    id: idx + 1,
                    template_id: props.template?.id || 1,
                    type: 'photo_slot',
                    slot_index: idx + 1,
                    label: `Foto ${idx + 1}`,
                    x: c.x,
                    y: c.y,
                    width: 42,
                    height: 39,
                    border_width: 0,
                    border_radius: 12,
                    z_index: 1,
                });
            });
        } else {
            // Life4Cuts Vertical Strip
            for (let i = 1; i <= 4; i++) {
                fallback.push({
                    id: i,
                    template_id: props.template?.id || 1,
                    type: 'photo_slot',
                    slot_index: i,
                    label: `Foto ${i}`,
                    x: 10,
                    y: 7.5 + (i - 1) * 20.8,
                    width: 80,
                    height: 19.5,
                    border_width: 0,
                    border_radius: 8,
                    z_index: 1,
                });
            }
        }
    } else {
        // Fallback grid
        const cols = 2;
        const rows = Math.ceil(count / cols);
        const itemW = 42;
        const itemH = Math.min(38, Math.floor(78 / rows));
        for (let i = 1; i <= count; i++) {
            const col = (i - 1) % cols;
            const row = Math.floor((i - 1) / cols);
            fallback.push({
                id: i,
                template_id: props.template?.id || 1,
                type: 'photo_slot',
                slot_index: i,
                label: `Foto ${i}`,
                x: col === 0 ? 6 : 52,
                y: 8 + row * (itemH + 2),
                width: itemW,
                height: itemH,
                border_width: 0,
                border_radius: 8,
                z_index: 1,
            });
        }
    }

    // Date & QR
    fallback.push({
        id: 902,
        template_id: props.template?.id || 1,
        type: 'text',
        content: '{date}',
        x: 10,
        y: 93,
        width: 55,
        height: 4,
        font_family: 'Poppins',
        font_size: 18,
        font_color: '#64748b',
        font_weight: 'normal',
        text_align: 'left',
        z_index: 2,
    });
    fallback.push({
        id: 903,
        template_id: props.template?.id || 1,
        type: 'qr_code',
        label: 'QR Code',
        x: 74,
        y: 90,
        width: 16,
        height: 7.5,
        z_index: 3,
    });

    return fallback;
});

// Helper for photo checking
function getCapturedPhoto(slotIndex: any): string | null {
    if (!props.capturedPhotos || slotIndex == null) return null;
    const num = Number(slotIndex);
    const photo = props.capturedPhotos[num] ?? props.capturedPhotos[slotIndex] ?? props.capturedPhotos[String(slotIndex)];
    return photo || null;
}

function isSlotCaptured(slotIndex: any): boolean {
    return Boolean(getCapturedPhoto(slotIndex));
}

function isSlotActiveLive(slotIndex: any): boolean {
    if (props.isInteractiveReview) return false;
    if (isSlotCaptured(slotIndex)) return false;
    return Number(slotIndex) === Number(props.currentSlotIndex);
}

// Video Element Setter via callback ref to guarantee binding inside v-for
function setActiveVideoRef(el: any) {
    if (el) {
        activeVideoEl.value = el as HTMLVideoElement;
        if (mediaStream.value) {
            if (activeVideoEl.value.srcObject !== mediaStream.value) {
                activeVideoEl.value.srcObject = mediaStream.value;
            }
            activeVideoEl.value.play().catch(() => {});
        }
    }
}

watch(activeVideoEl, (newEl) => {
    if (newEl && mediaStream.value) {
        if (newEl.srcObject !== mediaStream.value) {
            newEl.srcObject = mediaStream.value;
        }
        newEl.play().catch(() => {});
    }
});

watch(mediaStream, async (stream) => {
    if (stream) {
        await nextTick();
        attachStreamToVideo();
    }
});

function onVideoLoaded(e: Event) {
    const v = e.target as HTMLVideoElement;
    if (v && v.paused) {
        v.play().catch(() => {});
    }
}

// Background Theme Styling (Admin Template background image, color, or striped pattern)
const canvasBackground = computed(() => {
    if (props.template?.background_image) {
        return `url(${getAssetUrl(props.template.background_image)}) center / cover no-repeat`;
    }

    const nameOrSlug = `${props.template?.slug || ''} ${props.template?.name || ''}`.toLowerCase();
    if (nameOrSlug.includes('pink bows') || nameOrSlug.includes('beautyplus') || props.frameTheme === 'pink_bows') {
        return 'repeating-linear-gradient(90deg, #ffcde2, #ffcde2 24px, #ffffff 24px, #ffffff 48px)';
    }
    if (nameOrSlug.includes('lavender') || nameOrSlug.includes('purple')) {
        return 'repeating-linear-gradient(90deg, #f3e8ff, #f3e8ff 24px, #ffffff 24px, #ffffff 48px)';
    }
    if (nameOrSlug.includes('mint')) {
        return 'repeating-linear-gradient(90deg, #dcfce7, #dcfce7 24px, #ffffff 24px, #ffffff 48px)';
    }

    return props.template?.background_color || props.backgroundColor || '#ffffff';
});

onMounted(async () => {
    await initWebcam();

    // Auto-retry kamera saat user kembali ke tab (setelah mengizinkan akses di settings)
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Gunakan Permissions API untuk auto-reconnect saat izin kamera berubah menjadi 'granted'
    if (typeof navigator !== 'undefined' && navigator.permissions) {
        try {
            const permStatus = await navigator.permissions.query({ name: 'camera' as PermissionName });
            permStatus.addEventListener('change', () => {
                if (permStatus.state === 'granted' && !hasActiveStream.value) {
                    initWebcam();
                }
            });
        } catch (e) {
            // Permissions API tidak tersedia di semua browser — ini opsional
        }
    }
});

onUnmounted(() => {
    stopCamera();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

function handleVisibilityChange() {
    if (document.visibilityState === 'visible' && !hasActiveStream.value && !isConnectingCamera.value) {
        // Delay kecil untuk memastikan browser sudah siap setelah kembali ke tab
        setTimeout(() => {
            if (!hasActiveStream.value) {
                initWebcam();
            }
        }, 500);
    }
}


watch(
    () => props.currentSlotIndex,
    async () => {
        await nextTick();
        attachStreamToVideo();
    }
);

watch(
    () => props.isInteractiveReview,
    async (isReview) => {
        if (!isReview) {
            await nextTick();
            attachStreamToVideo();
        }
    }
);

async function refreshCameraList() {
    try {
        if (typeof navigator !== 'undefined' && navigator.mediaDevices?.enumerateDevices) {
            const allDevices = await navigator.mediaDevices.enumerateDevices();
            const videoInputs = allDevices.filter((d) => d.kind === 'videoinput');
            availableCameras.value = videoInputs;
            if (!selectedCameraId.value && videoInputs.length > 0) {
                selectedCameraId.value = videoInputs[0].deviceId;
            }
        }
    } catch (e) {
        console.warn('Gagal membaca daftar perangkat kamera:', e);
    }
}

async function initWebcam(deviceId?: string) {
    if (isConnectingCamera.value) return;
    stopCamera();
    isConnectingCamera.value = true;
    cameraError.value = null;

    // Helper pendeteksi ketersediaan API getUserMedia (termasuk browser lama/legacy)
    const nav = typeof navigator !== 'undefined' ? (navigator as any) : null;
    const hasMediaDevices = Boolean(nav && nav.mediaDevices && nav.mediaDevices.getUserMedia);
    const hasLegacyGetUserMedia = Boolean(nav && (nav.getUserMedia || nav.webkitGetUserMedia || nav.mozGetUserMedia || nav.msGetUserMedia));

    if (!hasMediaDevices && !hasLegacyGetUserMedia) {
        // Cek jika diblokir karena HTTP di luar localhost
        if (typeof window !== 'undefined' && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            const msg = 'Kamera memerlukan koneksi aman (HTTPS). Silakan buka dengan https://';
            cameraError.value = msg;
            isConnectingCamera.value = false;
            hasActiveStream.value = false;
            emit('camera-status', { hasStream: false, error: msg, cameras: [] });
            runCanvasSimulation();
            return;
        }

        const msg = 'Browser tidak mendukung akses kamera. Gunakan browser modern (Chrome, Safari, Edge, Firefox).';
        cameraError.value = msg;
        isConnectingCamera.value = false;
        hasActiveStream.value = false;
        emit('camera-status', { hasStream: false, error: msg, cameras: [] });
        runCanvasSimulation();
        return;
    }

    const targetDeviceId = deviceId || selectedCameraId.value;

    // Progressive tiers: dari target device, lalu facingMode (penting untuk iPhone/Android), lalu universal fallback
    const constraintTiers: MediaStreamConstraints[] = [];

    if (targetDeviceId) {
        constraintTiers.push({
            video: {
                deviceId: { exact: targetDeviceId },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
            audio: false,
        });
        constraintTiers.push({
            video: {
                deviceId: { exact: targetDeviceId },
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        });
        constraintTiers.push({
            video: {
                deviceId: { exact: targetDeviceId },
            },
            audio: false,
        });
    }

    // Tiers untuk mobile/iPhone: coba kamera depan dulu (user) lalu lingkungan (environment)
    constraintTiers.push({
        video: {
            facingMode: 'user',
            width: { ideal: 1920 },
            height: { ideal: 1080 },
        },
        audio: false,
    });
    constraintTiers.push({
        video: {
            facingMode: 'user',
            width: { ideal: 1280 },
            height: { ideal: 720 },
        },
        audio: false,
    });
    constraintTiers.push({
        video: {
            facingMode: 'user',
        },
        audio: false,
    });

    // Standard progressive tiers fallback
    constraintTiers.push({
        video: {
            width: { ideal: 1280 },
            height: { ideal: 720 },
        },
        audio: false,
    });
    constraintTiers.push({
        video: true,
        audio: false,
    });

    let stream: MediaStream | null = null;
    let lastError: any = null;

    // Universal getUserMedia caller
    const requestUserMedia = async (c: MediaStreamConstraints): Promise<MediaStream> => {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            return await navigator.mediaDevices.getUserMedia(c);
        }
        return new Promise((resolve, reject) => {
            const legacyGetUserMedia =
                nav?.getUserMedia ||
                nav?.webkitGetUserMedia ||
                nav?.mozGetUserMedia ||
                nav?.msGetUserMedia;
            if (legacyGetUserMedia) {
                legacyGetUserMedia.call(nav, c, resolve, reject);
            } else {
                reject(new Error('getUserMedia not supported'));
            }
        });
    };

    for (const constraints of constraintTiers) {
        try {
            stream = await requestUserMedia(constraints);
            if (stream) break;
        } catch (err: any) {
            lastError = err;
            console.warn('Tingkat constraint kamera gagal, mencoba alternatif berikutnya...', constraints, err);
        }
    }

    isConnectingCamera.value = false;

    if (stream) {
        mediaStream.value = stream;
        hasActiveStream.value = true;
        cameraError.value = null;

        await refreshCameraList();

        emit('stream-ready', stream);
        emit('camera-status', { hasStream: true, error: null, cameras: availableCameras.value });

        await nextTick();
        attachStreamToVideo();
        return;
    }

    // Determine readable user-facing error message
    hasActiveStream.value = false;
    let errMsg = 'Kamera tidak dapat diakses.';
    if (lastError?.name === 'NotAllowedError' || lastError?.name === 'PermissionDeniedError') {
        errMsg = 'Izin akses kamera ditolak. Silakan klik ikon gembok/kamera di address bar untuk mengizinkan akses.';
    } else if (lastError?.name === 'NotFoundError' || lastError?.name === 'DevicesNotFoundError') {
        errMsg = 'Perangkat kamera tidak ditemukan. Pastikan webcam terpasang.';
    } else if (lastError?.name === 'NotReadableError' || lastError?.name === 'TrackStartError') {
        errMsg = 'Kamera sedang digunakan aplikasi lain (OBS, Zoom, atau tab browser lain). Tutup aplikasi tersebut dan coba lagi.';
    } else if (lastError?.name === 'OverconstrainedError') {
        errMsg = 'Format resolusi kamera tidak didukung sensor.';
    } else if (lastError?.message) {
        errMsg = `Gagal membuka kamera: ${lastError.message}`;
    }

    cameraError.value = errMsg;
    emit('camera-status', { hasStream: false, error: errMsg, cameras: availableCameras.value });
    console.warn('Kamera fisik tidak dapat diakses:', errMsg, lastError);
    runCanvasSimulation();
}

async function switchCamera(deviceId: string) {
    selectedCameraId.value = deviceId;
    await initWebcam(deviceId);
}

function attachStreamToVideo() {
    if (!mediaStream.value) return;

    if (masterVideoRef.value && masterVideoRef.value.srcObject !== mediaStream.value) {
        masterVideoRef.value.srcObject = mediaStream.value;
        masterVideoRef.value.play().catch(() => {});
    }

    if (activeVideoEl.value) {
        if (activeVideoEl.value.srcObject !== mediaStream.value) {
            activeVideoEl.value.srcObject = mediaStream.value;
        }
        activeVideoEl.value.play().catch(() => {});
    }
}

function stopCamera() {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach((t) => t.stop());
        mediaStream.value = null;
    }
    if (animId) {
        cancelAnimationFrame(animId);
        animId = null;
    }
}

function runCanvasSimulation() {
    if (animId) {
        cancelAnimationFrame(animId);
        animId = null;
    }
    const canvas = simulatedCanvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let tick = 0;
    const render = () => {
        tick += 0.03;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Modern studio gradient
        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        grad.addColorStop(0, '#1e1b4b');
        grad.addColorStop(0.5, '#312e81');
        grad.addColorStop(1, '#0f172a');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Warm light orb
        const x = canvas.width * 0.5 + Math.sin(tick) * 80;
        const y = canvas.height * 0.5 + Math.cos(tick) * 50;
        const radial = ctx.createRadialGradient(x, y, 10, x, y, 160);
        radial.addColorStop(0, 'rgba(245, 158, 11, 0.4)');
        radial.addColorStop(1, 'rgba(245, 158, 11, 0)');
        ctx.fillStyle = radial;
        ctx.beginPath();
        ctx.arc(x, y, 160, 0, Math.PI * 2);
        ctx.fill();

        // Silhouette
        ctx.fillStyle = 'rgba(255, 255, 255, 0.25)';
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height * 0.4, 70, 0, Math.PI * 2);
        ctx.fill();

        ctx.beginPath();
        ctx.ellipse(canvas.width / 2, canvas.height * 0.85, 180, 100, 0, 0, Math.PI * 2);
        ctx.fill();

        animId = requestAnimationFrame(render);
    };
    render();
}

function captureActiveSlot(slotIndex: number): string | null {
    const el = elements.value.find(
        (e) => e.type === 'photo_slot' && Number(e.slot_index) === Number(slotIndex)
    );
    const tWidth = templateWidth.value;
    const tHeight = templateHeight.value;
    const slotW = el ? (el.width / 100) * tWidth : 400;
    const slotH = el ? (el.height / 100) * tHeight : 300;
    const targetRatio = slotW / slotH;

    // Pick active video element or master hidden video element
    const video =
        activeVideoEl.value && activeVideoEl.value.videoWidth > 0
            ? activeVideoEl.value
            : masterVideoRef.value && masterVideoRef.value.videoWidth > 0
            ? masterVideoRef.value
            : null;

    if (video && video.videoWidth > 0) {
        const offscreen = document.createElement('canvas');
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

        if (props.mirrorMode) {
            ctx.translate(offscreen.width, 0);
            ctx.scale(-1, 1);
        }

        ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);

        return offscreen.toDataURL('image/jpeg', 0.95);
    }

    // Fallback simulation capture
    const simCanvas = simulatedCanvasRef.value;
    if (simCanvas) {
        const offscreen = document.createElement('canvas');
        offscreen.width = Math.round(slotW);
        offscreen.height = Math.round(slotH);
        const ctx = offscreen.getContext('2d');
        if (ctx) {
            if (props.mirrorMode) {
                ctx.translate(offscreen.width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(simCanvas, 0, 0, offscreen.width, offscreen.height);
            return offscreen.toDataURL('image/jpeg', 0.95);
        }
    }

    return null;
}

function resolveTextContent(content?: string | null): string {
    if (!content) return '';
    const today = new Date();
    const dateStr = `${today.getFullYear()}.${String(today.getMonth() + 1).padStart(2, '0')}.${String(today.getDate()).padStart(2, '0')}`;
    return content
        .replace('{date}', dateStr)
        .replace('{event_name}', 'PHOTOBOOTH MEMORIES');
}

function computeFontSize(fontSize?: number | null): number {
    return Math.max(9, Math.round((fontSize || 24) * 0.42));
}

defineExpose({
    captureActiveSlot,
    hasActiveStream,
    initWebcam,
    stopCamera,
    mediaStream,
    isConnectingCamera,
    cameraError,
    availableCameras,
    selectedCameraId,
    switchCamera,
    activeVideoEl,
});
</script>

<template>
    <!-- Master Video Element for Instant Warm Capture -->
    <video
        ref="masterVideoRef"
        autoplay
        playsinline
        webkit-playsinline
        muted
        :muted="true"
        class="fixed top-0 left-0 w-2 h-2 opacity-[0.01] pointer-events-none z-0"
    ></video>

    <!-- LIVE INTERACTIVE TEMPLATE CANVAS -->
    <div
        class="relative mx-auto rounded-3xl overflow-hidden shadow-[0_0_60px_rgba(0,0,0,0.85)] border-2 border-white/20 select-none transition-all duration-300"
        :style="{
            aspectRatio: `${templateWidth} / ${templateHeight}`,
            background: canvasBackground,
            maxHeight: '74vh',
            maxWidth: '100%',
            height: '100%',
        }"
    >
        <!-- RENDER TEMPLATE ELEMENTS (Slots, Text, QR) -->
        <template v-for="el in elements" :key="el.id || `slot_${el.slot_index}`">
            <!-- 1. PHOTO SLOT -->
            <div
                v-if="el.type === 'photo_slot'"
                class="absolute overflow-hidden transition-all duration-200"
                :style="{
                    left: `${el.x}%`,
                    top: `${el.y}%`,
                    width: `${el.width}%`,
                    height: `${el.height}%`,
                    borderRadius: `${el.border_radius || 0}px`,
                    borderWidth: `${el.border_width || 0}px`,
                    borderColor: el.border_color || 'transparent',
                    borderStyle: el.border_width ? 'solid' : 'none',
                    zIndex: isSlotActiveLive(el.slot_index) ? 20 : (el.z_index || 1),
                    transform: el.rotation ? `rotate(${el.rotation}deg)` : undefined,
                    transformOrigin: 'center center',
                }"
                :class="[
                    isSlotActiveLive(el.slot_index)
                        ? 'ring-4 ring-amber-400 ring-offset-2 ring-offset-black/50 shadow-2xl scale-[1.01]'
                        : 'border-white/10'
                ]"
            >
                <!-- CASE A: PHOTO ALREADY CAPTURED & LOCKED IN THIS SLOT -->
                <template v-if="isSlotCaptured(el.slot_index)">
                    <div class="group relative w-full h-full bg-slate-900 overflow-hidden">
                        <img
                            :src="getAssetUrl(getCapturedPhoto(el.slot_index)!)"
                            :alt="'Foto ' + el.slot_index"
                            class="w-full h-full object-cover select-none"
                        />

                        <!-- Locked Badge (Frozen and locked in real time) -->
                        <div class="absolute top-1.5 left-1.5 z-10 px-1.5 py-0.5 rounded-full bg-black/70 backdrop-blur-sm text-white text-[8px] font-bold border border-white/20 flex items-center gap-1 shadow">
                            <Lock class="w-2.5 h-2.5 text-emerald-400" />
                            <span>#{{ el.slot_index }}</span>
                        </div>

                        <!-- In Review Mode: Retake Overlay Button -->
                        <div
                            v-if="isInteractiveReview"
                            @click="emit('retake', Number(el.slot_index))"
                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer z-20"
                        >
                            <button class="px-2.5 py-1 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-[10px] font-black flex items-center gap-1 shadow-lg transform active:scale-95">
                                <RotateCcw class="w-3 h-3" />
                                <span>Ulang</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- CASE B: ACTIVE WEBCAM LIVE VIEW IN THIS EXACT SLOT -->
                <template v-else-if="isSlotActiveLive(el.slot_index)">
                    <div class="relative w-full h-full bg-black overflow-hidden flex items-center justify-center">
                        <!-- Live Video Stream (Crystal Clear, Natural) -->
                        <video
                            v-show="hasActiveStream"
                            :ref="setActiveVideoRef"
                            autoplay
                            playsinline
                            webkit-playsinline
                            muted
                            :muted="true"
                            class="w-full h-full object-cover transition-transform duration-150"
                            :class="{ '-scale-x-100': mirrorMode }"
                            @loadedmetadata="onVideoLoaded"
                            @canplay="onVideoLoaded"
                        ></video>

                        <!-- Fallback Canvas if Simulated -->
                        <canvas
                            v-show="!hasActiveStream"
                            ref="simulatedCanvasRef"
                            width="640"
                            height="480"
                            class="w-full h-full object-cover"
                            :class="{ '-scale-x-100': mirrorMode }"
                        ></canvas>

                        <!-- Connecting Spinner Overlay -->
                        <div
                            v-if="isConnectingCamera"
                            class="absolute inset-0 z-25 bg-black/80 backdrop-blur-xs flex flex-col items-center justify-center text-center p-3"
                        >
                            <div class="w-6 h-6 rounded-full border-2 border-amber-400 border-t-transparent animate-spin mb-1.5"></div>
                            <span class="text-[9px] font-bold text-amber-300">Menghubungkan Kamera...</span>
                        </div>

                        <!-- Camera Error Overlay with Retry Button -->
                        <div
                            v-else-if="!hasActiveStream && cameraError"
                            class="absolute inset-0 z-25 bg-black/85 backdrop-blur-xs flex flex-col items-center justify-center text-center p-2"
                        >
                            <div class="w-6 h-6 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mb-1 border border-red-500/30">
                                <Camera class="w-3.5 h-3.5" />
                            </div>
                            <span class="text-[8px] font-bold text-red-200 leading-tight mb-1.5 max-w-[90%]">{{ cameraError }}</span>
                            <button
                                type="button"
                                @click.stop="initWebcam()"
                                class="px-2 py-0.5 rounded-md bg-amber-400 hover:bg-amber-300 text-slate-950 text-[9px] font-black shadow cursor-pointer active:scale-95"
                            >
                                Coba Lagi
                            </button>
                        </div>

                        <!-- Pro Viewfinder Corner Brackets -->
                        <div class="absolute inset-1.5 pointer-events-none z-10">
                            <div class="absolute top-0 left-0 w-3 h-3 border-t-2 border-l-2 border-amber-400"></div>
                            <div class="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-amber-400"></div>
                            <div class="absolute bottom-0 left-0 w-3 h-3 border-b-2 border-l-2 border-amber-400"></div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 border-b-2 border-r-2 border-amber-400"></div>
                        </div>

                        <!-- Red LIVE Badge -->
                        <div class="absolute top-2 left-2 z-20 px-2 py-0.5 rounded-full bg-red-600 text-white text-[9px] font-black flex items-center gap-1 shadow-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                            <span>LIVE</span>
                        </div>

                        <!-- Slot Number Badge -->
                        <div class="absolute top-2 right-2 z-20 px-2 py-0.5 rounded-full bg-black/60 text-amber-300 text-[9px] font-black border border-amber-400/40 shadow">
                            Foto #{{ el.slot_index }}
                        </div>

                        <!-- COUNTDOWN OVERLAY DIRECTLY OVER THIS ACTIVE SLOT (NO BLUR, CRYSTAL CLEAR) -->
                        <div
                            v-if="isCountingDown || countdown === 0"
                            class="absolute inset-0 z-30 flex flex-col items-center justify-center pointer-events-none select-none"
                        >
                            <span
                                :key="countdown"
                                class="font-black text-amber-300 drop-shadow-[0_4px_24px_rgba(0,0,0,0.95)]"
                                :class="countdown === 0 ? 'text-2xl md:text-3xl text-white animate-bounce' : 'text-6xl md:text-7xl animate-scale-up'"
                            >
                                {{ countdown === 0 ? '📸 SMILE!' : countdown }}
                            </span>
                        </div>

                        <!-- FLASH EFFECT ON CAPTURE -->
                        <div
                            v-if="isFlashingSlot === Number(el.slot_index)"
                            class="absolute inset-0 bg-white z-40 animate-flash pointer-events-none"
                        ></div>
                    </div>
                </template>

                <!-- CASE C: FUTURE SLOT NOT CAPTURED YET (Awaiting Turn) -->
                <template v-else>
                    <div class="w-full h-full bg-slate-100/90 border border-dashed border-slate-300/80 flex flex-col items-center justify-center p-2 text-slate-400 text-center">
                        <Camera class="w-5 h-5 mb-1 opacity-40 text-slate-600" />
                        <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider">Slot {{ el.slot_index }}</span>
                        <span class="text-[7px] text-slate-400">Menunggu</span>
                    </div>
                </template>
            </div>

            <!-- 2. TEXT ELEMENT (Header, Subtitle, Date Stamp) -->
            <div
                v-else-if="el.type === 'text'"
                class="absolute flex items-center px-1 pointer-events-none select-none"
                :class="{
                    'justify-center text-center': el.text_align === 'center',
                    'justify-start text-left': el.text_align === 'left',
                    'justify-end text-right': el.text_align === 'right',
                }"
                :style="{
                    left: `${el.x}%`,
                    top: `${el.y}%`,
                    width: `${el.width}%`,
                    height: `${el.height}%`,
                    zIndex: el.z_index || 2,
                    color: el.font_color || '#1e293b',
                    fontSize: `${computeFontSize(el.font_size)}px`,
                    fontFamily: el.font_family || 'Poppins',
                    fontWeight: el.font_weight || 'bold',
                }"
            >
                {{ resolveTextContent(el.content) }}
            </div>

            <!-- 3. QR CODE ELEMENT -->
            <div
                v-else-if="el.type === 'qr_code'"
                class="absolute bg-white border border-slate-300 flex flex-col items-center justify-center p-1 pointer-events-none shadow-sm"
                :style="{
                    left: `${el.x}%`,
                    top: `${el.y}%`,
                    width: `${el.width}%`,
                    height: `${el.height}%`,
                    zIndex: el.z_index || 3,
                    borderRadius: '6px',
                }"
            >
                <QrCode class="w-full h-full text-slate-900 p-0.5" />
            </div>
        </template>

        <!-- 3.5 DECORATIVE STICKER / BADGE (Ala BeautyPlus) -->
        <div
            v-if="activeSticker && activeSticker.id !== 'none'"
            class="absolute bottom-3 left-1/2 -translate-x-1/2 z-25 pointer-events-none select-none max-w-[85%]"
        >
            <div
                v-if="activeSticker.isTextStamp"
                class="px-3.5 py-1 rounded-full shadow-lg border flex flex-col items-center justify-center tracking-wider text-center"
                :style="{
                    backgroundColor: activeSticker.bg || '#0f172a',
                    borderColor: activeSticker.color || '#cbd5e1',
                    color: activeSticker.color || '#ffffff',
                }"
            >
                <span class="text-[8px] sm:text-[9px] font-black uppercase tracking-widest leading-tight">{{ activeSticker.badgeText }}</span>
                <span v-if="activeSticker.badgeSubtext" class="text-[6px] tracking-normal opacity-90 -mt-0.5 leading-tight">{{ activeSticker.badgeSubtext }}</span>
            </div>
            <div
                v-else
                class="w-9 h-9 rounded-full flex items-center justify-center text-lg shadow-lg border border-white/50"
                :style="{ backgroundColor: activeSticker.bg || '#fce7f3' }"
            >
                <span>{{ activeSticker.symbol }}</span>
            </div>
        </div>

        <!-- 3.6 PINK BOWS THEME DECORATIONS (Identik Screenshot 6) -->
        <template v-if="template?.slug?.includes('pink') || template?.name?.toLowerCase().includes('beautyplus') || frameTheme === 'pink_bows' || activeSticker?.hasStripes || activeSticker?.id === 'pink_bows'">
            <!-- 1. Double Hearts di Pojok Kiri Atas Slot 1 -->
            <div class="absolute top-[2.5%] left-[5%] z-26 pointer-events-none flex items-center -space-x-1 drop-shadow-md select-none">
                <span class="text-xl sm:text-2xl transform -rotate-12">🤍</span>
                <span class="text-xl sm:text-2xl transform rotate-6">💗</span>
            </div>

            <!-- 2. Pita Pink Cantik di Pembatas Slot 1 & 2 (Kanan) -->
            <div class="absolute top-[31%] right-[4%] z-26 pointer-events-none transform rotate-12 drop-shadow-md select-none">
                <svg class="w-10 h-10 sm:w-12 sm:h-12 text-pink-400" viewBox="0 0 100 100" fill="none">
                    <path d="M50 50 C28 20, 8 40, 44 52 Z" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M50 50 C72 20, 92 40, 56 52 Z" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M48 52 C32 72, 22 88, 28 96 C36 86, 44 76, 50 56" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M52 52 C68 72, 78 88, 72 96 C64 86, 56 76, 50 56" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <circle cx="50" cy="50" r="7.5" fill="#fda4af" stroke="#db2777" stroke-width="2.5"/>
                </svg>
            </div>

            <!-- 3. Double Hearts di Pembatas Slot 2 & 3 (Tengah) -->
            <div class="absolute top-[59.5%] left-1/2 -translate-x-1/2 z-26 pointer-events-none flex items-center -space-x-1 drop-shadow-md select-none">
                <span class="text-xl sm:text-2xl transform -rotate-12">🤍</span>
                <span class="text-xl sm:text-2xl transform rotate-6">💗</span>
            </div>

            <!-- 4. Pita Pink Cantik di Margin Bawah (Kiri) -->
            <div class="absolute bottom-[2%] left-[6%] z-26 pointer-events-none transform -rotate-12 drop-shadow-md select-none">
                <svg class="w-11 h-11 sm:w-14 sm:h-14 text-pink-400" viewBox="0 0 100 100" fill="none">
                    <path d="M50 50 C28 20, 8 40, 44 52 Z" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M50 50 C72 20, 92 40, 56 52 Z" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M48 52 C32 72, 22 88, 28 96 C36 86, 44 76, 50 56" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <path d="M52 52 C68 72, 78 88, 72 96 C64 86, 56 76, 50 56" fill="#f472b6" stroke="#db2777" stroke-width="2.5"/>
                    <circle cx="50" cy="50" r="7.5" fill="#fda4af" stroke="#db2777" stroke-width="2.5"/>
                </svg>
            </div>
        </template>

        <!-- 4. OVERLAY FRAME TRANSPARENT PNG ON TOP OF ALL ELEMENTS -->
        <div
            v-if="template?.overlay_image || activeFrame"
            class="absolute inset-0 pointer-events-none z-30 overflow-hidden"
        >
            <img
                :src="getAssetUrl(activeFrame || template?.overlay_image!)"
                alt="Frame Overlay"
                class="w-full h-full object-fill"
            />
        </div>
    </div>
</template>

<style scoped>
@keyframes flash {
    0% { opacity: 0.9; }
    100% { opacity: 0; }
}
.animate-flash {
    animation: flash 0.35s ease-out forwards;
}

@keyframes scaleUp {
    0% { transform: scale(0.6); opacity: 0; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-scale-up {
    animation: scaleUp 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
</style>
