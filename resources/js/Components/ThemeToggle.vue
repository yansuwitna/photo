<script setup lang="ts">
import { onMounted } from 'vue';
import { Sun, Moon } from 'lucide-vue-next';
import { useThemeStore } from '@/stores/themeStore';

const themeStore = useThemeStore();

onMounted(() => {
    themeStore.initTheme();
});
</script>

<template>
    <button
        @click="themeStore.toggleTheme()"
        type="button"
        class="relative p-2 rounded-xl border transition-all duration-300 flex items-center justify-center cursor-pointer select-none active:scale-95"
        :class="themeStore.isDark 
            ? 'bg-white/10 hover:bg-white/15 text-amber-300 border-white/15 shadow-sm' 
            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300 shadow-sm'"
        :title="themeStore.isDark ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'"
        :aria-label="themeStore.isDark ? 'Mode Terang' : 'Mode Gelap'"
    >
        <transition name="rotate-fade" mode="out-in">
            <Sun v-if="themeStore.isDark" class="w-4 h-4 text-amber-300 animate-spin-once" />
            <Moon v-else class="w-4 h-4 text-slate-700 animate-spin-once" />
        </transition>
    </button>
</template>

<style scoped>
.rotate-fade-enter-active,
.rotate-fade-leave-active {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.rotate-fade-enter-from {
    opacity: 0;
    transform: rotate(-45deg) scale(0.8);
}

.rotate-fade-leave-to {
    opacity: 0;
    transform: rotate(45deg) scale(0.8);
}
</style>
