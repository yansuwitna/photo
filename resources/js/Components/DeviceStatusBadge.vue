<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: 'ready' | 'busy' | 'error' | 'disconnected' | 'connected' | 'paper_empty' | string;
    label?: string;
    showText?: boolean;
    size?: 'sm' | 'md' | 'lg';
}>();

const config = computed(() => {
    switch (props.status) {
        case 'ready':
        case 'connected':
            return {
                bg: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
                dot: 'bg-emerald-500',
                text: 'READY',
            };
        case 'busy':
        case 'printing':
            return {
                bg: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                dot: 'bg-amber-500 animate-ping',
                text: 'BUSY',
            };
        case 'error':
        case 'paper_empty':
            return {
                bg: 'bg-rose-500/10 border-rose-500/30 text-rose-400',
                dot: 'bg-rose-500 animate-bounce',
                text: props.status === 'paper_empty' ? 'KERTAS HABIS' : 'ERROR',
            };
        default:
            return {
                bg: 'bg-slate-500/10 border-slate-500/30 text-slate-400',
                dot: 'bg-slate-400',
                text: 'OFFLINE',
            };
    }
});
</script>

<template>
    <div
        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border backdrop-blur-md transition-all"
        :class="[config.bg, size === 'sm' ? 'text-xs' : 'text-sm font-medium']"
    >
        <span class="relative flex h-2.5 w-2.5">
            <span
                v-if="status === 'busy' || status === 'printing'"
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="config.dot"
            ></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="config.dot"></span>
        </span>
        <span v-if="showText !== false">
            {{ label ? `${label}: ` : '' }}{{ config.text }}
        </span>
    </div>
</template>