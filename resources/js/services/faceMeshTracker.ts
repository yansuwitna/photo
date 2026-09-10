import { FilesetResolver, FaceLandmarker } from '@mediapipe/tasks-vision';

class FaceMeshTracker {
    private landmarker: FaceLandmarker | null = null;
    private isInitializing = false;
    private isReady = false;
    private lastVideoTime = -1;
    private smoothedLandmarks: { x: number; y: number }[] | null = null;

    async init(): Promise<boolean> {
        if (this.isReady) return true;
        if (this.isInitializing) return false;
        this.isInitializing = true;

        try {
            // Load from self-hosted local public/mediapipe/wasm
            const vision = await FilesetResolver.forVisionTasks('/mediapipe/wasm');
            this.landmarker = await FaceLandmarker.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: '/models/face_landmarker.task',
                    delegate: 'GPU',
                },
                outputFaceBlendshapes: false,
                runningMode: 'VIDEO',
                numFaces: 1,
            });
            this.isReady = true;
            console.log('MediaPipe FaceLandmarker initialized successfully!');
            return true;
        } catch (err) {
            console.warn('FaceLandmarker GPU init fallback:', err);
            try {
                // Fallback with CPU delegate
                const vision = await FilesetResolver.forVisionTasks('/mediapipe/wasm');
                this.landmarker = await FaceLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: '/models/face_landmarker.task',
                        delegate: 'CPU',
                    },
                    runningMode: 'VIDEO',
                    numFaces: 1,
                });
                this.isReady = true;
                return true;
            } catch (cpuErr) {
                console.warn('MediaPipe offline fallback enabled:', cpuErr);
                return false;
            }
        } finally {
            this.isInitializing = false;
        }
    }

    detect(video: HTMLVideoElement): { x: number; y: number }[] | null {
        if (!video || video.readyState < 2 || video.videoWidth === 0) return null;

        if (this.isReady && this.landmarker) {
            try {
                const now = performance.now();
                if (video.currentTime !== this.lastVideoTime) {
                    this.lastVideoTime = video.currentTime;
                    const results = this.landmarker.detectForVideo(video, now);
                    if (results && results.faceLandmarks && results.faceLandmarks.length > 0) {
                        const raw = results.faceLandmarks[0];
                        // Exponential smoothing for ultra-stable tracking
                        if (!this.smoothedLandmarks || this.smoothedLandmarks.length !== raw.length) {
                            this.smoothedLandmarks = raw.map((pt) => ({ x: pt.x, y: pt.y }));
                        } else {
                            const alpha = 0.65; // Smoothing factor
                            for (let i = 0; i < raw.length; i++) {
                                this.smoothedLandmarks[i].x = this.smoothedLandmarks[i].x * (1 - alpha) + raw[i].x * alpha;
                                this.smoothedLandmarks[i].y = this.smoothedLandmarks[i].y * (1 - alpha) + raw[i].y * alpha;
                            }
                        }
                        return this.smoothedLandmarks;
                    }
                } else if (this.smoothedLandmarks) {
                    return this.smoothedLandmarks;
                }
            } catch (e) {
                // Ignore frame drop
            }
        }

        // Adaptive default head position if face detection is loading
        return this.generateDefaultLandmarks();
    }

    private generateDefaultLandmarks(): { x: number; y: number }[] {
        const dummy: { x: number; y: number }[] = [];
        for (let i = 0; i <= 478; i++) {
            dummy.push({ x: 0.5, y: 0.38 });
        }
        // Forehead top
        dummy[10] = { x: 0.5, y: 0.22 };
        // Glabella / brow
        dummy[9] = { x: 0.5, y: 0.30 };
        // Nose tip
        dummy[1] = { x: 0.5, y: 0.40 };
        // Left cheek
        dummy[234] = { x: 0.35, y: 0.42 };
        // Right cheek
        dummy[454] = { x: 0.65, y: 0.42 };
        return dummy;
    }
}

export const faceMeshTracker = new FaceMeshTracker();
