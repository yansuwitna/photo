import { defineStore } from 'pinia';
import axios from 'axios';
import type { BoothSession, Template, SessionPhoto } from '@/types';
import { useAudioStore } from './audioStore';
import { showError } from '@/utils/swal';

export const useSessionStore = defineStore('session', {
    state: () => ({
        session: null as BoothSession | null,
        currentStep: 'start' as 'start' | 'template' | 'ready' | 'countdown' | 'capturing' | 'review' | 'composing' | 'final' | 'printing' | 'completed',
        countdown: 5,
        isCountingDown: false,
        activeSlot: 1,
        selectedTemplate: null as Template | null,
        printCopies: 1,
        printProgress: 0,
        isPrinting: false,
        isComposing: false,
        autoResetTimer: null as any,
        autoResetSeconds: 20,
    }),
    actions: {
        async startSession(templateId?: number, eventId?: number) {
            try {
                const res = await axios.post('/api/session/start', { template_id: templateId, event_id: eventId });
                if (res.data.success) {
                    this.session = res.data.session;
                    if (this.session?.template) {
                        this.selectedTemplate = this.session.template;
                    }
                    this.currentStep = templateId ? 'ready' : 'template';
                }
                return res.data;
            } catch (err: any) {
                console.error('Error starting session:', err);
                throw err;
            }
        },

        async selectTemplate(template: Template) {
            this.selectedTemplate = template;
            if (this.session) {
                const res = await axios.post(`/api/session/${this.session.id}/select-template`, {
                    template_id: template.id,
                });
                if (res.data.success) {
                    this.session = res.data.session;
                }
            }
            this.currentStep = 'ready';
        },

        async runCaptureSequence() {
            if (!this.session) return;
            const audio = useAudioStore();

            const totalPhotos = this.session.total_photos_required || 3;
            const currentCount = this.session.photos_captured_count || 0;

            if (currentCount >= totalPhotos) {
                this.currentStep = 'review';
                return;
            }

            this.activeSlot = currentCount + 1;
            await this.startCountdownAndCapture(this.activeSlot);
        },

        async startCountdownAndCapture(slotIndex: number) {
            const audio = useAudioStore();
            this.isCountingDown = true;
            this.countdown = this.session?.event?.countdown_seconds || 5;
            this.currentStep = 'countdown';

            audio.speakInstruction('Siapkan posisi Anda');

            return new Promise<void>((resolve) => {
                const interval = setInterval(async () => {
                    this.countdown -= 1;

                    if (this.countdown > 0 && this.countdown <= 3) {
                        audio.playCountdown(this.countdown);
                    } else if (this.countdown === 0) {
                        clearInterval(interval);
                        this.isCountingDown = false;
                        this.currentStep = 'capturing';

                        audio.playSmile();

                        // Sedikit jeda untuk efek senyum lalu shutter
                        setTimeout(async () => {
                            audio.playShutter();
                            await this.executeCapture(slotIndex);
                            resolve();
                        }, 500);
                    }
                }, 1000);
            });
        },

        async executeCapture(slotIndex: number) {
            if (!this.session) return;
            const audio = useAudioStore();

            try {
                const res = await axios.post(`/api/session/${this.session.id}/capture`, {
                    slot_index: slotIndex,
                });

                if (res.data.success) {
                    this.session = res.data.session;
                    audio.playSuccess();

                    if (res.data.is_complete) {
                        this.currentStep = 'review';
                        audio.speakInstruction('Silakan periksa foto-foto Anda');
                    } else {
                        // Lanjutkan ke slot berikutnya setelah jeda 2 detik
                        this.activeSlot = res.data.captured_count + 1;
                        setTimeout(() => {
                            this.runCaptureSequence();
                        }, 2000);
                    }
                }
            } catch (err) {
                console.error('Capture error:', err);
                showError('Gagal Mengambil Foto', 'Kamera tidak merespon. Silakan coba lagi.');
            }
        },

        async retakePhoto(slotIndex: number) {
            if (!this.session) return;
            this.activeSlot = slotIndex;
            await this.startCountdownAndCapture(slotIndex);
            this.currentStep = 'review';
        },

        async compose() {
            if (!this.session) return;
            this.isComposing = true;
            this.currentStep = 'composing';

            try {
                const res = await axios.post(`/api/session/${this.session.id}/compose`);
                if (res.data.success) {
                    this.session = res.data.session || {
                        ...this.session,
                        final_photo_path: res.data.file_path,
                        final_thumbnail_path: res.data.thumbnail_path,
                        status: 'ready_to_print',
                    };
                    this.currentStep = 'final';
                }
            } catch (err) {
                console.error('Compose error:', err);
            } finally {
                this.isComposing = false;
            }
        },

        async print(copies = 1) {
            if (!this.session) return;
            const audio = useAudioStore();
            this.isPrinting = true;
            this.printProgress = 10;
            this.currentStep = 'printing';

            // Animasi progress cetak
            const progressInterval = setInterval(() => {
                if (this.printProgress < 90) {
                    this.printProgress += 15;
                }
            }, 500);

            try {
                const res = await axios.post(`/api/session/${this.session.id}/print`, { copies });
                clearInterval(progressInterval);
                this.printProgress = 100;

                if (res.data.success) {
                    audio.playPrintDone();
                    setTimeout(() => {
                        this.isPrinting = false;
                        this.currentStep = 'completed';
                        this.startAutoReset();
                    }, 1200);
                } else {
                    this.isPrinting = false;
                    showError('Printer Error', res.data.message || 'Gagal mengirim dokumen ke printer.');
                }
            } catch (err: any) {
                clearInterval(progressInterval);
                this.isPrinting = false;
                showError('Printer Error', err.response?.data?.message || 'Gagal menghubungkan printer.');
            }
        },

        startAutoReset() {
            this.clearAutoReset();
            this.autoResetTimer = setTimeout(() => {
                this.resetSession();
            }, this.autoResetSeconds * 1000);
        },

        clearAutoReset() {
            if (this.autoResetTimer) {
                clearTimeout(this.autoResetTimer);
                this.autoResetTimer = null;
            }
        },

        resetSession() {
            this.clearAutoReset();
            this.session = null;
            this.selectedTemplate = null;
            this.currentStep = 'start';
            this.activeSlot = 1;
            this.printProgress = 0;
            this.isPrinting = false;
            this.isComposing = false;
        }
    }
});