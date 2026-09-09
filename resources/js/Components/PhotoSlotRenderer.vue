<script setup lang="ts">
import { ref } from 'vue';
import type { SessionPhoto, Template } from '@/types';
import { RotateCcw, CheckCircle, ZoomIn } from 'lucide-vue-next';

const props = defineProps<{
    photos: SessionPhoto[];
    totalSlots: number;
    template?: Template | null;
    allowRetake?: boolean;
}>();

const emit = defineEmits<{
    (e: 'retake', slotIndex: number): void;
    (e: 'preview', photo: SessionPhoto): void;
}>();

function getPhotoForSlot(slotIndex: number) {
    return props.photos.find(p => p.slot_index === slotIndex && p.is_accepted);
}

function getPhotoUrl(path?: string) {
    if (!path) return '';
    return '/' + path.replace('public/', 'storage/');
}
</script>

<template>
    <div class="w-full">
        <!-- Grid of Photos -->
        <div
            class="grid gap-4 w-full"
            :class="[
                totalSlots === 1 ? 'grid-cols-1 max-w-md mx-auto' :
                totalSlots === 2 ? 'grid-cols-2 max-w-2xl mx-auto' :
                totalSlots === 3 ? 'grid-cols-3 max-w-4xl mx-auto' :
                totalSlots === 4 ? 'grid-cols-2 sm:grid-cols-4 max-w-5xl mx-auto' :
                'grid-cols-3 sm:grid-cols-6 max-w-6xl mx-auto'
            ]"
        >
            <div
                v-for="slot in totalSlots"
                :key="slot"
                class="group relative aspect-[3/4] rounded-2xl bg-slate-900 border-2 overflow-hidden flex flex-col shadow-xl transition-all duration-300"
                :class="getPhotoForSlot(slot) ? 'border-amber-400/40 hover:border-amber-400 hover:scale-[1.02]' : 'border-white/10 border-dashed'"
            >
                <!-- Slot Label Badge -->
                <div class="absolute top-3 left-3 z-20 px-2.5 py-1 rounded-full bg-black/70 backdrop-blur-md text-xs font-bold text-white border border-white/10">
                    Foto #{{ slot }}
                </div>

                <!-- If Photo Available -->
                <template v-if="getPhotoForSlot(slot)">
                    <img
                        :src="getPhotoUrl(getPhotoForSlot(slot)?.original_path)"
                        :alt="'Foto ' + slot"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />

                    <!-- Actions Overlay On Hover / Touch -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent opacity-90 sm:opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4 gap-2 z-20">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                v-if="allowRetake"
                                @click.stop="emit('retake', slot)"
                                class="flex-1 py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold flex items-center justify-center gap-1.5 shadow-lg active:scale-95 transition-all"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>Ambil Ulang</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- If Photo Not Taken Yet -->
                <template v-else>
                    <div class="flex-1 flex flex-col items-center justify-center text-slate-500 p-4 text-center">
                        <span class="text-3xl mb-2">📸</span>
                        <span class="text-xs font-medium">Slot Kosong</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>