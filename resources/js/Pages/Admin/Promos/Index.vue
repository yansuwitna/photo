<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Tag, Plus, Check, X, Percent, Gift } from 'lucide-vue-next';
import axios from 'axios';
import { showSuccess, showError } from '@/utils/swal';

const props = defineProps<{
    promos: any[];
}>();

const showModal = ref(false);
const form = ref({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 20,
    min_spend: 25000,
    max_discount: 10000,
    usage_limit: 100,
    is_active: true,
});

async function savePromo() {
    try {
        await axios.post('/api/admin/promos', form.value);
        showModal.value = false;
        await showSuccess('Promo Disimpan!', `Kode promo "${form.value.code}" siap digunakan.`);
        router.reload();
    } catch (e) {
        showError('Gagal Menyimpan Promo', 'Terjadi kesalahan saat menambahkan kode promo baru.');
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">Promo & Voucher Diskon</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Atur kode promo diskon persentase, nominal tetap, dan voucher gratis untuk event
                    </p>
                </div>

                <button
                    @click="showModal = true"
                    class="py-2.5 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95"
                >
                    <Plus class="w-4 h-4 stroke-[3]" />
                    <span>Buat Promo Baru</span>
                </button>
            </div>

            <!-- Promos Table -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/10">
                                <th class="pb-3 font-semibold">KODE</th>
                                <th class="pb-3 font-semibold">NAMA PROMO</th>
                                <th class="pb-3 font-semibold">TIPE DISKON</th>
                                <th class="pb-3 font-semibold">NILAI POTONGAN</th>
                                <th class="pb-3 font-semibold">MIN. TRANSAKSI</th>
                                <th class="pb-3 font-semibold">PENGGUNAAN</th>
                                <th class="pb-3 font-semibold">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-200">
                            <tr v-for="p in promos" :key="p.id" class="hover:bg-white/5">
                                <td class="py-3.5 font-mono font-bold text-amber-400">{{ p.code }}</td>
                                <td class="py-3.5 font-semibold text-white">{{ p.name }}</td>
                                <td class="py-3.5 uppercase text-slate-300">{{ p.discount_type }}</td>
                                <td class="py-3.5 font-mono font-bold text-emerald-400">
                                    {{ p.discount_type === 'percentage' ? `${p.discount_value}%` : `Rp ${Number(p.discount_value).toLocaleString('id-ID')}` }}
                                </td>
                                <td class="py-3.5 font-mono text-slate-400">Rp {{ Number(p.min_spend).toLocaleString('id-ID') }}</td>
                                <td class="py-3.5 font-mono text-slate-300">{{ p.usage_count }} / {{ p.usage_limit || '∞' }}</td>
                                <td class="py-3.5">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="p.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'"
                                    >
                                        {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
            <div class="w-full max-w-md rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h3 class="text-lg font-bold text-white">Buat Kode Promo Baru</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white"><X class="w-5 h-5" /></button>
                </div>

                <form @submit.prevent="savePromo" class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Kode Promo *</label>
                        <input v-model="form.code" required class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white uppercase font-mono" placeholder="DISKON20" />
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nama Promo *</label>
                        <input v-model="form.name" required class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" placeholder="Promo Akhir Pekan" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Tipe Diskon</label>
                            <select v-model="form.discount_type" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white">
                                <option value="percentage">Persentase (%)</option>
                                <option value="fixed">Nominal Tetap (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Nilai Diskon</label>
                            <input type="number" v-model.number="form.discount_value" required class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Min. Belanja (Rp)</label>
                            <input type="number" v-model.number="form.min_spend" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Maksimal Penggunaan</label>
                            <input type="number" v-model.number="form.usage_limit" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="py-2.5 px-4 rounded-xl bg-white/10 text-slate-300">Batal</button>
                        <button type="submit" class="py-2.5 px-5 rounded-xl bg-amber-400 text-slate-950 font-bold">Simpan Promo</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>