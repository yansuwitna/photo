<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
    X, 
    Upload, 
    Sparkles, 
    Check, 
    Image as ImageIcon, 
    FolderPlus, 
    Info, 
    Trash2,
    RefreshCw,
    Sliders
} from 'lucide-vue-next';
import axios from 'axios';
import { getAssetUrl } from '@/utils/url';

export interface FrameItem {
    id: string;
    filename: string;
    path: string;
    url: string;
    is_preset: boolean;
    name: string;
    category?: string;
    description?: string;
    theme_color?: string;
}

const props = defineProps<{
    show: boolean;
    currentFramePath?: string | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'select', frame: FrameItem): void;
    (e: 'remove'): void;
}>();

const activeTab = ref<'presets' | 'upload'>('presets');
const selectedCategory = ref<string>('all');
const frames = ref<FrameItem[]>([]);
const isLoading = ref(false);
const isUploading = ref(false);
const uploadError = ref<string | null>(null);

// Upload form states
const uploadFile = ref<File | null>(null);
const uploadPreviewUrl = ref<string | null>(null);
const uploadCustomName = ref('');

const categories = [
    { id: 'all', label: 'Semua Bingkai' },
    { id: 'wedding', label: '💍 Wedding & Romantic' },
    { id: 'studio', label: '📸 Korean Minimal' },
    { id: 'party', label: '⚡ Cyber & Party' },
    { id: 'birthday', label: '🎉 Celebration' },
    { id: 'retro', label: '🎞️ Vintage Film' },
    { id: 'custom', label: '✨ Unggahan Sendiri' },
];

const filteredFrames = computed(() => {
    if (selectedCategory.value === 'all') return frames.value;
    if (selectedCategory.value === 'custom') return frames.value.filter(f => !f.is_preset);
    return frames.value.filter(f => f.category === selectedCategory.value);
});

onMounted(() => {
    fetchFrames();
});

async function fetchFrames() {
    isLoading.value = true;
    try {
        const res = await axios.get('/api/frames');
        if (res.data.success) {
            frames.value = res.data.frames || [];
        }
    } catch (e) {
        console.error('Gagal memuat daftar bingkai', e);
    } finally {
        isLoading.value = false;
    }
}

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        setUploadFile(file);
    }
}

function handleDrop(event: DragEvent) {
    event.preventDefault();
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        setUploadFile(event.dataTransfer.files[0]);
    }
}

function setUploadFile(file: File) {
    uploadError.value = null;
    if (!file.type.match(/^image\/(png|jpeg|jpg)$/)) {
        uploadError.value = 'Format file harus berupa gambar PNG transparan atau JPG!';
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        uploadError.value = 'Ukuran file maksimal 10 MB!';
        return;
    }
    uploadFile.value = file;
    uploadCustomName.value = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');

    // Generate local preview URL
    if (uploadPreviewUrl.value) URL.revokeObjectURL(uploadPreviewUrl.value);
    uploadPreviewUrl.value = URL.createObjectURL(file);
}

async function submitCustomUpload() {
    if (!uploadFile.value) return;
    isUploading.value = true;
    uploadError.value = null;

    try {
        const formData = new FormData();
        formData.append('file', uploadFile.value);
        if (uploadCustomName.value) {
            formData.append('name', uploadCustomName.value);
        }

        const res = await axios.post('/api/frames/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (res.data.success && res.data.frame) {
            await fetchFrames();
            // Otomatis pilih bingkai baru yang diunggah
            emit('select', res.data.frame);
            resetUploadForm();
            emit('close');
        }
    } catch (err: any) {
        uploadError.value = err.response?.data?.message || 'Gagal mengunggah file. Pastikan server aktif.';
    } finally {
        isUploading.value = false;
    }
}

function resetUploadForm() {
    uploadFile.value = null;
    if (uploadPreviewUrl.value) {
        URL.revokeObjectURL(uploadPreviewUrl.value);
        uploadPreviewUrl.value = null;
    }
    uploadCustomName.value = '';
    uploadError.value = null;
}

function selectFrame(frame: FrameItem) {
    emit('select', frame);
    emit('close');
}

function removeFrame() {
    emit('remove');
    emit('close');
}

async function deleteCustomFrame(frame: FrameItem) {
    if (!confirm(`Hapus bingkai "${frame.name}" secara permanen?`)) return;
    try {
        const res = await axios.post('/api/frames/delete', { path: frame.path });
        if (res.data.success) {
            if (props.currentFramePath === frame.path) {
                emit('remove');
            }
            await fetchFrames();
        }
    } catch (e) {
        alert('Gagal menghapus bingkai');
    }
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fade-in"
    >
        <div class="relative w-full max-w-4xl bg-slate-900 border border-white/15 rounded-3xl shadow-[0_0_60px_rgba(0,0,0,0.8)] flex flex-col max-h-[90vh] overflow-hidden">
            <!-- Header Modal -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-white/10 bg-slate-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center shadow-lg">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white tracking-tight">Koleksi Bingkai & Overlay Kustom</h3>
                        <p class="text-xs text-slate-400">Pilih bingkai profesional siap pakai atau unggah bingkai desain sendiri (PNG 300 DPI)</p>
                    </div>
                </div>

                <button
                    @click="emit('close')"
                    class="p-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition-all"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Top Nav Tabs -->
            <div class="flex items-center justify-between px-6 pt-4 pb-2 border-b border-white/10 bg-slate-900">
                <div class="flex items-center gap-2">
                    <button
                        @click="activeTab = 'presets'"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all"
                        :class="activeTab === 'presets' ? 'bg-amber-400 text-slate-950 shadow-lg' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                    >
                        <ImageIcon class="w-4 h-4" />
                        <span>Koleksi Bingkai ({{ frames.length }})</span>
                    </button>

                    <button
                        @click="activeTab = 'upload'"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all"
                        :class="activeTab === 'upload' ? 'bg-amber-400 text-slate-950 shadow-lg' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                    >
                        <Upload class="w-4 h-4" />
                        <span>+ Unggah Bingkai Sendiri</span>
                    </button>
                </div>

                <button
                    v-if="currentFramePath"
                    @click="removeFrame"
                    class="px-4 py-2 rounded-xl border border-rose-500/40 text-rose-300 hover:bg-rose-500/20 text-xs font-semibold flex items-center gap-1.5 transition-all"
                >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>Lepas Bingkai (Polos)</span>
                </button>
            </div>

            <!-- TAB 1: PRESET & GALLERY -->
            <div v-if="activeTab === 'presets'" class="flex-1 flex flex-col overflow-hidden p-6">
                <!-- Category Filter Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-3 scrollbar-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="selectedCategory = cat.id"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all border"
                        :class="selectedCategory === cat.id ? 'bg-white/20 border-amber-400/60 text-amber-300 font-bold' : 'bg-black/30 border-white/5 text-slate-400 hover:bg-white/10 hover:text-slate-200'"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Frames Grid Container -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div v-if="isLoading" class="py-20 flex flex-col items-center justify-center text-center">
                        <RefreshCw class="w-8 h-8 text-amber-400 animate-spin mb-3" />
                        <span class="text-xs text-slate-400">Memuat koleksi bingkai...</span>
                    </div>

                    <div v-else-if="filteredFrames.length === 0" class="py-16 text-center text-slate-500 flex flex-col items-center">
                        <FolderPlus class="w-12 h-12 text-slate-600 mb-3" />
                        <p class="text-sm font-semibold text-slate-400">Belum ada bingkai di kategori ini.</p>
                        <p class="text-xs text-slate-500 mt-1">Anda dapat mengunggah file bingkai PNG transparan sendiri.</p>
                        <button
                            @click="activeTab = 'upload'"
                            class="mt-4 px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-amber-300 text-xs font-bold"
                        >
                            Unggah Sekarang
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <!-- Option: Tanpa Bingkai -->
                        <div
                            @click="removeFrame"
                            class="group relative rounded-2xl p-3 border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between"
                            :class="!currentFramePath ? 'border-amber-400 bg-amber-500/10 shadow-lg' : 'border-white/10 bg-slate-950/60 hover:border-white/30'"
                        >
                            <div class="w-full aspect-[2/3] rounded-xl border border-dashed border-white/20 bg-slate-900 flex flex-col items-center justify-center text-slate-500 p-2 text-center">
                                <span class="text-2xl mb-1">🚫</span>
                                <span class="text-[11px] font-semibold">Tanpa Bingkai</span>
                                <span class="text-[9px] text-slate-500 mt-0.5">Foto polos murni</span>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="text-xs font-bold text-white block">Polos (Standar)</span>
                                <span class="text-[10px] text-slate-400">Tanpa overlay bingkai</span>
                            </div>
                            <div v-if="!currentFramePath" class="mt-2 text-center">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-400 bg-amber-400/20 px-2 py-0.5 rounded-full">
                                    <Check class="w-3 h-3 stroke-[3]" /> Terpilih
                                </span>
                            </div>
                        </div>

                        <!-- Each Frame Card -->
                        <div
                            v-for="frame in filteredFrames"
                            :key="frame.id"
                            @click="selectFrame(frame)"
                            class="group relative rounded-2xl p-3 border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between hover:scale-[1.02]"
                            :class="currentFramePath === frame.path ? 'border-amber-400 bg-amber-500/10 shadow-lg' : 'border-white/10 bg-slate-950/60 hover:border-white/30'"
                        >
                            <!-- Visual Frame Preview Box with Checkerboard pattern for transparency -->
                            <div class="relative w-full aspect-[2/3] rounded-xl overflow-hidden border border-white/10 bg-checkerboard flex items-center justify-center p-1 shadow-inner">
                                <!-- Simulated photo under the transparent frame -->
                                <div class="absolute inset-2 rounded-lg bg-gradient-to-tr from-slate-800 to-indigo-950 flex items-center justify-center opacity-70">
                                    <span class="text-[10px] font-mono text-white/40 uppercase tracking-widest">Foto Tamu</span>
                                </div>

                                <!-- The Overlay Frame -->
                                <img
                                    :src="getAssetUrl(frame.path)"
                                    :alt="frame.name"
                                    class="relative w-full h-full object-contain z-10 pointer-events-none drop-shadow-md"
                                />

                                <!-- Delete button for custom uploaded frame -->
                                <button
                                    v-if="!frame.is_preset"
                                    @click.stop="deleteCustomFrame(frame)"
                                    class="absolute top-1.5 right-1.5 z-20 p-1.5 rounded-lg bg-red-500/80 text-white hover:bg-red-600 shadow opacity-0 group-hover:opacity-100 transition-opacity"
                                    title="Hapus Bingkai Ini"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Frame Info -->
                            <div class="mt-2 text-center">
                                <span class="text-xs font-bold text-white block truncate">{{ frame.name }}</span>
                                <span class="text-[10px] text-slate-400 block truncate">{{ frame.description || 'Bingkai resolusi tinggi' }}</span>
                            </div>

                            <!-- Selection Badge -->
                            <div class="mt-2 text-center">
                                <span
                                    v-if="currentFramePath === frame.path"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-400 bg-amber-400/20 px-2 py-0.5 rounded-full"
                                >
                                    <Check class="w-3 h-3 stroke-[3]" /> Terpilih
                                </span>
                                <span
                                    v-else
                                    class="inline-block text-[10px] font-semibold text-slate-400 group-hover:text-amber-300 transition-colors"
                                >
                                    Klik untuk Pakai
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: UPLOAD BINGKAI SENDIRI -->
            <div v-else class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Dropzone Area -->
                <div
                    @dragover.prevent
                    @drop="handleDrop"
                    class="relative border-2 border-dashed rounded-3xl p-8 text-center transition-all flex flex-col items-center justify-center"
                    :class="uploadFile ? 'border-amber-400 bg-amber-400/5' : 'border-white/20 bg-slate-950/40 hover:border-amber-400/60 hover:bg-white/5'"
                >
                    <input
                        type="file"
                        accept="image/png,image/jpeg,image/jpg"
                        @change="handleFileChange"
                        class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                    />

                    <div class="w-16 h-16 rounded-2xl bg-amber-400/20 border border-amber-400/30 text-amber-400 flex items-center justify-center mb-4 shadow-xl">
                        <Upload class="w-8 h-8 animate-pulse" />
                    </div>

                    <h4 class="text-lg font-bold text-white mb-1">
                        {{ uploadFile ? uploadFile.name : 'Tarik & Letakkan File Bingkai di Sini' }}
                    </h4>
                    <p class="text-xs text-slate-400 max-w-md">
                        Klik untuk memilih file dari komputer atau seret file gambar ke kotak ini.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-[11px] text-slate-400">
                        <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Format: PNG Transparan (Disarankan) / JPG</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Rekomendasi: 1200 x 1800 px (300 DPI)</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Maksimal: 10 MB</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="uploadError" class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                    <Info class="w-4 h-4 text-rose-400 flex-shrink-0" />
                    <span>{{ uploadError }}</span>
                </div>

                <!-- Preview & Custom Details when file selected -->
                <div v-if="uploadFile" class="grid grid-cols-1 md:grid-cols-12 gap-6 bg-slate-950/60 p-5 rounded-3xl border border-white/10">
                    <!-- Left: Preview -->
                    <div class="md:col-span-4 flex flex-col items-center">
                        <span class="text-[11px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Pratinjau Bingkai</span>
                        <div class="relative w-40 aspect-[2/3] rounded-2xl overflow-hidden border border-white/20 bg-checkerboard flex items-center justify-center shadow-2xl p-1">
                            <div class="absolute inset-2 rounded-lg bg-gradient-to-tr from-slate-800 to-indigo-900 opacity-60 flex items-center justify-center">
                                <span class="text-[8px] font-mono text-white/50 uppercase">Layer Foto</span>
                            </div>
                            <img
                                v-if="uploadPreviewUrl"
                                :src="uploadPreviewUrl"
                                alt="Preview Upload"
                                class="relative w-full h-full object-contain z-10 drop-shadow-lg"
                            />
                        </div>
                    </div>

                    <!-- Right: Name & Action -->
                    <div class="md:col-span-8 flex flex-col justify-between space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-300 block mb-1.5">Nama / Judul Bingkai:</label>
                            <input
                                v-model="uploadCustomName"
                                type="text"
                                placeholder="Contoh: Wedding Rian & Sinta 2026"
                                class="w-full px-4 py-3 rounded-2xl bg-slate-900 border border-white/20 text-white font-medium text-sm focus:outline-none focus:border-amber-400"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Nama ini akan muncul di daftar pilihan bingkai operator & tamu.</p>
                        </div>

                        <!-- Professional Best Practice Guide -->
                        <div class="p-3.5 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-[11px] text-sky-200 leading-relaxed flex items-start gap-2.5">
                            <Info class="w-4 h-4 text-sky-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <span class="font-bold">Panduan Desain Canva / Photoshop:</span>
                                <p class="text-sky-300/80 mt-0.5">
                                    Pastikan bagian tengah tempat wajah/badan tamu dibuat <strong>bolong transparan (Alpha 0)</strong> agar foto di lapisan belakang terlihat sempurna.
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 pt-2">
                            <button
                                @click="resetUploadForm"
                                class="py-3 px-5 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-300 font-semibold text-xs transition-all"
                            >
                                Batal
                            </button>

                            <button
                                @click="submitCustomUpload"
                                :disabled="isUploading"
                                class="flex-1 py-3.5 px-6 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50"
                            >
                                <Upload class="w-4 h-4" />
                                <span>{{ isUploading ? 'Mengunggah & Memproses...' : 'SIMPAN & GUNAKAN BINGKAI' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Specs Bar -->
            <div class="px-6 py-3 border-t border-white/10 bg-slate-950/70 flex items-center justify-between text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Format Industri: PNG-24 Alpha Overlay (300 DPI High Definition)</span>
                </div>
                <button
                    @click="emit('close')"
                    class="font-semibold text-slate-300 hover:text-white"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-checkerboard {
    background-image: 
        linear-gradient(45deg, #1e293b 25%, transparent 25%), 
        linear-gradient(-45deg, #1e293b 25%, transparent 25%), 
        linear-gradient(45deg, transparent 75%, #1e293b 75%), 
        linear-gradient(-45deg, transparent 75%, #1e293b 75%);
    background-size: 16px 16px;
    background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
    background-color: #0f172a;
}
</style>
