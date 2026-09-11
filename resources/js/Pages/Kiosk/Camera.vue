<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import LiveTemplateCanvas from '@/Components/LiveTemplateCanvas.vue';
import PrintModal from '@/Components/PrintModal.vue';
import PaymentModal from '@/Components/PaymentModal.vue';
import type { BoothSession, Template, SessionPhoto, TemplateElement } from '@/types';
import { 
    Camera, 
    RotateCcw, 
    Check, 
    Printer, 
    QrCode, 
    Sparkles, 
    Layers, 
    Plus, 
    Minus,
    FlipHorizontal,
    Grid,
    ChevronLeft,
    ArrowRight,
    Palette,
    CheckCircle2,
    Eye
} from 'lucide-vue-next';
import { useAudioStore } from '@/stores/audioStore';
import { useSessionStore } from '@/stores/sessionStore';
import { getAssetUrl } from '@/utils/url';
import axios from 'axios';
import { showSuccess, showError, showConfirm, showToast } from '@/utils/swal';

const props = defineProps<{
    session: BoothSession;
    template: Template;
    templates?: Template[];
    active_printer?: any;
    active_paper_size?: string;
}>();

const audioStore = useAudioStore();
const sessionStore = useSessionStore();

// Local Reactive States
const currentSession = ref<BoothSession>(props.session);
const currentTemplate = ref<Template>(props.template);
const activePrinter = ref<any>(props.active_printer || null);
const activePaperSize = ref<string>(props.active_paper_size || props.active_printer?.default_paper_size || '4R');

// Step Navigation: 1 (Bentuk) | 2 (Jumlah Foto) | 3 (Template Admin) | 4 (Jepret) | 5 (Cetak)
const currentStep = ref<1 | 2 | 3 | 4 | 5>(1);

onMounted(async () => {
    try {
        const res = await axios.get('/api/devices/settings');
        if (res.data?.active_printer) {
            activePrinter.value = res.data.active_printer;
        }
        if (res.data?.active_paper_size) {
            activePaperSize.value = res.data.active_paper_size;
        } else if (res.data?.active_printer?.default_paper_size) {
            activePaperSize.value = res.data.active_printer.default_paper_size;
        }
    } catch (e) {}
});

// Format Foto: 'strip' (Setengah 4R / 2x6") vs 'full' (Kertas 4R Utuh / 4x6")
const selectedFormat = ref<'strip' | 'full'>('strip');

// Jumlah Foto Terpilih di Langkah 2
const selectedPhotoCount = ref<number>(3);

// Stage pemotretan di dalam Langkah 4
const captureStage = ref<'ready' | 'countdown' | 'capturing'>('ready');
const isComposing = ref(false);

// Timer Hitungan Mundur: 3s, 5s, 10s
const timerDuration = ref<number>(3);
const countdown = ref(3);
const isCountingDown = ref(false);

const currentSlotIndex = ref(1);
const totalSlots = computed(() => currentTemplate.value?.photo_count || selectedPhotoCount.value || 3);
const templateWidth = computed(() => currentTemplate.value?.width || 1200);
const templateHeight = computed(() => currentTemplate.value?.height || 1800);
const finalPhotoTimestamp = ref(Date.now());

// Map foto per slot untuk live template canvas: { 1: url, 2: url, ... }
const capturedPhotosMap = ref<Record<number, string>>({});
const isFlashingSlot = ref<number | null>(null);
const liveCanvasRef = ref<any>(null);
const mirrorMode = ref(true);

// Printing & Payment Modals
const showPrintModal = ref(false);
const printProgress = ref(0);
const isPrinting = ref(false);
const isPrintComplete = ref(false);
const printCopies = ref(1);
const showPaymentModal = ref(false);

// Helper: cek apakah template adalah format Strip (2x6" / 600x1800 px)
function isTemplateStrip(t?: Template | null): boolean {
    if (!t) return false;
    return t.paper_size === 'Strip 2x6' || t.width === 600 || t.frame_style === 'strip';
}

// -----------------------------------------------------------------------------
// FILTER TEMPLATE AKTIF DARI DATABASE (Hanya yang is_active = true)
// -----------------------------------------------------------------------------
const allActiveTemplates = computed(() => {
    return (props.templates || []).filter(t => Boolean(t.is_active));
});

// Template aktif khusus format Strip
const activeStripTemplates = computed(() => {
    return allActiveTemplates.value.filter(t => isTemplateStrip(t));
});

// Template aktif khusus format Full 4R
const activeFullTemplates = computed(() => {
    return allActiveTemplates.value.filter(t => !isTemplateStrip(t));
});

// Apakah bentuk Strip / Full memiliki template aktif
const hasActiveStrip = computed(() => activeStripTemplates.value.length > 0);
const hasActiveFull = computed(() => activeFullTemplates.value.length > 0);
const totalActiveTemplatesCount = computed(() => allActiveTemplates.value.length);

// Ringkasan pilihan jumlah foto yang ada template aktifnya
const activeStripCountsText = computed(() => {
    const counts = Array.from(new Set(activeStripTemplates.value.map(t => Number(t.photo_count) || 3))).sort((a, b) => a - b);
    return counts.length > 0 ? `${counts.join(', ')} Foto` : 'Tidak tersedia';
});

const activeFullCountsText = computed(() => {
    const counts = Array.from(new Set(activeFullTemplates.value.map(t => Number(t.photo_count) || 3))).sort((a, b) => a - b);
    return counts.length > 0 ? `${counts.join(', ')} Foto` : 'Tidak tersedia';
});

// Pilihan jumlah foto yang HANYA memiliki template aktif (jika tidak ada maka sembunyikan)
const availableCountOptions = computed(() => {
    const activeMatchingFormat = selectedFormat.value === 'strip' 
        ? activeStripTemplates.value 
        : activeFullTemplates.value;

    if (activeMatchingFormat.length === 0) return [];

    // Ambil semua photo_count unik yang benar-benar ada di template aktif
    const uniqueCounts = Array.from(new Set(activeMatchingFormat.map(t => Number(t.photo_count) || 3))).sort((a, b) => a - b);

    const descriptions: Record<number, { desc: string; badge?: string }> = {
        1: { desc: 'Single Portrait Studio foto tunggal', badge: undefined },
        2: { desc: selectedFormat.value === 'strip' ? '2 pose besar & leluasa' : 'Duet Atas-Bawah seimbang', badge: undefined },
        3: { desc: 'Strip klasik paling populer', badge: 'Favorit' },
        4: { desc: selectedFormat.value === 'strip' ? 'Format 4 pose estetik Life4Cuts' : 'Grid 2x2 seimbang & proporsional', badge: 'Favorit' },
        6: { desc: '6 momen seru rame-rame', badge: undefined },
    };

    return uniqueCounts
        .map(count => {
            const matchingTemplates = activeMatchingFormat.filter(t => (Number(t.photo_count) || 3) === count);
            const meta = descriptions[count] || { desc: `${count} pose dalam satu lembar`, badge: undefined };
            return {
                count,
                name: `${count} Foto`,
                description: meta.desc,
                badge: meta.badge,
                templateCount: matchingTemplates.length,
            };
        })
        .filter(opt => opt.templateCount > 0); // SEMBUNYIKAN JIKA TIDAK ADA YANG AKTIF!
});

// Template buatan admin yang aktif sesuai bentuk & jumlah foto terpilih
const activeAdminTemplates = computed(() => {
    return allActiveTemplates.value.filter(t => {
        // 1. Cocokkan bentuk (Strip vs Full)
        const isStrip = isTemplateStrip(t);
        if (selectedFormat.value === 'strip' && !isStrip) return false;
        if (selectedFormat.value === 'full' && isStrip) return false;

        // 2. Cocokkan jumlah foto
        if (selectedPhotoCount.value && (Number(t.photo_count) || 3) !== Number(selectedPhotoCount.value)) {
            return false;
        }

        return true;
    });
});

function getNextAvailableSlot(): number {
    for (let i = 1; i <= totalSlots.value; i++) {
        if (!capturedPhotosMap.value[i]) return i;
    }
    return 1;
}

onMounted(() => {
    // Inisialisasi format awal berdasarkan bentuk yang memiliki template aktif
    if (hasActiveStrip.value && !hasActiveFull.value) {
        selectedFormat.value = 'strip';
    } else if (hasActiveFull.value && !hasActiveStrip.value) {
        selectedFormat.value = 'full';
    } else if (props.template && isTemplateStrip(props.template)) {
        selectedFormat.value = hasActiveStrip.value ? 'strip' : 'full';
    } else {
        selectedFormat.value = hasActiveFull.value ? 'full' : (hasActiveStrip.value ? 'strip' : 'full');
    }

    // Inisialisasi jumlah foto awal yang benar-benar aktif
    if (availableCountOptions.value.length > 0) {
        const hasMatching = availableCountOptions.value.some(o => o.count === selectedPhotoCount.value);
        if (!hasMatching) {
            selectedPhotoCount.value = availableCountOptions.value[0].count;
        }
    } else if (props.template?.photo_count) {
        selectedPhotoCount.value = props.template.photo_count;
    }

    // Inisialisasi foto yang sudah ada dari sesi
    const photos = currentSession.value.photos || [];
    photos.forEach((p: SessionPhoto) => {
        if (p.slot_index && p.is_accepted) {
            capturedPhotosMap.value[Number(p.slot_index)] = p.thumbnail_path || p.original_path;
        }
    });

    // Menentukan langkah awal berdasarkan riwayat sesi
    if (currentSession.value.final_photo_path) {
        currentStep.value = 5; // Cetak
    } else if (Object.keys(capturedPhotosMap.value).length >= totalSlots.value && totalSlots.value > 0) {
        // Foto sudah lengkap -> langsung render komposit dan ke Langkah 5 (Cetak)
        handleProceedToCompose();
    } else if (Object.keys(capturedPhotosMap.value).length > 0) {
        currentStep.value = 4; // Lanjut jepret
        captureStage.value = 'ready';
        currentSlotIndex.value = getNextAvailableSlot();
    } else {
        currentStep.value = 1; // Mulai dari Langkah 1: Bentuk
    }
});

// =============================================================================
// LANGKAH 1: BENTUK (Strip vs Full)
// =============================================================================
function handleSelectFormat(format: 'strip' | 'full') {
    selectedFormat.value = format;
    
    // Auto-select jumlah foto pertama yang aktif untuk bentuk ini
    const activeMatchingFormat = format === 'strip' ? activeStripTemplates.value : activeFullTemplates.value;
    const availableCounts = Array.from(new Set(activeMatchingFormat.map(t => Number(t.photo_count) || 3))).sort((a, b) => a - b);
    if (availableCounts.length > 0) {
        selectedPhotoCount.value = availableCounts[0];
    } else {
        selectedPhotoCount.value = format === 'strip' ? 3 : 4;
    }

    currentStep.value = 2; // Lanjut ke Langkah 2: Jumlah Foto
    audioStore.playBeep(880, 0.08, 'sine');
    audioStore.speakInstruction(
        format === 'strip'
            ? 'Format strip setengah 4R dipilih. Silakan pilih jumlah foto.'
            : 'Format full 4R utuh dipilih. Silakan pilih jumlah foto.'
    );
}

// =============================================================================
// LANGKAH 2: JUMLAH FOTO
// =============================================================================
function handleSelectCount(count: number) {
    selectedPhotoCount.value = count;
    currentStep.value = 3; // Lanjut ke Langkah 3: Pilih Template Admin
    audioStore.playBeep(880, 0.08, 'sine');
    audioStore.speakInstruction(`Pilihan ${count} foto. Silakan pilih desain template yang Anda sukai.`);
}

// =============================================================================
// LANGKAH 3: TEMPLATE (Dibuat oleh Admin Lengkap dengan Desain, Hanya Aktif)
// =============================================================================
async function handleSelectTemplate(tpl: Template) {
    currentTemplate.value = tpl;

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/select-template`, {
            template_id: tpl.id,
        });
        if (res.data.success) {
            currentSession.value = res.data.session;
        }
    } catch (e) {
        console.error('Select template error', e);
    }

    // Reset foto untuk template baru & maju ke Langkah 4: Jepret
    capturedPhotosMap.value = {};
    currentSlotIndex.value = 1;
    captureStage.value = 'ready';
    currentStep.value = 4; // Lanjut ke Langkah 4: Jepret Foto
    audioStore.playSuccess();
    audioStore.speakInstruction(`Template ${tpl.name} siap digunakan. Bersiap untuk foto pertama!`);
}

// =============================================================================
// LANGKAH 4: JEPRET (Secara Live Masuk ke Posisi Foto pada Template)
// =============================================================================
async function startCapture(slot?: number) {
    if (isCountingDown.value) return;

    const targetSlot = slot ?? currentSlotIndex.value;
    currentSlotIndex.value = targetSlot;
    isCountingDown.value = true;
    countdown.value = timerDuration.value;
    captureStage.value = 'countdown';

    audioStore.speakInstruction(`Foto ke-${targetSlot}. Siapkan pose terbaik Anda!`);

    const interval = setInterval(async () => {
        countdown.value -= 1;

        if (countdown.value > 0) {
            audioStore.playCountdown(countdown.value);
        } else if (countdown.value === 0) {
            clearInterval(interval);
            isCountingDown.value = false;
            captureStage.value = 'capturing';

            audioStore.playSmile();

            // Flash & snap shutter
            setTimeout(async () => {
                audioStore.playShutter();
                await executeCameraCapture(targetSlot);
            }, 500);
        }
    }, 1000);
}

// EXECUTE CAPTURE & AUTO ADVANCE KE SLOT BERIKUTNYA
async function executeCameraCapture(slot: number) {
    try {
        let imageData: string | null = null;
        if (liveCanvasRef.value?.captureActiveSlot) {
            imageData = liveCanvasRef.value.captureActiveSlot(slot);
        }

        if (imageData) {
            capturedPhotosMap.value[slot] = imageData;
            isFlashingSlot.value = slot;
            setTimeout(() => {
                isFlashingSlot.value = null;
            }, 350);
        }

        const payload: Record<string, any> = {
            slot_index: slot,
        };
        if (imageData) {
            payload.image_data = imageData;
        }

        const res = await axios.post(`/api/session/${currentSession.value.id}/capture`, payload);

        if (res.data.success) {
            currentSession.value = res.data.session;
            if (res.data.photo) {
                capturedPhotosMap.value[slot] = imageData || res.data.photo.thumbnail_path || res.data.photo.original_path;
            }
            audioStore.playSuccess();

            // Cek apakah semua slot sudah terisi lengkap
            if (res.data.is_complete || Object.keys(capturedPhotosMap.value).length >= totalSlots.value) {
                captureStage.value = 'ready';
                audioStore.speakInstruction('Luar biasa! Semua foto selesai diambil. Memproses hasil cetak foto Anda.');
                // Otomatis susun template 300 DPI dan maju ke Langkah 5: Cetak!
                setTimeout(() => {
                    handleProceedToCompose();
                }, 800);
            } else {
                // Jeda 1.8 detik lalu lanjut foto slot berikutnya
                const nextSlot = getNextAvailableSlot();
                currentSlotIndex.value = nextSlot;
                captureStage.value = 'ready';
                audioStore.playBeep(784, 0.1, 'sine');
                audioStore.speakInstruction(`Bagus! Bersiap untuk foto ke-${nextSlot}.`);

                setTimeout(() => {
                    startCapture(nextSlot);
                }, 1800);
            }
        }
    } catch (err) {
        console.error('Capture error', err);
        showError('Gagal Mengambil Foto', 'Kamera tidak merespon. Silakan periksa atau coba lagi.');
        captureStage.value = 'ready';
    }
}

// Retake foto slot tertentu
function handleRetake(slotIndex: number) {
    const s = Number(slotIndex);
    delete capturedPhotosMap.value[s];
    currentSlotIndex.value = s;
    currentStep.value = 4;
    captureStage.value = 'ready';
    audioStore.speakInstruction(`Mengambil ulang foto ke-${s}. Siapkan pose Anda!`);
    setTimeout(() => {
        startCapture(s);
    }, 600);
}

// Ulangi semua foto
async function handleRestartAll() {
    const confirmed = await showConfirm(
        'Ulangi Sesi Foto?',
        'Semua foto yang telah diambil pada sesi ini akan diulang dari awal.',
        'Ya, Ulangi Foto',
        'Batal'
    );
    if (confirmed) {
        capturedPhotosMap.value = {};
        currentSlotIndex.value = 1;
        currentStep.value = 4;
        captureStage.value = 'ready';
        startCapture(1);
    }
}

// =============================================================================
// LANGKAH 5: CETAK (COMPOSE 300 DPI & PRINT - TANPA PEMILIHAN BACKGROUND)
// =============================================================================
async function handleProceedToCompose() {
    isComposing.value = true;
    currentStep.value = 5; // Maju ke Langkah 5: Cetak
    audioStore.speakInstruction('Sedang menyusun template beresolusi tinggi 300 DPI siap cetak.');

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/compose`);
        if (res.data.success) {
            currentSession.value = res.data.session || {
                ...currentSession.value,
                final_photo_path: res.data.file_path,
                final_thumbnail_path: res.data.thumbnail_path,
            };
            finalPhotoTimestamp.value = Date.now();
            audioStore.playSuccess();
        }
    } catch (err) {
        showError('Gagal Menyusun Foto', 'Terjadi kesalahan saat menyusun resolusi tinggi 300 DPI. Silakan coba kembali.');
    } finally {
        isComposing.value = false;
    }
}

async function triggerPrint() {
    if (currentSession.value.payment_status === 'unpaid' && (currentSession.value.event?.default_price || 0) > 0) {
        showPaymentModal.value = true;
        return;
    }
    await executePrint();
}

function handlePaymentSuccess(data?: any) {
    showPaymentModal.value = false;
    currentSession.value.payment_status = 'paid';
    if (data?.session) {
        currentSession.value = { ...currentSession.value, ...data.session, payment_status: 'paid' };
    }
    executePrint();
}

async function executePrint() {
    showPrintModal.value = true;
    isPrinting.value = true;
    isPrintComplete.value = false;
    printProgress.value = 15;

    const progInterval = setInterval(() => {
        if (printProgress.value < 90) {
            printProgress.value += 15;
        }
    }, 450);

    try {
        const res = await axios.post(`/api/session/${currentSession.value.id}/print`, {
            copies: printCopies.value,
            paper_size: activePaperSize.value,
        });

        clearInterval(progInterval);

        if (res.data.success) {
            printProgress.value = 100;
            isPrinting.value = false;
            isPrintComplete.value = true;
            audioStore.playPrintDone();
            audioStore.speakInstruction('Pencetakan berhasil dikirim ke print station! Silakan ambil foto Anda di tray printer.');
            setTimeout(() => {
                handleDoneSession();
            }, 1600);
        } else {
            showPrintModal.value = false;
            isPrinting.value = false;
            showError('Printer Bermasalah', res.data.message || 'Gagal mengirim dokumen ke printer.');
        }
    } catch (err: any) {
        clearInterval(progInterval);
        showPrintModal.value = false;
        isPrinting.value = false;
        showError('Gagal Mencetak', err.response?.data?.message || 'Koneksi printer fisik terputus atau tidak terdeteksi.');
    }
}

function handleDoneSession() {
    showPrintModal.value = false;
    router.visit(`/session/${currentSession.value.id}/success`);
}

// Helper untuk visual mini preview template di Langkah 3
function getTemplateBackgroundStyle(tpl: Template) {
    if (tpl.background_image) {
        return `url(${getAssetUrl(tpl.background_image)}) center / cover no-repeat`;
    }
    const nameOrSlug = `${tpl.slug || ''} ${tpl.name || ''}`.toLowerCase();
    if (nameOrSlug.includes('pink') || nameOrSlug.includes('beautyplus')) {
        return 'repeating-linear-gradient(90deg, #ffcde2, #ffcde2 8px, #ffffff 8px, #ffffff 16px)';
    }
    if (nameOrSlug.includes('lavender') || nameOrSlug.includes('purple')) {
        return 'repeating-linear-gradient(90deg, #f3e8ff, #f3e8ff 8px, #ffffff 8px, #ffffff 16px)';
    }
    if (nameOrSlug.includes('mint')) {
        return 'repeating-linear-gradient(90deg, #dcfce7, #dcfce7 8px, #ffffff 8px, #ffffff 16px)';
    }
    return tpl.background_color || '#ffffff';
}

function getPhotoSlots(tpl: Template): TemplateElement[] {
    if (tpl.elements && tpl.elements.length > 0) {
        return tpl.elements.filter(e => e.type === 'photo_slot');
    }
    // Fallback slots jika elements belum di-load
    const count = tpl.photo_count || 3;
    const slots: TemplateElement[] = [];
    for (let i = 1; i <= count; i++) {
        slots.push({
            id: i,
            template_id: tpl.id,
            type: 'photo_slot',
            slot_index: i,
            x: 8,
            y: 5 + (i - 1) * (85 / count),
            width: 84,
            height: Math.floor(75 / count),
            border_radius: 6,
        } as TemplateElement);
    }
    return slots;
}
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col overflow-hidden bg-[#fafafa]">
            <!-- ========================================================================= -->
            <!-- GLOBAL TOP STEP INDICATOR BAR                                             -->
            <!-- ========================================================================= -->
            <div class="w-full bg-white border-b border-slate-200 px-4 py-2.5 z-30 flex items-center justify-between shadow-xs">
                <!-- Left: Logo / Brand -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-pink-500 to-rose-400 flex items-center justify-center text-white font-black text-xs shadow-xs">
                        📸
                    </div>
                    <span class="font-black text-sm text-slate-800 tracking-tight hidden sm:inline">PHOTOBOOTH PRO</span>
                </div>

                <!-- Center: Steps Progression (1: Bentuk -> 2: Jumlah -> 3: Template -> 4: Jepret -> 5: Cetak) -->
                <div class="flex items-center gap-1 sm:gap-2.5 text-xs font-bold">
                    <!-- Step 1: Bentuk -->
                    <button 
                        @click="currentStep > 1 && (currentStep = 1)"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full transition-all"
                        :class="currentStep === 1 
                            ? 'bg-pink-500 text-white shadow-xs' 
                            : (currentStep > 1 ? 'bg-pink-50 text-pink-600 hover:bg-pink-100 cursor-pointer' : 'text-slate-400')"
                    >
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 1 ? 'bg-white text-pink-600 font-black' : 'bg-pink-200 text-pink-700'">1</span>
                        <span class="hidden md:inline">Bentuk</span>
                    </button>

                    <span class="text-slate-300">›</span>

                    <!-- Step 2: Jumlah Foto -->
                    <button 
                        @click="currentStep > 2 && (currentStep = 2)"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full transition-all"
                        :class="currentStep === 2 
                            ? 'bg-pink-500 text-white shadow-xs' 
                            : (currentStep > 2 ? 'bg-pink-50 text-pink-600 hover:bg-pink-100 cursor-pointer' : 'text-slate-400')"
                    >
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 2 ? 'bg-white text-pink-600 font-black' : 'bg-pink-200 text-pink-700'">2</span>
                        <span class="hidden md:inline">Jumlah Foto</span>
                    </button>

                    <span class="text-slate-300">›</span>

                    <!-- Step 3: Template -->
                    <button 
                        @click="currentStep > 3 && (currentStep = 3)"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full transition-all"
                        :class="currentStep === 3 
                            ? 'bg-pink-500 text-white shadow-xs' 
                            : (currentStep > 3 ? 'bg-pink-50 text-pink-600 hover:bg-pink-100 cursor-pointer' : 'text-slate-400')"
                    >
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 3 ? 'bg-white text-pink-600 font-black' : 'bg-pink-200 text-pink-700'">3</span>
                        <span class="hidden md:inline">Template</span>
                    </button>

                    <span class="text-slate-300">›</span>

                    <!-- Step 4: Jepret Foto -->
                    <div 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full transition-all"
                        :class="currentStep === 4 
                            ? 'bg-pink-500 text-white shadow-xs' 
                            : (currentStep > 4 ? 'bg-pink-50 text-pink-600' : 'text-slate-400')"
                    >
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 4 ? 'bg-white text-pink-600 font-black' : 'bg-pink-200 text-pink-700'">4</span>
                        <span class="hidden md:inline">Jepret</span>
                    </div>

                    <span class="text-slate-300">›</span>

                    <!-- Step 5: Cetak -->
                    <div 
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full transition-all"
                        :class="currentStep === 5 
                            ? 'bg-pink-500 text-white shadow-xs' 
                            : 'text-slate-400'"
                    >
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 5 ? 'bg-white text-pink-600 font-black' : 'bg-slate-200 text-slate-600'">5</span>
                        <span class="hidden md:inline">Cetak</span>
                    </div>
                </div>

                <!-- Right: Session Code Badge -->
                <div class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold font-mono">
                    {{ currentSession.session_code }}
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- LANGKAH 1: BENTUK (STRIP vs FULL)                                         -->
            <!-- ========================================================================= -->
            <div 
                v-if="currentStep === 1"
                class="flex-1 w-full h-full flex flex-col items-center justify-center p-6 md:p-10 max-w-5xl mx-auto overflow-y-auto"
            >
                <div class="text-center mb-8">
                    <span class="px-3.5 py-1 rounded-full bg-pink-100 text-pink-600 text-xs font-black uppercase tracking-wider">
                        Langkah 1 dari 5
                    </span>
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 mt-2">
                        PILIH BENTUK FOTO
                    </h2>
                    <p class="text-sm md:text-base text-slate-500 mt-1 max-w-md mx-auto">
                        Pilih gaya cetak foto yang Anda inginkan
                    </p>
                </div>

                <!-- KONDISI JIKA TIDAK ADA TEMPLATE AKTIF SAMA SEKALI -->
                <div 
                    v-if="totalActiveTemplatesCount === 0"
                    class="my-auto text-center py-12 px-8 max-w-md mx-auto bg-white rounded-3xl border-2 border-dashed border-slate-200 shadow-xl"
                >
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto mb-4 border border-amber-500/20">
                        <Layers class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Belum Ada Template Aktif</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Semua template foto booth saat ini sedang dinonaktifkan. Silakan aktifkan minimal satu template melalui Panel Admin untuk memulai sesi.
                    </p>
                    <div class="mt-6 flex flex-col gap-2.5">
                        <button
                            @click="router.visit('/')"
                            class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-all cursor-pointer"
                        >
                            Kembali ke Layar Utama
                        </button>
                        <a
                            href="/admin/templates"
                            class="w-full py-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 font-bold text-xs border border-amber-500/30 transition-all text-center"
                        >
                            Buka Kelola Template di Admin
                        </a>
                    </div>
                </div>

                <!-- CARDS BENTUK (HANYA TAMPILKAN BENTUK YANG MEMILIKI TEMPLATE AKTIF) -->
                <div 
                    v-else
                    :class="hasActiveStrip && hasActiveFull ? 'grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 w-full max-w-3xl' : 'flex justify-center w-full max-w-md mx-auto'"
                >
                    <!-- CARD A: PHOTO STRIP (Setengah Kertas 4R) - HANYA JIKA ADA TEMPLATE STRIP AKTIF -->
                    <button
                        v-if="hasActiveStrip"
                        @click="handleSelectFormat('strip')"
                        class="group relative bg-white rounded-3xl p-6 md:p-8 border-2 text-left transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl flex flex-col justify-between cursor-pointer w-full"
                        :class="selectedFormat === 'strip' 
                            ? 'border-pink-500 ring-4 ring-pink-500/20 shadow-xl' 
                            : 'border-slate-200 hover:border-pink-300 shadow-md'"
                    >
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 text-pink-600 text-xs font-black">
                                <span>✨</span>
                                <span>POPULER • BEAUTYPLUS</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-pink-500/10 text-pink-600 text-[11px] font-black border border-pink-500/20">
                                {{ activeStripTemplates.length }} Desain Aktif
                            </span>
                        </div>

                        <!-- Miniature Strip Preview Visual -->
                        <div class="w-full h-44 bg-slate-50 rounded-2xl p-3 flex items-center justify-center mb-5 border border-slate-100 group-hover:bg-pink-50/40 transition-colors">
                            <div class="h-full w-24 bg-white rounded-xl shadow-md border border-slate-200 p-1.5 flex flex-col justify-between">
                                <div class="w-full h-[26%] bg-[#ffcde2] rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-[26%] bg-[#ffcde2] rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-[26%] bg-[#ffcde2] rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-2 bg-slate-200 rounded-sm"></div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-2xl font-black text-slate-900 group-hover:text-pink-600 transition-colors">
                                Photo Strip
                            </h3>
                            <p class="text-xs font-bold text-pink-500 mt-0.5">
                                Setengah Kertas 4R (2x6 Inci / 600x1800 px)
                            </p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Format strip memanjang vertikal yang ramping. Dicetak 2 lembar berdampingan, pas untuk casing HP atau dibagi bersama teman.
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">Pilihan: {{ activeStripCountsText }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-pink-500 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                <ArrowRight class="w-5 h-5 stroke-[2.5]" />
                            </div>
                        </div>
                    </button>

                    <!-- CARD B: FULL PHOTO (Kertas 4R Utuh) - HANYA JIKA ADA TEMPLATE FULL AKTIF -->
                    <button
                        v-if="hasActiveFull"
                        @click="handleSelectFormat('full')"
                        class="group relative bg-white rounded-3xl p-6 md:p-8 border-2 text-left transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl flex flex-col justify-between cursor-pointer w-full"
                        :class="selectedFormat === 'full' 
                            ? 'border-pink-500 ring-4 ring-pink-500/20 shadow-xl' 
                            : 'border-slate-200 hover:border-pink-300 shadow-md'"
                    >
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-black">
                                <span>⭐</span>
                                <span>KLASIK STUDIO • LEGA & LUAS</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-700 text-[11px] font-black border border-amber-500/20">
                                {{ activeFullTemplates.length }} Desain Aktif
                            </span>
                        </div>

                        <!-- Miniature Full 4R Preview Visual -->
                        <div class="w-full h-44 bg-slate-50 rounded-2xl p-3 flex items-center justify-center mb-5 border border-slate-100 group-hover:bg-amber-50/30 transition-colors">
                            <div class="h-full w-32 bg-white rounded-xl shadow-md border border-slate-200 p-2 grid grid-cols-2 gap-1.5">
                                <div class="w-full h-full bg-slate-200 rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-full bg-slate-200 rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-full bg-slate-200 rounded-md flex items-center justify-center text-[10px]">📸</div>
                                <div class="w-full h-full bg-slate-200 rounded-md flex items-center justify-center text-[10px]">📸</div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-2xl font-black text-slate-900 group-hover:text-pink-600 transition-colors">
                                Full Photo
                            </h3>
                            <p class="text-xs font-bold text-amber-600 mt-0.5">
                                Kertas 4R Utuh (4x6 Inci / 1200x1800 px)
                            </p>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                Satu lembar foto kartu pos penuh dengan area foto yang luas. Leluasa untuk foto keluarga, rame-rame, atau pajangan frame dinding.
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">Pilihan: {{ activeFullCountsText }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-900 group-hover:bg-pink-500 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                <ArrowRight class="w-5 h-5 stroke-[2.5]" />
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- LANGKAH 2: JUMLAH FOTO                                                    -->
            <!-- ========================================================================= -->
            <div 
                v-else-if="currentStep === 2"
                class="flex-1 w-full h-full flex flex-col items-center justify-center p-6 md:p-10 max-w-5xl mx-auto overflow-y-auto"
            >
                <div class="w-full max-w-4xl flex items-center justify-between mb-6">
                    <button
                        @click="currentStep = 1"
                        class="px-4 py-2 rounded-2xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center gap-1.5 shadow-xs transition-all active:scale-95 cursor-pointer"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        <span>Ganti Bentuk</span>
                    </button>

                    <div class="text-center">
                        <span class="px-3.5 py-1 rounded-full bg-pink-100 text-pink-600 text-xs font-black uppercase tracking-wider">
                            Langkah 2 dari 5
                        </span>
                        <h2 class="text-2xl md:text-4xl font-black text-slate-900 mt-1">
                            PILIH JUMLAH FOTO
                        </h2>
                        <p class="text-xs md:text-sm text-slate-500">
                            Bentuk: <span class="font-bold text-pink-600">{{ selectedFormat === 'strip' ? 'Photo Strip (Setengah 4R)' : 'Full Photo (Kertas 4R Utuh)' }}</span>
                        </p>
                    </div>

                    <div class="w-24 hidden sm:block"></div>
                </div>

                <!-- JIKA TIDAK ADA JUMLAH FOTO AKTIF UNTUK BENTUK INI -->
                <div 
                    v-if="availableCountOptions.length === 0" 
                    class="my-auto text-center py-12 px-8 max-w-md mx-auto bg-white rounded-3xl border-2 border-dashed border-slate-200 shadow-xl"
                >
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                        <Layers class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-black text-slate-800">Tidak Ada Template Aktif</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Tidak ditemukan template aktif untuk format {{ selectedFormat === 'strip' ? 'Photo Strip' : 'Full Photo' }}.
                    </p>
                    <button
                        @click="currentStep = 1"
                        class="mt-4 px-6 py-2.5 rounded-xl bg-pink-500 text-white text-xs font-bold shadow-md hover:bg-pink-600 transition-colors cursor-pointer"
                    >
                        Pilih Bentuk Lain
                    </button>
                </div>

                <!-- CARDS GRID FOR PHOTO COUNT (HANYA YANG ADA TEMPLATE AKTIFNYA) -->
                <div 
                    v-else
                    :class="[
                        availableCountOptions.length === 1 ? 'flex justify-center w-full max-w-sm mx-auto' :
                        availableCountOptions.length === 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-6 w-full max-w-2xl mx-auto' :
                        'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full max-w-4xl mx-auto'
                    ]"
                >
                    <button
                        v-for="opt in availableCountOptions"
                        :key="opt.count"
                        @click="handleSelectCount(opt.count)"
                        class="group bg-white rounded-3xl p-6 border-2 border-slate-200 hover:border-pink-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col items-center text-center cursor-pointer w-full"
                    >
                        <!-- Badge -->
                        <span 
                            v-if="opt.badge" 
                            class="px-3 py-0.5 rounded-full bg-pink-500 text-white text-[10px] font-black uppercase mb-3 shadow-xs"
                        >
                            {{ opt.badge }}
                        </span>
                        <div v-else class="h-6"></div>

                        <!-- Miniature Visual Layout -->
                        <div 
                            class="bg-slate-50 rounded-2xl p-2 mb-4 border border-slate-100 flex items-center justify-center group-hover:bg-pink-50/40 transition-colors"
                            :class="selectedFormat === 'strip' ? 'w-24 h-44' : 'w-32 h-44'"
                        >
                            <div 
                                class="w-full h-full bg-white rounded-xl shadow-xs border border-slate-200 p-1.5"
                                :class="selectedFormat === 'full' && opt.count >= 4 ? 'grid grid-cols-2 gap-1' : 'flex flex-col justify-between'"
                            >
                                <div 
                                    v-for="sIdx in opt.count" 
                                    :key="sIdx"
                                    class="w-full h-full bg-[#888d92] rounded-[3px] flex items-center justify-center text-[10px] text-white font-bold"
                                    :style="{ height: selectedFormat === 'strip' ? `${100 / opt.count - 4}%` : '100%' }"
                                >
                                    {{ sIdx }}
                                </div>
                            </div>
                        </div>

                        <h3 class="text-2xl font-black text-slate-900 group-hover:text-pink-600 transition-colors">
                            {{ opt.name }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            {{ opt.description }}
                        </p>

                        <!-- Info Template Aktif Tersedia -->
                        <div class="mt-2 text-[11px] font-semibold text-pink-600 bg-pink-50 px-3 py-1 rounded-full border border-pink-100">
                            {{ opt.templateCount }} Desain Template Tersedia
                        </div>

                        <div class="mt-5 w-full py-2.5 rounded-xl bg-pink-50 group-hover:bg-pink-500 text-pink-600 group-hover:text-white font-black text-xs transition-colors flex items-center justify-center gap-1.5">
                            <span>Pilih {{ opt.name }}</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- LANGKAH 3: TEMPLATE (DIBUAT ADMIN LENGKAP DENGAN DESAIN, HANYA AKTIF)     -->
            <!-- ========================================================================= -->
            <div 
                v-else-if="currentStep === 3"
                class="flex-1 w-full h-full flex flex-col p-4 md:p-8 max-w-6xl mx-auto overflow-y-auto"
            >
                <!-- Top Navigation & Header -->
                <div class="w-full flex items-center justify-between mb-6">
                    <button
                        @click="currentStep = 2"
                        class="px-4 py-2 rounded-2xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center gap-1.5 shadow-xs transition-all active:scale-95"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        <span>Ganti Jumlah Foto</span>
                    </button>

                    <div class="text-center">
                        <span class="px-3.5 py-1 rounded-full bg-pink-100 text-pink-600 text-xs font-black uppercase tracking-wider">
                            Langkah 3 dari 5
                        </span>
                        <h2 class="text-2xl md:text-4xl font-black text-slate-900 mt-1">
                            PILIH TEMPLATE
                        </h2>
                        <p class="text-xs md:text-sm text-slate-500">
                            Pilih desain template buatan admin ({{ selectedPhotoCount }} Foto • {{ selectedFormat === 'strip' ? 'Photo Strip' : 'Full 4R' }})
                        </p>
                    </div>

                    <div class="px-3 py-1.5 rounded-2xl bg-pink-50 text-pink-600 text-xs font-black border border-pink-100 hidden sm:flex items-center gap-1">
                        <span>{{ activeAdminTemplates.length }} Template Aktif</span>
                    </div>
                </div>

                <!-- JIKA BELUM ADA TEMPLATE AKTIF UNTUK KOMBINASI INI -->
                <div 
                    v-if="activeAdminTemplates.length === 0"
                    class="my-auto text-center py-16 bg-white rounded-3xl border-2 border-dashed border-slate-200 p-8 max-w-lg mx-auto"
                >
                    <Layers class="w-12 h-12 text-slate-400 mx-auto mb-3" />
                    <h3 class="text-lg font-black text-slate-800">Belum Ada Template Aktif</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Belum ada template aktif buatan admin untuk {{ selectedPhotoCount }} foto format {{ selectedFormat }}.
                    </p>
                    <button
                        @click="currentStep = 2"
                        class="mt-4 px-6 py-2.5 rounded-xl bg-pink-500 text-white text-xs font-bold shadow-md hover:bg-pink-600 transition-colors"
                    >
                        Pilih Jumlah Foto Lain
                    </button>
                </div>

                <!-- GRID TEMPLATE BUATAN ADMIN (HANYA YANG AKTIF) -->
                <div 
                    v-else
                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 pb-8"
                >
                    <button
                        v-for="tpl in activeAdminTemplates"
                        :key="tpl.id"
                        @click="handleSelectTemplate(tpl)"
                        class="group bg-white rounded-3xl p-4 border-2 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl flex flex-col justify-between text-left cursor-pointer"
                        :class="currentTemplate?.id === tpl.id 
                            ? 'border-pink-500 ring-4 ring-pink-500/20 shadow-lg' 
                            : 'border-slate-200 hover:border-pink-300 shadow-sm'"
                    >
                        <!-- MINIATURE LIVE PREVIEW OF ADMIN DESIGN (Background, Slots, Texts) -->
                        <div class="w-full flex items-center justify-center p-2 mb-3 bg-slate-50 rounded-2xl overflow-hidden group-hover:bg-pink-50/20 transition-colors">
                            <div 
                                class="relative rounded-xl overflow-hidden shadow-md border border-black/10 flex flex-col justify-between p-1 select-none pointer-events-none"
                                :style="{
                                    aspectRatio: `${tpl.width} / ${tpl.height}`,
                                    background: getTemplateBackgroundStyle(tpl),
                                    height: '220px',
                                    maxHeight: '240px',
                                }"
                            >
                                <!-- Render Elements Preview (Slots) -->
                                <div 
                                    v-for="slotEl in getPhotoSlots(tpl)"
                                    :key="slotEl.id"
                                    class="absolute bg-slate-800/20 border border-black/20 flex items-center justify-center"
                                    :style="{
                                        left: `${slotEl.x}%`,
                                        top: `${slotEl.y}%`,
                                        width: `${slotEl.width}%`,
                                        height: `${slotEl.height}%`,
                                        borderRadius: `${slotEl.border_radius || 4}px`,
                                    }"
                                >
                                    <span class="text-[8px] font-black text-slate-700">📸 {{ slotEl.slot_index }}</span>
                                </div>

                                <!-- Overlay Image Preview if set -->
                                <img
                                    v-if="tpl.overlay_image"
                                    :src="getAssetUrl(tpl.overlay_image)"
                                    alt="Overlay"
                                    class="absolute inset-0 w-full h-full object-fill pointer-events-none z-10"
                                />
                            </div>
                        </div>

                        <!-- Template Info -->
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-black uppercase">
                                    {{ tpl.paper_size || (selectedFormat === 'strip' ? 'Strip 2x6"' : '4R') }}
                                </span>
                                <span v-if="tpl.is_default" class="text-[10px] text-pink-500 font-bold">Default</span>
                            </div>

                            <h4 class="text-sm font-black text-slate-900 group-hover:text-pink-600 transition-colors line-clamp-1">
                                {{ tpl.name }}
                            </h4>
                            <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5 leading-tight">
                                {{ tpl.description || 'Desain template siap cetak buatan admin.' }}
                            </p>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-4 w-full py-2 rounded-xl bg-pink-50 group-hover:bg-pink-500 text-pink-600 group-hover:text-white font-black text-xs transition-colors flex items-center justify-center gap-1.5">
                            <span>Gunakan Template Ini</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- LANGKAH 4: JEPRET (SECARA LIVE MASUK KE POSISI FOTO PADA TEMPLATE)        -->
            <!-- ========================================================================= -->
            <div 
                v-else-if="currentStep === 4"
                class="relative flex-1 w-full h-full flex flex-col justify-between p-3 sm:p-5"
            >
                <!-- TOP BAR: BACK TO TEMPLATE, TIMER, MIRROR -->
                <div class="w-full flex items-center justify-between max-w-5xl mx-auto z-20">
                    <button
                        @click="currentStep = 3"
                        class="px-3.5 py-1.5 rounded-2xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all active:scale-95"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        <span>Ganti Template</span>
                    </button>

                    <!-- Center: Timer Hitungan Mundur (3s, 5s, 10s) -->
                    <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-2xl shadow-sm border border-slate-200">
                        <span class="text-[11px] font-bold text-slate-400 mr-1 hidden sm:inline">Hitungan Mundur:</span>
                        <button
                            v-for="t in [3, 5, 10]"
                            :key="t"
                            @click="timerDuration = t"
                            class="px-3.5 py-1 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                            :class="timerDuration === t 
                                ? 'bg-pink-500 text-white shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        >
                            <span>⏱️</span>
                            <span>{{ t }}s</span>
                        </button>
                    </div>

                    <!-- Right: Slot Indicator & Cermin Toggle -->
                    <div class="flex items-center gap-2">
                        <div class="px-3 py-1.5 rounded-2xl bg-pink-50 text-pink-600 text-xs font-black border border-pink-100 hidden sm:flex items-center gap-1">
                            <span>Foto {{ currentSlotIndex }} dari {{ totalSlots }}</span>
                        </div>

                        <button
                            @click="mirrorMode = !mirrorMode"
                            class="px-3 py-1.5 rounded-2xl bg-white border border-slate-200 hover:border-pink-300 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all active:scale-95"
                            :class="{ 'border-pink-500 text-pink-600 bg-pink-50': mirrorMode }"
                        >
                            <FlipHorizontal class="w-4 h-4" />
                            <span class="hidden sm:inline">Cermin</span>
                        </button>
                    </div>
                </div>

                <!-- CENTER STUDIO: LIVE TEMPLATE CANVAS (Masuk ke Posisi Foto pada Template) -->
                <div class="relative flex-1 w-full flex items-center justify-center my-2 overflow-hidden">
                    <LiveTemplateCanvas
                        ref="liveCanvasRef"
                        :template="currentTemplate"
                        :currentSlotIndex="currentSlotIndex"
                        :capturedPhotos="capturedPhotosMap"
                        :isCountingDown="isCountingDown"
                        :countdown="countdown"
                        :mirrorMode="mirrorMode"
                        :isInteractiveReview="false"
                        :isFlashingSlot="isFlashingSlot"
                        @retake="handleRetake"
                    />
                </div>

                <!-- BOTTOM BAR: BIG PINK SHUTTER BUTTON -->
                <div class="w-full flex items-center justify-center max-w-5xl mx-auto pt-2 z-20">
                    <button
                        v-if="captureStage === 'ready'"
                        @click="startCapture(currentSlotIndex)"
                        class="px-6 sm:px-12 md:px-16 py-3.5 sm:py-4 rounded-full bg-gradient-to-r from-pink-500 via-rose-500 to-pink-500 hover:from-pink-400 hover:to-rose-400 text-white font-black text-sm sm:text-lg md:text-xl tracking-wider shadow-lg shadow-pink-500/40 flex items-center gap-2.5 sm:gap-3 transition-all transform active:scale-95 hover:scale-105 cursor-pointer"
                    >
                        <Camera class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.5]" />
                        <span>AMBIL FOTO KE-{{ currentSlotIndex }}</span>
                    </button>

                    <div
                        v-else-if="captureStage === 'countdown'"
                        class="px-8 sm:px-10 py-3.5 sm:py-4 rounded-full bg-pink-500 text-white font-black text-lg sm:text-xl flex items-center gap-2 shadow-lg shadow-pink-500/40 animate-pulse"
                    >
                        <span>SENYUM! ( {{ countdown }} )</span>
                    </div>

                    <div
                        v-else-if="captureStage === 'capturing'"
                        class="px-8 sm:px-10 py-3.5 sm:py-4 rounded-full bg-slate-900 text-white font-black text-lg sm:text-xl flex items-center gap-2 shadow-lg"
                    >
                        <span>MEMPROSES...</span>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- LANGKAH 5: CETAK (FINAL 300 DPI PREVIEW & PRINT)                           -->
            <!-- ========================================================================= -->
            <div 
                v-else-if="currentStep === 5"
                class="flex-1 flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8 max-w-6xl mx-auto w-full h-full overflow-y-auto md:overflow-hidden p-4 sm:p-6"
            >
                <!-- Left: Final 300 DPI Composite Preview Card -->
                <div class="flex-1 h-full max-h-[78vh] flex items-center justify-center">
                    <div 
                        v-if="!isComposing && currentSession.final_photo_path"
                        class="relative max-h-full rounded-3xl overflow-hidden border-2 border-slate-200 shadow-2xl bg-white group"
                        :style="{ aspectRatio: `${templateWidth} / ${templateHeight}` }"
                    >
                        <img
                            :src="getAssetUrl(currentSession.final_photo_path) + '?v=' + finalPhotoTimestamp"
                            alt="Final Photobooth Output"
                            class="w-full h-full object-contain"
                        />
                    </div>

                    <!-- Spinner saat sedang menyusun 300 DPI -->
                    <div v-else class="flex flex-col items-center justify-center text-center p-8">
                        <div class="w-16 h-16 rounded-full border-4 border-pink-500 border-t-transparent animate-spin mb-4"></div>
                        <h3 class="text-xl font-black text-slate-800">Menyusun Foto Resolusi Tinggi 300 DPI...</h3>
                        <p class="text-xs text-slate-400 mt-1">Menggabungkan foto ke template {{ currentTemplate.name }}</p>
                    </div>
                </div>

                <!-- Right: Print Options & QR Download Panel -->
                <div class="w-full md:w-96 rounded-3xl bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-2xl">
                    <div>
                        <span class="text-xs font-bold text-pink-500 uppercase tracking-wider">Langkah 5: Cetak Foto</span>
                        <h3 class="text-2xl font-black text-slate-900 mt-1">Cetak & Unduh</h3>
                        <p class="text-xs text-slate-500 mt-1">Template: {{ currentTemplate.name }}</p>

                        <!-- Active Printer Card Sesuai Pengaturan -->
                        <div class="mt-4 p-3 rounded-2xl bg-sky-50/80 border border-sky-200/60 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-sky-500/15 text-sky-600 flex items-center justify-center font-bold">
                                    <Printer class="w-4 h-4" />
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-black text-slate-800 line-clamp-1">
                                        {{ activePrinter?.name || 'Printer Standar Windows' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                        <span>Kertas:</span>
                                        <span class="font-black text-sky-700 bg-sky-100 px-1.5 py-0.5 rounded">{{ activePaperSize || '4R' }}</span>
                                        <span>• {{ activePrinter?.adapter === 'windows' ? 'Spooler Direct' : 'Photo DyeSub' }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">
                                Siap
                            </span>
                        </div>

                        <!-- Number of Copies Picker -->
                        <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <label class="text-xs font-semibold text-slate-700 block mb-3">Jumlah Lembar Cetak:</label>
                            <div class="flex items-center justify-between">
                                <button
                                    @click="printCopies = Math.max(1, printCopies - 1)"
                                    class="w-12 h-12 rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-lg flex items-center justify-center border border-slate-200 active:scale-90 transition-all shadow-sm"
                                >
                                    <Minus class="w-5 h-5" />
                                </button>
                                <span class="text-3xl font-black text-pink-600 font-mono">{{ printCopies }}</span>
                                <button
                                    @click="printCopies = Math.min(10, printCopies + 1)"
                                    class="w-12 h-12 rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-lg flex items-center justify-center border border-slate-200 active:scale-90 transition-all shadow-sm"
                                >
                                    <Plus class="w-5 h-5" />
                                </button>
                            </div>
                            <div class="text-[11px] text-slate-500 text-center mt-3">
                                Format Kertas: <strong class="text-slate-700">{{ activePaperSize || currentTemplate?.paper_size || '4R' }}</strong> Glossy Premium
                            </div>
                        </div>

                        <!-- QR Code Mobile Download Info -->
                        <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-white p-1 flex items-center justify-center shadow-sm border border-slate-200">
                                <QrCode class="w-12 h-12 text-slate-900" />
                            </div>
                            <div class="text-xs">
                                <p class="font-bold text-slate-900">Salinan Digital Tersedia</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">Kode: {{ currentSession.digital_code }}</p>
                                <p class="text-[10px] text-pink-600 font-medium mt-0.5">Scan di hasil cetak untuk unduh</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-2.5">
                        <button
                            @click="triggerPrint"
                            class="w-full py-4 md:py-5 rounded-2xl bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-400 hover:to-rose-400 text-white font-black text-base md:text-lg shadow-lg shadow-pink-500/40 flex items-center justify-center gap-3 transition-all active:scale-95"
                        >
                            <Printer class="w-6 h-6 stroke-[2.5]" />
                            <span>CETAK FOTO SEKARANG</span>
                        </button>

                        <button
                            @click="handleRestartAll"
                            class="w-full py-3 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 transition-all flex items-center justify-center gap-1.5"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span>Foto Ulang</span>
                        </button>

                        <button
                            @click="handleDoneSession"
                            class="w-full py-2.5 rounded-2xl text-slate-400 hover:text-slate-600 font-semibold text-xs transition-all text-center"
                        >
                            Lewati & Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT PROGRESS MODAL -->
        <PrintModal
            :show="showPrintModal"
            :progress="printProgress"
            :isPrinting="isPrinting"
            :isCompleted="isPrintComplete"
            :copies="printCopies"
            @print-again="triggerPrint"
            @done="handleDoneSession"
        />

        <!-- PAYMENT MODAL -->
        <PaymentModal
            :show="showPaymentModal"
            :sessionId="currentSession.id"
            :basePrice="currentSession.event?.default_price || 0"
            :extraPrintPrice="currentSession.event?.extra_print_price || 0"
            :copies="printCopies"
            @close="showPaymentModal = false"
            @paid="handlePaymentSuccess"
        />
    </KioskLayout>
</template>

<style scoped>
@keyframes scaleUp {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-scale-up {
    animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
