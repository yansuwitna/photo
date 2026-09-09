<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Settings, Save, Shield, HardDrive, Volume2, Monitor } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    settings: any[];
}>();

const settingsForm = ref<Record<string, any>>({});
props.settings.forEach(s => {
    settingsForm.value[s.key] = s.value;
});

const isSaving = ref(false);

async function saveSettings() {
    isSaving.value = true;
    try {
        await axios.post('/api/admin/settings', { settings: settingsForm.value });
        alert('Pengaturan berhasil disimpan!');
    } catch (e) {
        alert('Gagal menyimpan pengaturan');
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 max-w-4xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white">Pengaturan Sistem Photo Booth</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Konfigurasi global Kiosk mode, PIN keamanan, parameter audio, dan retensi penyimpanan
                    </p>
                </div>

                <button
                    @click="saveSettings"
                    :disabled="isSaving"
                    class="py-2.5 px-6 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold flex items-center gap-2 shadow-lg transition-all active:scale-95 disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
            </div>

            <!-- Settings Groups -->
            <div class="space-y-6">
                <!-- Group 1: General & Kiosk -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 space-y-4">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <Monitor class="w-4 h-4 text-amber-400" />
                        <span>Kiosk & Tampilan Layar</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Nama Aplikasi</label>
                            <input v-model="settingsForm['app_name']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white" />
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">PIN Keluar Kiosk Mode (4 Digit)</label>
                            <input v-model="settingsForm['kiosk_exit_pin']" maxlength="4" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Durasi Countdown Default (Detik)</label>
                            <input type="number" v-model.number="settingsForm['countdown_duration']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Reset Otomatis ke START (Detik)</label>
                            <input type="number" v-model.number="settingsForm['auto_reset_seconds']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>
                    </div>
                </div>

                <!-- Group 2: Audio & Suara -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 space-y-4">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <Volume2 class="w-4 h-4 text-purple-400" />
                        <span>Audio & Panduan Suara</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Volume Audio Speaker (%)</label>
                            <input type="number" min="0" max="100" v-model.number="settingsForm['audio_volume']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Panduan Suara Bahasa Indonesia</label>
                            <select v-model="settingsForm['enable_voice_guidance']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white">
                                <option value="1">Aktif (Bicara pada hitung mundur & instruksi)</option>
                                <option value="0">Nonaktif (Hanya nada beep)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Group 3: Storage & Cleanup -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-white/10 space-y-4">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <HardDrive class="w-4 h-4 text-sky-400" />
                        <span>Penyimpanan & Auto Cleanup</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Hapus Foto Otomatis Setelah (Hari)</label>
                            <input type="number" v-model.number="settingsForm['auto_cleanup_days']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white font-mono" />
                            <p class="text-[10px] text-slate-500 mt-1">Sesuai bagian 23 rancangan.md, sistem membersihkan file lama</p>
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Simpan Foto Original Raw</label>
                            <select v-model="settingsForm['keep_originals']" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/10 text-white">
                                <option value="1">Simpan semua file jepretan asli</option>
                                <option value="0">Hanya simpan file final komposit</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>