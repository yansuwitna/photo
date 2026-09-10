<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
    Printer, 
    CheckCircle2, 
    RefreshCw, 
    Play, 
    Volume2, 
    VolumeX, 
    AlertCircle, 
    Clock, 
    Copy, 
    ExternalLink, 
    HelpCircle, 
    Check, 
    Tablet, 
    ShieldCheck, 
    Sparkles, 
    Layers,
    SlidersHorizontal,
    ArrowLeft
} from 'lucide-vue-next';
import axios from 'axios';
import { useAudioStore } from '@/stores/audioStore';
import { showSuccess, showError, showInfo } from '@/utils/swal';

interface PrintJobItem {
    id: number;
    session_id?: string;
    session_code?: string;
    event_name?: string;
    copies: number;
    paper_size: string;
    printer_name?: string;
    file_url?: string;
    status: 'pending' | 'queued' | 'printing' | 'completed' | 'failed';
    error_message?: string;
    created_at?: string;
    completed_at?: string;
}

const props = defineProps<{
    active_printer?: any;
    active_paper_size?: string;
    web_station_enabled: boolean;
    stats?: {
        today_jobs: number;
        today_completed: number;
    };
}>();

const audioStore = useAudioStore();

// State
const autoPrintEnabled = ref(true);
const soundEnabled = ref(true);
const isPolling = ref(false);
const isPrinting = ref(false);
const activeJob = ref<PrintJobItem | null>(null);
const printProgress = ref(0);
const pendingJobs = ref<PrintJobItem[]>([]);
const recentJobs = ref<PrintJobItem[]>([]);
const activePrinter = ref(props.active_printer);
const activePaperSize = ref(props.active_paper_size || '4R');
const showGuideModal = ref(false);
const isTestingPrint = ref(false);
const previewPhotoUrl = ref<string | null>(null);
const copiedShortcut = ref(false);

const completedCount = ref(props.stats?.today_completed || 0);
const queueCount = computed(() => pendingJobs.value.length);

let pollTimer: any = null;

onMounted(() => {
    audioStore.initContext();
    fetchJobs();
    pollTimer = setInterval(pollAndPrintEngine, 2000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

// Fetch pending and recent jobs
async function fetchJobs() {
    try {
        isPolling.value = true;
        const res = await axios.get('/api/print-station/jobs');
        if (res.data.success) {
            pendingJobs.value = res.data.pending_jobs || [];
            recentJobs.value = res.data.recent_jobs || [];
            if (res.data.active_printer) {
                activePrinter.value = res.data.active_printer;
            }
            if (res.data.active_paper_size) {
                activePaperSize.value = res.data.active_paper_size;
            }
        }
    } catch (e) {
        console.warn('Failed to fetch jobs:', e);
    } finally {
        isPolling.value = false;
    }
}

// Engine loop yang memproses antrean cetak
async function pollAndPrintEngine() {
    if (isPrinting.value) return; // Tunggu jika job saat ini masih diproses

    await fetchJobs();

    if (!autoPrintEnabled.value) return;

    if (pendingJobs.value.length > 0) {
        const nextJob = pendingJobs.value[0];
        await executePrintJob(nextJob);
    }
}

// Eksekusi cetak foto melalui silent iframe di browser
async function executePrintJob(job: PrintJobItem) {
    if (isPrinting.value) return;
    isPrinting.value = true;
    activeJob.value = job;
    printProgress.value = 15;

    try {
        // 1. Beritahu backend bahwa job mulai dicetak
        await axios.post(`/api/print-station/jobs/${job.id}/update`, {
            status: 'printing',
            progress: 30,
        });
        printProgress.value = 40;

        if (soundEnabled.value) {
            audioStore.playBeep(784, 0.15, 'sine');
        }

        // 2. Preload gambar foto dan konversi ke Blob URL untuk menjamin tersedia di RAM
        if (!job.file_url) {
            throw new Error('URL foto tidak ditemukan pada antrean.');
        }

        const safeUrl = resolvePhotoUrl(job.file_url);
        const resolvedBlobUrl = await preloadImage(safeUrl);
        printProgress.value = 70;

        // 3. Render ke dalam Hidden Iframe dan tunggu decoding gambar selesai
        await printViaIframe(job, resolvedBlobUrl);
        printProgress.value = 95;

        // Bersihkan blob URL
        if (resolvedBlobUrl && resolvedBlobUrl.startsWith('blob:')) {
            URL.revokeObjectURL(resolvedBlobUrl);
        }

        // 4. Update status job menjadi completed
        await axios.post(`/api/print-station/jobs/${job.id}/update`, {
            status: 'completed',
            progress: 100,
        });

        completedCount.value += (job.copies || 1);
        printProgress.value = 100;

        if (soundEnabled.value) {
            audioStore.playPrintDone();
            audioStore.speakInstruction(`Foto ${job.session_code || 'sesi'} berhasil dicetak.`);
        }

        // Cooldown sebelum mengambil job berikutnya
        setTimeout(async () => {
            isPrinting.value = false;
            activeJob.value = null;
            printProgress.value = 0;
            await fetchJobs();
        }, 1500);

    } catch (err: any) {
        console.error('Print job error:', err);
        const errMsg = err?.message || 'Gagal memproses cetak browser.';
        try {
            await axios.post(`/api/print-station/jobs/${job.id}/update`, {
                status: 'failed',
                error_message: errMsg,
            });
        } catch (e) {}

        if (soundEnabled.value) {
            audioStore.playBeep(350, 0.4, 'sawtooth');
        }

        showError('Gagal Mencetak', errMsg);
        isPrinting.value = false;
        activeJob.value = null;
        printProgress.value = 0;
        await fetchJobs();
    }
}

// Helper untuk memastikan URL selalu terhubung ke Host dan Port aktif browser
function resolvePhotoUrl(rawUrl?: string): string {
    if (!rawUrl) return '';
    if (rawUrl.startsWith('data:') || rawUrl.startsWith('blob:')) return rawUrl;
    
    // Jika URL absolut tapi beda port/host, arahkan ke origin browser saat ini
    if (rawUrl.startsWith('http://') || rawUrl.startsWith('https://')) {
        try {
            const parsed = new URL(rawUrl);
            return window.location.origin + parsed.pathname + parsed.search;
        } catch (e) {
            return rawUrl;
        }
    }
    
    if (rawUrl.startsWith('/')) {
        return window.location.origin + rawUrl;
    }
    return window.location.origin + '/' + rawUrl;
}

// Preload helper dengan Fetch Blob untuk menyimpan gambar langsung di memori lokal
async function preloadImage(url: string): Promise<string> {
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        const blob = await res.blob();
        if (blob.size === 0) throw new Error('File foto berukuran 0 byte.');
        return URL.createObjectURL(blob);
    } catch (err) {
        console.warn('Fetch blob fallback ke Image() decode:', err);
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = async () => {
                if (img.decode) {
                    try { await img.decode(); } catch (e) {}
                }
                resolve(url);
            };
            img.onerror = () => reject(new Error('Gagal memuat gambar foto untuk dicetak dari: ' + url));
            img.src = url;
        });
    }
}

// Cetak melalui hidden iframe dengan styling @media print akurat dan anti-blank
function printViaIframe(job: PrintJobItem, imageUrl: string): Promise<void> {
    return new Promise(async (resolve) => {
        let iframe = document.getElementById('web-print-station-iframe') as HTMLIFrameElement;
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'web-print-station-iframe';
            // PENTING: Jangan gunakan width: 0 / height: 0 karena Chromium akan menganggap viewport kosong (blank)!
            iframe.style.position = 'fixed';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '100vw';
            iframe.style.height = '100vh';
            iframe.style.border = '0';
            iframe.style.opacity = '0';
            iframe.style.pointerEvents = 'none';
            iframe.style.zIndex = '-9999';
            document.body.appendChild(iframe);
        }

        const doc = iframe.contentWindow?.document || iframe.contentDocument;
        if (!doc) {
            resolve();
            return;
        }

        // Tentukan CSS size berdasarkan paper size
        let pageSizeCss = 'auto';
        const pSize = (job.paper_size || activePaperSize.value || '4R').toUpperCase();
        if (pSize === '4R' || pSize === '4X6') {
            pageSizeCss = '4in 6in';
        } else if (pSize === '5R' || pSize === '5X7') {
            pageSizeCss = '5in 7in';
        } else if (pSize === 'A4') {
            pageSizeCss = 'A4 portrait';
        } else if (pSize.includes('STRIP') || pSize.includes('2X6')) {
            pageSizeCss = '2in 6in';
        }

        const copies = Math.max(1, job.copies || 1);
        let pagesHtml = '';
        for (let i = 0; i < copies; i++) {
            pagesHtml += `
                <div class="print-page">
                    <img src="${imageUrl}" alt="Print Photo" />
                </div>
            `;
        }

        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Print Job #${job.id} - ${job.session_code || ''}</title>
                <style>
                    @page {
                        size: ${pageSizeCss};
                        margin: 0mm;
                    }
                    *, *:before, *:after {
                        box-sizing: border-box;
                        margin: 0;
                        padding: 0;
                    }
                    html, body {
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                        background-color: #ffffff !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    .print-page {
                        width: 100% !important;
                        height: 100vh !important;
                        max-height: 100% !important;
                        page-break-inside: avoid !important;
                        page-break-after: always !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        overflow: hidden !important;
                    }
                    .print-page:last-child {
                        page-break-after: auto !important;
                    }
                    .print-page img {
                        max-width: 100% !important;
                        max-height: 100% !important;
                        width: auto !important;
                        height: auto !important;
                        object-fit: contain !important;
                        display: block !important;
                        margin: auto !important;
                    }
                </style>
            </head>
            <body>
                ${pagesHtml}
            </body>
            </html>
        `);
        doc.close();

        // Tunggu hingga seluruh tag <img> di dalam iframe benar-benar ter-load & ter-decode
        try {
            const imgs = Array.from(doc.getElementsByTagName('img'));
            await Promise.all(imgs.map(img => {
                if (img.complete && img.naturalWidth > 0) return Promise.resolve();
                return new Promise((res) => {
                    img.onload = () => res(true);
                    img.onerror = () => res(false);
                    setTimeout(() => res(true), 2500); // safety fallback
                });
            }));

            // Decode gambar untuk memastikan rasterizer GPU selesai
            await Promise.all(imgs.map(img => img.decode ? img.decode().catch(() => {}) : Promise.resolve()));
        } catch (e) {
            console.warn('Image decode wait warning:', e);
        }

        // Beri jeda 350ms agar browser selesai menyusun layer layout visual
        setTimeout(() => {
            try {
                iframe.contentWindow?.focus();
                iframe.contentWindow?.print();
            } catch (e) {
                console.warn('Iframe print warning:', e);
            }
            // Selesaikan promise setelah print dialog/spooler terkirim
            setTimeout(() => {
                resolve();
            }, 800);
        }, 350);
    });
}

// Uji cetak instan
async function handleTestPrint() {
    isTestingPrint.value = true;
    try {
        const res = await axios.post('/api/print-station/test', {
            copies: 1,
            paper_size: activePaperSize.value,
        });
        if (res.data.success) {
            showSuccess('Uji Cetak Dimasukkan', 'Kartu uji cetak berhasil dimasukkan ke antrean!');
            await fetchJobs();
        } else {
            showError('Gagal Uji Cetak', res.data.message);
        }
    } catch (e: any) {
        showError('Gagal Uji Cetak', e?.response?.data?.message || 'Terjadi kesalahan sistem.');
    } finally {
        isTestingPrint.value = false;
    }
}

// Cetak ulang job lama
async function handleReprint(jobId: number) {
    try {
        const res = await axios.post(`/api/print-station/jobs/${jobId}/reprint`);
        if (res.data.success) {
            showSuccess('Cetak Ulang Dijadwalkan', 'Foto dimasukkan kembali ke antrean cetak.');
            await fetchJobs();
        }
    } catch (e: any) {
        showError('Gagal Reprint', e?.response?.data?.message || 'Terjadi kesalahan.');
    }
}

// Toggle status web station
async function handleToggleStation() {
    autoPrintEnabled.value = !autoPrintEnabled.value;
    try {
        await axios.post('/api/print-station/toggle', {
            enabled: autoPrintEnabled.value,
        });
    } catch (e) {}
}

// Copy shortcut command untuk Windows Run
function copyRunCommand() {
    const currentUrl = window.location.href;
    const cmd = `chrome.exe --kiosk-printing "${currentUrl}"`;
    navigator.clipboard.writeText(cmd);
    copiedShortcut.value = true;
    setTimeout(() => {
        copiedShortcut.value = false;
    }, 2500);
}
</script>

<template>
    <Head title="Real-Time Print Station" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none">
        <!-- TOP NAVIGATION BAR -->
        <header class="bg-slate-900/90 border-b border-white/10 px-6 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-40 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <button 
                    @click="router.visit('/controller')" 
                    class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition-all border border-white/10"
                    title="Kembali ke Controller"
                >
                    <ArrowLeft class="w-5 h-5" />
                </button>
                <div class="w-11 h-11 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.25)]">
                    <Printer class="w-6 h-6" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-black tracking-tight text-white">WEB PRINT STATION</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                            :class="autoPrintEnabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-white/10'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="autoPrintEnabled ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'"></span>
                            {{ autoPrintEnabled ? 'Real-Time Aktif' : 'Standby / Jeda' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Pencetakan otomatis browser terhubung langsung dengan Kiosk & Tablet
                    </p>
                </div>
            </div>

            <!-- RIGHT CONTROLS -->
            <div class="flex items-center gap-2.5">
                <!-- Sound Toggle -->
                <button
                    @click="soundEnabled = !soundEnabled"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 border transition-all"
                    :class="soundEnabled ? 'bg-white/10 text-slate-200 border-white/20 hover:bg-white/15' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                    title="Toggle Efek Suara"
                >
                    <Volume2 v-if="soundEnabled" class="w-4 h-4 text-emerald-400" />
                    <VolumeX v-else class="w-4 h-4" />
                    <span>{{ soundEnabled ? 'Audio Aktif' : 'Mute' }}</span>
                </button>

                <!-- Help Silent Print Guide -->
                <button
                    @click="showGuideModal = true"
                    class="px-3.5 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-2 transition-all shadow-sm"
                >
                    <HelpCircle class="w-4 h-4" />
                    <span>Panduan Silent Print</span>
                </button>

                <!-- Test Print Button -->
                <button
                    @click="handleTestPrint"
                    :disabled="isTestingPrint || isPrinting"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs flex items-center gap-2 transition-all shadow-md active:scale-95 disabled:opacity-50"
                >
                    <Sparkles class="w-4 h-4" />
                    <span>{{ isTestingPrint ? 'Menyiapkan...' : 'Uji Cetak (Test)' }}</span>
                </button>

                <!-- Link to Admin -->
                <a
                    href="/admin/devices"
                    class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 transition-all"
                    title="Pengaturan Perangkat Admin"
                >
                    <SlidersHorizontal class="w-5 h-5" />
                </a>
            </div>
        </header>

        <!-- MAIN WORKSPACE -->
        <main class="flex-1 p-4 md:p-6 max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT PANEL: STATS & ACTIVE PRINT VISUALIZER (5 COLS) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- PRINTER & HARDWARE STATUS CARD -->
                <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                                <ShieldCheck class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Target Printer PC</h2>
                                <p class="text-xs text-slate-400">Default Windows Spooler</p>
                            </div>
                        </div>

                        <!-- Auto-print Toggle Switch -->
                        <button
                            @click="handleToggleStation"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold flex items-center gap-2 border transition-all"
                            :class="autoPrintEnabled ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-slate-800 text-slate-300 border-white/10'"
                        >
                            <Play v-if="autoPrintEnabled" class="w-3.5 h-3.5 fill-current" />
                            <span>{{ autoPrintEnabled ? 'AUTO ON' : 'PAUSED' }}</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4 text-xs">
                        <div class="bg-slate-950/60 p-3.5 rounded-2xl border border-white/5">
                            <span class="text-slate-400 block mb-1">Printer Terdaftar:</span>
                            <span class="font-bold text-white text-sm truncate block" :title="activePrinter?.name">
                                {{ activePrinter?.name || 'Windows Default Printer' }}
                            </span>
                            <span class="text-[11px] text-amber-400 mt-1 block">
                                {{ activePrinter?.brand || 'Spooler' }} • {{ activePrinter?.connection_type || 'USB' }}
                            </span>
                        </div>

                        <div class="bg-slate-950/60 p-3.5 rounded-2xl border border-white/5">
                            <span class="text-slate-400 block mb-1">Ukuran Kertas:</span>
                            <span class="font-bold text-amber-300 text-sm block">
                                {{ activePaperSize }}
                            </span>
                            <span class="text-[11px] text-slate-400 mt-1 block">
                                Margin: 0mm (Borderless)
                            </span>
                        </div>
                    </div>

                    <!-- STAT COUNTERS -->
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="bg-gradient-to-br from-slate-950 to-slate-900 border border-white/5 p-4 rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-black">
                                <Clock class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-2xl font-black text-amber-400">{{ queueCount }}</span>
                                <span class="text-[11px] text-slate-400 block uppercase font-bold">Antrean Job</span>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-slate-950 to-slate-900 border border-white/5 p-4 rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-black">
                                <CheckCircle2 class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-2xl font-black text-emerald-400">{{ completedCount }}</span>
                                <span class="text-[11px] text-slate-400 block uppercase font-bold">Total Dicetak</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE ACTIVE JOB MONITOR -->
                <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 shadow-xl flex-1 flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[300px]">
                    <!-- ACTIVE PRINTING STATE -->
                    <div v-if="isPrinting && activeJob" class="w-full flex flex-col items-center animate-fade-in">
                        <div class="relative mb-4">
                            <div class="w-20 h-20 rounded-2xl bg-amber-500/20 border-2 border-amber-400/50 text-amber-400 flex items-center justify-center animate-pulse shadow-[0_0_30px_rgba(245,158,11,0.3)]">
                                <Printer class="w-10 h-10 animate-bounce" />
                            </div>
                        </div>

                        <span class="text-xs font-black text-amber-400 uppercase tracking-widest mb-1">SEDANG MENCETAK KE PRINTER</span>
                        <h3 class="text-xl font-black text-white">{{ activeJob.session_code || 'JOB #' + activeJob.id }}</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ activeJob.copies }} Salinan • Ukuran {{ activeJob.paper_size }}
                        </p>

                        <!-- Progress Bar -->
                        <div class="w-full max-w-xs mt-6 bg-slate-800 rounded-full h-3.5 overflow-hidden border border-white/10 p-0.5">
                            <div 
                                class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-400 transition-all duration-300"
                                :style="{ width: `${printProgress}%` }"
                            ></div>
                        </div>
                        <span class="text-xs text-slate-300 font-bold mt-2">{{ printProgress }}% Memproses Driver...</span>

                        <!-- Thumbnail Mini -->
                        <div v-if="activeJob.file_url" class="mt-4 w-28 aspect-[2/3] rounded-xl overflow-hidden border border-white/20 shadow-lg bg-black">
                            <img :src="activeJob.file_url" class="w-full h-full object-contain" />
                        </div>
                    </div>

                    <!-- IDLE STANDBY STATE -->
                    <div v-else class="flex flex-col items-center py-8">
                        <div class="w-20 h-20 rounded-2xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-400 mb-4 shadow-inner">
                            <Printer class="w-9 h-9 opacity-50" />
                        </div>
                        <h3 class="text-base font-bold text-white">Standby Menunggu Foto</h3>
                        <p class="text-xs text-slate-400 mt-1.5 max-w-xs leading-relaxed">
                            Begitu pengunjung menekan <span class="text-amber-400 font-bold">Cetak</span> di Kiosk/Tablet, browser di PC ini akan langsung mengeksekusi pencetakan secara otomatis.
                        </p>
                        <div class="flex items-center gap-2 mt-4 text-[11px] text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded-full border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>Listening Port 1.5s Interval</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: QUEUE & RECENT JOBS LOG (7 COLS) -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <!-- PENDING QUEUE LIST -->
                <div v-if="pendingJobs.length > 0" class="bg-amber-500/10 border border-amber-500/30 rounded-3xl p-5 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                            <h3 class="text-sm font-black text-amber-300 uppercase tracking-wider">Antrean Sedang Menunggu ({{ pendingJobs.length }})</h3>
                        </div>
                        <span class="text-xs text-slate-400">Otomatis diproses satu per satu</span>
                    </div>

                    <div class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                        <div 
                            v-for="job in pendingJobs" 
                            :key="job.id"
                            class="bg-slate-900/90 border border-white/10 rounded-2xl p-3 flex items-center justify-between gap-3 shadow-sm"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-14 rounded-lg bg-black overflow-hidden border border-white/10 flex-shrink-0">
                                    <img v-if="job.file_url" :src="job.file_url" class="w-full h-full object-cover" />
                                </div>
                                <div class="truncate">
                                    <span class="font-bold text-white text-xs block truncate">{{ job.session_code || 'JOB #' + job.id }}</span>
                                    <span class="text-[11px] text-slate-400">{{ job.copies }}x Salinan • {{ job.paper_size }}</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-400 text-[11px] font-bold border border-amber-500/30">
                                Menunggu...
                            </span>
                        </div>
                    </div>
                </div>

                <!-- RECENT JOBS HISTORY TABLE -->
                <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 shadow-xl flex-1 flex flex-col">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Riwayat Cetak Terkini</h3>
                            <p class="text-xs text-slate-400">15 pekerjaan cetak terakhir pada sesi ini</p>
                        </div>
                        <button 
                            @click="fetchJobs" 
                            class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition-all border border-white/10"
                            title="Segarkan Riwayat"
                        >
                            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isPolling }" />
                        </button>
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-if="recentJobs.length === 0" class="flex-1 flex flex-col items-center justify-center py-12 text-center text-slate-500">
                        <Layers class="w-10 h-10 stroke-1 mb-2 opacity-50" />
                        <p class="text-xs">Belum ada riwayat cetak hari ini.</p>
                        <p class="text-[11px] text-slate-600 mt-0.5">Klik "Uji Cetak" di atas untuk mencoba.</p>
                    </div>

                    <!-- JOBS LIST -->
                    <div v-else class="space-y-2.5 overflow-y-auto max-h-[520px] pr-1">
                        <div 
                            v-for="job in recentJobs" 
                            :key="job.id"
                            class="bg-slate-950/70 border border-white/5 hover:border-white/15 rounded-2xl p-3.5 flex items-center justify-between gap-4 transition-all"
                        >
                            <!-- Left: Thumbnail & Session Info -->
                            <div class="flex items-center gap-3 min-w-0">
                                <div 
                                    @click="job.file_url ? previewPhotoUrl = job.file_url : null"
                                    class="w-12 h-16 rounded-xl bg-black overflow-hidden border border-white/10 flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
                                    title="Klik untuk memperbesar"
                                >
                                    <img v-if="job.file_url" :src="job.file_url" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-slate-700">
                                        <Printer class="w-5 h-5" />
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white text-xs truncate">{{ job.session_code || 'JOB #' + job.id }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ job.completed_at || job.created_at }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ job.copies }} Lembar • <span class="text-amber-400 font-medium">{{ job.paper_size }}</span>
                                    </div>
                                    <div v-if="job.error_message" class="text-[10px] text-rose-400 truncate max-w-xs mt-0.5">
                                        {{ job.error_message }}
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Status Badge & Reprint Button -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': job.status === 'completed',
                                        'bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse': job.status === 'printing',
                                        'bg-rose-500/20 text-rose-400 border border-rose-500/30': job.status === 'failed',
                                    }"
                                >
                                    {{ job.status === 'completed' ? 'Tercetak' : (job.status === 'printing' ? 'Mencetak' : 'Gagal') }}
                                </span>

                                <button
                                    @click="handleReprint(job.id)"
                                    class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 text-xs font-semibold flex items-center gap-1.5 transition-all active:scale-95"
                                    title="Cetak Ulang Foto Ini"
                                >
                                    <RefreshCw class="w-3.5 h-3.5" />
                                    <span>Cetak Ulang</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL PANDUAN SILENT PRINT (--kiosk-printing) -->
        <div 
            v-if="showGuideModal" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
        >
            <div class="bg-slate-900 border border-white/20 rounded-3xl max-w-xl w-full p-6 md:p-8 shadow-2xl relative">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                            <Sparkles class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-white">Cara Aktifkan Silent Print (Cetak Otomatis)</h3>
                            <p class="text-xs text-slate-400">Agar cetak langsung keluar tanpa pop-up dialog print browser</p>
                        </div>
                    </div>
                    <button 
                        @click="showGuideModal = false"
                        class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-4 my-6 text-xs text-slate-300">
                    <!-- Step 1 -->
                    <div class="flex gap-3 items-start bg-slate-950 p-3.5 rounded-2xl border border-white/5">
                        <div class="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 font-black flex items-center justify-center flex-shrink-0">1</div>
                        <div>
                            <p class="font-bold text-white">Setel Printer Foto sebagai "Default Printer" di Windows</p>
                            <p class="text-slate-400 mt-0.5">Buka Windows Settings > Bluetooth & Devices > Printers & Scanners > Pilih printer Anda (Epson/Canon/DNP) > Klik "Set as default".</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-3 items-start bg-slate-950 p-3.5 rounded-2xl border border-white/5">
                        <div class="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 font-black flex items-center justify-center flex-shrink-0">2</div>
                        <div>
                            <p class="font-bold text-white">Jalankan Chrome dengan Mode `--kiosk-printing`</p>
                            <p class="text-slate-400 mt-0.5">Tutup semua Chrome, lalu tekan <kbd class="px-1.5 py-0.5 bg-slate-800 rounded border border-white/20 text-amber-300">Win + R</kbd> di keyboard dan masukkan perintah ini:</p>
                            
                            <div class="mt-2 bg-slate-900 border border-white/10 p-2.5 rounded-xl flex items-center justify-between font-mono text-[11px] text-amber-300">
                                <span class="truncate mr-2">chrome.exe --kiosk-printing "{{ typeof window !== 'undefined' ? window.location.href : '' }}"</span>
                                <button 
                                    @click="copyRunCommand"
                                    class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-white flex items-center gap-1 text-[10px] font-sans font-bold flex-shrink-0"
                                >
                                    <Check v-if="copiedShortcut" class="w-3 h-3 text-emerald-400" />
                                    <Copy v-else class="w-3 h-3" />
                                    <span>{{ copiedShortcut ? 'Disalin!' : 'Salin' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-3 items-start bg-slate-950 p-3.5 rounded-2xl border border-white/5">
                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 font-black flex items-center justify-center flex-shrink-0">3</div>
                        <div>
                            <p class="font-bold text-white">Selesai! Tab Ini Siap Mencetak Secara Instan</p>
                            <p class="text-slate-400 mt-0.5">Biarkan tab Print Station ini tetap terbuka di PC printer. Semua foto yang dipicu dari Kiosk / Tablet akan langsung tercetak tanpa klik konfirmasi apapun!</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button 
                        @click="showGuideModal = false"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md"
                    >
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL PREVIEW FOTO -->
        <div 
            v-if="previewPhotoUrl" 
            @click="previewPhotoUrl = null"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md animate-fade-in cursor-pointer"
        >
            <div class="relative max-w-md w-full max-h-[85vh] rounded-2xl overflow-hidden border border-white/20 shadow-2xl" @click.stop>
                <img :src="previewPhotoUrl" class="w-full h-full object-contain bg-black" />
                <button 
                    @click="previewPhotoUrl = null"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/70 text-white flex items-center justify-center text-sm font-bold border border-white/20"
                >
                    ✕
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.25s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
</style>
