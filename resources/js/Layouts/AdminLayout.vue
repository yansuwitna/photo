<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Calendar, 
    Layers, 
    Cpu, 
    Image, 
    Tag, 
    BarChart3, 
    Settings, 
    MonitorPlay, 
    Tablet, 
    LogOut, 
    Menu, 
    X,
    CheckCircle2,
    ShieldCheck,
    Printer
} from 'lucide-vue-next';
import DeviceStatusBadge from '@/Components/DeviceStatusBadge.vue';
import { useDeviceStore } from '@/stores/deviceStore';

const page = usePage();
const deviceStore = useDeviceStore();

const sidebarOpen = ref(false);
const activeEvent = computed(() => page.props.active_event as any);
const user = computed(() => page.props.auth?.user as any);

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { name: 'Kelola Event', href: '/admin/events', icon: Calendar },
    { name: 'Template Desain', href: '/admin/templates', icon: Layers },
    { name: 'Pusat Perangkat', href: '/admin/devices', icon: Cpu },
    { name: 'Print Station (PC)', href: '/print-station', icon: Printer },
    { name: 'Galeri Foto Sesi', href: '/admin/gallery', icon: Image },
    { name: 'Promo & Voucher', href: '/admin/promos', icon: Tag },
    { name: 'Laporan & Statistik', href: '/admin/reports', icon: BarChart3 },
    { name: 'Pengaturan', href: '/admin/settings', icon: Settings },
];

function isCurrentRoute(path: string) {
    if (path === '/admin') {
        return window.location.pathname === '/admin';
    }
    return window.location.pathname.startsWith(path);
}

function launchKiosk() {
    router.visit('/');
}

function launchController() {
    router.visit('/controller');
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
        <!-- MOBILE HEADER -->
        <header class="md:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-white/10 sticky top-0 z-50">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                <span class="font-bold text-white tracking-wider">PHOTOBOOTH PRO</span>
            </div>
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="p-2 rounded-lg bg-white/10 text-slate-300"
            >
                <X v-if="sidebarOpen" class="w-6 h-6" />
                <Menu v-else class="w-6 h-6" />
            </button>
        </header>

        <!-- SIDEBAR -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-72 bg-slate-900 border-r border-white/10 flex flex-col transition-transform duration-300 transform md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo Header -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.8)]"></span>
                        <h2 class="font-black text-lg tracking-wider text-white">PHOTOBOOTH PRO</h2>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Sistem Photo Booth Profesional</p>
                </div>
            </div>

            <!-- Quick Launch Buttons -->
            <div class="p-4 border-b border-white/10 space-y-2">
                <button
                    @click="launchKiosk"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 shadow-lg transition-all active:scale-95"
                >
                    <MonitorPlay class="w-4 h-4 stroke-[2.5]" />
                    <span>JALANKAN KIOSK LAYAR</span>
                </button>

                <button
                    @click="launchController"
                    class="w-full py-2.5 px-4 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 font-semibold text-xs border border-white/10 flex items-center justify-center gap-2 transition-all"
                >
                    <Tablet class="w-4 h-4 text-sky-400" />
                    <span>Remote Controller Tablet</span>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-semibold transition-all"
                    :class="[
                        isCurrentRoute(item.href)
                            ? 'bg-amber-400/10 text-amber-400 border border-amber-400/30'
                            : 'text-slate-400 hover:text-white hover:bg-white/5'
                    ]"
                >
                    <component :is="item.icon" class="w-4 h-4" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Active Event Info Card -->
            <div v-if="activeEvent" class="p-4 mx-4 mb-4 rounded-2xl bg-black/40 border border-white/10">
                <span class="text-[10px] text-amber-400 uppercase font-bold tracking-wider">Event Aktif</span>
                <p class="text-xs font-bold text-white truncate mt-0.5">{{ activeEvent.name }}</p>
                <p class="text-[10px] text-slate-400 truncate">{{ activeEvent.location || 'Studio Standar' }}</p>
            </div>

            <!-- User Footer -->
            <div class="p-4 border-t border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-400 text-xs font-bold">
                        {{ user?.name ? user.name[0] : 'A' }}
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-white truncate w-32">{{ user?.name || 'Admin' }}</p>
                        <p class="text-[10px] text-slate-400 uppercase">{{ user?.role || 'Administrator' }}</p>
                    </div>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="p-2 rounded-lg hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-colors"
                    title="Keluar"
                >
                    <LogOut class="w-4 h-4" />
                </Link>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 md:ml-72 flex flex-col min-h-screen">
            <!-- TOP NAVBAR -->
            <header class="hidden md:flex items-center justify-between px-8 py-4 bg-slate-900/50 backdrop-blur-md border-b border-white/10 sticky top-0 z-30">
                <div>
                    <span class="text-xs text-slate-400">Area Manajemen Sistem</span>
                    <h1 class="text-lg font-bold text-white">Panel Kontrol Photo Booth</h1>
                </div>

                <div class="flex items-center gap-4">
                    <DeviceStatusBadge :status="deviceStore.camera.status" label="Kamera" size="sm" />
                    <DeviceStatusBadge :status="deviceStore.printer.status" label="Printer" size="sm" />
                </div>
            </header>

            <!-- FLASH ALERTS -->
            <div v-if="page.props.flash?.message" class="mx-8 mt-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4" />
                <span>{{ page.props.flash.message }}</span>
            </div>

            <main class="flex-1 p-6 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>