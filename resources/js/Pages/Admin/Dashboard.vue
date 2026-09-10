<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import { 
    Camera, 
    Printer, 
    Calendar, 
    Banknote, 
    Image, 
    Layers, 
    RefreshCw, 
    Play, 
    CheckCircle2, 
    Clock, 
    ExternalLink 
} from 'lucide-vue-next';
import { useDeviceStore } from '@/stores/deviceStore';
import { getAssetUrl } from '@/utils/url';
import { showToast } from '@/utils/swal';

const props = defineProps<{
    stats: {
        today_sessions: number;
        today_prints: number;
        today_revenue: number;
        active_templates_count: number;
    };
    recentSessions: any[];
    activeEvent?: any;
}>();

const deviceStore = useDeviceStore();
const isRefreshing = ref(false);
const isTestingPrinter = ref(false);

async function refreshAll() {
    isRefreshing.value = true;
    await deviceStore.fetchStatus();
    router.reload({ only: ['stats', 'recentSessions'] });
    isRefreshing.value = false;
}

async function handleTestPrint() {
    isTestingPrinter.value = true;
    const res = await deviceStore.testPrinter();
    isTestingPrinter.value = false;
    if (res.success) {
        showToast(res.message || 'Cetak lembar tes berhasil dikirim!', 'success');
    } else {
        showToast(res.message || 'Gagal mencetak lembar tes', 'error');
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-8">
            <!-- TOP WELCOME & STATS BAR -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                        Ringkasan Operasional Hari Ini
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Pantau performa live photo booth, status perangkat keras, dan riwayat cetak foto
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="refreshAll"
                        :disabled="isRefreshing"
                        class="py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 text-xs font-semibold flex items-center gap-2 border border-white/10 transition-all"
                    >
                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                        <span>Refresh Data</span>
                    </button>

                    <Link
                        href="/"
                        class="py-2.5 px-5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold shadow-lg flex items-center gap-2 transition-all active:scale-95"
                    >
                        <Play class="w-4 h-4 fill-slate-950" />
                        <span>Mulai Kiosk</span>
                    </Link>
                </div>
            </div>

            <!-- KEY METRICS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Sesi Hari Ini -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Total Sesi Foto</span>
                        <Camera class="w-5 h-5 text-amber-400" />
                    </div>
                    <div class="my-4">
                        <p class="text-4xl font-black text-white font-mono">{{ stats.today_sessions }}</p>
                        <p class="text-xs text-slate-400 mt-1">Sesi pemotretan berhasil</p>
                    </div>
                    <span class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" /> Sesi Aktif Berjalan Normal
                    </span>
                </div>

                <!-- Lembar Cetak -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Lembar Dicetak</span>
                        <Printer class="w-5 h-5 text-sky-400" />
                    </div>
                    <div class="my-4">
                        <p class="text-4xl font-black text-white font-mono">{{ stats.today_prints }}</p>
                        <p class="text-xs text-slate-400 mt-1">Kertas foto 4R / Strip</p>
                    </div>
                    <span class="text-[11px] text-slate-400">
                        Sisa kertas printer: <b class="text-white">{{ deviceStore.printer.paper_remaining }}</b>
                    </span>
                </div>

                <!-- Pendapatan -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Pendapatan Hari Ini</span>
                        <Banknote class="w-5 h-5 text-emerald-400" />
                    </div>
                    <div class="my-4">
                        <p class="text-3xl font-black text-emerald-400 font-mono">
                            Rp {{ (stats.today_revenue || 0).toLocaleString('id-ID') }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Pembayaran cash & QRIS</p>
                    </div>
                    <span class="text-[11px] text-slate-400">Dari seluruh metode pembayaran</span>
                </div>

                <!-- Template Aktif -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Template Tersedia</span>
                        <Layers class="w-5 h-5 text-purple-400" />
                    </div>
                    <div class="my-4">
                        <p class="text-4xl font-black text-white font-mono">{{ stats.active_templates_count }}</p>
                        <p class="text-xs text-slate-400 mt-1">Desain siap digunakan</p>
                    </div>
                    <Link href="/admin/templates" class="text-[11px] text-amber-400 hover:underline">
                        Buka Template Builder &rarr;
                    </Link>
                </div>
            </div>

            <!-- DEVICE CENTER QUICK CARDS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Camera Card -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center border border-amber-400/30">
                                <Camera class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">Kamera Photo Booth</h3>
                                <p class="text-xs text-slate-400">{{ deviceStore.camera.name }}</p>
                            </div>
                        </div>
                        <DeviceStatusBadge :status="deviceStore.camera.status" size="sm" />
                    </div>

                    <div class="grid grid-cols-3 gap-3 my-5 text-center">
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Baterai</span>
                            <span class="font-mono font-bold text-emerald-400 text-sm">{{ deviceStore.camera.battery_level }}%</span>
                        </div>
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Penyimpanan</span>
                            <span class="font-mono font-bold text-sky-400 text-xs truncate">{{ deviceStore.camera.storage_remaining }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Exposure</span>
                            <span class="font-mono font-bold text-amber-300 text-xs">ISO {{ deviceStore.camera.iso }} • {{ deviceStore.camera.shutter_speed }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="deviceStore.testCamera"
                            class="flex-1 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/10 transition-all"
                        >
                            Uji Jepret Test
                        </button>
                        <Link
                            href="/admin/devices"
                            class="py-2.5 px-4 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-xs font-semibold border border-white/10"
                        >
                            Device Center
                        </Link>
                    </div>
                </div>

                <!-- Printer Card -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 flex flex-col justify-between">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-400/20 text-sky-400 flex items-center justify-center border border-sky-400/30">
                                <Printer class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">Printer Foto Sublimasi</h3>
                                <p class="text-xs text-slate-400">{{ deviceStore.printer.name }}</p>
                            </div>
                        </div>
                        <DeviceStatusBadge :status="deviceStore.printer.status" size="sm" />
                    </div>

                    <div class="grid grid-cols-3 gap-3 my-5 text-center">
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Sisa Kertas</span>
                            <span class="font-mono font-bold text-white text-sm">{{ deviceStore.printer.paper_remaining }} Lembar</span>
                        </div>
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Status Kertas</span>
                            <span class="font-mono font-bold text-emerald-400 text-xs">{{ deviceStore.printer.paper_status }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[10px] text-slate-500 uppercase block">Antrian Print</span>
                            <span class="font-mono font-bold text-amber-300 text-xs">{{ deviceStore.printer.queue_count }} Job</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="handleTestPrint"
                            :disabled="isTestingPrinter"
                            class="flex-1 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/10 transition-all disabled:opacity-50"
                        >
                            {{ isTestingPrinter ? 'Mengirim...' : 'Cetak Lembar Tes' }}
                        </button>
                        <Link
                            href="/admin/devices"
                            class="py-2.5 px-4 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-xs font-semibold border border-white/10"
                        >
                            Device Center
                        </Link>
                    </div>
                </div>
            </div>

            <!-- RECENT SESSIONS TABLE -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-white/10">
                <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                    <div>
                        <h3 class="font-bold text-white text-base">Riwayat Sesi Terkini</h3>
                        <p class="text-xs text-slate-400">Daftar sesi foto yang baru saja selesai diproses</p>
                    </div>

                    <Link
                        href="/admin/gallery"
                        class="text-xs font-bold text-amber-400 hover:underline flex items-center gap-1"
                    >
                        <span>Lihat Semua di Galeri</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/10">
                                <th class="pb-3 font-semibold">KODE SESI</th>
                                <th class="pb-3 font-semibold">PREVIEW</th>
                                <th class="pb-3 font-semibold">TEMPLATE</th>
                                <th class="pb-3 font-semibold">STATUS</th>
                                <th class="pb-3 font-semibold">CETAK</th>
                                <th class="pb-3 font-semibold">PEMBAYARAN</th>
                                <th class="pb-3 font-semibold">WAKTU</th>
                                <th class="pb-3 font-semibold text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-200">
                            <tr v-for="s in recentSessions" :key="s.id" class="hover:bg-white/5 transition-colors">
                                <td class="py-3 font-mono font-bold text-white">{{ s.session_code }}</td>
                                <td class="py-3">
                                    <div class="w-10 h-14 rounded-lg bg-black overflow-hidden border border-white/10">
                                        <img
                                            v-if="s.final_photo_path"
                                            :src="getAssetUrl(s.final_photo_path)"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-slate-600 text-[10px]">
                                            N/A
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">{{ s.template?.name || '-' }}</td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-white/10 text-slate-300">
                                        {{ s.status }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                                        :class="s.print_status === 'printed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400'"
                                    >
                                        {{ s.print_status }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                                        :class="s.payment_status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'"
                                    >
                                        {{ s.payment_status }}
                                    </span>
                                </td>
                                <td class="py-3 font-mono text-slate-400">{{ s.created_at ? new Date(s.created_at).toLocaleTimeString('id-ID') : '-' }}</td>
                                <td class="py-3 text-right">
                                    <a
                                        v-if="s.digital_code"
                                        :href="`/download/${s.digital_code}`"
                                        target="_blank"
                                        class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-amber-300 text-[11px] font-semibold"
                                    >
                                        Unduh
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="!recentSessions || recentSessions.length === 0">
                                <td colspan="8" class="py-6 text-center text-slate-500">
                                    Belum ada sesi tercatat. Mulai sesi foto di Kiosk!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>