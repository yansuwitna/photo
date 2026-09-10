import { defineStore } from 'pinia';
import axios from 'axios';
import type { Camera, Printer, Device } from '@/types';

export const useDeviceStore = defineStore('device', {
    state: () => ({
        camera: {
            name: 'Canon EOS R6 (Simulated)',
            status: 'ready' as 'ready' | 'busy' | 'error' | 'disconnected',
            battery_level: 94,
            storage_remaining: '48 GB Free',
            iso: '400',
            shutter_speed: '1/160',
            aperture: 'f/2.8',
            live_view: false,
        },
        printer: {
            name: 'DNP DS-RX1HS',
            status: 'ready' as 'ready' | 'printing' | 'paper_empty' | 'error' | 'disconnected',
            paper_remaining: 380,
            paper_status: 'Normal',
            queue_count: 0,
            default_paper_size: '4R',
        },
        display: {
            status: 'connected',
            touchscreen: true,
            kiosk_mode: false,
        },
        isChecking: false,
        isLocked: false,
        activeCamera: null as any,
        activePrinter: null as any,
        cameras: [] as any[],
        printers: [] as any[],
    }),
    actions: {
        async fetchStatus() {
            try {
                this.isChecking = true;
                const res = await axios.get('/api/devices/overview');
                if (res.data) {
                    if (res.data.camera) {
                        this.camera = { ...this.camera, ...res.data.camera };
                    }
                    if (res.data.printer) {
                        this.printer = { ...this.printer, ...res.data.printer };
                    }
                }
            } catch (err) {
                console.warn('Gagal memuat status perangkat:', err);
            } finally {
                this.isChecking = false;
            }
        },

        async fetchSettings() {
            try {
                const res = await axios.get('/api/devices/settings');
                if (res.data && res.data.success) {
                    this.isLocked = res.data.is_locked;
                    this.cameras = res.data.cameras || [];
                    this.printers = res.data.printers || [];
                    this.activeCamera = res.data.active_camera;
                    this.activePrinter = res.data.active_printer;
                    if (res.data.active_camera) {
                        this.camera = { ...this.camera, ...res.data.active_camera };
                    }
                    if (res.data.active_printer) {
                        this.printer = { ...this.printer, ...res.data.active_printer };
                    }
                    return res.data;
                }
            } catch (err) {
                console.warn('Gagal memuat pengaturan perangkat:', err);
            }
        },

        async selectDevices(cameraId?: number, printerId?: number, paperSize?: string) {
            try {
                const res = await axios.post('/api/devices/select', {
                    camera_id: cameraId,
                    printer_id: printerId,
                    paper_size: paperSize,
                });
                if (res.data && res.data.success) {
                    await this.fetchSettings();
                    return { success: true, message: res.data.message };
                }
                return { success: false, message: res.data.message || 'Gagal menyimpan perangkat' };
            } catch (err: any) {
                return {
                    success: false,
                    message: err.response?.data?.message || 'Gagal menyimpan pengaturan perangkat',
                };
            }
        },

        async lock() {
            try {
                const res = await axios.post('/api/devices/lock');
                if (res.data && res.data.success) {
                    this.isLocked = true;
                    return { success: true, message: res.data.message };
                }
                return { success: false, message: 'Gagal mengunci perangkat' };
            } catch (err: any) {
                return {
                    success: false,
                    message: err.response?.data?.message || 'Gagal mengunci pengaturan perangkat',
                };
            }
        },

        async unlock(pin: string) {
            try {
                const res = await axios.post('/api/devices/unlock', { pin });
                if (res.data && res.data.success) {
                    this.isLocked = false;
                    return { success: true, message: res.data.message };
                }
                return { success: false, message: 'PIN salah' };
            } catch (err: any) {
                return {
                    success: false,
                    message: err.response?.data?.message || 'PIN yang dimasukkan salah',
                };
            }
        },

        async syncPrinters() {
            try {
                const res = await axios.post('/api/devices/sync-printers');
                if (res.data && res.data.success) {
                    await this.fetchSettings();
                    return { success: true, message: res.data.message };
                }
                return { success: false, message: 'Gagal sinkronisasi printer' };
            } catch (err: any) {
                return {
                    success: false,
                    message: err.response?.data?.message || 'Gagal sinkronisasi printer sistem',
                };
            }
        },

        async testCamera() {
            try {
                const res = await axios.post('/api/camera/test');
                await this.fetchStatus();
                return res.data;
            } catch (err: any) {
                return { success: false, message: err.response?.data?.message || 'Uji coba kamera gagal' };
            }
        },

        async testPrinter() {
            try {
                const res = await axios.post('/api/printer/test');
                await this.fetchStatus();
                return res.data;
            } catch (err: any) {
                return { success: false, message: err.response?.data?.message || 'Uji coba printer gagal' };
            }
        },

        async setCameraParam(key: string, value: string) {
            try {
                const res = await axios.post('/api/camera/settings', { key, value });
                await this.fetchStatus();
                return res.data;
            } catch (err) {
                return { success: false };
            }
        }
    }
});