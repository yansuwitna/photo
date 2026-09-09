import { defineStore } from 'pinia';

export const useAudioStore = defineStore('audio', {
    state: () => ({
        volume: 0.85,
        enabled: true,
        voiceGuidance: true,
        audioContext: null as AudioContext | null,
    }),
    actions: {
        initContext() {
            if (!this.audioContext && typeof window !== 'undefined') {
                const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
                if (AudioCtx) {
                    this.audioContext = new AudioCtx();
                }
            }
            if (this.audioContext && this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }
        },

        playBeep(freq = 880, duration = 0.15, type: OscillatorType = 'sine') {
            if (!this.enabled) return;
            try {
                this.initContext();
                if (!this.audioContext) return;

                const osc = this.audioContext.createOscillator();
                const gain = this.audioContext.createGain();

                osc.type = type;
                osc.frequency.setValueAtTime(freq, this.audioContext.currentTime);

                gain.gain.setValueAtTime(this.volume * 0.4, this.audioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration);

                osc.connect(gain);
                gain.connect(this.audioContext.destination);

                osc.start();
                osc.stop(this.audioContext.currentTime + duration);
            } catch (e) {
                console.warn('Audio play error:', e);
            }
        },

        playCountdown(step: number) {
            if (!this.enabled) return;
            // Nada naik bertahap untuk 3, 2, 1
            const freqs: Record<number, number> = { 3: 523.25, 2: 659.25, 1: 783.99 }; // C5, E5, G5
            const f = freqs[step] || 600;
            this.playBeep(f, 0.2, 'triangle');

            if (this.voiceGuidance && 'speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(step.toString());
                utterance.lang = 'id-ID';
                utterance.rate = 1.1;
                window.speechSynthesis.speak(utterance);
            }
        },

        playShutter() {
            if (!this.enabled) return;
            try {
                this.initContext();
                if (!this.audioContext) return;

                const now = this.audioContext.currentTime;

                // Suara klik mekanis kamera (noise burst + bandpass)
                const bufferSize = this.audioContext.sampleRate * 0.12;
                const buffer = this.audioContext.createBuffer(1, bufferSize, this.audioContext.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    data[i] = Math.random() * 2 - 1;
                }

                const noise = this.audioContext.createBufferSource();
                noise.buffer = buffer;

                const filter = this.audioContext.createBiquadFilter();
                filter.type = 'bandpass';
                filter.frequency.setValueAtTime(1200, now);
                filter.Q.setValueAtTime(3, now);

                const gain = this.audioContext.createGain();
                gain.gain.setValueAtTime(this.volume * 0.8, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);

                noise.connect(filter);
                filter.connect(gain);
                gain.connect(this.audioContext.destination);

                noise.start(now);
            } catch (e) {
                this.playBeep(1200, 0.1, 'square');
            }
        },

        playSmile() {
            if (!this.enabled) return;
            this.playBeep(1046.5, 0.3, 'sine'); // C6 ring
            if (this.voiceGuidance && 'speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance("Senyum!");
                utterance.lang = 'id-ID';
                utterance.pitch = 1.2;
                window.speechSynthesis.speak(utterance);
            }
        },

        playSuccess() {
            if (!this.enabled) return;
            // Arpeggio nada gembira
            [523.25, 659.25, 783.99, 1046.5].forEach((freq, idx) => {
                setTimeout(() => {
                    this.playBeep(freq, 0.25, 'sine');
                }, idx * 100);
            });
        },

        playPrintDone() {
            if (!this.enabled) return;
            this.playBeep(880, 0.2, 'sine');
            setTimeout(() => {
                this.playBeep(1174.66, 0.3, 'sine');
            }, 180);
        },

        speakInstruction(text: string) {
            if (!this.enabled || !this.voiceGuidance || !('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 1.0;
            window.speechSynthesis.speak(utterance);
        }
    }
});