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
    ArrowLeft,
    Settings,
    CheckSquare
} from 'lucide-vue-next';
import axios from 'axios';
import { useAudioStore } from '@/stores/audioStore';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { showSuccess, showError, showInfo } from '@/utils/swal';

interface PrintJobItem {
    id: number;
    session_id?: string;
    session_code?: string;
    event_name?: string;
    booth_id?: string;
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
    printers?: any[];
    web_station_enabled: boolean;
    selected_booth?: string;
    available_booths?: string[];
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
const printersList = ref<any[]>(props.printers || []);
const showPrinterModal = ref(false);
const isSavingPrinter = ref(false);
const isSyncingPrinters = ref(false);
const chosenPrinterId = ref<number | null>(props.active_printer?.id || null);
const chosenPaperSize = ref<string>(props.active_paper_size || '4R');
const showGuideModal = ref(false);
const isTestingPrint = ref(false);
const previewPhotoUrl = ref<string | null>(null);
const copiedShortcut = ref(false);

const availablePaperSizes = [
    { value: '4R', label: '4R (10 x 15 cm / 4x6")' },
    { value: 'Strip 2x6', label: 'Strip 2x6 (5 x 15 cm / 2x6")' },
    { value: '5R', label: '5R (13 x 18 cm / 5x7")' },
    { value: '6R', label: '6R (15 x 20 cm / 6x8")' },
    { value: 'A4', label: 'A4 (21 x 29.7 cm)' },
];

// Booth identity state
const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const initialBooth = urlParams?.get('booth') || (urlParams?.get('stand') ? `STAND-0${urlParams.get('stand')}` : null) || props.selected_booth || (typeof window !== 'undefined' ? localStorage.getItem('print_station_booth_id') : null) || 'STAND-01';
const selectedBooth = ref(initialBooth);

const availableBoothsList = computed(() => {
    const list = ['STAND-01', 'STAND-02', 'STAND-03', 'STAND-04'];
    if (props.available_booths) {
        props.available_booths.forEach(b => {
            if (b && !list.includes(b)) list.push(b);
        });
    }
    return list;
});

function changeBooth(booth: string) {
    selectedBooth.value = booth;
    if (typeof window !== 'undefined') {
        localStorage.setItem('print_station_booth_id', booth);
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('booth', booth);
        window.history.replaceState({}, '', newUrl.toString());
    }
    fetchJobs();
}

function openPrinterModal() {
    chosenPrinterId.value = activePrinter.value?.id || (printersList.value.length > 0 ? printersList.value[0].id : null);
    chosenPaperSize.value = activePaperSize.value || '4R';
    showPrinterModal.value = true;
}

async function handleSavePrinter() {
    if (!chosenPrinterId.value) {
        showError('Pilih Printer', 'Silakan pilih salah satu printer dari daftar.');
        return;
    }
    isSavingPrinter.value = true;
    try {
        const res = await axios.post('/api/print-station/select-printer', {
            booth_id: selectedBooth.value,
            printer_id: chosenPrinterId.value,
            paper_size: chosenPaperSize.value,
        });
        if (res.data.success) {
            activePrinter.value = res.data.active_printer;
            activePaperSize.value = res.data.active_paper_size;
            showPrinterModal.value = false;
            showSuccess('Printer Disimpan', res.data.message || `Printer ${selectedBooth.value} berhasil diubah!`);
            await fetchJobs();
        } else {
            showError('Gagal Menyimpan', res.data.message);
        }
    } catch (e: any) {
        showError('Gagal Menyimpan Printer', e?.response?.data?.message || 'Terjadi kesalahan sistem.');
    } finally {
        isSavingPrinter.value = false;
    }
}

async function handleSyncPrinters() {
    isSyncingPrinters.value = true;
    try {
        const res = await axios.post('/api/print-station/sync-printers');
        if (res.data.success) {
            printersList.value = res.data.printers || [];
            showSuccess('Sinkronisasi Berhasil', res.data.message || 'Daftar printer sistem operasi berhasil diperbarui.');
        } else {
            showError('Sinkronisasi Gagal', res.data.message);
        }
    } catch (e: any) {
        showError('Gagal Sinkronisasi', e?.response?.data?.message || 'Gagal memindai printer sistem operasi.');
    } finally {
        isSyncingPrinters.value = false;
    }
}

const completedCount = ref(props.stats?.today_completed || 0);
const queueCount = computed(() => pendingJobs.value.length);

let pollTimer: any = null;

onMounted(() => {
    audioStore.initContext();
    if (props.active_printer) {
        chosenPrinterId.value = props.active_printer.id;
    }
    if (props.active_paper_size) {
        chosenPaperSize.value = props.active_paper_size;
    }
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
        const res = await axios.get('/api/print-station/jobs', {
            params: { booth: selectedBooth.value }
        });
        if (res.data.success) {
            pendingJobs.value = res.data.pending_jobs || [];
            recentJobs.value = res.data.recent_jobs || [];
            if (res.data.active_printer) {
                activePrinter.value = res.data.active_printer;
                if (!showPrinterModal.value) {
                    chosenPrinterId.value = res.data.active_printer.id;
                }
            }
            if (res.data.active_paper_size) {
                activePaperSize.value = res.data.active_paper_size;
                if (!showPrinterModal.value) {
                    chosenPaperSize.value = res.data.active_paper_size;
                }
            }
            if (res.data.printers) {
                printersList.value = res.data.printers;
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
            booth_id: selectedBooth.value !== 'all' ? selectedBooth.value : 'STAND-01',
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
    const url = new URL(window.location.origin + '/print-station');
    if (selectedBooth.value) {
        url.searchParams.set('booth', selectedBooth.value);
    }
    const cmd = `chrome.exe --kiosk-printing "${url.toString()}"`;
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
        <header class="bg-slate-900/90 border-b border-white/10 px-4 sm:px-6 py-3 sm:py-4 flex flex-wrap items-center justify-between gap-3 sm:gap-4 sticky top-0 z-40 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <button 
                    @click="router.visit('/controller')" 
                    class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition-all border border-white/10"
                    title="Kembali ke Controller"
                >
                    <ArrowLeft class="w-5 h-5" />
                </button>
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.25)] shrink-0">
                    <Printer class="w-5 h-5 sm:w-6 sm:h-6" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight text-white">WEB PRINT STATION</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                            :class="autoPrintEnabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-white/10'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="autoPrintEnabled ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'"></span>
                            <span class="hidden sm:inline">{{ autoPrintEnabled ? 'Real-Time Aktif' : 'Standby / Jeda' }}</span>
                            <span class="sm:hidden">{{ autoPrintEnabled ? 'ON' : 'OFF' }}</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 hidden sm:block">
                        Pencetakan otomatis background untuk Stand & Printer lokal
                    </p>
                </div>
            </div>

            <!-- STAND / BOOTH SELECTOR (DEDICATED PRINT PER STAND) -->
            <div class="flex items-center gap-2 bg-slate-950/90 border-2 border-amber-500/40 px-3 py-1.5 rounded-2xl shadow-lg">
                <span class="text-[11px] font-black text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Stand:
                </span>
                <select 
                    :value="selectedBooth"
                    @change="changeBooth(($event.target as HTMLSelectElement).value)"
                    class="bg-slate-900 text-amber-300 font-black text-xs px-2.5 py-1 rounded-xl border border-white/10 focus:outline-none focus:border-amber-400 cursor-pointer"
                >
                    <option 
                        v-for="b in availableBoothsList" 
                        :key="b" 
                        :value="b"
                        class="bg-slate-900 text-white font-bold"
                    >
                        {{ b }} (Stand Ini)
                    </option>
                    <option value="all" class="bg-slate-900 text-amber-400 font-bold">Semua Stand (Global)</option>
                </select>

                <!-- Quick Printer Button in Header -->
                <button
                    @click="openPrinterModal"
                    class="ml-1 px-2.5 py-1 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-all"
                    title="Pilih printer fisik untuk stand ini"
                >
                    <Printer class="w-3.5 h-3.5" />
                    <span class="hidden md:inline truncate max-w-[140px]">{{ activePrinter?.name || 'Pilih Printer' }}</span>
                </button>
            </div>

            <!-- RIGHT CONTROLS -->
            <div class="flex items-center gap-2 sm:gap-2.5">
                <!-- Theme Toggle -->
                <ThemeToggle />

                <!-- Sound Toggle -->
                <button
                    @click="soundEnabled = !soundEnabled"
                    class="px-2.5 sm:px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 sm:gap-2 border transition-all"
                    :class="soundEnabled ? 'bg-white/10 text-slate-200 border-white/20 hover:bg-white/15' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                    title="Toggle Efek Suara"
                >
                    <Volume2 v-if="soundEnabled" class="w-4 h-4 text-emerald-400" />
                    <VolumeX v-else class="w-4 h-4" />
                    <span class="hidden md:inline">{{ soundEnabled ? 'Audio Aktif' : 'Mute' }}</span>
                </button>

                <!-- Help Silent Print Guide -->
                <button
                    @click="showGuideModal = true"
                    class="px-2.5 sm:px-3.5 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm"
                >
                    <HelpCircle class="w-4 h-4" />
                    <span class="hidden md:inline">Panduan Silent Print</span>
                </button>

                <!-- Test Print Button -->
                <button
                    @click="handleTestPrint"
                    :disabled="isTestingPrint || isPrinting"
                    class="px-3 sm:px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs flex items-center gap-1.5 sm:gap-2 transition-all shadow-md active:scale-95 disabled:opacity-50"
                >
                    <Sparkles class="w-4 h-4" />
                    <span>{{ isTestingPrint ? 'Menyiapkan...' : 'Uji Cetak' }}</span>
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
                            <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 flex items-center justify-center">
                                <Printer class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Printer Stand</h2>
                                    <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 text-[10px] font-black font-mono border border-amber-500/30">
                                        {{ selectedBooth === 'all' ? 'SEMUA STAND' : selectedBooth }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400">Pencetakan mandiri untuk stand ini</p>
                            </div>
                        </div>

                        <!-- Auto-print Toggle Switch -->
                        <button
                            @click="handleToggleStation"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold flex items-center gap-2 border transition-all cursor-pointer"
                            :class="autoPrintEnabled ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-slate-800 text-slate-300 border-white/10'"
                        >
                            <Play v-if="autoPrintEnabled" class="w-3.5 h-3.5 fill-current" />
                            <span>{{ autoPrintEnabled ? 'AUTO ON' : 'PAUSED' }}</span>
                        </button>
                    </div>

                    <!-- PRINTER DISPLAY & QUICK SELECTOR -->
                    <div class="mt-4 bg-slate-950/70 p-4 rounded-2xl border border-white/5 flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Printer Terhubung:</span>
                                <span class="font-black text-white text-base truncate block mt-0.5" :title="activePrinter?.name">
                                    {{ activePrinter?.name || 'Windows Default Printer' }}
                                </span>
                                <div class="flex items-center gap-2 mt-1 flex-wrap text-xs">
                                    <span class="px-2 py-0.5 rounded bg-white/10 text-amber-300 font-bold text-[10px]">
                                        {{ activePrinter?.brand || 'Spooler' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">
                                        {{ activePrinter?.connection_type || 'USB' }}
                                    </span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-emerald-400 font-bold text-[11px] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        {{ activePrinter?.status || 'Siap' }}
                                    </span>
                                </div>
                            </div>

                            <button
                                @click="openPrinterModal"
                                class="px-3.5 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer flex-shrink-0 active:scale-95 shadow-xs"
                                title="Pilih printer fisik untuk stand ini"
                            >
                                <Settings class="w-3.5 h-3.5" />
                                <span>Ganti Printer</span>
                            </button>
                        </div>

                        <!-- PAPER SIZE & ACTIONS -->
                        <div class="pt-3 border-t border-white/5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400">Ukuran:</span>
                                <span class="font-black text-amber-300 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                                    {{ activePaperSize }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="handleSyncPrinters"
                                    :disabled="isSyncingPrinters"
                                    class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer disabled:opacity-50"
                                    title="Pindai ulang printer USB/Windows di PC ini"
                                >
                                    <RefreshCw class="w-3 h-3" :class="isSyncingPrinters ? 'animate-spin' : ''" />
                                    <span>{{ isSyncingPrinters ? 'Pindai...' : 'Pindai OS' }}</span>
                                </button>

                                <button
                                    @click="handleTestPrint"
                                    :disabled="isTestingPrint || isPrinting"
                                    class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-300 border border-amber-500/20 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer disabled:opacity-50"
                                    title="Uji cetak langsung ke printer stand ini"
                                >
                                    <Sparkles class="w-3 h-3" />
                                    <span>Uji Cetak</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STAND TARGET BANNER -->
                    <div class="mt-4 p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span class="text-slate-300">Menangani Antrean:</span>
                            <span class="font-black text-amber-400">{{ selectedBooth === 'all' ? 'SEMUA STAND (GLOBAL)' : selectedBooth }}</span>
                        </div>
                        <span class="text-[10px] text-amber-300/80 bg-amber-500/10 px-2 py-0.5 rounded-lg font-mono">1 Stand = 1 Printer</span>
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
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white text-xs block truncate">{{ job.session_code || 'JOB #' + job.id }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono text-[10px] font-bold border border-amber-500/30">{{ job.booth_id || 'STAND-01' }}</span>
                                    </div>
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
                                        <span class="px-1.5 py-0.5 rounded bg-white/10 text-amber-300 font-mono text-[9px] font-bold border border-white/10">{{ job.booth_id || 'STAND-01' }}</span>
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

        <!-- ========================================================================= -->
        <!-- MODAL PILIH PRINTER UNTUK STAND                                          -->
        <!-- ========================================================================= -->
        <div 
            v-if="showPrinterModal" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in"
        >
            <div class="bg-slate-900 border border-white/15 rounded-3xl p-6 max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-600 text-slate-950 flex items-center justify-center shadow-lg font-black">
                            <Printer class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white">Pilih Printer Stand</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-xs font-black font-mono border border-amber-500/30">
                                    {{ selectedBooth === 'all' ? 'Semua Stand' : selectedBooth }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">Tentukan printer fisik dan ukuran kertas untuk stand ini</p>
                        </div>
                    </div>
                    <button 
                        @click="showPrinterModal = false"
                        class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-colors cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                    <!-- Informative Alert -->
                    <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-200/90 leading-relaxed flex items-start gap-2.5">
                        <ShieldCheck class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" />
                        <span>
                            Setiap stand foto booth memiliki printer fisiknya sendiri. Sesi foto yang dibuat pada stand <strong>{{ selectedBooth }}</strong> akan otomatis dicetak menggunakan printer yang Anda pilih di bawah.
                        </span>
                    </div>

                    <!-- Printers List -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-black text-slate-300 uppercase tracking-wider">
                                Daftar Printer Terdeteksi ({{ printersList.length }})
                            </label>
                            <button
                                @click="handleSyncPrinters"
                                :disabled="isSyncingPrinters"
                                class="text-xs font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1 cursor-pointer transition-colors"
                            >
                                <RefreshCw class="w-3 h-3" :class="isSyncingPrinters ? 'animate-spin' : ''" />
                                <span>Pindai Printer Windows / USB</span>
                            </button>
                        </div>

                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <div
                                v-for="p in printersList"
                                :key="p.id"
                                @click="chosenPrinterId = p.id; if (p.default_paper_size && !chosenPaperSize) chosenPaperSize = p.default_paper_size;"
                                class="p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-center justify-between group"
                                :class="chosenPrinterId === p.id 
                                    ? 'bg-amber-500/10 border-amber-500 shadow-md ring-2 ring-amber-500/20' 
                                    : 'bg-slate-950/60 border-white/5 hover:border-white/20 hover:bg-slate-950'"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
                                        :class="chosenPrinterId === p.id ? 'bg-amber-500 text-slate-950 font-black' : 'bg-white/5 text-slate-400 group-hover:text-white'"
                                    >
                                        <Printer class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-black truncate text-white" :class="chosenPrinterId === p.id ? 'text-amber-300' : ''">
                                            {{ p.name }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                                            <span>{{ p.brand || 'Windows' }}</span>
                                            <span>•</span>
                                            <span>{{ p.connection_type || 'USB' }}</span>
                                            <span v-if="p.default_paper_size" class="text-amber-400/90 font-mono">({{ p.default_paper_size }})</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <div 
                                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                        :class="chosenPrinterId === p.id ? 'border-amber-400 bg-amber-400 text-slate-950' : 'border-white/20'"
                                    >
                                        <Check v-if="chosenPrinterId === p.id" class="w-3 h-3 stroke-[3]" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="printersList.length === 0" class="text-center py-6 text-slate-400 text-xs bg-slate-950 rounded-2xl border border-white/5">
                                Tidak ada printer terdaftar. Klik "Pindai Printer Windows / USB" di atas untuk mencari printer terpasang.
                            </div>
                        </div>
                    </div>

                    <!-- Paper Size Selector -->
                    <div>
                        <label class="text-xs font-black text-slate-300 uppercase tracking-wider block mb-2">
                            Ukuran Kertas Stand Ini
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="size in availablePaperSizes"
                                :key="size.value"
                                type="button"
                                @click="chosenPaperSize = size.value"
                                class="p-2.5 rounded-xl border text-left transition-all cursor-pointer"
                                :class="chosenPaperSize === size.value 
                                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 font-black' 
                                    : 'bg-slate-950/60 border-white/5 hover:border-white/20 text-slate-300 font-medium'"
                            >
                                <div class="text-xs font-bold">{{ size.value }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5 truncate">{{ size.label }}</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-white/10 flex items-center justify-between gap-3">
                    <button 
                        @click="showPrinterModal = false"
                        class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white text-xs font-bold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="handleSavePrinter"
                        :disabled="isSavingPrinter || !chosenPrinterId"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs transition-all shadow-md active:scale-95 disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                    >
                        <Check v-if="!isSavingPrinter" class="w-4 h-4 stroke-[2.5]" />
                        <span>{{ isSavingPrinter ? 'Menyimpan...' : 'Simpan & Terapkan untuk ' + (selectedBooth === 'all' ? 'Semua Stand' : selectedBooth) }}</span>
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
