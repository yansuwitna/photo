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
    <!-- Countdown Overlay: NO BLUR, NO BLACKOUT - Kamera tetap 100% jernih dan tajam -->
    <div
        v-if="isCountingDown || countdown === 0"
        class="absolute inset-0 z-40 flex flex-col justify-between items-center p-6 pointer-events-none transition-all duration-200"
    >
        <!-- Top Slot Indicator Badge -->
        <div class="flex items-center gap-2.5 px-5 py-2 rounded-full bg-black/70 border border-white/20 text-white font-bold text-xs md:text-sm tracking-wider shadow-2xl animate-fade-in">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span>
            <span>MENGAMBIL FOTO {{ slotIndex }} DARI {{ totalSlots }}</span>
        </div>

        <!-- Central Countdown Number: Transparent background so user's face is 100% visible -->
        <div class="relative flex flex-col items-center justify-center select-none my-auto">
            <div class="relative flex items-center justify-center">
                <!-- Outer subtle pulse ring (see-through interior) -->
                <div
                    v-if="countdown > 0"
                    class="absolute w-44 h-44 rounded-full border-2 border-amber-400/40 animate-ping"
                ></div>
                <div
                    v-if="countdown > 0"
                    class="absolute w-36 h-36 rounded-full border-2 border-dashed border-white/30 animate-spin"
                    style="animation-duration: 6s;"
                ></div>

                <!-- Number with crisp drop shadow, NO opaque background -->
                <span
                    :key="countdown"
                    class="font-black tracking-tighter text-amber-300 drop-shadow-[0_4px_30px_rgba(0,0,0,0.95)] transform transition-transform duration-200"
                    :class="countdown === 0 ? 'text-5xl md:text-6xl text-center text-white drop-shadow-[0_0_35px_rgba(245,158,11,1)] scale-110' : 'text-8xl md:text-9xl scale-100 animate-bounce'"
                >
                    {{ displayText }}
                </span>
            </div>
        </div>

        <!-- Bottom Guidance Pill -->
        <div class="px-5 py-2 rounded-full bg-black/60 border border-white/15 text-white text-xs md:text-sm font-semibold tracking-wide drop-shadow-md">
            {{ countdown === 0 ? '📸 Senyum! Tahan posisi terbaik Anda...' : '👀 Tatap kamera & bersiap...' }}
        </div>
    </div>
</template>