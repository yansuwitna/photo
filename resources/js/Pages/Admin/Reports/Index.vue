<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { BarChart3, Download, FileSpreadsheet, FileText, Calendar, DollarSign, Printer, Layers } from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        total_sessions: number;
        total_prints: number;
        total_revenue: number;
        popular_template: string;
    };
    transactions: any[];
}>();

function exportCSV() {
    window.location.href = '/api/admin/reports/export-csv';
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">Laporan & Statistik Bisnis</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Rekapitulasi pendapatan, volume cetak printer, dan performa event photo booth
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="exportCSV"
                        class="py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95"
                    >
                        <FileSpreadsheet class="w-4 h-4" />
                        <span>Ekspor Laporan (CSV / Excel)</span>
                    </button>
                </div>
            </div>

            <!-- Big Stat Highlights -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] text-slate-400 uppercase font-bold">Total Pendapatan</span>
                    <p class="text-3xl font-black text-emerald-400 font-mono mt-1">
                        Rp {{ Number(stats.total_revenue || 0).toLocaleString('id-ID') }}
                    </p>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] text-slate-400 uppercase font-bold">Total Sesi Foto</span>
                    <p class="text-3xl font-black text-white font-mono mt-1">
                        {{ stats.total_sessions }}
                    </p>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] text-slate-400 uppercase font-bold">Total Lembar Cetak</span>
                    <p class="text-3xl font-black text-sky-400 font-mono mt-1">
                        {{ stats.total_prints }} Lembar
                    </p>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10">
                    <span class="text-[11px] text-slate-400 uppercase font-bold">Template Terfavorit</span>
                    <p class="text-xl font-black text-amber-400 mt-2 truncate">
                        {{ stats.popular_template || 'Classic 3-Photo Strip' }}
                    </p>
                </div>
            </div>

            <!-- Transaction Log Table -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10">
                <h3 class="text-base font-bold text-white mb-4">Daftar Transaksi Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/10">
                                <th class="pb-3 font-semibold">REF #</th>
                                <th class="pb-3 font-semibold">SESI ID</th>
                                <th class="pb-3 font-semibold">METODE</th>
                                <th class="pb-3 font-semibold">SUBTOTAL</th>
                                <th class="pb-3 font-semibold">DISKON</th>
                                <th class="pb-3 font-semibold">TOTAL DIBAYAR</th>
                                <th class="pb-3 font-semibold">STATUS</th>
                                <th class="pb-3 font-semibold">TANGGAL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-200">
                            <tr v-for="t in transactions" :key="t.id" class="hover:bg-white/5">
                                <td class="py-3 font-mono font-bold text-amber-400">{{ t.reference_number || '-' }}</td>
                                <td class="py-3 font-mono text-slate-300">{{ t.session?.session_code || t.session_id }}</td>
                                <td class="py-3 uppercase text-white font-semibold">{{ t.method }}</td>
                                <td class="py-3 font-mono text-slate-400">Rp {{ Number(t.subtotal).toLocaleString('id-ID') }}</td>
                                <td class="py-3 font-mono text-rose-400">- Rp {{ Number(t.discount_amount).toLocaleString('id-ID') }}</td>
                                <td class="py-3 font-mono font-bold text-emerald-400">Rp {{ Number(t.total_amount).toLocaleString('id-ID') }}</td>
                                <td class="py-3 uppercase font-semibold text-emerald-300">{{ t.status }}</td>
                                <td class="py-3 text-slate-400">{{ t.created_at ? new Date(t.created_at).toLocaleString('id-ID') : '-' }}</td>
                            </tr>
                            <tr v-if="!transactions || transactions.length === 0">
                                <td colspan="8" class="py-6 text-center text-slate-500">
                                    Belum ada catatan transaksi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>