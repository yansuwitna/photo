import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export type ThemeMode = 'dark' | 'light';

export const useThemeStore = defineStore('theme', () => {
    // Default to 'dark' for photobooth, but respect saved preference
    const savedTheme = typeof window !== 'undefined' ? (localStorage.getItem('photobooth_theme') as ThemeMode) : null;
    const currentTheme = ref<ThemeMode>(savedTheme || 'dark');

    const isDark = computed(() => currentTheme.value === 'dark');

    function applyThemeToDom(theme: ThemeMode) {
        if (typeof document === 'undefined') return;

        const root = document.documentElement;
        if (theme === 'dark') {
            root.classList.add('dark');
            root.classList.remove('light');
            root.setAttribute('data-theme', 'dark');
            updateMetaThemeColor('#0f172a');
        } else {
            root.classList.add('light');
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
            updateMetaThemeColor('#f8fafc');
        }
    }

    function updateMetaThemeColor(color: string) {
        if (typeof document === 'undefined') return;
        let meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'theme-color');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', color);
    }

    function toggleTheme() {
        const nextTheme: ThemeMode = currentTheme.value === 'dark' ? 'light' : 'dark';
        setTheme(nextTheme);
    }

    function setTheme(theme: ThemeMode) {
        currentTheme.value = theme;
        if (typeof window !== 'undefined') {
            localStorage.setItem('photobooth_theme', theme);
        }
        applyThemeToDom(theme);
    }

    function initTheme() {
        applyThemeToDom(currentTheme.value);
    }

    return {
        currentTheme,
        isDark,
        toggleTheme,
        setTheme,
        initTheme,
    };
});
