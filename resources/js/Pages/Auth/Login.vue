<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Camera, Lock, Mail, ArrowRight, ShieldCheck } from 'lucide-vue-next';

const form = useForm({
    email: 'admin@photobooth.pro',
    password: 'password',
    remember: true,
});

function submit() {
    form.post('/login');
}

function fillCredentials(role: 'admin' | 'operator') {
    if (role === 'admin') {
        form.email = 'admin@photobooth.pro';
        form.password = 'password';
    } else {
        form.email = 'operator@photobooth.pro';
        form.password = 'password';
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white flex flex-col items-center justify-center p-4 font-sans select-none">
        <div class="w-full max-w-md rounded-3xl bg-slate-900 border border-white/10 p-8 shadow-2xl flex flex-col items-center">
            <!-- Brand Icon -->
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-400 to-amber-600 flex items-center justify-center text-slate-950 shadow-[0_0_35px_rgba(245,158,11,0.4)] mb-4">
                <Camera class="w-8 h-8" />
            </div>

            <h2 class="text-2xl font-black tracking-tight text-white uppercase text-center">
                PHOTOBOOTH <span class="text-amber-400">PRO</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1 mb-6 text-center">Masuk ke Panel Kontrol Admin & Operator</p>

            <form @submit.prevent="submit" class="w-full space-y-4 text-xs">
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Email Pengguna</label>
                    <div class="relative">
                        <Mail class="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                        <input
                            type="email"
                            v-model="form.email"
                            required
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white/10 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400"
                            placeholder="admin@photobooth.pro"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Password</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                        <input
                            type="password"
                            v-model="form.password"
                            required
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white/10 border border-white/10 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400"
                            placeholder="••••••••"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.remember" class="rounded bg-white/10 text-amber-500 border-white/10" />
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm shadow-xl flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50 mt-2"
                >
                    <span>{{ form.processing ? 'Memverifikasi...' : 'MASUK KE SISTEM' }}</span>
                    <ArrowRight class="w-4 h-4 stroke-[2.5]" />
                </button>
            </form>

            <!-- Demo Quick Fill Buttons -->
            <div class="w-full mt-6 pt-6 border-t border-white/10 text-center">
                <span class="text-[11px] text-slate-500 block mb-2">Akun Demo Siap Pakai:</span>
                <div class="flex items-center gap-2">
                    <button
                        @click="fillCredentials('admin')"
                        type="button"
                        class="flex-1 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 border border-white/10"
                    >
                        Admin (Full Access)
                    </button>
                    <button
                        @click="fillCredentials('operator')"
                        type="button"
                        class="flex-1 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 border border-white/10"
                    >
                        Operator Booth
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>