<script setup lang="ts">
import { ref, computed } from 'vue';
import { CreditCard, QrCode, Banknote, Gift, Tag, Check, X, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    show: boolean;
    sessionId: string;
    basePrice: number;
    extraPrintPrice: number;
    copies: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'paid', payment: any): void;
}>();

const selectedMethod = ref<'cash' | 'qris' | 'voucher' | 'free'>('cash');
const promoCode = ref('');
const discountAmount = ref(0);
const promoMessage = ref('');
const isCheckingPromo = ref(false);
const isProcessing = ref(false);

const subtotal = computed(() => {
    const additional = Math.max(0, props.copies - 1);
    return props.basePrice + (additional * props.extraPrintPrice);
});

const totalAmount = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
});

async function applyPromo() {
    if (!promoCode.value) return;
    try {
        isCheckingPromo.value = true;
        const res = await axios.post('/api/promo/check', {
            code: promoCode.value,
            total: subtotal.value,
        });
        if (res.data.valid) {
            discountAmount.value = res.data.discount;
            promoMessage.value = `✓ Diskon Rp ${res.data.discount.toLocaleString('id-ID')} diterapkan.`;
        } else {
            promoMessage.value = res.data.message || 'Promo tidak valid';
            discountAmount.value = 0;
        }
    } catch (e: any) {
        promoMessage.value = 'Gagal memeriksa promo';
    } finally {
        isCheckingPromo.value = false;
    }
}

async function confirmPayment() {
    try {
        isProcessing.value = true;
        const res = await axios.post(`/api/session/${props.sessionId}/payment`, {
            method: selectedMethod.value,
            amount_paid: totalAmount.value,
            promo_code: promoCode.value,
            copies: props.copies,
        });

        if (res.data.success) {
            emit('paid', res.data.payment);
            emit('close');
        } else {
            alert(res.data.message || 'Pembayaran gagal');
        }
    } catch (err: any) {
        alert('Gagal memproses pembayaran');
    } finally {
        isProcessing.value = false;
    }
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-fade-in"
    >
        <div class="relative w-full max-w-lg rounded-3xl bg-slate-900 border border-white/20 p-6 shadow-2xl flex flex-col">
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-slate-400 hover:text-white transition-colors"
            >
                <X class="w-5 h-5" />
            </button>

            <!-- Header -->
            <div class="flex items-center gap-3 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                    <ShieldCheck class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Pembayaran Sesi Foto</h3>
                    <p class="text-xs text-slate-400">Pilih metode pembayaran yang tersedia di photo booth</p>
                </div>
            </div>

            <!-- Methods Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5">
                <button
                    @click="selectedMethod = 'cash'"
                    class="p-3.5 rounded-2xl border flex flex-col items-center gap-2 transition-all"
                    :class="selectedMethod === 'cash' ? 'bg-amber-500/20 border-amber-400 text-amber-300' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'"
                >
                    <Banknote class="w-6 h-6" />
                    <span class="text-xs font-semibold">Tunai</span>
                </button>

                <button
                    @click="selectedMethod = 'qris'"
                    class="p-3.5 rounded-2xl border flex flex-col items-center gap-2 transition-all"
                    :class="selectedMethod === 'qris' ? 'bg-amber-500/20 border-amber-400 text-amber-300' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'"
                >
                    <QrCode class="w-6 h-6" />
                    <span class="text-xs font-semibold">QRIS</span>
                </button>

                <button
                    @click="selectedMethod = 'voucher'"
                    class="p-3.5 rounded-2xl border flex flex-col items-center gap-2 transition-all"
                    :class="selectedMethod === 'voucher' ? 'bg-amber-500/20 border-amber-400 text-amber-300' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'"
                >
                    <Tag class="w-6 h-6" />
                    <span class="text-xs font-semibold">Voucher</span>
                </button>

                <button
                    @click="selectedMethod = 'free'"
                    class="p-3.5 rounded-2xl border flex flex-col items-center gap-2 transition-all"
                    :class="selectedMethod === 'free' ? 'bg-amber-500/20 border-amber-400 text-amber-300' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'"
                >
                    <Gift class="w-6 h-6" />
                    <span class="text-xs font-semibold">Gratis</span>
                </button>
            </div>

            <!-- QRIS View Mock if Selected -->
            <div v-if="selectedMethod === 'qris'" class="p-4 rounded-2xl bg-white text-slate-900 flex flex-col items-center mb-5 shadow-inner">
                <p class="text-xs font-bold tracking-wider mb-2">SCAN QRIS UNTUK MEMBAYAR</p>
                <!-- QR Box -->
                <div class="w-40 h-40 bg-slate-100 border-2 border-slate-900 rounded-xl flex items-center justify-center p-2">
                    <QrCode class="w-32 h-32 text-slate-950" />
                </div>
                <span class="text-xs font-semibold mt-2 text-slate-600">NMID: ID102030405060 (GPN)</span>
            </div>

            <!-- Promo Input -->
            <div class="flex items-center gap-2 mb-4">
                <input
                    type="text"
                    v-model="promoCode"
                    placeholder="Masukkan Kode Promo / Voucher"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-white/10 border border-white/15 text-white placeholder-slate-500 text-xs uppercase font-medium focus:outline-none focus:border-amber-400"
                />
                <button
                    @click="applyPromo"
                    :disabled="isCheckingPromo || !promoCode"
                    class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-semibold disabled:opacity-50 transition-all"
                >
                    Terapkan
                </button>
            </div>
            <p v-if="promoMessage" class="text-xs text-amber-300 mb-4 px-1">{{ promoMessage }}</p>

            <!-- Bill Summary -->
            <div class="p-4 rounded-2xl bg-black/40 border border-white/10 space-y-2 text-xs mb-5">
                <div class="flex justify-between text-slate-400">
                    <span>Sesi Foto & Cetak Utama</span>
                    <span class="font-mono text-white">Rp {{ basePrice.toLocaleString('id-ID') }}</span>
                </div>
                <div v-if="copies > 1" class="flex justify-between text-slate-400">
                    <span>Salinan Tambahan ({{ copies - 1 }}x)</span>
                    <span class="font-mono text-white">Rp {{ ((copies - 1) * extraPrintPrice).toLocaleString('id-ID') }}</span>
                </div>
                <div v-if="discountAmount > 0" class="flex justify-between text-emerald-400">
                    <span>Potongan Diskon</span>
                    <span class="font-mono">- Rp {{ discountAmount.toLocaleString('id-ID') }}</span>
                </div>
                <div class="border-t border-white/10 pt-2 flex justify-between text-sm font-bold text-white">
                    <span>TOTAL HARGA</span>
                    <span class="text-amber-400 font-mono text-base">
                        {{ selectedMethod === 'free' ? 'GRATIS (Event Pass)' : `Rp ${totalAmount.toLocaleString('id-ID')}` }}
                    </span>
                </div>
            </div>

            <!-- Confirm Button -->
            <button
                @click="confirmPayment"
                :disabled="isProcessing"
                class="w-full py-4 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50"
            >
                <Check class="w-5 h-5 stroke-[2.5]" />
                <span>{{ isProcessing ? 'Memproses...' : 'Konfirmasi & Lanjutkan' }}</span>
            </button>
        </div>
    </div>
</template>