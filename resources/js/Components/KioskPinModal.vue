<script setup lang="ts">
import { ref } from 'vue';
import { Lock, X, ArrowRight, Delete } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

const enteredPin = ref('');
const errorMessage = ref('');

function enterDigit(digit: number) {
    if (enteredPin.value.length < 4) {
        enteredPin.value += digit.toString();
        errorMessage.value = '';
    }
    if (enteredPin.value.length === 4) {
        verifyPin();
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

function verifyPin() {
    // Default PIN: 1234 (admin) or 0000 (operator)
    if (enteredPin.value === '1234') {
        emit('success');
        emit('close');
        router.visit('/admin');
    } else if (enteredPin.value === '0000') {
        emit('success');
        emit('close');
        router.visit('/controller');
    } else {
        errorMessage.value = 'PIN tidak valid. Coba lagi.';
        enteredPin.value = '';
    }
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-fade-in"
    >
        <div class="relative w-full max-w-sm rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl flex flex-col items-center">
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-slate-400 hover:text-white transition-colors"
            >
                <X class="w-5 h-5" />
            </button>

            <!-- Header -->
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-3 shadow-lg">
                <Lock class="w-6 h-6" />
            </div>

            <h3 class="text-xl font-bold text-white">Mode Operator / Admin</h3>
            <p class="text-xs text-slate-400 mt-1 text-center">Masukkan 4 digit PIN untuk beralih mode</p>

            <!-- PIN Mask Indicators -->
            <div class="flex items-center gap-3 my-6">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="w-4 h-4 rounded-full border-2 transition-all duration-200"
                    :class="[
                        enteredPin.length >= i
                            ? 'bg-amber-400 border-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.6)]'
                            : 'border-slate-600 bg-slate-800'
                    ]"
                ></div>
            </div>

            <p v-if="errorMessage" class="text-rose-400 text-xs font-medium mb-3 text-center animate-bounce">
                {{ errorMessage }}
            </p>

            <!-- Numeric Touch Keypad -->
            <div class="grid grid-cols-3 gap-3 w-full max-w-[260px]">
                <button
                    v-for="n in 9"
                    :key="n"
                    @click="enterDigit(n)"
                    class="h-14 rounded-2xl bg-white/10 hover:bg-white/20 active:bg-amber-500 active:text-slate-950 font-bold text-2xl text-white border border-white/10 shadow-md transition-all active:scale-95 flex items-center justify-center"
                >
                    {{ n }}
                </button>

                <button
                    @click="clear"
                    class="h-14 rounded-2xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 font-semibold text-xs uppercase border border-rose-500/30 shadow-md flex items-center justify-center transition-all active:scale-95"
                >
                    Clear
                </button>

                <button
                    @click="enterDigit(0)"
                    class="h-14 rounded-2xl bg-white/10 hover:bg-white/20 active:bg-amber-500 active:text-slate-950 font-bold text-2xl text-white border border-white/10 shadow-md transition-all active:scale-95 flex items-center justify-center"
                >
                    0
                </button>

                <button
                    @click="backspace"
                    class="h-14 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 font-semibold border border-white/10 shadow-md flex items-center justify-center transition-all active:scale-95"
                >
                    <Delete class="w-5 h-5" />
                </button>
            </div>

            <div class="mt-4 text-[11px] text-slate-500 text-center">
                PIN Bawaan: 1234 (Admin) | 0000 (Operator)
            </div>
        </div>
    </div>
</template>