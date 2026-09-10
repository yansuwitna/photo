<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import TemplateCard from '@/Components/TemplateCard.vue';
import FrameSelectorModal, { type FrameItem } from '@/Components/FrameSelectorModal.vue';
import type { BoothSession, Template } from '@/types';
import { Sparkles, ArrowRight, ArrowLeft, Image as ImageIcon } from 'lucide-vue-next';
import { useAudioStore } from '@/stores/audioStore';
import axios from 'axios';

const props = defineProps<{
    session: BoothSession;
    templates: Template[];
}>();

const audioStore = useAudioStore();

const showFrameModal = ref(false);
const activeFramePath = ref<string | null>(
    (props.session.metadata as any)?.custom_overlay_image || null
);
const activeFrameName = ref<string | null>(null);

async function handleSelectFrame(frame: FrameItem) {
    activeFramePath.value = frame.path;
    activeFrameName.value = frame.name;
    try {
        await axios.post(`/api/session/${props.session.id}/set-frame`, {
            frame_path: frame.path,
        });
        audioStore.playBeep(784, 0.1, 'sine');
    } catch (e) {
        console.error('Gagal memasang bingkai', e);
    }
}

async function handleRemoveFrame() {
    activeFramePath.value = null;
    activeFrameName.value = null;
    try {
        await axios.post(`/api/session/${props.session.id}/set-frame`, {
            frame_path: null,
        });
    } catch (e) {
        console.error('Gagal melepas bingkai', e);
    }
}

// Default pilih template pertama atau yang sudah terpasang
const selectedTemplate = ref<Template>(
    props.templates.find(t => t.id === props.session.template_id) || props.templates[0]
);

const isSubmitting = ref(false);

function handleSelect(template: Template) {
    selectedTemplate.value = template;
    audioStore.playBeep(659.25, 0.1, 'sine');
}

async function handleProceed() {
    if (!selectedTemplate.value) return;
    isSubmitting.value = true;
    audioStore.speakInstruction('Template terpilih. Bersiaplah di depan kamera!');

    try {
        await axios.post(`/api/session/${props.session.id}/select-template`, {
            template_id: selectedTemplate.value.id,
        });
        router.visit(`/session/${props.session.id}/camera`);
    } catch (e) {
        alert('Gagal memilih template');
    } finally {
        isSubmitting.value = false;
    }
}

function handleBack() {
    router.visit('/');
}
</script>

<template>
    <KioskLayout>
        <div class="flex-1 flex flex-col p-6 md:p-10 max-w-7xl mx-auto w-full justify-between overflow-hidden">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold uppercase tracking-wider mb-2">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>Langkah 1 dari 3</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight">
                    CHOOSE YOUR STYLE
                </h2>
                <p class="text-sm md:text-base text-slate-400 mt-2">
                    Pilih tata letak dan jumlah foto yang Anda inginkan untuk dicetak
                </p>
            </div>

            <!-- Frame Customization Bar -->
            <div class="mb-4 max-w-5xl mx-auto w-full flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-900/90 border border-white/10 backdrop-blur-md shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-400/30 text-amber-300 flex items-center justify-center shadow flex-shrink-0">
                        <Sparkles class="w-5 h-5 text-amber-400" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white">Bingkai Foto (Overlay):</span>
                            <span
                                class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                :class="activeFramePath ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-white/10 text-slate-400'"
                            >
                                {{ activeFrameName || (activeFramePath ? 'Bingkai Kustom Aktif' : (selectedTemplate?.overlay_image ? 'Bingkai Default Template' : 'Polos / Tanpa Bingkai')) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Pilih tema bingkai (Wedding, Party, Retro, Korean) atau unggah bingkai sendiri (PNG 300 DPI)</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button
                        v-if="activeFramePath"
                        @click="handleRemoveFrame"
                        class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-xs font-semibold transition-all"
                    >
                        Lepas
                    </button>
                    <button
                        @click="showFrameModal = true"
                        class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs flex items-center gap-1.5 shadow-md active:scale-95 transition-all"
                    >
                        <ImageIcon class="w-3.5 h-3.5" />
                        <span>{{ activeFramePath ? 'Ganti Bingkai' : '+ Pilih / Unggah Bingkai' }}</span>
                    </button>
                </div>
            </div>

            <!-- Templates Horizontal / Grid Container -->
            <div class="flex-1 overflow-y-auto px-2 py-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <TemplateCard
                        v-for="t in templates"
                        :key="t.id"
                        :template="t"
                        :selected="selectedTemplate?.id === t.id"
                        @select="handleSelect"
                    />
                </div>
            </div>

            <!-- Bottom Navigation Bar -->
            <div class="pt-6 border-t border-white/10 flex items-center justify-between">
                <button
                    @click="handleBack"
                    class="py-4 px-6 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-300 font-semibold text-sm flex items-center gap-2 border border-white/10 transition-all active:scale-95"
                >
                    <ArrowLeft class="w-5 h-5" />
                    <span>Kembali</span>
                </button>

                <div class="hidden sm:block text-xs text-slate-400">
                    Template: <span class="font-bold text-amber-300">{{ selectedTemplate?.name }}</span>
                    ({{ selectedTemplate?.photo_count }} Foto)
                </div>

                <button
                    @click="handleProceed"
                    :disabled="isSubmitting || !selectedTemplate"
                    class="py-4 px-8 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-base shadow-[0_0_30px_rgba(245,158,11,0.5)] flex items-center gap-3 transition-all active:scale-95 disabled:opacity-50"
                >
                    <span>{{ isSubmitting ? 'MENYIAPKAN...' : 'LANJUT KE KAMERA' }}</span>
                    <ArrowRight class="w-5 h-5 stroke-[2.5]" />
                </button>
            </div>
        </div>

        <!-- Modal Pemilih Bingkai Foto -->
        <FrameSelectorModal
            :show="showFrameModal"
            :current-frame-path="activeFramePath"
            @close="showFrameModal = false"
            @select="handleSelectFrame"
            @remove="handleRemoveFrame"
        />
    </KioskLayout>
</template>