<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Calendar, Plus, Check, X, MapPin, Tag, Sparkles } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    events: any[];
}>();

const showModal = ref(false);
const isSubmitting = ref(false);

const form = ref({
    name: '',
    description: '',
    event_date: '',
    location: '',
    default_price: 25000,
    extra_print_price: 10000,
    watermark_text: '',
    countdown_seconds: 5,
    is_active: false,
});

async function saveEvent() {
    isSubmitting.value = true;
    try {
        await axios.post('/api/admin/events', form.value);
        showModal.value = false;
        router.reload();
    } catch (e) {
        alert('Gagal menyimpan event');
    } finally {
        isSubmitting.value = false;
    }
}

async function activateEvent(id: number) {
    try {
        await axios.post(`/api/admin/events/${id}/activate`);
        router.reload();
    } catch (e) {
        alert('Gagal mengaktifkan event');
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">Kelola Acara / Event</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Konfigurasi tema, harga, watermark, dan template khusus untuk setiap acara
                    </p>
                </div>

                <button
                    @click="showModal = true"
                    class="py-2.5 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95"
                >
                    <Plus class="w-4 h-4 stroke-[3]" />
                    <span>Buat Event Baru</span>
                </button>
            </div>

            <!-- Events Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="ev in events"
                    :key="ev.id"
                    class="p-6 rounded-3xl bg-slate-900 border transition-all flex flex-col justify-between"
                    :class="ev.is_active ? 'border-amber-400/50 shadow-[0_0_30px_rgba(245,158,11,0.2)]' : 'border-white/10 hover:border-white/20'"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="ev.is_active ? 'bg-amber-400 text-slate-950' : 'bg-white/10 text-slate-400'"
                            >
                                {{ ev.is_active ? 'EVENT AKTIF' : 'NONAKTIF' }}
                            </span>
                            <span class="text-xs font-mono text-slate-400">{{ ev.event_date || 'Tanggal fleksibel' }}</span>
                        </div>

                        <h3 class="text-lg font-bold text-white mb-1">{{ ev.name }}</h3>
                        <p class="text-xs text-slate-400 flex items-center gap-1.5 mb-4">
                            <MapPin class="w-3.5 h-3.5 text-amber-400" />
                            <span>{{ ev.location || 'Lokasi Studio' }}</span>
                        </p>

                        <div class="p-3.5 rounded-2xl bg-black/40 border border-white/5 space-y-1.5 text-xs text-slate-300 mb-4">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Harga Sesi:</span>
                                <span class="font-mono font-bold text-white">Rp {{ Number(ev.default_price).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Cetak Tambahan:</span>
                                <span class="font-mono text-white">Rp {{ Number(ev.extra_print_price).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Hitung Mundur:</span>
                                <span class="font-mono text-amber-300">{{ ev.countdown_seconds }} Detik</span>
                            </div>
                        </div>

                        <div v-if="ev.watermark_text" class="text-[11px] text-slate-400 italic">
                            Watermark: "{{ ev.watermark_text }}"
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/10">
                        <button
                            v-if="!ev.is_active"
                            @click="activateEvent(ev.id)"
                            class="w-full py-2.5 rounded-xl bg-white/10 hover:bg-amber-400 hover:text-slate-950 text-white text-xs font-bold transition-all"
                        >
                            Jadikan Event Aktif
                        </button>
                        <div v-else class="text-center text-xs font-bold text-emerald-400 flex items-center justify-center gap-1.5 py-2">
                            <Check class="w-4 h-4 stroke-[3]" />
                            <span>Sedang Digunakan Pada Kiosk</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
            <div class="w-full max-w-lg rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h3 class="text-lg font-bold text-white">Tambah Acara / Event Baru</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white"><X class="w-5 h-5" /></button>
                </div>

                <form @submit.prevent="saveEvent" class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nama Acara / Pasangan / Institusi *</label>
                        <input v-model="form.name" required class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" placeholder="Contoh: Wedding Rama & Sinta" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Tanggal Event</label>
                            <input type="date" v-model="form.event_date" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Lokasi Event</label>
                            <input v-model="form.location" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" placeholder="Hotel / Gedung" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Harga Sesi (Rp)</label>
                            <input type="number" v-model.number="form.default_price" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Cetak Ekstra (Rp)</label>
                            <input type="number" v-model.number="form.extra_print_price" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Teks Watermark Cetak</label>
                        <input v-model="form.watermark_text" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" placeholder="Contoh: Rama & Sinta Wedding - 2026" />
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Hitung Mundur Kamera (Detik)</label>
                        <select v-model.number="form.countdown_seconds" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white">
                            <option :value="3">3 Detik (Cepat)</option>
                            <option :value="5">5 Detik (Standar)</option>
                            <option :value="10">10 Detik (Santai)</option>
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="py-2.5 px-4 rounded-xl bg-white/10 text-slate-300">Batal</button>
                        <button type="submit" :disabled="isSubmitting" class="py-2.5 px-5 rounded-xl bg-amber-400 text-slate-950 font-bold">Simpan Event</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>