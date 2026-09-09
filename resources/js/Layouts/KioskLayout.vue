<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Lock, Camera, Printer, Maximize, Minimize } from 'lucide-vue-next';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import KioskPinModal from '@/Components/KioskPinModal.vue';
import { useDeviceStore } from '@/stores/deviceStore';

const page = usePage();
const deviceStore = useDeviceStore();

const showPinModal = ref(false);
const isFullscreen = ref(false);

const activeEvent = page.props.active_event as any;

onMounted(() => {
    deviceStore.fetchStatus();
    // Polling status perangkat tiap 15 detik
    setInterval(() => {
        deviceStore.fetchStatus();
    }, 15000);
});

function toggleFullscreen() {
    const elem = document.documentElement;
    if (!isFullscreen.value) {
        if (elem.requestFullscreen) elem.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
        isFullscreen.value = false;
    }
}
</script>

<template>
    <div class="relative w-screen h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white flex flex-col select-none">
        <!-- TOP MINIMAL KIOSK HEADER -->
        <header class="relative z-40 flex items-center justify-between px-8 py-4 bg-slate-950/40 backdrop-blur-md border-b border-white/5">
            <!-- Left: Brand & Event Info -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <h1 class="font-black text-lg tracking-wider bg-gradient-to-r from-amber-300 via-white to-amber-200 bg-clip-text text-transparent">
                        PHOTOBOOTH PRO
                    </h1>
                </div>

                <div v-if="activeEvent" class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-slate-300">
                    <span class="text-amber-400 font-semibold">{{ activeEvent.name }}</span>
                    <span v-if="activeEvent.location" class="text-slate-500">• {{ activeEvent.location }}</span>
                </div>
            </div>

            <!-- Right: Device Indicators & Control -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2">
                    <DeviceStatusBadge :status="deviceStore.camera.status" label="Kamera" size="sm" />
                    <DeviceStatusBadge :status="deviceStore.printer.status" label="Printer" size="sm" />
                </div>

                <button
                    @click="toggleFullscreen"
                    class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white border border-white/10 transition-colors"
                    title="Fullscreen"
                >
                    <Maximize v-if="!isFullscreen" class="w-4 h-4" />
                    <Minimize v-else class="w-4 h-4" />
                </button>

                <!-- Kiosk Exit / Settings Lock Button -->
                <button
                    @click="showPinModal = true"
                    class="p-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 transition-all flex items-center gap-1 text-xs font-semibold"
                    title="Beralih ke Operator/Admin"
                >
                    <Lock class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Keluar Kiosk</span>
                </button>
            </div>
        </header>

        <!-- MAIN KIOSK VIEWPORT -->
        <main class="relative flex-1 w-full h-full overflow-hidden flex flex-col">
            <slot />
        </main>

        <!-- PIN MODAL -->
        <KioskPinModal
            :show="showPinModal"
            @close="showPinModal = false"
        />
    </div>
</template>