document.addEventListener('DOMContentLoaded', () => {
    // Style configurations
    const PRESETS = {
        'style_1': { // Classic Gold Stars
            chars: ['★', '✨', '✦', '⭐', '✯', '✵'], colors: ['#FFD700', '#FFA500', '#FFF8DC', '#FFFF00'],
            size: [12, 22], moveX: [-50, 50], moveY: [30, 80], rot: [-180, 180],
            anim: 'magicCursorFall', duration: 1000, throttle: 30, glow: true, shape: 'text'
        },
        'style_2': { // Magic Pixie Dust
            chars: ['•', '·', '✦'], colors: ['#00FFFF', '#FF00FF', '#00FF00', '#FFFF00', '#FFF'],
            size: [8, 16], moveX: [-40, 40], moveY: [-20, 40], rot: [-90, 90],
            anim: 'magicCursorPop', duration: 800, throttle: 20, glow: true, shape: 'text'
        },
        'style_3': { // Bubble Stream
            chars: ['⚪', '○', '🫧', '°'], colors: ['#E0FFFF', '#F0FFFF', '#87CEFA', '#FFFFFF'],
            size: [10, 25], moveX: [-30, 30], moveY: [-100, -40], rot: [-45, 45],
            anim: 'magicCursorFloat', duration: 1500, throttle: 40, glow: false, shape: 'text'
        },
        'style_4': { // Crimson Hearts
            chars: ['❤', '💗', '💖', '💕'], colors: ['#FF69B4', '#FF1493', '#DC143C', '#FF0000'],
            size: [15, 25], moveX: [-40, 40], moveY: [-80, -30], rot: [-30, 30],
            anim: 'magicCursorFloat', duration: 1200, throttle: 40, glow: true, shape: 'text'
        },
        'style_5': { // Winter Snow
            chars: ['❄', '❅', '❆', '•'], colors: ['#FFFFFF', '#E0FFFF', '#F0F8FF'],
            size: [10, 20], moveX: [-60, 60], moveY: [40, 100], rot: [-360, 360],
            anim: 'magicCursorDrift', duration: 2000, throttle: 50, glow: true, shape: 'text'
        },
        'style_6': { // Fireworks Sparks
            chars: ['✦', '*', '·'], colors: ['#FF4500', '#FFD700', '#00FF00', '#1E90FF', '#FF1493'],
            size: [12, 24], moveX: [-100, 100], moveY: [-100, 100], rot: [-180, 180],
            anim: 'magicCursorPop', duration: 800, throttle: 25, glow: true, shape: 'text'
        },
        'style_7': { // Autumn Leaves
            chars: ['🍂', '🍁'], colors: [], // fallback to native emoji
            size: [16, 26], moveX: [-80, 80], moveY: [40, 120], rot: [-180, 180],
            anim: 'magicCursorDrift', duration: 1800, throttle: 60, glow: false, shape: 'text'
        },
        'style_8': { // Matrix Rain
            chars: ['0', '1'], colors: ['#00FF41', '#008F11'],
            size: [14, 20], moveX: [0, 0], moveY: [60, 150], rot: [0, 0],
            anim: 'magicCursorDrop', duration: 1000, throttle: 35, glow: true, shape: 'text'
        },
        'style_9': { // Neon Pulse Orbs
            chars: [], colors: ['#00FFFF', '#FF00FF', '#FFFF00', '#39FF14'],
            size: [10, 20], moveX: [-30, 30], moveY: [-50, -10], rot: [0, 0],
            anim: 'magicCursorPulse', duration: 1600, throttle: 45, glow: false, shape: 'circle'
        },
        'style_10': { // Cherry Blossoms (Sakura)
            chars: ['🌸'], colors: [],
            size: [14, 24], moveX: [-100, 100], moveY: [30, 80], rot: [-360, 360],
            anim: 'magicCursorDrift', duration: 2200, throttle: 55, glow: false, shape: 'text'
        },
        'style_11': { // Lightning Zaps
            chars: ['⚡'], colors: [],
            size: [15, 25], moveX: [-50, 50], moveY: [30, 100], rot: [-45, 45],
            anim: 'magicCursorPop', duration: 600, throttle: 35, glow: true, shape: 'text'
        },
        'style_12': { // Confetti Party
            chars: ['▮', '▬', '■'], colors: ['#FF3366', '#33CCFF', '#FFFF66', '#33FF66', '#FF9933'],
            size: [8, 16], moveX: [-80, 80], moveY: [40, 150], rot: [-720, 720],
            anim: 'magicCursorFall', duration: 1500, throttle: 30, glow: false, shape: 'text'
        },
        'style_13': { // Musical Notes
            chars: ['♪', '♫', '♬', '♩'], colors: ['#333333', '#111111', '#555555'],
            size: [16, 26], moveX: [-50, 50], moveY: [-100, -40], rot: [-30, 30],
            anim: 'magicCursorFloat', duration: 1800, throttle: 50, glow: false, shape: 'text'
        },
        'style_14': { // Campfire Embers
            chars: ['•'], colors: ['#FF4500', '#FF8C00', '#FF0000', '#FFFF00'],
            size: [8, 18], moveX: [-60, 60], moveY: [-120, -50], rot: [0, 0],
            anim: 'magicCursorDrift', duration: 1400, throttle: 30, glow: true, shape: 'text'
        },
        'style_15': { // Ice Crystal/Diamonds
            chars: ['♦', '♢', '💎'], colors: ['#E0FFFF', '#B0E0E6', '#ADD8E6'],
            size: [12, 22], moveX: [-40, 40], moveY: [40, 100], rot: [-360, 360],
            anim: 'magicCursorFall', duration: 1300, throttle: 40, glow: true, shape: 'text'
        },
        'style_16': { // Cyberpunk Geometry
            chars: ['△', '○', '□'], colors: ['#FF003C', '#00E5FF', '#FCEE09'],
            size: [12, 20], moveX: [-60, 60], moveY: [40, 90], rot: [-180, 180],
            anim: 'magicCursorDrift', duration: 1100, throttle: 40, glow: true, shape: 'text'
        },
        'style_17': { // Retro 8-Bit Pixels
            chars: [], colors: ['#FFF', '#F00', '#0F0', '#00F', '#FF0'],
            size: [8, 12], moveX: [-40, 40], moveY: [40, 80], rot: [0, 0],
            anim: 'magicCursorDrop', duration: 800, throttle: 30, glow: false, shape: 'square'
        },
        'style_18': { // Ghost Wisps
            chars: ['☁', '•'], colors: ['rgba(138,43,226,0.5)', 'rgba(0,255,127,0.5)', 'rgba(75,0,130,0.5)'],
            size: [20, 40], moveX: [-40, 40], moveY: [-100, -30], rot: [-45, 45],
            anim: 'magicCursorFloat', duration: 2500, throttle: 60, glow: true, shape: 'text'
        },
        'style_19': { // Shooting Stars
            chars: [''], colors: ['#FFF', '#FFFACD', '#E0FFFF'],
            size: [3, 8], moveX: [100, 200], moveY: [100, 200], rot: [45, 45], // Fixed rotation matching movement
            anim: 'magicCursorShoot', duration: 600, throttle: 30, glow: true, shape: 'circle'
        },
        'style_20': { // Rainbow Sparkles
            chars: ['✨', '❇', '✴'], colors: ['#FF0000', '#FF7F00', '#FFFF00', '#00FF00', '#0000FF', '#4B0082', '#9400D3'],
            size: [14, 26], moveX: [-50, 50], moveY: [-50, 50], rot: [-180, 180],
            anim: 'magicCursorPop', duration: 1100, throttle: 30, glow: true, shape: 'text'
        }
    };

    let lastX = -1000, lastY = -1000;

    const randomRange = (min, max) => Math.random() * (max - min) + min;

    const spawnParticle = (x, y) => {
        // Fetch config dynamically for LIVE PREVIEW
        const optStyle = (typeof magicCursorConfig !== 'undefined' && magicCursorConfig.style) ? magicCursorConfig.style : 'style_1';
        const optSize = (typeof magicCursorConfig !== 'undefined' && magicCursorConfig.size) ? magicCursorConfig.size : 'normal';

        const config = PRESETS[optStyle] || PRESETS['style_1'];

        const sizeScale = optSize === 'large' ? 1.6 : (optSize === 'small' ? 0.6 : 1);

        const p = document.createElement('div');
        p.className = 'magic-cursor-star';

        const size = randomRange(config.size[0], config.size[1]) * sizeScale;
        const moveX = randomRange(config.moveX[0], config.moveX[1]);
        const moveY = randomRange(config.moveY[0], config.moveY[1]);
        const rotStart = randomRange(config.rot[0], config.rot[1]);
        const rotEnd = rotStart + randomRange(-180, 180);

        let color = '';
        if (config.colors && config.colors.length > 0) {
            color = config.colors[Math.floor(Math.random() * config.colors.length)];
            p.style.color = color;
        }

        // Apply Shape
        if (config.shape === 'text' && config.chars.length > 0) {
            p.textContent = config.chars[Math.floor(Math.random() * config.chars.length)];
            p.style.fontSize = `${size}px`;
            if (config.glow && color) {
                p.style.textShadow = `0 0 ${size / 2}px ${color}, 0 0 ${size}px ${color}`;
            }
        } else if (config.shape === 'circle') {
            p.classList.add('magic-cursor-shape-circle');
            p.style.width = `${size}px`;
            p.style.height = `${size}px`;
            if (config.glow && color) {
                p.style.boxShadow = `0 0 ${size / 2}px ${color}`;
            }
        } else if (config.shape === 'square') {
            p.classList.add('magic-cursor-shape-square');
            p.style.width = `${size}px`;
            p.style.height = `${size}px`;
        }

        // Setup Shooting star specific styling if needed
        if (optStyle === 'style_19') {
            p.style.height = `${size * 6}px`; // Make it a streak
            p.style.borderRadius = '50px';
        }

        // Position & Variables
        p.style.left = `${x}px`;
        p.style.top = `${y}px`;

        p.style.setProperty('--move-x', `${moveX}px`);
        p.style.setProperty('--move-y', `${moveY}px`);
        p.style.setProperty('--rot-start', `${rotStart}deg`);
        p.style.setProperty('--rot-end', `${rotEnd}deg`);

        // Apply Animation
        p.style.animationName = config.anim;
        p.style.animationDuration = `${config.duration}ms`;
        // Ease based on animation
        if (config.anim === 'magicCursorPop' || config.anim === 'magicCursorShoot') {
            p.style.animationTimingFunction = 'ease-out';
        } else if (config.anim === 'magicCursorDrift') {
            p.style.animationTimingFunction = 'ease-in-out';
        } else {
            p.style.animationTimingFunction = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        }

        document.body.appendChild(p);

        // GC
        setTimeout(() => p.remove(), config.duration + 50);
    };

    const interact = (e) => {
        let currentX, currentY;
        if (e.type === 'touchmove') {
            currentX = e.touches[0].pageX;
            currentY = e.touches[0].pageY;
        } else {
            currentX = e.pageX;
            currentY = e.pageY;
        }

        if (lastX === -1000) {
            spawnParticle(currentX, currentY);
            lastX = currentX;
            lastY = currentY;
            return;
        }

        // Calculate dynamic threshold based on density
        const optDensity = (typeof magicCursorConfig !== 'undefined' && magicCursorConfig.density) ? magicCursorConfig.density : 'normal';
        let densityScale = 1;
        if (optDensity === 'high') densityScale = 0.5; // Spawn 2x more often
        if (optDensity === 'low') densityScale = 2.5;  // Spawn 2.5x less often
        const DISTANCE_THRESHOLD = 20 * densityScale;

        const deltaX = currentX - lastX;
        const deltaY = currentY - lastY;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

        if (distance >= DISTANCE_THRESHOLD) {
            const expectedSteps = Math.floor(distance / DISTANCE_THRESHOLD);
            const steps = Math.min(expectedSteps, 15); // Khống chế tối đa 15 hạt 1 lúc để chống lấp đầy line
            if (steps > 0) {
                for (let i = 1; i <= steps; i++) {
                    const interpX = lastX + (deltaX * i / expectedSteps);
                    const interpY = lastY + (deltaY * i / expectedSteps);
                    spawnParticle(interpX, interpY);
                }
            }

            lastX = currentX;
            lastY = currentY;
        }
    };

    document.addEventListener('mousemove', interact);
    document.addEventListener('touchmove', interact, { passive: true });

    // Sửa lỗi: Cập nhật lại toạ độ tức thì khi đưa chuột vào màn hình hoặc bắt đầu chạm cảm ứng (Tránh vẽ line)
    document.addEventListener('touchstart', (e) => {
        if (e.touches.length > 0) {
            lastX = e.touches[0].pageX;
            lastY = e.touches[0].pageY;
        }
    }, { passive: true });

    document.addEventListener('mouseenter', (e) => {
        lastX = e.pageX;
        lastY = e.pageY;
    });
});
