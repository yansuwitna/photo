<script setup lang="ts">
import { computed } from 'vue';
import { Camera, X, CheckCircle2, RotateCcw, Sliders, HardDrive, Clock } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    imageUrl?: string;
    cameraName?: string;
    width?: number;
    height?: number;
    metadata?: Record<string, any>;
    isLoading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'retake'): void;
}>();

const resolutionText = computed(() => {
    if (props.width && props.height) {
        return `${props.width} × ${props.height} px`;
    }
    return '1920 × 1280 px';
});

const shutterParams = computed(() => {
    const meta = props.metadata || {};
    const parts = [];
    if (meta.iso) parts.push(`ISO ${meta.iso}`);
    if (meta.shutter) parts.push(`${meta.shutter}s`);
    if (meta.aperture) parts.push(meta.aperture);
    return parts.length ? parts.join(' • ') : 'ISO 400 • 1/160s • f/2.8';
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 animate-fade-in"
    >
        <div class="relative w-full max-w-2xl rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl flex flex-col">
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-slate-400 hover:text-white transition-colors z-20"
                title="Tutup"
            >
                <X class="w-5 h-5" />
            </button>

            <!-- Header -->
            <div class="flex items-center gap-3 pb-3 border-b border-white/10 pr-10">
                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-400 shadow-md">
                    <Camera class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base md:text-lg font-black text-white">HASIL UJI JEPRET KAMERA</h3>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">
                            Live Shot OK
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ cameraName || 'Canon EOS R6 Mark II' }} • Hasil tangkapan sensor kamera
                    </p>
                </div>
            </div>

            <!-- Image Viewport -->
            <div class="relative w-full my-4 rounded-2xl bg-black/70 border border-white/10 overflow-hidden flex items-center justify-center min-h-[260px] max-h-[50vh] shadow-inner">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex flex-col items-center justify-center p-8 text-amber-400 gap-3">
                    <div class="w-10 h-10 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs font-bold tracking-wider uppercase">Sedang Memproses Jepretan Kamera...</p>
                </div>

                <!-- Image Result -->
                <img
                    v-else-if="imageUrl"
                    :src="imageUrl"
                    alt="Hasil Uji Kamera"
                    class="w-full h-full max-h-[50vh] object-contain select-none"
                />

                <div v-else class="text-slate-500 text-xs p-8 text-center">
                    Belum ada foto tangkapan yang tersedia.
                </div>
            </div>

            <!-- Metadata Pills -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mb-5 text-xs">
                <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-medium">Resolusi</span>
                        <span class="font-bold text-white font-mono text-[11px]">{{ resolutionText }}</span>
                    </div>
                </div>

                <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2">
                    <Sliders class="w-4 h-4 text-amber-400 shrink-0" />
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-medium">Parameter Shutter</span>
                        <span class="font-bold text-white font-mono text-[11px]">{{ shutterParams }}</span>
                    </div>
                </div>

                <div class="col-span-2 sm:col-span-1 p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2">
                    <Clock class="w-4 h-4 text-sky-400 shrink-0" />
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-medium">Waktu Uji Coba</span>
                        <span class="font-bold text-white font-mono text-[11px]">Baru saja</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 pt-3 border-t border-white/10">
                <button
                    @click="emit('retake')"
                    :disabled="isLoading"
                    class="flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 active:scale-95 text-slate-950 font-black text-xs flex items-center justify-center gap-2 shadow-lg transition-all disabled:opacity-50"
                >
                    <RotateCcw class="w-4 h-4 stroke-[2.5]" />
                    <span>{{ isLoading ? 'Menjepret...' : 'Jepret Uji Coba Lagi' }}</span>
                </button>

                <button
                    @click="emit('close')"
                    class="py-3 px-6 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white font-bold text-xs border border-white/10 transition-all"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
