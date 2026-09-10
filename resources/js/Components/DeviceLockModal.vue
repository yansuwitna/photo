<script setup lang="ts">
import { ref } from 'vue';
import { Lock, Unlock, X, Delete, ArrowRight } from 'lucide-vue-next';
import { useDeviceStore } from '@/stores/deviceStore';
import { showSuccess, showError } from '@/utils/swal';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'unlocked'): void;
}>();

const deviceStore = useDeviceStore();
const enteredPin = ref('');
const errorMessage = ref('');
const isSubmitting = ref(false);

function enterDigit(d: number) {
    if (enteredPin.value.length < 4) {
        enteredPin.value += d.toString();
        errorMessage.value = '';
    }
    if (enteredPin.value.length === 4) {
        submitUnlock();
    }
}

function backspace() {
    enteredPin.value = enteredPin.value.slice(0, -1);
    errorMessage.value = '';
}

function clear() {
    enteredPin.value = '';
    errorMessage.value = '';
}

async function submitUnlock() {
    if (enteredPin.value.length === 0) return;
    isSubmitting.value = true;
    errorMessage.value = '';
    try {
        const res = await deviceStore.unlock(enteredPin.value);
        if (res.success) {
            showSuccess('Kunci Terbuka', 'Pengaturan kamera dan printer sekarang dapat diubah.');
            enteredPin.value = '';
            emit('unlocked');
            emit('close');
        } else {
            errorMessage.value = res.message || 'PIN yang Anda masukkan salah.';
            enteredPin.value = '';
        }
    } catch (e: any) {
        errorMessage.value = 'Gagal memverifikasi PIN.';
        enteredPin.value = '';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 animate-fade-in"
    >
        <div class="relative w-full max-w-sm rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl flex flex-col items-center">
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-slate-400 hover:text-white transition-colors"
                title="Batal"
            >
                <X class="w-5 h-5" />
            </button>

            <!-- Icon Header -->
            <div class="w-14 h-14 rounded-2xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-400 mb-3 shadow-lg">
                <Lock class="w-7 h-7" />
            </div>

            <h3 class="text-base md:text-lg font-black text-white text-center">
                BUKA KUNCI PERANGKAT
            </h3>
            <p class="text-xs text-slate-400 text-center mt-1 max-w-xs">
                Masukkan PIN 4 digit untuk membuka pengaturan kamera dan printer.
            </p>

            <!-- PIN Display Dots -->
            <div class="flex items-center gap-3 my-6">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="w-4 h-4 rounded-full border-2 transition-all"
                    :class="enteredPin.length >= i ? 'bg-amber-400 border-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.8)] scale-110' : 'border-slate-600 bg-transparent'"
                ></div>
            </div>

            <div v-if="errorMessage" class="text-rose-400 text-xs font-semibold mb-3 text-center animate-bounce">
                {{ errorMessage }}
            </div>

            <!-- Numeric Keypad -->
            <div class="grid grid-cols-3 gap-2.5 w-full max-w-[240px]">
                <button
                    v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]"
                    :key="n"
                    @click="enterDigit(n)"
                    class="h-12 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white font-bold text-lg flex items-center justify-center transition-all"
                >
                    {{ n }}
                </button>
                <button
                    @click="clear"
                    class="h-12 rounded-xl bg-white/5 hover:bg-white/10 active:scale-95 text-slate-400 font-bold text-xs flex items-center justify-center transition-all"
                >
                    CLEAR
                </button>
                <button
                    @click="enterDigit(0)"
                    class="h-12 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white font-bold text-lg flex items-center justify-center transition-all"
                >
                    0
                </button>
                <button
                    @click="backspace"
                    class="h-12 rounded-xl bg-white/5 hover:bg-white/10 active:scale-95 text-slate-400 font-bold text-sm flex items-center justify-center transition-all"
                >
                    <Delete class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
