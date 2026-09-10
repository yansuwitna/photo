<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Template, TemplateElement } from '@/types';
import { 
    Layers, 
    Plus, 
    Trash2, 
    Save, 
    Type, 
    Camera, 
    QrCode, 
    ArrowLeft, 
    Sparkles, 
    RefreshCw,
    RotateCw,
    RotateCcw,
    Sliders,
    Check,
    Square,
    Lock
} from 'lucide-vue-next';
import axios from 'axios';
import FrameSelectorModal, { type FrameItem } from '@/Components/FrameSelectorModal.vue';
import { getAssetUrl } from '@/utils/url';
import { showSuccess, showError } from '@/utils/swal';

const props = defineProps<{
    template?: Template | null;
    preset?: {
        paper_size?: string | null;
        photo_count?: number | null;
        name?: string | null;
        format?: string | null;
    } | null;
}>();

// Read query params from URL if available
const searchParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const queryPaperSize = props.preset?.paper_size || searchParams?.get('paper_size');
const queryFormat = props.preset?.format || searchParams?.get('format');
const queryCount = props.preset?.photo_count || (searchParams?.get('count') ? Number(searchParams?.get('count')) : null);
const queryName = props.preset?.name || searchParams?.get('name');

// Kunci format secara otomatis: Strip (Setengah 4R / 2x6) vs Full (4R Utuh / 4x6)
// Template Strip TIDAK BISA diubah ke Full, dan sebaliknya!
const isStrip = computed(() => {
    if (props.template?.paper_size) {
        const ps = props.template.paper_size.toLowerCase();
        return ps.includes('strip') || ps.includes('2x6');
    }
    const qps = (queryPaperSize || '').toLowerCase();
    const qf = (queryFormat || '').toLowerCase();
    return qps.includes('strip') || qps.includes('2x6') || qf === 'strip';
});

// Ukuran kertas & kanvas terkunci otomatis (Strip 2x6 / 600x1800 px atau 4R / 1200x1800 px)
const paperSize = computed<'Strip 2x6' | '4R'>(() => {
    return isStrip.value ? 'Strip 2x6' : '4R';
});
const orientation = ref<'portrait' | 'landscape'>('portrait');

const initialCount = props.template?.photo_count || queryCount || (isStrip.value ? 3 : 4);
const initialName = props.template?.name || queryName || (isStrip.value ? `Desain Baru Strip ${initialCount} Foto` : `Desain Baru Full ${initialCount} Foto`);

const templateName = ref(initialName);
const backgroundColor = ref(props.template?.background_color || '#ffffff');
const photoCount = ref(initialCount);
const overlayImage = ref<string | null>(props.template?.overlay_image || null);
const isActive = ref(props.template?.is_active ?? true);
const showFrameModal = ref(false);
const frameOpacity = ref(100);

function handleSelectFrame(frame: FrameItem) {
    overlayImage.value = frame.path;
}

function handleRemoveFrame() {
    overlayImage.value = null;
}

// Preset Elements Generator for all 7 standard categories
function generatePresetElements(size: string, count: number): any[] {
    const isStrip = size === 'Strip 2x6';
    
    if (isStrip) {
        if (count === 2) {
            return [
                {
                    type: 'photo_slot',
                    slot_index: 1,
                    label: 'Slot 1',
                    x: 8,
                    y: 5,
                    width: 84,
                    height: 40,
                    rotation: 0,
                    border_radius: 8,
                    border_width: 0,
                    z_index: 1,
                },
                {
                    type: 'photo_slot',
                    slot_index: 2,
                    label: 'Slot 2',
                    x: 8,
                    y: 48,
                    width: 84,
                    height: 40,
                    rotation: 0,
                    border_radius: 8,
                    border_width: 0,
                    z_index: 1,
                },
                {
                    type: 'text',
                    content: '{date} • PHOTO STRIP DUO',
                    x: 8,
                    y: 92,
                    width: 60,
                    height: 4,
                    font_size: 18,
                    font_color: '#94a3b8',
                    text_align: 'left',
                    z_index: 2,
                    rotation: 0,
                },
                {
                    type: 'qr_code',
                    label: 'QR',
                    x: 74,
                    y: 89.5,
                    width: 18,
                    height: 8,
                    z_index: 3,
                    rotation: 0,
                }
            ];
        } else if (count === 4) {
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 8, y: 4.5, width: 84, height: 19.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 2, label: 'Slot 2', x: 8, y: 25.5, width: 84, height: 19.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 3, label: 'Slot 3', x: 8, y: 46.5, width: 84, height: 19.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 4, label: 'Slot 4', x: 8, y: 67.5, width: 84, height: 19.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • LIFE4CUTS KOREA', x: 8, y: 92, width: 60, height: 4, font_size: 18, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 74, y: 89.5, width: 18, height: 8, z_index: 3, rotation: 0 }
            ];
        } else {
            // Default 3 slots strip
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 8, y: 4.5, width: 84, height: 26.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 2, label: 'Slot 2', x: 8, y: 33.0, width: 84, height: 26.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 3, label: 'Slot 3', x: 8, y: 61.5, width: 84, height: 26.5, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • BEAUTYPLUS STRIP', x: 8, y: 92, width: 60, height: 4, font_size: 18, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 74, y: 89.5, width: 18, height: 8, z_index: 3, rotation: 0 }
            ];
        }
    } else {
        // Full 4R (1200x1800 px)
        if (count === 1) {
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 8, y: 6, width: 84, height: 82, rotation: 0, border_radius: 12, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • PORTRAIT STUDIO', x: 8, y: 92, width: 60, height: 4, font_size: 22, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 78, y: 89.5, width: 14, height: 7, z_index: 3, rotation: 0 }
            ];
        } else if (count === 2) {
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 8, y: 6, width: 84, height: 40, rotation: 0, border_radius: 10, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 2, label: 'Slot 2', x: 8, y: 49, width: 84, height: 40, rotation: 0, border_radius: 10, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • DUET MEMORIES', x: 8, y: 92, width: 60, height: 4, font_size: 22, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 78, y: 89.5, width: 14, height: 7, z_index: 3, rotation: 0 }
            ];
        } else if (count === 6) {
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 6, y: 5, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 2, label: 'Slot 2', x: 52, y: 5, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 3, label: 'Slot 3', x: 6, y: 33, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 4, label: 'Slot 4', x: 52, y: 33, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 5, label: 'Slot 5', x: 6, y: 61, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 6, label: 'Slot 6', x: 52, y: 61, width: 42, height: 26, rotation: 0, border_radius: 6, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • 6 MOMENTS STUDIO', x: 6, y: 91.5, width: 65, height: 4, font_size: 18, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 78, y: 89.5, width: 14, height: 7, z_index: 3, rotation: 0 }
            ];
        } else {
            // Default 4 slots grid (2x2)
            return [
                { type: 'photo_slot', slot_index: 1, label: 'Slot 1', x: 6, y: 6, width: 42, height: 39, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 2, label: 'Slot 2', x: 52, y: 6, width: 42, height: 39, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 3, label: 'Slot 3', x: 6, y: 48, width: 42, height: 39, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'photo_slot', slot_index: 4, label: 'Slot 4', x: 52, y: 48, width: 42, height: 39, rotation: 0, border_radius: 8, border_width: 0, z_index: 1 },
                { type: 'text', content: '{date} • 4-GRID STUDIO', x: 6, y: 91, width: 65, height: 4, font_size: 20, font_color: '#94a3b8', text_align: 'left', z_index: 2, rotation: 0 },
                { type: 'qr_code', label: 'QR', x: 78, y: 89.5, width: 14, height: 7, z_index: 3, rotation: 0 }
            ];
        }
    }
}

// Elements List
const elements = ref<any[]>(
    props.template?.elements && Array.isArray(props.template.elements) && props.template.elements.length > 0
        ? JSON.parse(JSON.stringify(props.template.elements)).map((el: any) => ({
            ...el,
            rotation: el.rotation ?? 0,
        }))
        : generatePresetElements(paperSize.value, initialCount)
);

const selectedIndex = ref<number | null>(0);
const selectedElement = computed(() => {
    if (selectedIndex.value === null || !elements.value[selectedIndex.value]) return null;
    return elements.value[selectedIndex.value];
});

const isSaving = ref(false);

function addPhotoSlot() {
    const slots = elements.value.filter(e => e.type === 'photo_slot');
    const newIdx = slots.length + 1;
    elements.value.push({
        type: 'photo_slot',
        slot_index: newIdx,
        label: `Foto ${newIdx}`,
        x: 15,
        y: 20 + ((newIdx - 1) * 12),
        width: 70,
        height: 22,
        rotation: 0,
        border_width: 2,
        border_color: '#cbd5e1',
        border_radius: 8,
        z_index: 1,
    });
    photoCount.value = elements.value.filter(e => e.type === 'photo_slot').length;
    selectedIndex.value = elements.value.length - 1;
}

function addText() {
    elements.value.push({
        type: 'text',
        content: 'Teks Kustom',
        x: 20,
        y: 80,
        width: 60,
        height: 6,
        rotation: 0,
        font_size: 24,
        font_color: '#111827',
        font_weight: 'normal',
        text_align: 'center',
        z_index: 2,
    });
    selectedIndex.value = elements.value.length - 1;
}

function addQrCode() {
    elements.value.push({
        type: 'qr_code',
        x: 40,
        y: 90,
        width: 20,
        height: 8,
        rotation: 0,
        z_index: 3,
    });
    selectedIndex.value = elements.value.length - 1;
}

function removeSelected() {
    if (selectedIndex.value !== null) {
        elements.value.splice(selectedIndex.value, 1);
        selectedIndex.value = null;
        photoCount.value = elements.value.filter(e => e.type === 'photo_slot').length;
    }
}

// Preset Slot Switcher (hanya mengubah slot foto dalam format yang sedang terkunci)
function applySlotPreset(targetCount: number) {
    photoCount.value = targetCount;
    elements.value = generatePresetElements(paperSize.value, targetCount);
    selectedIndex.value = 0;
}

// Rotate quick helpers
function setRotation(deg: number) {
    if (!selectedElement.value) return;
    selectedElement.value.rotation = deg;
}

function rotateStep(delta: number) {
    if (!selectedElement.value) return;
    const current = Number(selectedElement.value.rotation) || 0;
    selectedElement.value.rotation = Math.round(((current + delta) % 360));
}

async function saveTemplate() {
    isSaving.value = true;
    try {
        const payload = {
            id: props.template?.id,
            name: templateName.value,
            paper_size: paperSize.value,
            orientation: orientation.value,
            background_color: backgroundColor.value,
            photo_count: elements.value.filter(e => e.type === 'photo_slot').length,
            elements: elements.value,
            overlay_image: overlayImage.value,
            is_active: isActive.value,
        };

        const res = await axios.post('/api/admin/templates/save', payload);
        if (res.data.success) {
            await showSuccess('Berhasil Disimpan!', `Template "${templateName.value}" siap digunakan.`);
            router.visit('/admin/templates');
        }
    } catch (e: any) {
        showError('Gagal Menyimpan Template', e.response?.data?.message || 'Terjadi kesalahan saat menyimpan data template ke server.');
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 flex flex-col h-[calc(100vh-120px)]">
            <!-- TOP CONTROLS BAR -->
            <div class="flex items-center justify-between pb-3 border-b border-white/10 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button
                        @click="router.visit('/admin/templates')"
                        class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 transition-colors"
                        title="Kembali ke Daftar Tabel Template"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                    <div>
                        <input
                            v-model="templateName"
                            class="bg-transparent border-b border-white/20 font-black text-lg md:text-xl text-white focus:outline-none focus:border-amber-400 px-1 py-0.5"
                            placeholder="Nama Template (misal: Acara Ultah)"
                        />
                        <div class="flex items-center gap-2 mt-0.5">
                            <span
                                class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2 py-0.5 rounded-md border"
                                :class="isStrip ? 'bg-pink-500/10 border-pink-500/30 text-pink-300' : 'bg-amber-500/10 border-amber-500/30 text-amber-300'"
                            >
                                <Lock class="w-3 h-3" />
                                <span>{{ isStrip ? 'Format Photo Strip (600x1800 px) • Terkunci' : 'Format Full Photo 4R (1200x1800 px) • Terkunci' }}</span>
                            </span>
                            <span class="text-slate-500">•</span>
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ elements.filter(e => e.type === 'photo_slot').length }} Slot Foto • Resolusi 300 DPI
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Status Active Toggle -->
                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer select-none bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                        <input type="checkbox" v-model="isActive" class="rounded accent-amber-400 w-4 h-4 cursor-pointer" />
                        <span :class="isActive ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                            {{ isActive ? 'Aktif di Kiosk' : 'Nonaktif' }}
                        </span>
                    </label>

                    <button
                        @click="saveTemplate"
                        :disabled="isSaving"
                        class="py-2.5 px-6 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs shadow-lg flex items-center gap-2 transition-all active:scale-95 disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'SIMPAN DESAIN' }}</span>
                    </button>
                </div>
            </div>

            <!-- 3-COLUMN WORKSPACE: TOOLBOX | CANVAS | INSPECTOR -->
            <div class="flex-1 grid grid-cols-12 gap-5 overflow-hidden min-h-0">
                <!-- LEFT: TOOLBOX (Col 3) -->
                <div class="col-span-3 rounded-3xl bg-slate-900 border border-white/10 p-4 overflow-y-auto space-y-4 text-xs">
                    <!-- PRESET CEPAT SESUAI FORMAT TERKUNCI -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-400 uppercase tracking-wider block text-[11px]">
                                ⚡ Preset Slot Foto:
                            </span>
                            <span
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded border inline-flex items-center gap-1"
                                :class="isStrip ? 'bg-pink-500/20 text-pink-300 border-pink-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30'"
                            >
                                <Lock class="w-2.5 h-2.5" />
                                <span>{{ isStrip ? 'Strip Terkunci' : 'Full 4R Terkunci' }}</span>
                            </span>
                        </div>
                        
                        <!-- Strip Group: Hanya tampil jika format Strip -->
                        <div v-if="isStrip" class="p-2.5 rounded-2xl bg-white/5 border border-pink-500/20 space-y-1.5">
                            <span class="text-[10px] font-bold text-pink-300 block uppercase">Pilih Jumlah Foto Strip:</span>
                            <div class="grid grid-cols-3 gap-1">
                                <button
                                    type="button"
                                    @click="applySlotPreset(2)"
                                    class="py-1 px-1.5 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 2 ? 'bg-pink-500 text-white border-pink-400 shadow' : 'bg-pink-500/10 text-pink-300 border-pink-500/20 hover:bg-pink-500/20'"
                                >
                                    2 Foto
                                </button>
                                <button
                                    type="button"
                                    @click="applySlotPreset(3)"
                                    class="py-1 px-1.5 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 3 ? 'bg-pink-500 text-white border-pink-400 shadow' : 'bg-pink-500/10 text-pink-300 border-pink-500/20 hover:bg-pink-500/20'"
                                >
                                    3 Foto
                                </button>
                                <button
                                    type="button"
                                    @click="applySlotPreset(4)"
                                    class="py-1 px-1.5 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 4 ? 'bg-pink-500 text-white border-pink-400 shadow' : 'bg-pink-500/10 text-pink-300 border-pink-500/20 hover:bg-pink-500/20'"
                                >
                                    4 Foto
                                </button>
                            </div>
                        </div>

                        <!-- Full 4R Group: Hanya tampil jika format Full -->
                        <div v-else class="p-2.5 rounded-2xl bg-white/5 border border-amber-500/20 space-y-1.5">
                            <span class="text-[10px] font-bold text-amber-300 block uppercase">Pilih Jumlah Foto Full 4R:</span>
                            <div class="grid grid-cols-4 gap-1">
                                <button
                                    type="button"
                                    @click="applySlotPreset(1)"
                                    class="py-1 px-1 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 1 ? 'bg-amber-400 text-slate-950 border-amber-300 shadow font-black' : 'bg-amber-400/10 text-amber-300 border-amber-400/20 hover:bg-amber-400/20'"
                                >
                                    1 Foto
                                </button>
                                <button
                                    type="button"
                                    @click="applySlotPreset(2)"
                                    class="py-1 px-1 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 2 ? 'bg-amber-400 text-slate-950 border-amber-300 shadow font-black' : 'bg-amber-400/10 text-amber-300 border-amber-400/20 hover:bg-amber-400/20'"
                                >
                                    2 Foto
                                </button>
                                <button
                                    type="button"
                                    @click="applySlotPreset(4)"
                                    class="py-1 px-1 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 4 ? 'bg-amber-400 text-slate-950 border-amber-300 shadow font-black' : 'bg-amber-400/10 text-amber-300 border-amber-400/20 hover:bg-amber-400/20'"
                                >
                                    4 Foto
                                </button>
                                <button
                                    type="button"
                                    @click="applySlotPreset(6)"
                                    class="py-1 px-1 rounded-lg border text-[10px] font-bold text-center transition-all"
                                    :class="photoCount === 6 ? 'bg-amber-400 text-slate-950 border-amber-300 shadow font-black' : 'bg-amber-400/10 text-amber-300 border-amber-400/20 hover:bg-amber-400/20'"
                                >
                                    6 Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAMBAH ELEMEN -->
                    <div class="pt-3 border-t border-white/10 space-y-2">
                        <span class="font-bold text-slate-300 uppercase tracking-wider block text-[11px]">Tambah Elemen</span>
                        <div class="space-y-1.5">
                            <button
                                @click="addPhotoSlot"
                                class="w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <Camera class="w-4 h-4 text-amber-400" />
                                <span>+ Slot Foto Baru</span>
                            </button>

                            <button
                                @click="addText"
                                class="w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <Type class="w-4 h-4 text-sky-400" />
                                <span>+ Teks / Judul Event</span>
                            </button>

                            <button
                                @click="addQrCode"
                                class="w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <QrCode class="w-4 h-4 text-emerald-400" />
                                <span>+ QR Code Download</span>
                            </button>
                        </div>
                    </div>

                    <!-- BINGKAI FOTO (FRAME OVERLAY) SECTION -->
                    <div class="pt-3 border-t border-white/10 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-400 uppercase tracking-wider block text-[11px]">Bingkai Overlay (Layer Atas)</span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-400/20 text-amber-300 font-bold">300 DPI</span>
                        </div>

                        <!-- If frame selected -->
                        <div v-if="overlayImage" class="p-3 rounded-2xl bg-black/40 border border-amber-400/30 space-y-2.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-14 rounded-xl bg-slate-950 border border-white/20 overflow-hidden flex items-center justify-center relative flex-shrink-0 shadow">
                                    <img :src="getAssetUrl(overlayImage)" alt="Frame" class="w-full h-full object-contain" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-bold text-white block truncate">Bingkai Aktif</span>
                                    <span class="text-[10px] text-slate-400 block truncate">{{ overlayImage }}</span>
                                    <button
                                        @click="showFrameModal = true"
                                        class="mt-1 text-xs text-amber-300 hover:text-amber-200 font-bold inline-flex items-center gap-1"
                                    >
                                        <RefreshCw class="w-3 h-3" /> Ganti Bingkai
                                    </button>
                                </div>
                                <button
                                    @click="handleRemoveFrame"
                                    class="p-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-400 transition-all"
                                    title="Lepas Bingkai"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <div class="pt-2 border-t border-white/10">
                                <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                                    <span>Transparansi:</span>
                                    <span class="font-mono text-white">{{ frameOpacity }}%</span>
                                </div>
                                <input
                                    type="range"
                                    min="20"
                                    max="100"
                                    v-model.number="frameOpacity"
                                    class="w-full accent-amber-400 cursor-pointer h-1.5 bg-white/10 rounded-lg"
                                />
                            </div>
                        </div>

                        <!-- If no frame selected -->
                        <div v-else>
                            <button
                                @click="showFrameModal = true"
                                class="w-full py-2.5 px-3 rounded-2xl bg-gradient-to-r from-amber-500/20 to-purple-500/20 hover:from-amber-500/30 hover:to-purple-500/30 border border-amber-400/40 text-amber-300 font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-md active:scale-95"
                            >
                                <Sparkles class="w-4 h-4 text-amber-400" />
                                <span>+ Pasang / Unggah Bingkai</span>
                            </button>
                            <p class="text-[10px] text-slate-400 mt-1 leading-tight">
                                Tambahkan file PNG transparan untuk diletakkan di atas semua foto.
                            </p>
                        </div>
                    </div>

                    <!-- Background Canvas Color & Locked Format Info -->
                    <div class="pt-3 border-t border-white/10 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-300 uppercase tracking-wider block text-[11px]">Warna Background</span>
                            <span class="text-[10px] font-mono text-slate-400 flex items-center gap-1">
                                <Lock class="w-3 h-3 text-amber-400" />
                                <span>{{ isStrip ? 'Strip (600x1800)' : 'Full 4R (1200x1800)' }}</span>
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="backgroundColor" class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border border-white/10" />
                                <input v-model="backgroundColor" class="flex-1 px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs uppercase" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CENTER: VISUAL CANVAS (Col 6) -->
                <div class="col-span-6 flex items-center justify-center p-4 bg-slate-950/60 rounded-3xl border border-white/10 overflow-hidden relative">
                    <!-- The Scaled Canvas Element -->
                    <div
                        class="relative rounded-2xl shadow-[0_0_60px_rgba(0,0,0,0.9)] overflow-hidden transition-all duration-300 select-none border border-slate-300"
                        :style="{
                            backgroundColor: backgroundColor,
                            width: isStrip ? '210px' : '360px',
                            height: isStrip ? '630px' : '540px',
                        }"
                    >
                        <!-- Render Canvas Elements with Rotation -->
                        <div
                            v-for="(el, idx) in elements"
                            :key="idx"
                            @click.stop="selectedIndex = idx"
                            class="absolute cursor-pointer transition-all duration-100 flex items-center justify-center"
                            :class="[
                                selectedIndex === idx
                                    ? 'ring-2 ring-amber-400 shadow-xl z-30'
                                    : 'hover:ring-1 hover:ring-amber-300/60'
                            ]"
                            :style="{
                                left: `${el.x}%`,
                                top: `${el.y}%`,
                                width: `${el.width}%`,
                                height: `${el.height}%`,
                                zIndex: el.z_index || 1,
                                borderRadius: `${el.border_radius || 0}px`,
                                borderWidth: `${el.border_width || 0}px`,
                                borderColor: el.border_color || 'transparent',
                                borderStyle: el.border_width ? 'solid' : 'none',
                                transform: el.rotation ? `rotate(${el.rotation}deg)` : undefined,
                                transformOrigin: 'center center',
                            }"
                        >
                            <!-- Photo Slot Element -->
                            <template v-if="el.type === 'photo_slot'">
                                <div class="w-full h-full bg-slate-200/90 flex flex-col items-center justify-center p-2 text-slate-800 text-center pointer-events-none relative overflow-hidden">
                                    <Camera class="w-5 h-5 text-slate-600 mb-0.5" />
                                    <span class="text-[10px] font-black">SLOT {{ el.slot_index }}</span>
                                    <!-- Angle Badge if rotated -->
                                    <span v-if="el.rotation" class="absolute top-1 right-1 text-[8px] font-mono bg-amber-400 text-slate-950 font-bold px-1 rounded shadow">
                                        {{ el.rotation }}°
                                    </span>
                                </div>
                            </template>

                            <!-- Text Element -->
                            <template v-else-if="el.type === 'text'">
                                <div
                                    class="w-full h-full flex items-center px-1.5 pointer-events-none"
                                    :class="{
                                        'justify-center text-center': el.text_align === 'center',
                                        'justify-start text-left': el.text_align === 'left',
                                        'justify-end text-right': el.text_align === 'right',
                                    }"
                                    :style="{
                                        color: el.font_color || '#111827',
                                        fontSize: `${(el.font_size || 24) * 0.42}px`,
                                        fontWeight: el.font_weight || 'normal',
                                    }"
                                >
                                    {{ el.content }}
                                </div>
                            </template>

                            <!-- QR Code Element -->
                            <template v-else-if="el.type === 'qr_code'">
                                <div class="w-full h-full bg-white border border-slate-400 flex flex-col items-center justify-center p-0.5 pointer-events-none text-slate-900">
                                    <QrCode class="w-6 h-6" />
                                    <span class="text-[7px] font-bold">QR</span>
                                </div>
                            </template>
                        </div>

                        <!-- LIVE OVERLAY FRAME ON TOP OF ALL SLOTS -->
                        <div
                            v-if="overlayImage"
                            class="absolute inset-0 pointer-events-none z-20 overflow-hidden"
                            :style="{ opacity: frameOpacity / 100 }"
                        >
                            <img
                                :src="getAssetUrl(overlayImage)"
                                alt="Frame Overlay"
                                class="w-full h-full object-fill"
                            />
                        </div>
                    </div>
                </div>

                <!-- RIGHT: INSPECTOR (Col 3) -->
                <div class="col-span-3 rounded-3xl bg-slate-900 border border-white/10 p-4 overflow-y-auto space-y-3.5 text-xs">
                    <div class="flex items-center justify-between pb-2.5 border-b border-white/10">
                        <span class="font-bold text-white uppercase tracking-wider text-xs">Properties Inspector</span>
                        <button
                            v-if="selectedElement"
                            @click="removeSelected"
                            class="p-1.5 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 transition-colors"
                            title="Hapus Elemen"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <template v-if="selectedElement">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-amber-400 uppercase">
                                    Tipe: {{ selectedElement.type }}
                                </span>
                                <span v-if="selectedElement.type === 'photo_slot'" class="text-[10px] font-mono text-slate-400">
                                    Slot #{{ selectedElement.slot_index }}
                                </span>
                            </div>

                            <!-- Text Content if Text -->
                            <div v-if="selectedElement.type === 'text'">
                                <label class="text-slate-400 block mb-1 text-[11px]">Konten Teks</label>
                                <input v-model="selectedElement.content" class="w-full px-2.5 py-1.5 rounded-xl bg-black/40 border border-white/10 text-white text-xs" />
                                <p class="text-[10px] text-slate-500 mt-0.5">Token: {event_name}, {date}</p>
                            </div>

                            <!-- ========================================================================= -->
                            <!-- FITUR PUTAR / ROTASI FOTO & ELEMEN                                         -->
                            <!-- ========================================================================= -->
                            <div class="p-3 rounded-2xl bg-white/5 border border-amber-400/20 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-slate-200 font-bold flex items-center gap-1.5 text-xs">
                                        <RotateCw class="w-3.5 h-3.5 text-amber-400" />
                                        <span>Putar / Rotasi Bentuk Foto</span>
                                    </label>
                                    <span class="font-mono text-amber-300 font-bold text-xs bg-black/50 px-2 py-0.5 rounded border border-white/10">
                                        {{ selectedElement.rotation || 0 }}°
                                    </span>
                                </div>

                                <!-- Quick Angle Preset Buttons: 0°, 90°, 180°, 270° -->
                                <div class="grid grid-cols-4 gap-1">
                                    <button
                                        type="button"
                                        @click="setRotation(0)"
                                        class="py-1 px-1 rounded-lg text-[10px] font-bold transition-all text-center"
                                        :class="(selectedElement.rotation || 0) === 0 ? 'bg-amber-400 text-slate-950 font-black shadow' : 'bg-white/10 text-slate-300 hover:bg-white/20'"
                                    >
                                        0°
                                    </button>
                                    <button
                                        type="button"
                                        @click="setRotation(90)"
                                        class="py-1 px-1 rounded-lg text-[10px] font-bold transition-all text-center"
                                        :class="(selectedElement.rotation || 0) === 90 ? 'bg-amber-400 text-slate-950 font-black shadow' : 'bg-white/10 text-slate-300 hover:bg-white/20'"
                                    >
                                        90°
                                    </button>
                                    <button
                                        type="button"
                                        @click="setRotation(180)"
                                        class="py-1 px-1 rounded-lg text-[10px] font-bold transition-all text-center"
                                        :class="(selectedElement.rotation || 0) === 180 ? 'bg-amber-400 text-slate-950 font-black shadow' : 'bg-white/10 text-slate-300 hover:bg-white/20'"
                                    >
                                        180°
                                    </button>
                                    <button
                                        type="button"
                                        @click="setRotation(270)"
                                        class="py-1 px-1 rounded-lg text-[10px] font-bold transition-all text-center"
                                        :class="(selectedElement.rotation || 0) === 270 || (selectedElement.rotation || 0) === -90 ? 'bg-amber-400 text-slate-950 font-black shadow' : 'bg-white/10 text-slate-300 hover:bg-white/20'"
                                    >
                                        270°
                                    </button>
                                </div>

                                <!-- Step Tuning Buttons: -15°, +15°, +90° -->
                                <div class="grid grid-cols-3 gap-1 pt-1">
                                    <button
                                        type="button"
                                        @click="rotateStep(-15)"
                                        class="py-1 px-1 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 text-[10px] font-semibold flex items-center justify-center gap-1 transition-colors"
                                        title="Miringkan -15° (Gaya Polaroid Estetik)"
                                    >
                                        <RotateCcw class="w-3 h-3" /> -15°
                                    </button>
                                    <button
                                        type="button"
                                        @click="rotateStep(15)"
                                        class="py-1 px-1 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 text-[10px] font-semibold flex items-center justify-center gap-1 transition-colors"
                                        title="Miringkan +15° (Gaya Polaroid Estetik)"
                                    >
                                        <RotateCw class="w-3 h-3" /> +15°
                                    </button>
                                    <button
                                        type="button"
                                        @click="rotateStep(90)"
                                        class="py-1 px-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 text-[10px] font-bold flex items-center justify-center gap-1 transition-colors"
                                        title="Putar 90 Derajat"
                                    >
                                        ↻ +90°
                                    </button>
                                </div>

                                <!-- Range Slider & Direct Numeric Angle Input -->
                                <div class="flex items-center gap-2 pt-1">
                                    <input
                                        type="range"
                                        min="-180"
                                        max="180"
                                        step="1"
                                        v-model.number="selectedElement.rotation"
                                        class="flex-1 accent-amber-400 cursor-pointer h-1.5 bg-white/10 rounded-lg"
                                    />
                                    <div class="flex items-center">
                                        <input
                                            type="number"
                                            min="-360"
                                            max="360"
                                            v-model.number="selectedElement.rotation"
                                            class="w-14 px-1.5 py-0.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-center text-xs"
                                        />
                                        <span class="text-slate-400 ml-1">°</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Position Coordinates (%) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Posisi X (%)</label>
                                    <input type="number" v-model.number="selectedElement.x" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Posisi Y (%)</label>
                                    <input type="number" v-model.number="selectedElement.y" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                            </div>

                            <!-- Size (%) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Lebar (%)</label>
                                    <input type="number" v-model.number="selectedElement.width" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Tinggi (%)</label>
                                    <input type="number" v-model.number="selectedElement.height" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                            </div>

                            <!-- Borders -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Border (px)</label>
                                    <input type="number" v-model.number="selectedElement.border_width" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1 text-[11px]">Radius Sudut (px)</label>
                                    <input type="number" v-model.number="selectedElement.border_radius" class="w-full px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs" />
                                </div>
                            </div>

                            <!-- Border Color -->
                            <div v-if="selectedElement.border_width > 0">
                                <label class="text-slate-400 block mb-1 text-[11px]">Warna Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" v-model="selectedElement.border_color" class="w-7 h-7 rounded cursor-pointer bg-transparent border border-white/10" />
                                    <input v-model="selectedElement.border_color" class="flex-1 px-2.5 py-1 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs uppercase" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="py-12 text-center text-slate-500">
                            Klik salah satu elemen di kanvas tengah untuk mengedit atau memutar posisinya.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- FRAME SELECTOR MODAL -->
        <FrameSelectorModal
            :show="showFrameModal"
            :currentFramePath="overlayImage"
            @close="showFrameModal = false"
            @select="handleSelectFrame"
            @remove="handleRemoveFrame"
        />
    </AdminLayout>
</template>
