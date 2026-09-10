<script setup lang="ts">
import { Download, Share2, Sparkles, Camera, Check } from 'lucide-vue-next';
import type { BoothSession } from '@/types';
import { getAssetUrl } from '@/utils/url';

const props = defineProps<{
    session: BoothSession;
}>();

async function handleShare() {
    if (navigator.share) {
        try {
            await navigator.share({
                title: props.session.event?.name || 'Photobooth Pro Photo',
                text: 'Lihat foto kenangan kami di photo booth!',
                url: window.location.href,
            });
        } catch (e) {}
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Tautan foto berhasil disalin ke clipboard!');
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-between p-4 md:p-8 font-sans">
        <!-- Top Header -->
        <header class="w-full max-w-md flex flex-col items-center text-center pt-4">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold uppercase mb-2">
                <Sparkles class="w-3.5 h-3.5" />
                <span>{{ session.event?.name || 'PHOTOBOOTH PRO' }}</span>
            </div>
            <h1 class="text-2xl font-black text-white">Digital Photo Copy</h1>
            <p class="text-xs text-slate-400 mt-0.5 font-mono">Kode Sesi: {{ session.digital_code }}</p>
        </header>

        <!-- Center Photo Container -->
        <main class="my-auto w-full max-w-md flex flex-col items-center">
            <div class="relative w-full aspect-[2/3] rounded-3xl overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.8)] border border-white/20 bg-slate-900">
                <img
                    :src="getAssetUrl(session.final_photo_path)"
                    alt="Hasil Foto Photobooth"
                    class="w-full h-full object-contain"
                />
            </div>

            <!-- Action Buttons -->
            <div class="w-full mt-6 space-y-3">
                <a
                    :href="getAssetUrl(session.final_photo_path)"
                    download="photobooth_pro_photo.jpg"
                    class="w-full py-4 px-6 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95 text-center"
                >
                    <Download class="w-5 h-5 stroke-[2.5]" />
                    <span>UNDUH FOTO RESOLUSI PENUH</span>
                </a>

                <button
                    @click="handleShare"
                    class="w-full py-3.5 px-6 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-200 font-bold text-xs border border-white/10 flex items-center justify-center gap-2 transition-all active:scale-95"
                >
                    <Share2 class="w-4 h-4" />
                    <span>Bagikan Foto</span>
                </button>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center text-[11px] text-slate-500 pb-4">
            PHOTOBOOTH PRO • Studio Quality Instant Photography
        </footer>
    </div>
</template>