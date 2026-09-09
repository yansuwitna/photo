<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    countdown: number;
    isCountingDown: boolean;
    slotIndex: number;
    totalSlots: number;
}>();

const displayText = computed(() => {
    if (props.countdown === 0) return 'SMILE! 📸';
    return props.countdown.toString();
});
</script>

<template>
    <div
        v-if="isCountingDown || countdown === 0"
        class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-black/40 backdrop-blur-sm transition-all duration-300 pointer-events-none"
    >
        <!-- Slot Indicator -->
        <div class="mb-6 px-6 py-2 rounded-full bg-black/60 border border-white/20 text-white font-medium text-lg tracking-wider shadow-2xl backdrop-blur-md">
            MENGAMBIL FOTO {{ slotIndex }} DARI {{ totalSlots }}
        </div>

        <!-- Giant Glow Circle & Number -->
        <div class="relative flex items-center justify-center">
            <!-- Pulsing outer ring -->
            <div class="absolute w-72 h-72 rounded-full bg-gradient-to-tr from-amber-500/30 to-purple-500/30 animate-ping opacity-60"></div>
            <div class="absolute w-60 h-60 rounded-full border-4 border-dashed border-white/40 animate-spin" style="animation-duration: 8s;"></div>

            <!-- Central Badge -->
            <div class="relative z-10 flex items-center justify-center w-52 h-52 rounded-full bg-gradient-to-br from-slate-900/90 to-black/90 border-2 border-white/30 shadow-[0_0_60px_rgba(255,255,255,0.25)]">
                <span
                    :key="countdown"
                    class="font-extrabold tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-rose-300 to-white transform transition-all duration-300 scale-100 animate-bounce"
                    :class="countdown === 0 ? 'text-4xl text-center px-4 leading-tight' : 'text-8xl'"
                >
                    {{ displayText }}
                </span>
            </div>
        </div>

        <p class="mt-8 text-xl font-light text-slate-200 tracking-widest uppercase">
            {{ countdown === 0 ? 'Tahan posisi terbaik Anda!' : 'Bersiap di depan kamera...' }}
        </p>
    </div>
</template>