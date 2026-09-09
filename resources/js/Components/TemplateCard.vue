<script setup lang="ts">
import type { Template } from '@/types';
import { Camera, Check, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    template: Template;
    selected?: boolean;
}>();

const emit = defineEmits<{
    (e: 'select', template: Template): void;
}>();
</script>

<template>
    <div
        @click="emit('select', template)"
        class="group relative flex flex-col rounded-3xl p-5 cursor-pointer transition-all duration-300 transform select-none"
        :class="[
            selected
                ? 'bg-gradient-to-b from-amber-500/20 to-slate-900 border-2 border-amber-400 shadow-[0_0_35px_rgba(245,158,11,0.3)] scale-[1.03]'
                : 'bg-slate-900/80 hover:bg-slate-800/90 border border-white/10 hover:border-white/30 hover:scale-[1.02] shadow-xl'
        ]"
    >
        <!-- Top Badges -->
        <div class="flex items-center justify-between mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/10 text-slate-300 border border-white/10">
                <Camera class="w-3.5 h-3.5 text-amber-400" />
                {{ template.photo_count }} Foto
            </span>

            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono text-slate-400 bg-black/40 border border-white/5">
                {{ template.paper_size }} ({{ template.orientation }})
            </span>
        </div>

        <!-- Template Schematic Visual Preview Box -->
        <div class="relative w-full aspect-[2/3] rounded-2xl bg-slate-950/80 border border-white/10 p-3 flex flex-col justify-between overflow-hidden shadow-inner group-hover:border-amber-400/40 transition-colors">
            <!-- Header simulated -->
            <div class="h-4 w-3/4 mx-auto rounded bg-white/15"></div>

            <!-- Slots simulation -->
            <div class="flex-1 my-3 flex flex-col justify-around gap-2">
                <template v-if="template.photo_count === 1">
                    <div class="h-full rounded-xl bg-gradient-to-br from-amber-500/20 to-purple-500/20 border border-dashed border-white/30 flex items-center justify-center">
                        <Sparkles class="w-8 h-8 text-amber-400/60 animate-pulse" />
                    </div>
                </template>
                <template v-else-if="template.photo_count === 4">
                    <div class="grid grid-cols-2 grid-rows-2 gap-2 h-full">
                        <div v-for="n in 4" :key="n" class="rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-[10px] text-slate-400 font-bold">
                            {{ n }}
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div v-for="n in Math.min(template.photo_count, 3)" :key="n" class="h-16 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-xs text-slate-300 font-medium">
                        Slot {{ n }}
                    </div>
                </template>
            </div>

            <!-- Footer simulated QR & Date -->
            <div class="flex items-center justify-between pt-1 border-t border-white/10">
                <div class="h-2.5 w-16 rounded bg-white/15"></div>
                <div class="w-5 h-5 rounded bg-white/20"></div>
            </div>

            <!-- Selected Checkmark Overlay -->
            <div
                v-if="selected"
                class="absolute inset-0 bg-amber-500/10 backdrop-blur-[2px] flex items-center justify-center"
            >
                <div class="w-14 h-14 rounded-full bg-amber-400 text-slate-950 flex items-center justify-center shadow-2xl animate-bounce">
                    <Check class="w-8 h-8 stroke-[3]" />
                </div>
            </div>
        </div>

        <!-- Template Meta Details -->
        <div class="mt-4 text-center">
            <h3 class="font-bold text-lg text-white group-hover:text-amber-300 transition-colors">
                {{ template.name }}
            </h3>
            <p class="mt-1 text-xs text-slate-400 line-clamp-2 leading-relaxed">
                {{ template.description || 'Pilihan gaya foto booth modern & elegan.' }}
            </p>
        </div>

        <!-- Select Button -->
        <button
            class="mt-4 w-full py-3 rounded-xl font-semibold text-sm transition-all shadow-lg flex items-center justify-center gap-2"
            :class="[
                selected
                    ? 'bg-amber-400 text-slate-950 hover:bg-amber-300'
                    : 'bg-white/10 hover:bg-white/20 text-white border border-white/10'
            ]"
        >
            <Check v-if="selected" class="w-4 h-4" />
            <span>{{ selected ? 'Terpilih' : 'Pilih Gaya Ini' }}</span>
        </button>
    </div>
</template>