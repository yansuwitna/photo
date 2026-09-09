<script setup lang="ts">
import { ref, computed } from 'vue';
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
    Smile, 
    ArrowLeft, 
    Check,
    Palette
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    template?: Template | null;
}>();

const templateName = ref(props.template?.name || 'Template Kustom Baru');
const paperSize = ref(props.template?.paper_size || '4R');
const orientation = ref<'portrait' | 'landscape'>(props.template?.orientation || 'portrait');
const backgroundColor = ref(props.template?.background_color || '#ffffff');
const photoCount = ref(props.template?.photo_count || 3);

// Elements List
const elements = ref<any[]>(
    props.template?.elements && props.template.elements.length > 0
        ? JSON.parse(JSON.stringify(props.template.elements))
        : [
            {
                type: 'text',
                content: '{event_name}',
                x: 10,
                y: 4,
                width: 80,
                height: 6,
                font_size: 28,
                font_color: '#0f172a',
                font_weight: 'bold',
                text_align: 'center',
                z_index: 2,
            },
            {
                type: 'photo_slot',
                slot_index: 1,
                label: 'Slot 1',
                x: 10,
                y: 12,
                width: 80,
                height: 24,
                border_width: 2,
                border_color: '#cbd5e1',
                border_radius: 8,
                z_index: 1,
            },
            {
                type: 'photo_slot',
                slot_index: 2,
                label: 'Slot 2',
                x: 10,
                y: 39,
                width: 80,
                height: 24,
                border_width: 2,
                border_color: '#cbd5e1',
                border_radius: 8,
                z_index: 1,
            },
            {
                type: 'photo_slot',
                slot_index: 3,
                label: 'Slot 3',
                x: 10,
                y: 66,
                width: 80,
                height: 24,
                border_width: 2,
                border_color: '#cbd5e1',
                border_radius: 8,
                z_index: 1,
            },
            {
                type: 'qr_code',
                x: 75,
                y: 91,
                width: 15,
                height: 7,
                z_index: 3,
            }
        ]
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
        y: 20 + (newIdx * 10),
        width: 70,
        height: 22,
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
        };

        const res = await axios.post('/api/admin/templates/save', payload);
        if (res.data.success) {
            alert('Template berhasil disimpan!');
            router.visit('/admin/templates');
        }
    } catch (e: any) {
        alert('Gagal menyimpan template: ' + (e.response?.data?.message || 'Error server'));
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 flex flex-col h-[calc(100vh-140px)]">
            <!-- TOP CONTROLS BAR -->
            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <button
                        @click="router.visit('/admin/templates')"
                        class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                    <div>
                        <input
                            v-model="templateName"
                            class="bg-transparent border-b border-white/20 font-black text-xl text-white focus:outline-none focus:border-amber-400 px-1"
                            placeholder="Nama Template"
                        />
                        <p class="text-xs text-slate-400 mt-0.5">Template Visual Editor (Resolusi Cetak 300 DPI)</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="saveTemplate"
                        :disabled="isSaving"
                        class="py-2.5 px-6 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs shadow-lg flex items-center gap-2 transition-all active:scale-95 disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'SIMPAN TEMPLATE' }}</span>
                    </button>
                </div>
            </div>

            <!-- 3-COLUMN WORKSPACE: TOOLBOX | CANVAS | INSPECTOR -->
            <div class="flex-1 grid grid-cols-12 gap-6 overflow-hidden">
                <!-- LEFT: TOOLBOX (Col 3) -->
                <div class="col-span-3 rounded-3xl bg-slate-900 border border-white/10 p-5 overflow-y-auto space-y-5 text-xs">
                    <div>
                        <span class="font-bold text-amber-400 uppercase tracking-wider block mb-3">Tambah Elemen</span>
                        <div class="space-y-2">
                            <button
                                @click="addPhotoSlot"
                                class="w-full py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <Camera class="w-4 h-4 text-amber-400" />
                                <span>+ Slot Foto Baru</span>
                            </button>

                            <button
                                @click="addText"
                                class="w-full py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <Type class="w-4 h-4 text-sky-400" />
                                <span>+ Teks / Judul Event</span>
                            </button>

                            <button
                                @click="addQrCode"
                                class="w-full py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold flex items-center gap-2.5 transition-all"
                            >
                                <QrCode class="w-4 h-4 text-emerald-400" />
                                <span>+ QR Code Download</span>
                            </button>
                        </div>
                    </div>

                    <!-- Layout & Paper Settings -->
                    <div class="pt-4 border-t border-white/10 space-y-3">
                        <span class="font-bold text-slate-300 uppercase tracking-wider block">Ukuran Kertas & Kanvas</span>
                        <div>
                            <label class="text-slate-400 block mb-1">Ukuran Kertas</label>
                            <select v-model="paperSize" class="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-white">
                                <option value="4R">4R (10x15 cm / 1200x1800 px)</option>
                                <option value="Strip 2x6">Photo Strip (2x6 inch)</option>
                                <option value="5R">5R (13x18 cm)</option>
                                <option value="6R">6R (15x20 cm)</option>
                                <option value="A4">A4 Print</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-slate-400 block mb-1">Warna Background Kanvas</label>
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="backgroundColor" class="w-10 h-8 rounded-lg cursor-pointer bg-transparent border border-white/10" />
                                <input v-model="backgroundColor" class="flex-1 px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs uppercase" />
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
                            width: orientation === 'portrait' ? '380px' : '520px',
                            height: orientation === 'portrait' ? '570px' : '380px',
                        }"
                    >
                        <!-- Render Canvas Elements -->
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
                            }"
                        >
                            <!-- Photo Slot Element -->
                            <template v-if="el.type === 'photo_slot'">
                                <div class="w-full h-full bg-slate-200/90 flex flex-col items-center justify-center p-2 text-slate-800 text-center pointer-events-none">
                                    <Camera class="w-6 h-6 text-slate-600 mb-1" />
                                    <span class="text-[11px] font-bold">SLOT {{ el.slot_index }}</span>
                                </div>
                            </template>

                            <!-- Text Element -->
                            <template v-else-if="el.type === 'text'">
                                <div
                                    class="w-full h-full flex items-center px-2 pointer-events-none"
                                    :class="{
                                        'justify-center text-center': el.text_align === 'center',
                                        'justify-start text-left': el.text_align === 'left',
                                        'justify-end text-right': el.text_align === 'right',
                                    }"
                                    :style="{
                                        color: el.font_color || '#111827',
                                        fontSize: `${(el.font_size || 24) * 0.45}px`,
                                        fontWeight: el.font_weight || 'normal',
                                    }"
                                >
                                    {{ el.content }}
                                </div>
                            </template>

                            <!-- QR Code Element -->
                            <template v-else-if="el.type === 'qr_code'">
                                <div class="w-full h-full bg-white border border-slate-400 flex flex-col items-center justify-center p-1 pointer-events-none text-slate-900">
                                    <QrCode class="w-8 h-8" />
                                    <span class="text-[8px] font-bold">QR CODE</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: INSPECTOR (Col 3) -->
                <div class="col-span-3 rounded-3xl bg-slate-900 border border-white/10 p-5 overflow-y-auto space-y-4 text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <span class="font-bold text-white uppercase tracking-wider">Properties Inspector</span>
                        <button
                            v-if="selectedElement"
                            @click="removeSelected"
                            class="p-1.5 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30"
                            title="Hapus Elemen"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <template v-if="selectedElement">
                        <div class="space-y-3">
                            <span class="text-[10px] font-bold text-amber-400 uppercase">Tipe: {{ selectedElement.type }}</span>

                            <!-- Text Content if Text -->
                            <div v-if="selectedElement.type === 'text'">
                                <label class="text-slate-400 block mb-1">Konten Teks</label>
                                <input v-model="selectedElement.content" class="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-white" />
                                <p class="text-[10px] text-slate-500 mt-1">Gunakan token: {event_name}, {date}</p>
                            </div>

                            <!-- Position Coordinates (%) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1">Posisi X (%)</label>
                                    <input type="number" v-model.number="selectedElement.x" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1">Posisi Y (%)</label>
                                    <input type="number" v-model.number="selectedElement.y" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                            </div>

                            <!-- Size (%) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1">Lebar (%)</label>
                                    <input type="number" v-model.number="selectedElement.width" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1">Tinggi (%)</label>
                                    <input type="number" v-model.number="selectedElement.height" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                            </div>

                            <!-- Borders -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-slate-400 block mb-1">Ketebalan Border</label>
                                    <input type="number" v-model.number="selectedElement.border_width" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1">Radius Sudut (px)</label>
                                    <input type="number" v-model.number="selectedElement.border_radius" class="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono" />
                                </div>
                            </div>

                            <!-- Border Color -->
                            <div v-if="selectedElement.border_width > 0">
                                <label class="text-slate-400 block mb-1">Warna Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" v-model="selectedElement.border_color" class="w-8 h-8 rounded cursor-pointer bg-transparent border border-white/10" />
                                    <input v-model="selectedElement.border_color" class="flex-1 px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-white font-mono text-xs uppercase" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="py-12 text-center text-slate-500">
                            Klik salah satu elemen di kanvas tengah untuk mengedit posisinya.
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>