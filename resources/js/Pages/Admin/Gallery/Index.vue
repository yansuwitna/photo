<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Image, Download, Printer, QrCode, Search, Filter, X, ExternalLink } from 'lucide-vue-next';

const props = defineProps<{
    sessions: any[];
}>();

const selectedSession = ref<any | null>(null);
const searchQuery = ref('');

function getAssetUrl(path?: string) {
    if (!path) return '';
    return '/' + path.replace('public/', 'storage/');
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-white">Galeri Sesi Foto</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Koleksi foto hasil render final dan foto original dari seluruh sesi photo booth
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari kode sesi..."
                            class="pl-9 pr-4 py-2 rounded-xl bg-slate-900 border border-white/10 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-amber-400 w-48 sm:w-64"
                        />
                    </div>
                </div>
            </div>

            <!-- MASONRY GRID OF PHOTOS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div
                    v-for="s in sessions"
                    :key="s.id"
                    @click="selectedSession = s"
                    class="group relative rounded-2xl overflow-hidden bg-slate-900 border border-white/10 hover:border-amber-400/50 cursor-pointer shadow-lg transition-all duration-300 transform hover:-translate-y-1"
                >
                    <div class="aspect-[2/3] w-full bg-black">
                        <img
                            v-if="s.final_photo_path"
                            :src="getAssetUrl(s.final_photo_path)"
                            :alt="s.session_code"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-600 text-xs">
                            Tidak Ada Final
                        </div>
                    </div>

                    <!-- Overlay Info on Hover -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3">
                        <p class="font-mono font-bold text-xs text-white">{{ s.session_code }}</p>
                        <p class="text-[10px] text-amber-300">{{ s.template?.name }}</p>
                        <div class="flex items-center justify-between text-[9px] text-slate-400 mt-1">
                            <span>{{ s.print_status }}</span>
                            <span>{{ s.payment_status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!sessions || sessions.length === 0" class="py-16 text-center text-slate-500">
                <Image class="w-12 h-12 mx-auto mb-3 text-slate-600" />
                <p class="text-sm font-semibold text-slate-400">Belum ada foto yang tersimpan di galeri</p>
            </div>
        </div>

        <!-- MODAL DETAIL INSPECTION -->
        <div
            v-if="selectedSession"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-md p-4"
        >
            <div class="relative w-full max-w-3xl rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl flex flex-col md:flex-row gap-6">
                <button
                    @click="selectedSession = null"
                    class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-slate-400 hover:text-white"
                >
                    <X class="w-5 h-5" />
                </button>

                <!-- Left: Full Image -->
                <div class="w-full md:w-1/2 aspect-[2/3] rounded-2xl overflow-hidden bg-black border border-white/10 shadow-inner">
                    <img
                        :src="getAssetUrl(selectedSession.final_photo_path)"
                        class="w-full h-full object-contain"
                    />
                </div>

                <!-- Right: Details & Actions -->
                <div class="flex-1 flex flex-col justify-between text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Detail Sesi</span>
                        <h3 class="text-xl font-bold text-white mt-1">{{ selectedSession.session_code }}</h3>
                        <p class="text-slate-400 mt-1">Digital Code: {{ selectedSession.digital_code }}</p>

                        <div class="mt-4 space-y-2 text-slate-300">
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-500">Event</span>
                                <span class="font-semibold">{{ selectedSession.event?.name || 'Default' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-500">Template Desain</span>
                                <span class="font-semibold">{{ selectedSession.template?.name }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-500">Status Cetak</span>
                                <span class="uppercase font-bold text-emerald-400">{{ selectedSession.print_status }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-white/5">
                                <span class="text-slate-500">Status Bayar</span>
                                <span class="uppercase font-bold text-amber-300">{{ selectedSession.payment_status }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <a
                            v-if="selectedSession.digital_code"
                            :href="`/download/${selectedSession.digital_code}`"
                            target="_blank"
                            class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs flex items-center justify-center gap-2"
                        >
                            <ExternalLink class="w-4 h-4" />
                            <span>Buka Halaman Download Guest</span>
                        </a>

                        <a
                            :href="getAssetUrl(selectedSession.final_photo_path)"
                            download
                            class="w-full py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs flex items-center justify-center gap-2"
                        >
                            <Download class="w-4 h-4" />
                            <span>Unduh File Asli</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>