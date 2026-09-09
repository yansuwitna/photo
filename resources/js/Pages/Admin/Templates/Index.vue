<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Template } from '@/types';
import { Layers, Plus, Camera, Check, Edit, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    templates: Template[];
}>();
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">Desain Template Photo Booth</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Kelola tata letak foto, bingkai, elemen teks, logo, dan ukuran kertas cetak
                    </p>
                </div>

                <Link
                    href="/admin/templates/builder"
                    class="py-2.5 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95"
                >
                    <Plus class="w-4 h-4 stroke-[3]" />
                    <span>Buka Template Builder Visual</span>
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="t in templates"
                    :key="t.id"
                    class="p-5 rounded-3xl bg-slate-900 border border-white/10 hover:border-white/20 transition-all flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ t.photo_count }} Slot Foto
                            </span>
                            <span class="text-xs font-mono text-slate-400">{{ t.paper_size }} ({{ t.orientation }})</span>
                        </div>

                        <!-- Template schematic box -->
                        <div class="aspect-[2/3] rounded-2xl bg-black/60 border border-white/10 p-3 flex flex-col justify-between my-3">
                            <div class="h-3 w-1/2 mx-auto rounded bg-white/20"></div>
                            <div class="grid gap-2 my-2 flex-1" :class="t.photo_count === 4 ? 'grid-cols-2 grid-rows-2' : 'grid-cols-1'">
                                <div v-for="s in Math.min(t.photo_count, 4)" :key="s" class="rounded-lg bg-white/10 border border-white/15 flex items-center justify-center text-[10px] text-slate-400 font-bold">
                                    Slot {{ s }}
                                </div>
                            </div>
                            <div class="h-2.5 w-1/3 rounded bg-white/20"></div>
                        </div>

                        <h3 class="font-bold text-white text-base">{{ t.name }}</h3>
                        <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ t.description }}</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center gap-2">
                        <Link
                            :href="`/admin/templates/builder?id=${t.id}`"
                            class="flex-1 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <Edit class="w-3.5 h-3.5" />
                            <span>Edit Desain di Builder</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>