<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Template, TemplateElement } from '@/types';
import { 
    Layers, 
    Plus, 
    Edit, 
    Trash2, 
    Camera, 
    Check, 
    X, 
    ExternalLink, 
    Sparkles, 
    Eye, 
    RotateCw,
    SlidersHorizontal,
    Printer
} from 'lucide-vue-next';
import axios from 'axios';
import { getAssetUrl } from '@/utils/url';
import { showSuccess, showError, showToast, showDeleteConfirm } from '@/utils/swal';

const props = defineProps<{
    templates: Template[];
}>();

const localTemplates = ref<Template[]>(JSON.parse(JSON.stringify(props.templates || [])));

// Filter Tab State: 'all' | 1 | 2 | 3 | 4 | 5 | 6 | 7
const selectedTab = ref<number | 'all'>('all');

// Helper to determine if a template is Strip or Full
function isStripFormat(t: Template): boolean {
    return t.paper_size === 'Strip 2x6' || t.width === 600 || t.frame_style === 'strip';
}

// 7 Categories Definition
interface TableDefinition {
    id: number;
    title: string;
    badge: string;
    subTitle: string;
    format: 'strip' | 'full';
    paperSize: string;
    photoCount: number;
    colorTheme: {
        border: string;
        bgBadge: string;
        textBadge: string;
        btnBg: string;
        btnHover: string;
    };
    defaultName: string;
}

const tableDefinitions: TableDefinition[] = [
    {
        id: 1,
        title: 'Strip 2 Foto',
        badge: 'Strip 2 Foto',
        subTitle: 'Setengah Kertas 4R (2x6 Inci / 600x1800 px) • 2 Slot Foto Vertikal',
        format: 'strip',
        paperSize: 'Strip 2x6',
        photoCount: 2,
        colorTheme: {
            border: 'border-pink-500/30',
            bgBadge: 'bg-pink-500/10 border-pink-500/20 text-pink-400',
            textBadge: 'text-pink-400',
            btnBg: 'bg-pink-500',
            btnHover: 'hover:bg-pink-400',
        },
        defaultName: 'Desain Baru Strip 2 Foto',
    },
    {
        id: 2,
        title: 'Strip 3 Foto',
        badge: 'Strip 3 Foto',
        subTitle: 'Setengah Kertas 4R (2x6 Inci / 600x1800 px) • 3 Pose Populer BeautyPlus',
        format: 'strip',
        paperSize: 'Strip 2x6',
        photoCount: 3,
        colorTheme: {
            border: 'border-rose-500/30',
            bgBadge: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
            textBadge: 'text-rose-400',
            btnBg: 'bg-rose-500',
            btnHover: 'hover:bg-rose-400',
        },
        defaultName: 'Desain Baru Strip 3 Foto',
    },
    {
        id: 3,
        title: 'Strip 4 Foto',
        badge: 'Strip 4 Foto',
        subTitle: 'Setengah Kertas 4R (2x6 Inci / 600x1800 px) • 4 Pose Gaya Korea Life4Cuts',
        format: 'strip',
        paperSize: 'Strip 2x6',
        photoCount: 4,
        colorTheme: {
            border: 'border-purple-500/30',
            bgBadge: 'bg-purple-500/10 border-purple-500/20 text-purple-400',
            textBadge: 'text-purple-400',
            btnBg: 'bg-purple-500',
            btnHover: 'hover:bg-purple-400',
        },
        defaultName: 'Desain Baru Strip 4 Foto',
    },
    {
        id: 4,
        title: 'Full 1 Foto',
        badge: 'Full 1 Foto',
        subTitle: 'Kertas 4R Utuh (4x6 Inci / 1200x1800 px) • 1 Foto Portrait Studio Elegan',
        format: 'full',
        paperSize: '4R',
        photoCount: 1,
        colorTheme: {
            border: 'border-amber-500/30',
            bgBadge: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
            textBadge: 'text-amber-400',
            btnBg: 'bg-amber-500 text-slate-950',
            btnHover: 'hover:bg-amber-400',
        },
        defaultName: 'Desain Baru Full 1 Foto',
    },
    {
        id: 5,
        title: 'Full 2 Foto',
        badge: 'Full 2 Foto',
        subTitle: 'Kertas 4R Utuh (4x6 Inci / 1200x1800 px) • 2 Foto Duet Kartu Pos',
        format: 'full',
        paperSize: '4R',
        photoCount: 2,
        colorTheme: {
            border: 'border-sky-500/30',
            bgBadge: 'bg-sky-500/10 border-sky-500/20 text-sky-400',
            textBadge: 'text-sky-400',
            btnBg: 'bg-sky-500',
            btnHover: 'hover:bg-sky-400',
        },
        defaultName: 'Desain Baru Full 2 Foto',
    },
    {
        id: 6,
        title: 'Full 4 Foto',
        badge: 'Full 4 Foto',
        subTitle: 'Kertas 4R Utuh (4x6 Inci / 1200x1800 px) • 4 Foto Grid 2x2 Seimbang',
        format: 'full',
        paperSize: '4R',
        photoCount: 4,
        colorTheme: {
            border: 'border-emerald-500/30',
            bgBadge: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
            textBadge: 'text-emerald-400',
            btnBg: 'bg-emerald-500',
            btnHover: 'hover:bg-emerald-400',
        },
        defaultName: 'Desain Baru Full 4 Foto Grid',
    },
    {
        id: 7,
        title: 'Full 6 Foto',
        badge: 'Full 6 Foto',
        subTitle: 'Kertas 4R Utuh (4x6 Inci / 1200x1800 px) • 6 Foto Grid 2x3 Momen Seru',
        format: 'full',
        paperSize: '4R',
        photoCount: 6,
        colorTheme: {
            border: 'border-indigo-500/30',
            bgBadge: 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400',
            textBadge: 'text-indigo-400',
            btnBg: 'bg-indigo-500',
            btnHover: 'hover:bg-indigo-400',
        },
        defaultName: 'Desain Baru Full 6 Foto Grid',
    },
];

// Helper to filter templates for a specific category table
function getTemplatesForCategory(def: TableDefinition): Template[] {
    return localTemplates.value.filter(t => {
        const isStrip = isStripFormat(t);
        if (def.format === 'strip' && !isStrip) return false;
        if (def.format === 'full' && isStrip) return false;
        return Number(t.photo_count) === Number(def.photoCount);
    });
}

// Global Summary Metrics
const totalTemplatesCount = computed(() => localTemplates.value.length);
const totalActiveCount = computed(() => localTemplates.value.filter(t => t.is_active).length);
const totalStripCount = computed(() => localTemplates.value.filter(t => isStripFormat(t)).length);
const totalFullCount = computed(() => localTemplates.value.filter(t => !isStripFormat(t)).length);

// Helper for Background style preview
function getTemplateBackgroundStyle(tpl: Template): string {
    if (tpl.background_image) {
        return `url(${getAssetUrl(tpl.background_image)}) center / cover no-repeat`;
    }
    const nameOrSlug = `${tpl.slug || ''} ${tpl.name || ''}`.toLowerCase();
    if (nameOrSlug.includes('pink bows') || nameOrSlug.includes('beautyplus') || tpl.frame_style === 'pink_bows') {
        return 'repeating-linear-gradient(90deg, #ffcde2, #ffcde2 8px, #ffffff 8px, #ffffff 16px)';
    }
    if (nameOrSlug.includes('lavender') || nameOrSlug.includes('purple')) {
        return 'repeating-linear-gradient(90deg, #f3e8ff, #f3e8ff 8px, #ffffff 8px, #ffffff 16px)';
    }
    return tpl.background_color || '#ffffff';
}

function getPhotoSlots(tpl: Template): TemplateElement[] {
    if (tpl.elements && tpl.elements.length > 0) {
        return tpl.elements.filter(e => e.type === 'photo_slot');
    }
    // Fallback simulation
    const slots: any[] = [];
    const count = Number(tpl.photo_count) || 3;
    for (let i = 1; i <= count; i++) {
        slots.push({
            id: i,
            type: 'photo_slot',
            slot_index: i,
            x: 10,
            y: 5 + (i - 1) * (85 / count),
            width: 80,
            height: 80 / count,
            border_radius: 4,
            rotation: 0,
        });
    }
    return slots;
}

// Toggle Template Active State
async function handleToggleActive(template: Template) {
    try {
        const res = await axios.post(`/api/admin/templates/${template.id}/toggle`);
        if (res.data.success) {
            template.is_active = res.data.is_active;
            showToast(template.is_active ? `"${template.name}" aktif di kiosk` : `"${template.name}" dinonaktifkan`, 'success');
        }
    } catch (e) {
        showError('Gagal Mengubah Status', 'Terjadi kendala saat mengubah status aktif template.');
    }
}

// Delete Template
async function handleDeleteTemplate(template: Template) {
    const confirmed = await showDeleteConfirm(template.name, 'Template beserta semua elemennya akan dihapus permanen.');
    if (!confirmed) return;

    try {
        const res = await axios.delete(`/api/admin/templates/${template.id}`);
        if (res.data.success) {
            localTemplates.value = localTemplates.value.filter(t => t.id !== template.id);
            showSuccess('Berhasil Dihapus!', `Template "${template.name}" telah dihapus.`);
        }
    } catch (e) {
        showError('Gagal Menghapus', 'Terjadi kesalahan pada server saat menghapus template.');
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- PAGE HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white flex items-center gap-3">
                        <Layers class="w-8 h-8 text-amber-400" />
                        <span>Kelola Template Desain Photo Booth</span>
                    </h1>
                    <p class="text-xs md:text-sm text-slate-400 mt-1">
                        Dikelompokkan rapi berdasarkan bentuk foto (Strip vs Full 4R) dan jumlah slot foto.
                    </p>
                </div>

                <!-- Global Add Builder Button -->
                <Link
                    href="/admin/templates/builder"
                    class="py-2.5 px-5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs flex items-center gap-2 shadow-lg shadow-amber-400/20 transition-all active:scale-95 self-start md:self-auto"
                >
                    <Plus class="w-4 h-4 stroke-[3]" />
                    <span>BUKA VISUAL BUILDER BARU</span>
                </Link>
            </div>

            <!-- SUMMARY STATS BAR -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4">
                <div class="p-4 rounded-2xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Desain</span>
                    <span class="text-2xl md:text-3xl font-black text-white font-mono mt-1 block">{{ totalTemplatesCount }}</span>
                    <span class="text-[10px] text-slate-500">Semua template tersimpan</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider block">Aktif di Kiosk</span>
                    <span class="text-2xl md:text-3xl font-black text-emerald-400 font-mono mt-1 block">{{ totalActiveCount }}</span>
                    <span class="text-[10px] text-slate-500">Dapat dipilih pengunjung</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] font-bold text-pink-400 uppercase tracking-wider block">Photo Strip (Setengah 4R)</span>
                    <span class="text-2xl md:text-3xl font-black text-pink-400 font-mono mt-1 block">{{ totalStripCount }}</span>
                    <span class="text-[10px] text-slate-500">Format Strip (2, 3, 4 Foto)</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider block">Full Photo (4R Utuh)</span>
                    <span class="text-2xl md:text-3xl font-black text-amber-400 font-mono mt-1 block">{{ totalFullCount }}</span>
                    <span class="text-[10px] text-slate-500">Format Full 4R (1, 2, 4, 6 Foto)</span>
                </div>
            </div>

            <!-- QUICK NAVIGATION TABS -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 border-b border-white/10 scrollbar-thin">
                <button
                    @click="selectedTab = 'all'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap"
                    :class="selectedTab === 'all' ? 'bg-amber-400 text-slate-950 shadow' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
                >
                    Semua Kategori (7 Pilihan)
                </button>

                <button
                    v-for="def in tableDefinitions"
                    :key="def.id"
                    @click="selectedTab = def.id"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="selectedTab === def.id ? 'bg-white text-slate-950 shadow' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
                >
                    <span>{{ def.badge }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="selectedTab === def.id ? 'bg-slate-200 text-slate-800' : 'bg-white/10 text-slate-300'">
                        {{ getTemplatesForCategory(def).length }}
                    </span>
                </button>
            </div>

            <!-- ========================================================================= -->
            <!-- 7 TABEL TEMPLATE TERPISAH BERDASARKAN BENTUK & JUMLAH FOTO                -->
            <!-- ========================================================================= -->
            <div class="space-y-8">
                <template v-for="def in tableDefinitions" :key="def.id">
                    <div
                        v-if="selectedTab === 'all' || selectedTab === def.id"
                        class="rounded-3xl bg-slate-900/90 border transition-all overflow-hidden shadow-xl"
                        :class="def.colorTheme.border"
                    >
                        <!-- TABLE HEADER & ACTION BAR -->
                        <div class="p-5 bg-white/5 border-b border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border" :class="def.colorTheme.bgBadge">
                                        {{ def.title }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">
                                        {{ def.paperSize }} • {{ def.photoCount }} Foto
                                    </span>
                                </div>
                                <h3 class="text-lg md:text-xl font-black text-white">
                                    {{ def.title }}
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ def.subTitle }}
                                </p>
                            </div>

                            <!-- BUTTON TAMBAH KHUSUS TABEL INI -->
                            <div class="flex items-center gap-3 self-start sm:self-auto">
                                <span class="text-xs text-slate-400 hidden md:inline">
                                    <span class="font-bold text-white">{{ getTemplatesForCategory(def).length }}</span> desain
                                    (<span class="text-emerald-400 font-bold">{{ getTemplatesForCategory(def).filter(t => t.is_active).length }}</span> aktif)
                                </span>

                                <Link
                                    :href="`/admin/templates/builder?paper_size=${encodeURIComponent(def.paperSize)}&count=${def.photoCount}&format=${def.format}&name=${encodeURIComponent(def.defaultName)}`"
                                    class="py-2 px-4 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-md transition-all active:scale-95"
                                    :class="[def.colorTheme.btnBg, def.colorTheme.btnHover]"
                                >
                                    <Plus class="w-4 h-4 stroke-[3]" />
                                    <span>Tambah Desain {{ def.badge }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- EMPTY STATE FOR THIS CATEGORY -->
                        <div 
                            v-if="getTemplatesForCategory(def).length === 0" 
                            class="py-12 text-center p-6"
                        >
                            <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                <Camera class="w-6 h-6" />
                            </div>
                            <h4 class="text-sm font-bold text-slate-300">Belum Ada Desain untuk {{ def.title }}</h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                Belum ada template yang terdaftar untuk kategori ini. Klik tombol di bawah untuk membuat desain baru.
                            </p>
                            <Link
                                :href="`/admin/templates/builder?paper_size=${encodeURIComponent(def.paperSize)}&count=${def.photoCount}&format=${def.format}&name=${encodeURIComponent(def.defaultName)}`"
                                class="mt-4 inline-flex items-center gap-1.5 py-2 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-colors"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>Buat Desain Sekarang</span>
                            </Link>
                        </div>

                        <!-- DATA TABLE LIST -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-black/30 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-white/10">
                                    <tr>
                                        <th class="py-3 px-4 w-12 text-center">No</th>
                                        <th class="py-3 px-4 w-28 text-center">Desain</th>
                                        <th class="py-3 px-4">Nama Template</th>
                                        <th class="py-3 px-4">Ukuran & Format</th>
                                        <th class="py-3 px-4 w-36 text-center">Status Kiosk</th>
                                        <th class="py-3 px-4 w-44 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr
                                        v-for="(t, idx) in getTemplatesForCategory(def)"
                                        :key="t.id"
                                        class="hover:bg-white/[0.02] transition-colors group"
                                    >
                                        <!-- No -->
                                        <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-400">
                                            {{ idx + 1 }}
                                        </td>

                                        <!-- Desain Preview Thumbnail -->
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center">
                                                <div
                                                    class="relative rounded-lg overflow-hidden border border-white/20 shadow-md flex flex-col justify-between p-1 transition-transform group-hover:scale-105"
                                                    :style="{
                                                        width: def.format === 'strip' ? '40px' : '56px',
                                                        height: '80px',
                                                        background: getTemplateBackgroundStyle(t),
                                                    }"
                                                >
                                                    <!-- Photo Slots preview miniature -->
                                                    <div 
                                                        v-for="slotEl in getPhotoSlots(t)"
                                                        :key="slotEl.id || slotEl.slot_index"
                                                        class="absolute bg-black/25 border border-black/20 flex items-center justify-center rounded-[2px]"
                                                        :style="{
                                                            left: `${slotEl.x}%`,
                                                            top: `${slotEl.y}%`,
                                                            width: `${slotEl.width}%`,
                                                            height: `${slotEl.height}%`,
                                                            borderRadius: `${Math.min(3, slotEl.border_radius || 2)}px`,
                                                            transform: slotEl.rotation ? `rotate(${slotEl.rotation}deg)` : undefined,
                                                        }"
                                                    >
                                                        <span class="text-[6px] font-black text-white/80">📸</span>
                                                    </div>

                                                    <!-- Overlay Image if exists -->
                                                    <img
                                                        v-if="t.overlay_image"
                                                        :src="getAssetUrl(t.overlay_image)"
                                                        alt="Frame"
                                                        class="absolute inset-0 w-full h-full object-fill pointer-events-none z-10"
                                                    />
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Nama Template & Info -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-white text-sm group-hover:text-amber-300 transition-colors">
                                                    {{ t.name }}
                                                </span>
                                                <span 
                                                    v-if="t.is_default"
                                                    class="px-1.5 py-0.2 rounded bg-amber-400/20 text-amber-300 text-[9px] font-bold"
                                                >
                                                    Default
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                                {{ t.description || 'Desain siap cetak resolusi tinggi 300 DPI' }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-500 font-mono">
                                                <span>Slug: {{ t.slug || t.id }}</span>
                                                <span>•</span>
                                                <span>Warna: {{ t.background_color || '#ffffff' }}</span>
                                            </div>
                                        </td>

                                        <!-- Ukuran & Format -->
                                        <td class="py-3.5 px-4 font-mono">
                                            <div class="text-white font-bold text-xs">
                                                {{ t.paper_size }} ({{ t.width }}x{{ t.height }} px)
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ t.orientation === 'portrait' ? 'Orientasi Portrait' : 'Orientasi Landscape' }} • 300 DPI
                                            </div>
                                        </td>

                                        <!-- Status Kiosk Toggle Button -->
                                        <td class="py-3.5 px-4 text-center">
                                            <button
                                                @click="handleToggleActive(t)"
                                                class="px-3 py-1 rounded-full text-[11px] font-bold border transition-all inline-flex items-center gap-1.5 cursor-pointer active:scale-95"
                                                :class="t.is_active 
                                                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20' 
                                                    : 'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-white'"
                                                :title="t.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan di Kiosk'"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full" :class="t.is_active ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                                                <span>{{ t.is_active ? 'Aktif di Kiosk' : 'Nonaktif' }}</span>
                                            </button>
                                        </td>

                                        <!-- Aksi: Ubah & Hapus -->
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- TOMBOL UBAH -->
                                                <Link
                                                    :href="`/admin/templates/builder?id=${t.id}`"
                                                    class="py-1.5 px-3 rounded-xl bg-amber-400/10 hover:bg-amber-400 border border-amber-400/30 text-amber-300 hover:text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all active:scale-95 shadow-xs"
                                                >
                                                    <Edit class="w-3.5 h-3.5" />
                                                    <span>Ubah</span>
                                                </Link>

                                                <!-- TOMBOL HAPUS -->
                                                <button
                                                    @click="handleDeleteTemplate(t)"
                                                    class="p-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 border border-red-500/20 transition-all active:scale-95"
                                                    title="Hapus template"
                                                >
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
