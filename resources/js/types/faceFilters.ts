// Definitions and Canvas Renderers for BeautyPlus AR Face Filters (Screenshot 5 & 6)
export interface FaceFilterItem {
    id: string;
    name: string;
    category: 'Animal' | 'Cute' | 'Funny' | 'Aesthetic';
    badge?: string;
    description: string;
    draw: (ctx: CanvasRenderingContext2D, landmarks: { x: number; y: number; z?: number }[], width: number, height: number, mirror: boolean) => void;
}

// Helper math functions for face geometry
function getPoint(landmarks: { x: number; y: number }[], index: number, width: number, height: number, mirror: boolean) {
    if (!landmarks || !landmarks[index]) {
        return { x: width / 2, y: height * 0.35 };
    }
    const lm = landmarks[index];
    const x = mirror ? (1 - lm.x) * width : lm.x * width;
    const y = lm.y * height;
    return { x, y };
}

function getFaceScale(landmarks: { x: number; y: number }[], width: number, height: number) {
    if (!landmarks || landmarks.length < 455) return width * 0.35;
    // Distance between left and right cheek landmarks (234 and 454)
    const p1 = landmarks[234];
    const p2 = landmarks[454];
    const dx = (p2.x - p1.x) * width;
    const dy = (p2.y - p1.y) * height;
    return Math.sqrt(dx * dx + dy * dy);
}

// 12 Face Filters matching rev/5.png
export const FACE_FILTERS: FaceFilterItem[] = [
    {
        id: 'wild_boar',
        name: 'Wild Boar',
        category: 'Animal',
        description: 'Telinga babi hutan, bunga merah, moncong dengan taring & rona pipi (Identik Screenshot 5 & 6)',
        draw: (ctx, landmarks, width, height, mirror) => {
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const brow = getPoint(landmarks, 9, width, height, mirror);
            const leftCheek = getPoint(landmarks, 234, width, height, mirror);
            const rightCheek = getPoint(landmarks, 454, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();

            // 1. BOAR EARS (Telinga Kiri & Kanan)
            const earWidth = scale * 0.42;
            const earHeight = scale * 0.52;

            // Left Ear
            const leftEarX = forehead.x - scale * 0.38;
            const leftEarY = forehead.y - scale * 0.35;
            ctx.save();
            ctx.translate(leftEarX, leftEarY);
            ctx.rotate(-0.35);
            // Outer ear (tan/peach)
            ctx.beginPath();
            ctx.moveTo(0, earHeight * 0.7);
            ctx.quadraticCurveTo(-earWidth * 0.7, earHeight * 0.2, -earWidth * 0.4, -earHeight * 0.4);
            ctx.quadraticCurveTo(0, -earHeight * 0.65, earWidth * 0.45, -earHeight * 0.15);
            ctx.quadraticCurveTo(earWidth * 0.5, earHeight * 0.5, 0, earHeight * 0.7);
            ctx.fillStyle = '#f5a97f';
            ctx.fill();
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#c47853';
            ctx.stroke();
            // Inner ear (darker pink/tan)
            ctx.beginPath();
            ctx.moveTo(-earWidth * 0.1, earHeight * 0.4);
            ctx.quadraticCurveTo(-earWidth * 0.4, earHeight * 0.1, -earWidth * 0.2, -earHeight * 0.25);
            ctx.quadraticCurveTo(0, -earHeight * 0.4, earWidth * 0.2, -earHeight * 0.1);
            ctx.quadraticCurveTo(earWidth * 0.25, earHeight * 0.3, -earWidth * 0.1, earHeight * 0.4);
            ctx.fillStyle = '#cb7558';
            ctx.fill();
            ctx.restore();

            // Right Ear (with cute red flower)
            const rightEarX = forehead.x + scale * 0.38;
            const rightEarY = forehead.y - scale * 0.35;
            ctx.save();
            ctx.translate(rightEarX, rightEarY);
            ctx.rotate(0.35);
            // Outer ear
            ctx.beginPath();
            ctx.moveTo(0, earHeight * 0.7);
            ctx.quadraticCurveTo(earWidth * 0.7, earHeight * 0.2, earWidth * 0.4, -earHeight * 0.4);
            ctx.quadraticCurveTo(0, -earHeight * 0.65, -earWidth * 0.45, -earHeight * 0.15);
            ctx.quadraticCurveTo(-earWidth * 0.5, earHeight * 0.5, 0, earHeight * 0.7);
            ctx.fillStyle = '#f5a97f';
            ctx.fill();
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#c47853';
            ctx.stroke();
            // Inner ear
            ctx.beginPath();
            ctx.moveTo(earWidth * 0.1, earHeight * 0.4);
            ctx.quadraticCurveTo(earWidth * 0.4, earHeight * 0.1, earWidth * 0.2, -earHeight * 0.25);
            ctx.quadraticCurveTo(0, -earHeight * 0.4, -earWidth * 0.2, -earHeight * 0.1);
            ctx.quadraticCurveTo(-earWidth * 0.25, earHeight * 0.3, earWidth * 0.1, earHeight * 0.4);
            ctx.fillStyle = '#cb7558';
            ctx.fill();

            // Tiny cute red flower on right ear base (Screenshot 5 & 6)
            ctx.translate(-earWidth * 0.15, earHeight * 0.35);
            // Green leaf
            ctx.beginPath();
            ctx.ellipse(-12, -4, 10, 5, -0.6, 0, Math.PI * 2);
            ctx.fillStyle = '#22c55e';
            ctx.fill();
            // Red flower petals
            for (let i = 0; i < 5; i++) {
                const angle = (i * 2 * Math.PI) / 5;
                ctx.beginPath();
                ctx.arc(Math.cos(angle) * 8, Math.sin(angle) * 8, 6, 0, Math.PI * 2);
                ctx.fillStyle = '#ef4444';
                ctx.fill();
            }
            // Yellow center
            ctx.beginPath();
            ctx.arc(0, 0, 4.5, 0, Math.PI * 2);
            ctx.fillStyle = '#facc15';
            ctx.fill();
            ctx.restore();

            // 2. FOREHEAD SWEAT/ANGER MARKS (3 garis vertikal cokelat oranye)
            ctx.save();
            const markW = scale * 0.035;
            const markH = scale * 0.22;
            const markY = brow.y - scale * 0.18;
            ctx.fillStyle = '#b85d38';
            for (let i = -1; i <= 1; i++) {
                const mx = brow.x + i * (scale * 0.07);
                const my = markY + Math.abs(i) * 6;
                ctx.beginPath();
                ctx.ellipse(mx, my, markW * 0.6, markH * 0.5, i * 0.08, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();

            // 3. BOAR SNOUT & TUSKS (Moncong & Taring)
            ctx.save();
            const snoutW = scale * 0.38;
            const snoutH = scale * 0.28;
            const snoutY = nose.y + scale * 0.05;

            // White Tusks (kiri & kanan)
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth = 2;
            // Left Tusk
            ctx.beginPath();
            ctx.moveTo(nose.x - snoutW * 0.45, snoutY + snoutH * 0.1);
            ctx.quadraticCurveTo(nose.x - snoutW * 0.75, snoutY - snoutH * 0.25, nose.x - snoutW * 0.6, snoutY - snoutH * 0.45);
            ctx.quadraticCurveTo(nose.x - snoutW * 0.45, snoutY - snoutH * 0.15, nose.x - snoutW * 0.35, snoutY + snoutH * 0.2);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
            // Right Tusk
            ctx.beginPath();
            ctx.moveTo(nose.x + snoutW * 0.45, snoutY + snoutH * 0.1);
            ctx.quadraticCurveTo(nose.x + snoutW * 0.75, snoutY - snoutH * 0.25, nose.x + snoutW * 0.6, snoutY - snoutH * 0.45);
            ctx.quadraticCurveTo(nose.x + snoutW * 0.45, snoutY - snoutH * 0.15, nose.x + snoutW * 0.35, snoutY + snoutH * 0.2);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Snout Base (Peach / Tan Oval)
            ctx.beginPath();
            ctx.ellipse(nose.x, snoutY, snoutW * 0.5, snoutH * 0.5, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#e89a6a';
            ctx.fill();
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#c47853';
            ctx.stroke();

            // Snout Nostrils (2 lubang hidung oval coklat kemerahan)
            ctx.fillStyle = '#7d3a24';
            ctx.beginPath();
            ctx.ellipse(nose.x - snoutW * 0.18, snoutY + snoutH * 0.05, snoutW * 0.1, snoutH * 0.16, -0.15, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.ellipse(nose.x + snoutW * 0.18, snoutY + snoutH * 0.05, snoutW * 0.1, snoutH * 0.16, 0.15, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            // 4. CHEEK BLUSH (Garis-garis imut di pipi)
            ctx.save();
            ctx.strokeStyle = 'rgba(239, 68, 68, 0.75)';
            ctx.lineWidth = 3.5;
            ctx.lineCap = 'round';
            // Left cheek blush
            const lbx = leftCheek.x + scale * 0.08;
            const lby = leftCheek.y - scale * 0.02;
            for (let i = -1; i <= 1; i++) {
                ctx.beginPath();
                ctx.moveTo(lbx + i * 8 - 6, lby + 8);
                ctx.lineTo(lbx + i * 8 + 6, lby - 8);
                ctx.stroke();
            }
            // Right cheek blush
            const rbx = rightCheek.x - scale * 0.08;
            const rby = rightCheek.y - scale * 0.02;
            for (let i = -1; i <= 1; i++) {
                ctx.beginPath();
                ctx.moveTo(rbx + i * 8 - 6, rby + 8);
                ctx.lineTo(rbx + i * 8 + 6, rby - 8);
                ctx.stroke();
            }
            ctx.restore();

            ctx.restore();
        }
    },
    {
        id: 'cat_ear',
        name: 'Cat Ear',
        category: 'Cute',
        badge: '✨',
        description: 'Telinga kucing neon glowing pink bercahaya imut',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            const earW = scale * 0.32;
            const earH = scale * 0.42;

            // Neon Pink Glow Effect
            ctx.shadowColor = '#f43f5e';
            ctx.shadowBlur = 18;
            ctx.lineWidth = 5;
            ctx.strokeStyle = '#fda4af';
            ctx.fillStyle = 'rgba(244, 63, 94, 0.25)';

            // Left Cat Ear
            ctx.beginPath();
            ctx.moveTo(forehead.x - scale * 0.45, forehead.y - scale * 0.1);
            ctx.lineTo(forehead.x - scale * 0.35, forehead.y - scale * 0.55);
            ctx.lineTo(forehead.x - scale * 0.12, forehead.y - scale * 0.25);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Right Cat Ear
            ctx.beginPath();
            ctx.moveTo(forehead.x + scale * 0.12, forehead.y - scale * 0.25);
            ctx.lineTo(forehead.x + scale * 0.35, forehead.y - scale * 0.55);
            ctx.lineTo(forehead.x + scale * 0.45, forehead.y - scale * 0.1);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            ctx.restore();
        }
    },
    {
        id: 'purikura_cat',
        name: 'Purikura Cat',
        category: 'Cute',
        badge: 'b',
        description: 'Kucing purikura jepang dengan kumis & stempel プリクラ',
        draw: (ctx, landmarks, width, height, mirror) => {
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const leftCheek = getPoint(landmarks, 234, width, height, mirror);
            const rightCheek = getPoint(landmarks, 454, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            // Cat Ears
            ctx.fillStyle = '#0f172a';
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#000000';

            // Left Ear
            ctx.beginPath();
            ctx.moveTo(forehead.x - scale * 0.4, forehead.y - scale * 0.15);
            ctx.lineTo(forehead.x - scale * 0.32, forehead.y - scale * 0.5);
            ctx.lineTo(forehead.x - scale * 0.12, forehead.y - scale * 0.22);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Right Ear
            ctx.beginPath();
            ctx.moveTo(forehead.x + scale * 0.12, forehead.y - scale * 0.22);
            ctx.lineTo(forehead.x + scale * 0.32, forehead.y - scale * 0.5);
            ctx.lineTo(forehead.x + scale * 0.4, forehead.y - scale * 0.15);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Whiskers (3 whiskers per cheek)
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 3.5;
            ctx.lineCap = 'round';
            for (let i = -1; i <= 1; i++) {
                // Left
                ctx.beginPath();
                ctx.moveTo(leftCheek.x + scale * 0.05, leftCheek.y + i * 12);
                ctx.lineTo(leftCheek.x - scale * 0.25, leftCheek.y + i * 18);
                ctx.stroke();
                // Right
                ctx.beginPath();
                ctx.moveTo(rightCheek.x - scale * 0.05, rightCheek.y + i * 12);
                ctx.lineTo(rightCheek.x + scale * 0.25, rightCheek.y + i * 18);
                ctx.stroke();
            }

            // Cute Heart Nose
            ctx.fillStyle = '#f43f5e';
            ctx.beginPath();
            ctx.arc(nose.x, nose.y, scale * 0.06, 0, Math.PI * 2);
            ctx.fill();

            // Purikura badge text
            ctx.font = 'bold 20px sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.textAlign = 'center';
            ctx.fillText('プリクラ', forehead.x, forehead.y - scale * 0.6);
            ctx.restore();
        }
    },
    {
        id: 'bunny',
        name: 'Bunny',
        category: 'Animal',
        badge: 'b',
        description: 'Telinga kelinci putih imut, hidung pink & rona pipi',
        draw: (ctx, landmarks, width, height, mirror) => {
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            const earW = scale * 0.22;
            const earH = scale * 0.7;

            // Left Bunny Ear (White with pink inside)
            ctx.save();
            ctx.translate(forehead.x - scale * 0.25, forehead.y - scale * 0.4);
            ctx.rotate(-0.15);
            ctx.beginPath();
            ctx.ellipse(0, 0, earW * 0.5, earH * 0.5, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#e2e8f0';
            ctx.stroke();
            // Pink inside
            ctx.beginPath();
            ctx.ellipse(0, 0, earW * 0.28, earH * 0.38, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#fbcfe8';
            ctx.fill();
            ctx.restore();

            // Right Bunny Ear
            ctx.save();
            ctx.translate(forehead.x + scale * 0.25, forehead.y - scale * 0.4);
            ctx.rotate(0.15);
            ctx.beginPath();
            ctx.ellipse(0, 0, earW * 0.5, earH * 0.5, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#e2e8f0';
            ctx.stroke();
            // Pink inside
            ctx.beginPath();
            ctx.ellipse(0, 0, earW * 0.28, earH * 0.38, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#fbcfe8';
            ctx.fill();
            ctx.restore();

            // Bunny Nose & Mouth
            ctx.fillStyle = '#f472b6';
            ctx.beginPath();
            ctx.arc(nose.x, nose.y, scale * 0.05, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    },
    {
        id: 'pink_bows',
        name: 'Pink Bows',
        category: 'Cute',
        badge: 'b',
        description: 'Pita satin pink cantik di kiri dan kanan rambut',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            const drawBow = (cx: number, cy: number, rot: number) => {
                ctx.save();
                ctx.translate(cx, cy);
                ctx.rotate(rot);
                const s = scale * 0.16;

                // Left loop
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.bezierCurveTo(-s * 1.5, -s * 1.2, -s * 1.8, s * 1.2, 0, 0);
                ctx.fillStyle = '#f472b6';
                ctx.fill();
                ctx.strokeStyle = '#db2777';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Right loop
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.bezierCurveTo(s * 1.5, -s * 1.2, s * 1.8, s * 1.2, 0, 0);
                ctx.fillStyle = '#f472b6';
                ctx.fill();
                ctx.stroke();

                // Ribbons hanging down
                ctx.beginPath();
                ctx.moveTo(-s * 0.3, s * 0.2);
                ctx.quadraticCurveTo(-s * 0.8, s * 1.8, -s * 1.1, s * 2.2);
                ctx.lineTo(-s * 0.6, s * 2.1);
                ctx.lineTo(0, s * 0.3);
                ctx.fillStyle = '#ec4899';
                ctx.fill();

                ctx.beginPath();
                ctx.moveTo(s * 0.3, s * 0.2);
                ctx.quadraticCurveTo(s * 0.8, s * 1.8, s * 1.1, s * 2.2);
                ctx.lineTo(s * 0.6, s * 2.1);
                ctx.lineTo(0, s * 0.3);
                ctx.fillStyle = '#ec4899';
                ctx.fill();

                // Center knot
                ctx.beginPath();
                ctx.arc(0, 0, s * 0.35, 0, Math.PI * 2);
                ctx.fillStyle = '#fda4af';
                ctx.fill();
                ctx.stroke();
                ctx.restore();
            };

            drawBow(forehead.x - scale * 0.42, forehead.y - scale * 0.2, -0.25);
            drawBow(forehead.x + scale * 0.42, forehead.y - scale * 0.2, 0.25);
        }
    },
    {
        id: 'blue_ribbons',
        name: 'Blue Ribbons',
        category: 'Cute',
        badge: 'b',
        description: 'Pita satin biru muda elegan dengan pita menjuntai',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            const drawBlueBow = (cx: number, cy: number) => {
                ctx.save();
                ctx.translate(cx, cy);
                const s = scale * 0.16;

                ctx.fillStyle = '#7dd3fc';
                ctx.strokeStyle = '#0284c7';
                ctx.lineWidth = 2;

                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.bezierCurveTo(-s * 1.5, -s * 1.2, -s * 1.8, s * 1.2, 0, 0);
                ctx.fill();
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.bezierCurveTo(s * 1.5, -s * 1.2, s * 1.8, s * 1.2, 0, 0);
                ctx.fill();
                ctx.stroke();

                // Tails
                ctx.beginPath();
                ctx.moveTo(-s * 0.2, s * 0.2);
                ctx.quadraticCurveTo(-s * 0.6, s * 2.2, -s * 0.8, s * 2.8);
                ctx.lineWidth = 4;
                ctx.strokeStyle = '#38bdf8';
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(s * 0.2, s * 0.2);
                ctx.quadraticCurveTo(s * 0.6, s * 2.2, s * 0.8, s * 2.8);
                ctx.stroke();

                // Knot
                ctx.beginPath();
                ctx.arc(0, 0, s * 0.35, 0, Math.PI * 2);
                ctx.fillStyle = '#bae6fd';
                ctx.fill();
                ctx.restore();
            };

            drawBlueBow(forehead.x - scale * 0.42, forehead.y - scale * 0.2);
            drawBlueBow(forehead.x + scale * 0.42, forehead.y - scale * 0.2);
        }
    },
    {
        id: 'groucho_glasses',
        name: 'Groucho Glasses',
        category: 'Funny',
        badge: 'b',
        description: 'Kacamata tebal, hidung palsu & kumis lebat lucu',
        draw: (ctx, landmarks, width, height, mirror) => {
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            const glassR = scale * 0.18;
            const glassY = nose.y - scale * 0.18;

            // Spectacle Frames
            ctx.lineWidth = 6;
            ctx.strokeStyle = '#0f172a';
            ctx.fillStyle = 'rgba(255,255,255,0.1)';

            // Left lens
            ctx.beginPath();
            ctx.arc(nose.x - scale * 0.22, glassY, glassR, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            // Right lens
            ctx.beginPath();
            ctx.arc(nose.x + scale * 0.22, glassY, glassR, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            // Bridge
            ctx.beginPath();
            ctx.arc(nose.x, glassY - glassR * 0.4, scale * 0.1, Math.PI, 0);
            ctx.stroke();

            // Big Fake Nose
            ctx.beginPath();
            ctx.ellipse(nose.x, nose.y + scale * 0.02, scale * 0.16, scale * 0.22, 0, 0, Math.PI * 2);
            ctx.fillStyle = '#fbcfe8';
            ctx.fill();
            ctx.strokeStyle = '#f472b6';
            ctx.lineWidth = 2.5;
            ctx.stroke();

            // Bushy Mustache
            ctx.fillStyle = '#0f172a';
            const mustY = nose.y + scale * 0.18;
            ctx.beginPath();
            ctx.moveTo(nose.x, mustY);
            ctx.quadraticCurveTo(nose.x - scale * 0.35, mustY - scale * 0.05, nose.x - scale * 0.4, mustY + scale * 0.16);
            ctx.quadraticCurveTo(nose.x - scale * 0.2, mustY + scale * 0.1, nose.x, mustY + scale * 0.06);
            ctx.quadraticCurveTo(nose.x + scale * 0.2, mustY + scale * 0.1, nose.x + scale * 0.4, mustY + scale * 0.16);
            ctx.quadraticCurveTo(nose.x + scale * 0.35, mustY - scale * 0.05, nose.x, mustY);
            ctx.fill();
            ctx.restore();
        }
    },
    {
        id: 'demon_kitty',
        name: 'Demon Kitty',
        category: 'Cute',
        description: 'Tanduk iblis hitam kecil, moncong kucing & sayap',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            ctx.fillStyle = '#0f172a';
            ctx.strokeStyle = '#ef4444';
            ctx.lineWidth = 2.5;

            // Left Horn
            ctx.beginPath();
            ctx.moveTo(forehead.x - scale * 0.35, forehead.y - scale * 0.1);
            ctx.quadraticCurveTo(forehead.x - scale * 0.45, forehead.y - scale * 0.45, forehead.x - scale * 0.25, forehead.y - scale * 0.5);
            ctx.quadraticCurveTo(forehead.x - scale * 0.25, forehead.y - scale * 0.3, forehead.x - scale * 0.15, forehead.y - scale * 0.12);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Right Horn
            ctx.beginPath();
            ctx.moveTo(forehead.x + scale * 0.35, forehead.y - scale * 0.1);
            ctx.quadraticCurveTo(forehead.x + scale * 0.45, forehead.y - scale * 0.45, forehead.x + scale * 0.25, forehead.y - scale * 0.5);
            ctx.quadraticCurveTo(forehead.x + scale * 0.25, forehead.y - scale * 0.3, forehead.x + scale * 0.15, forehead.y - scale * 0.12);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Mini Cat Snout
            ctx.fillStyle = '#ef4444';
            ctx.beginPath();
            ctx.arc(nose.x, nose.y, scale * 0.04, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    },
    {
        id: 'reicore3',
        name: 'Reicore 3',
        category: 'Aesthetic',
        badge: 'b',
        description: 'Bintang berkilau aesthetic anime & hiasan jepit rambut',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const leftCheek = getPoint(landmarks, 234, width, height, mirror);
            const rightCheek = getPoint(landmarks, 454, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            const drawSparkle = (cx: number, cy: number, r: number, color: string) => {
                ctx.save();
                ctx.translate(cx, cy);
                ctx.fillStyle = color;
                ctx.beginPath();
                for (let i = 0; i < 4; i++) {
                    const angle = (i * Math.PI) / 2;
                    ctx.lineTo(Math.cos(angle) * r, Math.sin(angle) * r);
                    const innerAngle = angle + Math.PI / 4;
                    ctx.lineTo(Math.cos(innerAngle) * (r * 0.25), Math.sin(innerAngle) * (r * 0.25));
                }
                ctx.closePath();
                ctx.fill();
                ctx.restore();
            };

            ctx.save();
            drawSparkle(forehead.x - scale * 0.3, forehead.y - scale * 0.15, 16, '#fef08a');
            drawSparkle(forehead.x + scale * 0.3, forehead.y - scale * 0.18, 20, '#fbcfe8');
            drawSparkle(leftCheek.x + scale * 0.1, leftCheek.y, 12, '#bae6fd');
            drawSparkle(rightCheek.x - scale * 0.1, rightCheek.y, 14, '#fef08a');
            ctx.restore();
        }
    },
    {
        id: 'reicore4',
        name: 'Reicore 4',
        category: 'Aesthetic',
        badge: 'b',
        description: 'Kilauan permata bintang & rona pastel dreamy',
        draw: (ctx, landmarks, width, height, mirror) => {
            const forehead = getPoint(landmarks, 10, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            const drawStar = (x: number, y: number, s: number, col: string) => {
                ctx.fillStyle = col;
                ctx.beginPath();
                ctx.arc(x, y, s, 0, Math.PI * 2);
                ctx.fill();
            };
            drawStar(forehead.x - scale * 0.35, forehead.y - scale * 0.2, 8, '#f472b6');
            drawStar(forehead.x + scale * 0.35, forehead.y - scale * 0.2, 10, '#38bdf8');
            drawStar(forehead.x, forehead.y - scale * 0.35, 7, '#facc15');
            ctx.restore();
        }
    },
    {
        id: 'funny_face',
        name: 'Funny Face',
        category: 'Funny',
        description: 'Mata kartun komedi besar & alis ekspresif',
        draw: (ctx, landmarks, width, height, mirror) => {
            const brow = getPoint(landmarks, 9, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 4;

            // Big Goofy Eyeballs
            const eyeY = brow.y + scale * 0.05;
            ctx.beginPath();
            ctx.arc(brow.x - scale * 0.22, eyeY, scale * 0.14, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(brow.x + scale * 0.22, eyeY, scale * 0.14, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            // Pupils
            ctx.fillStyle = '#0f172a';
            ctx.beginPath();
            ctx.arc(brow.x - scale * 0.2, eyeY, scale * 0.05, 0, Math.PI * 2);
            ctx.arc(brow.x + scale * 0.24, eyeY, scale * 0.05, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    },
    {
        id: 'funny_face2',
        name: 'Funny Face2',
        category: 'Funny',
        description: 'Distorsi senyum lebar kartun & pipi gembul',
        draw: (ctx, landmarks, width, height, mirror) => {
            const nose = getPoint(landmarks, 1, width, height, mirror);
            const scale = getFaceScale(landmarks, width, height);

            ctx.save();
            ctx.fillStyle = 'rgba(239, 68, 68, 0.4)';
            ctx.beginPath();
            ctx.arc(nose.x - scale * 0.35, nose.y + scale * 0.1, scale * 0.14, 0, Math.PI * 2);
            ctx.arc(nose.x + scale * 0.35, nose.y + scale * 0.1, scale * 0.14, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }
];
