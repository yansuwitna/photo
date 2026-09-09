<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import { Camera, Sparkles, ArrowRight, Play } from 'lucide-vue-next';
import { useSessionStore } from '@/stores/sessionStore';
import { useAudioStore } from '@/stores/audioStore';

const page = usePage();
const sessionStore = useSessionStore();
const audioStore = useAudioStore();

const activeEvent = page.props.active_event as any;
const isStarting = ref(false);

async function handleStart() {
    isStarting.value = true;
    audioStore.playBeep(880, 0.2, 'sine');
    audioStore.speakInstruction('Selamat datang di photo booth! Silakan pilih gaya foto Anda.');

    try {
        const res = await sessionStore.startSession(undefined, activeEvent?.id);
        if (res.success) {
            router.visit(`/session/${res.session.id}/template`);
        }
    } catch (err) {
        alert('Gagal memulai sesi. Periksa koneksi kamera dan sistem.');
    } finally {
        isStarting.value = false;
    }
}
</script>

<template>
    <KioskLayout>
        <div class="relative flex-1 w-full h-full flex flex-col items-center justify-between p-8 md:p-12 text-center overflow-hidden">
            <!-- Ambient Glowing Background Elements -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-gradient-to-tr from-amber-500/20 via-purple-600/15 to-transparent blur-3xl pointer-events-none"></div>

            <!-- Top Event Welcome Badge -->
            <div class="relative z-10 pt-4 animate-fade-in">
                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-sm tracking-widest uppercase font-medium text-amber-300 shadow-xl">
                    <Sparkles class="w-4 h-4 text-amber-400" />
                    <span>{{ activeEvent?.name || 'STUDIO PHOTOBOOTH PRO' }}</span>
                </div>
            </div>

            <!-- Center Call to Action -->
            <div class="relative z-10 my-auto flex flex-col items-center max-w-2xl">
                <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-slate-950 mb-8 shadow-[0_0_50px_rgba(245,158,11,0.5)] animate-bounce">
                    <Camera class="w-10 h-10" />
                </div>

                <h1 class="text-5xl md:text-7xl font-black tracking-tight text-white uppercase leading-none">
                    PHOTOBOOTH <span class="bg-gradient-to-r from-amber-400 via-amber-200 to-white bg-clip-text text-transparent">PRO</span>
                </h1>

                <p class="mt-4 text-xl md:text-2xl text-slate-300 font-light tracking-wide">
                    Capture The Moment
                </p>

                <p class="mt-2 text-sm text-slate-400 max-w-md">
                    Abadikan senyuman dan kenangan terbaik Anda dengan hasil cetak instan kualitas studio profesional.
                </p>

                <!-- GIANT TOUCH-FRIENDLY START BUTTON -->
                <button
                    @click="handleStart"
                    :disabled="isStarting"
                    class="mt-12 group relative inline-flex items-center justify-center px-12 py-6 rounded-full text-2xl font-black tracking-wider uppercase text-slate-950 bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-400 hover:from-amber-300 hover:to-amber-200 shadow-[0_0_45px_rgba(245,158,11,0.6)] transform transition-all duration-300 hover:scale-105 active:scale-95 disabled:opacity-50"
                >
                    <span class="absolute -inset-1 rounded-full bg-amber-400/40 blur-lg group-hover:opacity-100 transition-opacity"></span>
                    <span class="relative flex items-center gap-4">
                        <Play class="w-8 h-8 fill-slate-950" />
                        <span>{{ isStarting ? 'MEMULAI...' : 'START' }}</span>
                        <ArrowRight class="w-8 h-8 group-hover:translate-x-1 transition-transform" />
                    </span>
                </button>
            </div>

            <!-- Bottom Instruction Footer -->
            <div class="relative z-10 pb-4 text-xs text-slate-400 tracking-wider">
                SENTUH TOMBOL START UNTUK MEMULAI SESI FOTO
            </div>
        </div>
    </KioskLayout>
</template>