<script setup lang="ts">
import { computed } from 'vue';
import { Printer, CheckCircle, RefreshCw, Check } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    progress: number;
    isPrinting: boolean;
    isCompleted: boolean;
    copies: number;
}>();

const emit = defineEmits<{
    (e: 'print-again'): void;
    (e: 'done'): void;
}>();

const title = computed(() => {
    if (props.isCompleted) return 'PRINT COMPLETE!';
    if (props.progress > 20) return 'Sedang Mencetak...';
    return 'Menyiapkan Foto Anda...';
});

const subtitle = computed(() => {
    if (props.isCompleted) return 'Silakan ambil hasil foto berkualitas tinggi Anda di tray printer.';
    return 'Foto sedang diproses dan dikirim ke printer resolusi tinggi.';
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 backdrop-blur-lg p-4 animate-fade-in"
    >
        <div class="relative w-full max-w-md rounded-3xl bg-slate-900 border border-white/20 p-8 shadow-2xl flex flex-col items-center text-center">
            <!-- Icon Indicator -->
            <div class="relative mb-6">
                <div
                    class="w-24 h-24 rounded-3xl flex items-center justify-center shadow-2xl transition-all duration-500"
                    :class="[
                        isCompleted
                            ? 'bg-emerald-500/20 text-emerald-400 border-2 border-emerald-400/40 shadow-[0_0_40px_rgba(16,185,129,0.3)]'
                            : 'bg-amber-500/20 text-amber-400 border-2 border-amber-400/40 shadow-[0_0_40px_rgba(245,158,11,0.3)] animate-pulse'
                    ]"
                >
                    <CheckCircle v-if="isCompleted" class="w-12 h-12" />
                    <Printer v-else class="w-12 h-12" />
                </div>
            </div>

            <!-- Titles -->
            <h3 class="text-2xl font-black text-white tracking-tight">{{ title }}</h3>
            <p class="text-sm text-slate-300 mt-2 max-w-sm leading-relaxed">{{ subtitle }}</p>

            <!-- Progress Bar -->
            <div class="w-full my-6 bg-slate-800 rounded-full h-4 overflow-hidden border border-white/10 p-0.5 shadow-inner">
                <div
                    class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                    :class="isCompleted ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-amber-500 to-rose-400'"
                    :style="{ width: `${progress}%` }"
                >
                    <span class="text-[10px] font-bold text-slate-950">{{ progress }}%</span>
                </div>
            </div>

            <div class="text-xs text-slate-400 font-mono mb-6">
                Jumlah Salinan: {{ copies }} Lembar (Format 4R Studio)
            </div>

            <!-- Action Buttons when complete -->
            <div v-if="isCompleted" class="flex flex-col sm:flex-row items-center gap-3 w-full">
                <button
                    @click="emit('print-again')"
                    class="w-full sm:w-1/2 py-3.5 px-4 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/15 flex items-center justify-center gap-2 transition-all active:scale-95"
                >
                    <RefreshCw class="w-4 h-4" />
                    <span>Cetak Lagi</span>
                </button>

                <button
                    @click="emit('done')"
                    class="w-full sm:w-1/2 py-3.5 px-4 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95"
                >
                    <Check class="w-4 h-4 stroke-[3]" />
                    <span>Selesai</span>
                </button>
            </div>
        </div>
    </div>
</template>