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
        },
        display: {
            status: 'connected',
            touchscreen: true,
            kiosk_mode: false,
        },
        isChecking: false,
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