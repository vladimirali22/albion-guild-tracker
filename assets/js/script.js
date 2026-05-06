/* ═══════════════════════════════════════════════════════════
   ALBION GUILD TRACKER — Main JavaScript
   Features: Particle System, Form Animations, Sound FX
═══════════════════════════════════════════════════════════ */

'use strict';

// ═══════════════════════════════════════
//  PARTICLE SYSTEM
// ═══════════════════════════════════════

class ParticleSystem {
    constructor(canvas) {
        this.canvas  = canvas;
        this.ctx     = canvas.getContext('2d');
        this.particles = [];
        this.mouse   = { x: null, y: null };
        this.animId  = null;
        this.resize();
        this.spawnAll();
        this.bindEvents();
        this.loop();
    }

    resize() {
        this.canvas.width  = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    spawnAll() {
        const count = Math.min(60, Math.floor((window.innerWidth * window.innerHeight) / 18000));
        for (let i = 0; i < count; i++) {
            this.particles.push(this.createParticle(true));
        }
    }

    createParticle(anywhere = false) {
        const types = ['dust', 'ember', 'star'];
        const type  = types[Math.floor(Math.random() * types.length)];
        return {
            x:      Math.random() * this.canvas.width,
            y:      anywhere ? Math.random() * this.canvas.height : this.canvas.height + 10,
            vx:     (Math.random() - 0.5) * 0.4,
            vy:     -(Math.random() * 0.6 + 0.2),
            life:   0,
            maxLife: Math.random() * 400 + 200,
            size:   type === 'star' ? Math.random() * 2 + 0.5 : Math.random() * 1.5 + 0.5,
            type,
            hue:    Math.random() * 30 + 35, // gold range
            flicker: Math.random() * Math.PI * 2,
            flickerSpeed: Math.random() * 0.05 + 0.02,
        };
    }

    update() {
        this.particles.forEach((p, i) => {
            p.x    += p.vx + Math.sin(p.life * 0.02) * 0.15;
            p.y    += p.vy;
            p.life ++;
            p.flicker += p.flickerSpeed;

            // Mouse repulsion
            if (this.mouse.x !== null) {
                const dx = p.x - this.mouse.x;
                const dy = p.y - this.mouse.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 100) {
                    p.vx += (dx / dist) * 0.08;
                    p.vy += (dy / dist) * 0.08;
                }
            }

            // Dampen velocity
            p.vx *= 0.99;

            if (p.life >= p.maxLife || p.y < -20 || p.x < -20 || p.x > this.canvas.width + 20) {
                this.particles[i] = this.createParticle(false);
            }
        });
    }

    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        this.particles.forEach(p => {
            const progress = p.life / p.maxLife;
            const alpha = progress < 0.15
                ? progress / 0.15
                : progress > 0.75
                    ? 1 - (progress - 0.75) / 0.25
                    : 1;

            const flicker = 0.6 + Math.sin(p.flicker) * 0.4;
            const finalAlpha = alpha * flicker;

            this.ctx.save();
            this.ctx.globalAlpha = finalAlpha * 0.7;

            if (p.type === 'ember') {
                // Glowing ember
                const grad = this.ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 3);
                grad.addColorStop(0, `hsl(${p.hue}, 90%, 75%)`);
                grad.addColorStop(0.5, `hsl(${p.hue}, 80%, 55%)`);
                grad.addColorStop(1, 'transparent');
                this.ctx.fillStyle = grad;
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, p.size * 3, 0, Math.PI * 2);
                this.ctx.fill();

            } else if (p.type === 'star') {
                // Twinkling star
                this.ctx.fillStyle = `hsl(${p.hue + 20}, 100%, 90%)`;
                this.ctx.shadowBlur = 6;
                this.ctx.shadowColor = `hsl(${p.hue}, 90%, 70%)`;
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                this.ctx.fill();

            } else {
                // Dust mote
                this.ctx.fillStyle = `hsl(${p.hue}, 70%, 65%)`;
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, p.size * 0.8, 0, Math.PI * 2);
                this.ctx.fill();
            }

            this.ctx.restore();
        });
    }

    loop() {
        this.update();
        this.draw();
        this.animId = requestAnimationFrame(() => this.loop());
    }

    bindEvents() {
        window.addEventListener('resize', () => {
            this.resize();
        });

        window.addEventListener('mousemove', e => {
            this.mouse.x = e.clientX;
            this.mouse.y = e.clientY;
        });

        window.addEventListener('mouseleave', () => {
            this.mouse.x = null;
            this.mouse.y = null;
        });
    }

    destroy() {
        if (this.animId) cancelAnimationFrame(this.animId);
    }
}


// ═══════════════════════════════════════
//  SOUND EFFECTS (Web Audio API)
// ═══════════════════════════════════════

class SoundFX {
    constructor() {
        this.ctx = null;
        this.enabled = false;
        this._init();
    }

    _init() {
        // Lazy init on first user interaction
        const enable = () => {
            if (this.enabled) return;
            try {
                this.ctx = new (window.AudioContext || window.webkitAudioContext)();
                this.enabled = true;
            } catch (e) { /* no audio */ }
            document.removeEventListener('click', enable);
            document.removeEventListener('keydown', enable);
        };
        document.addEventListener('click', enable, { once: true });
        document.addEventListener('keydown', enable, { once: true });
    }

    _beep(freq = 440, type = 'sine', duration = 0.12, gain = 0.15, delay = 0) {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gainNode = this.ctx.createGain();
            osc.connect(gainNode);
            gainNode.connect(this.ctx.destination);
            osc.type = type;
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime + delay);
            osc.frequency.exponentialRampToValueAtTime(freq * 0.7, this.ctx.currentTime + delay + duration);
            gainNode.gain.setValueAtTime(0, this.ctx.currentTime + delay);
            gainNode.gain.linearRampToValueAtTime(gain, this.ctx.currentTime + delay + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + delay + duration);
            osc.start(this.ctx.currentTime + delay);
            osc.stop(this.ctx.currentTime + delay + duration + 0.01);
        } catch (e) { /* ignore */ }
    }

    hover() {
        this._beep(600, 'sine', 0.08, 0.06);
    }

    click() {
        this._beep(350, 'triangle', 0.1, 0.1);
        this._beep(500, 'sine', 0.08, 0.08, 0.06);
    }

    success() {
        this._beep(523, 'sine', 0.15, 0.12, 0);
        this._beep(659, 'sine', 0.15, 0.12, 0.1);
        this._beep(784, 'sine', 0.2, 0.12, 0.2);
    }

    error() {
        this._beep(200, 'sawtooth', 0.18, 0.1);
        this._beep(160, 'sawtooth', 0.18, 0.1, 0.12);
    }
}


// ═══════════════════════════════════════
//  FORM VALIDATION & ENHANCEMENT
// ═══════════════════════════════════════

class FormEnhancer {
    constructor(sfx) {
        this.sfx = sfx;
        this._enhanceInputs();
        this._enhanceButtons();
        this._enhancePasswordToggle();
        this._enhanceCheckboxes();
        this._enhanceRegisterForm();
        this._enhanceLoginForm();
        this._animateCard();
    }

    _animateCard() {
        const card = document.getElementById('authCard');
        if (!card) return;
        // Tilt effect on mouse move
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = (e.clientX - cx) / (rect.width / 2);
            const dy = (e.clientY - cy) / (rect.height / 2);
            card.style.transform = `perspective(1000px) rotateY(${dx * 3}deg) rotateX(${-dy * 3}deg)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateY(0) rotateX(0)';
        });
    }

    _enhanceInputs() {
        document.querySelectorAll('.form-group input').forEach(input => {
            // Label float effect
            input.addEventListener('focus', () => {
                input.closest('.form-group').classList.add('focused');
                this.sfx.hover();
            });
            input.addEventListener('blur', () => {
                input.closest('.form-group').classList.remove('focused');
            });

            // Real-time border color feedback
            input.addEventListener('input', () => {
                if (input.value.length > 0) {
                    input.style.borderColor = 'rgba(212, 168, 83, 0.4)';
                } else {
                    input.style.borderColor = '';
                }
            });
        });
    }

    _enhanceButtons() {
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('mouseenter', () => this.sfx.hover());
            btn.addEventListener('click', () => {
                this.sfx.click();
                this._createRipple(btn);
            });
        });
    }

    _createRipple(btn) {
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(212, 168, 83, 0.3);
            width: 10px; height: 10px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: rippleOut 0.5s ease forwards;
            pointer-events: none;
        `;
        if (!document.getElementById('rippleStyle')) {
            const s = document.createElement('style');
            s.id = 'rippleStyle';
            s.textContent = `@keyframes rippleOut {
                to { transform: translate(-50%,-50%) scale(30); opacity: 0; }
            }`;
            document.head.appendChild(s);
        }
        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    }

    _enhancePasswordToggle() {
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput  = document.getElementById('password');
        if (!toggleBtn || !pwdInput) return;

        toggleBtn.addEventListener('click', () => {
            const isHidden = pwdInput.type === 'password';
            pwdInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? '🙈' : '👁';
            toggleBtn.style.opacity = isHidden ? '1' : '0.5';
            this.sfx.click();
        });
    }

    _enhanceCheckboxes() {
        document.querySelectorAll('.checkbox-label').forEach(label => {
            label.addEventListener('change', () => this.sfx.click());
        });
    }

    _enhanceRegisterForm() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        const pwdInput  = document.getElementById('password');
        const confInput = document.getElementById('confirm_password');

        if (pwdInput && confInput) {
            // Password strength meter
            const meter = this._createStrengthMeter(pwdInput);

            pwdInput.addEventListener('input', () => {
                this._updateStrengthMeter(meter, pwdInput.value);
            });

            // Confirm password match indicator
            confInput.addEventListener('input', () => {
                const match = confInput.value === pwdInput.value && confInput.value.length > 0;
                confInput.style.borderColor = confInput.value.length === 0
                    ? ''
                    : match
                        ? 'rgba(34, 197, 94, 0.6)'
                        : 'rgba(239, 68, 68, 0.6)';
            });
        }

        form.addEventListener('submit', e => {
            const pwd  = form.password.value;
            const conf = form.confirm_password.value;

            if (pwd !== conf) {
                e.preventDefault();
                this.sfx.error();
                this._shakeCard();
                return;
            }

            this.sfx.success();
            this._showLoadingState(form.querySelector('.btn-primary'), 'Forging your legend...');
        });
    }

    _createStrengthMeter(input) {
        const container = document.createElement('div');
        container.style.cssText = `
            display: flex; gap: 4px; margin-top: 4px; height: 3px;
        `;
        for (let i = 0; i < 4; i++) {
            const bar = document.createElement('div');
            bar.style.cssText = `
                flex: 1; border-radius: 99px;
                background: rgba(255,255,255,0.08);
                transition: background 0.3s;
            `;
            container.appendChild(bar);
        }
        input.closest('.form-group').appendChild(container);
        return container;
    }

    _updateStrengthMeter(meter, value) {
        const bars = meter.querySelectorAll('div');
        let score = 0;
        if (value.length >= 8)   score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
        bars.forEach((bar, i) => {
            bar.style.background = i < score ? colors[score - 1] : 'rgba(255,255,255,0.08)';
        });
    }

    _enhanceLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', () => {
            this.sfx.click();
            this._showLoadingState(form.querySelector('.btn-primary'), 'Opening the gates...');
        });
    }

    _showLoadingState(btn, text) {
        if (!btn) return;
        btn.disabled = true;
        const orig = btn.querySelector('.btn-text').textContent;
        btn.querySelector('.btn-text').textContent = text;
        btn.style.opacity = '0.8';

        // Restore if page doesn't navigate (e.g. validation error)
        setTimeout(() => {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = orig;
            btn.style.opacity = '';
        }, 5000);
    }

    _shakeCard() {
        const card = document.getElementById('authCard');
        if (!card) return;
        card.style.animation = 'none';
        card.style.transform = 'translateX(0)';

        const keyframes = [
            { transform: 'translateX(-10px)' },
            { transform: 'translateX(10px)' },
            { transform: 'translateX(-8px)' },
            { transform: 'translateX(8px)' },
            { transform: 'translateX(-4px)' },
            { transform: 'translateX(0)' },
        ];
        card.animate(keyframes, { duration: 400, easing: 'ease-in-out' });
    }
}


// ═══════════════════════════════════════
//  DASHBOARD ENHANCEMENTS
// ═══════════════════════════════════════

class DashboardEnhancer {
    constructor() {
        this._animateStatsOnScroll();
        this._enhanceRankNodes();
        this._enhanceNavLogout();
        this._floatingActionHints();
    }

    _animateStatsOnScroll() {
        const cards = document.querySelectorAll('.stat-card, .rank-card, .profile-section, .rank-section, .ranks-guide');
        if (!cards.length || !window.IntersectionObserver) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        cards.forEach(card => observer.observe(card));
    }

    _enhanceRankNodes() {
        document.querySelectorAll('.rank-node').forEach(node => {
            node.addEventListener('mouseenter', () => {
                node.querySelector('.rank-bubble').style.transform = 'scale(1.15)';
            });
            node.addEventListener('mouseleave', () => {
                node.querySelector('.rank-bubble').style.transform = '';
            });
        });

        // Animate rank progress bar on load
        const fill = document.querySelector('.rank-progress-fill');
        if (fill) {
            const targetWidth = fill.style.width;
            fill.style.width = '0%';
            requestAnimationFrame(() => {
                setTimeout(() => {
                    fill.style.transition = 'width 1.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    fill.style.width = targetWidth;
                }, 300);
            });
        }
    }

    _enhanceNavLogout() {
        const logoutBtn = document.querySelector('.btn-logout');
        if (!logoutBtn) return;
        logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            logoutBtn.textContent = '⏻ Leaving...';
            logoutBtn.style.opacity = '0.6';
            setTimeout(() => { window.location.href = logoutBtn.href; }, 400);
        });
    }

    _floatingActionHints() {
        // Tooltip on rank cards showing next rank requirements
        document.querySelectorAll('.rank-card').forEach(card => {
            card.style.cursor = 'default';
        });
    }
}


// ═══════════════════════════════════════
//  CURSOR TRAIL EFFECT
// ═══════════════════════════════════════

class CursorTrail {
    constructor() {
        this.dots = [];
        this.count = 8;
        this.mouse = { x: 0, y: 0 };
        this._create();
        this._bind();
        this._animate();
    }

    _create() {
        for (let i = 0; i < this.count; i++) {
            const dot = document.createElement('div');
            const size = 6 - i * 0.5;
            dot.style.cssText = `
                position: fixed;
                width: ${size}px;
                height: ${size}px;
                border-radius: 50%;
                background: rgba(212, 168, 83, ${0.5 - i * 0.05});
                pointer-events: none;
                z-index: 9999;
                transition: transform 0.1s;
                transform: translate(-50%, -50%);
                mix-blend-mode: screen;
                filter: blur(${i * 0.3}px);
                left: -10px; top: -10px;
            `;
            document.body.appendChild(dot);
            this.dots.push({ el: dot, x: 0, y: 0 });
        }
    }

    _bind() {
        window.addEventListener('mousemove', e => {
            this.mouse.x = e.clientX;
            this.mouse.y = e.clientY;
        });
    }

    _animate() {
        let lx = this.mouse.x, ly = this.mouse.y;

        const step = () => {
            this.dots.forEach((dot, i) => {
                const delay = i === 0 ? 0.35 : 0.25;
                dot.x += ((i === 0 ? this.mouse.x : this.dots[i - 1].x) - dot.x) * delay;
                dot.y += ((i === 0 ? this.mouse.y : this.dots[i - 1].y) - dot.y) * delay;
                dot.el.style.left = dot.x + 'px';
                dot.el.style.top  = dot.y + 'px';
            });
            requestAnimationFrame(step);
        };
        step();
    }
}


// ═══════════════════════════════════════
//  FLASH MESSAGE AUTO-DISMISS
// ═══════════════════════════════════════

function initFlashMessages() {
    document.querySelectorAll('.flash').forEach(flash => {
        // Add close button
        const close = document.createElement('button');
        close.textContent = '×';
        close.style.cssText = `
            margin-left: auto; background: none; border: none;
            color: inherit; cursor: pointer; font-size: 1.2rem;
            opacity: 0.6; flex-shrink: 0; padding: 0 4px;
            line-height: 1;
        `;
        close.addEventListener('click', () => dismissFlash(flash));
        flash.appendChild(close);

        // Auto-dismiss after 6 seconds
        setTimeout(() => dismissFlash(flash), 6000);
    });
}

function dismissFlash(el) {
    el.style.transition = 'opacity 0.4s, transform 0.4s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-8px)';
    setTimeout(() => el.remove(), 400);
}


// ═══════════════════════════════════════
//  TYPING EFFECT FOR SUBTITLES
// ═══════════════════════════════════════

function typewriterEffect(el, text, speed = 40) {
    if (!el) return;
    el.textContent = '';
    el.style.opacity = '1';
    let i = 0;
    const interval = setInterval(() => {
        el.textContent += text[i];
        i++;
        if (i >= text.length) clearInterval(interval);
    }, speed);
}


// ═══════════════════════════════════════
//  INIT
// ═══════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // ── Particle System ──
    const canvas = document.getElementById('particles');
    if (canvas) {
        new ParticleSystem(canvas);
    }

    // ── Sound FX ──
    const sfx = new SoundFX();

    // ── Flash Messages ──
    initFlashMessages();

    // ── Page-specific init ──
    const isAuthPage    = document.querySelector('.auth-page');
    const isDashboard   = document.querySelector('.dashboard-page');

    if (isAuthPage) {
        new FormEnhancer(sfx);

        // Typewriter on subtitle
        const subtitle = document.querySelector('.auth-subtitle');
        if (subtitle) {
            const originalText = subtitle.textContent;
            subtitle.textContent = '';
            setTimeout(() => typewriterEffect(subtitle, originalText, 35), 600);
        }

        // Cursor trail only on auth pages (more dramatic)
        if (window.innerWidth > 768) {
            new CursorTrail();
        }
    }

    if (isDashboard) {
        new DashboardEnhancer();
    }

    // ── Gold link hover sounds ──
    document.querySelectorAll('.gold-link, .btn-logout').forEach(link => {
        link.addEventListener('mouseenter', () => sfx.hover());
    });

    // ── Keyboard navigation enhancement ──
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter' && document.activeElement.tagName === 'INPUT') {
            const form = document.activeElement.closest('form');
            if (form) {
                const inputs = Array.from(form.querySelectorAll('input:not([type="checkbox"])'));
                const idx    = inputs.indexOf(document.activeElement);
                if (idx < inputs.length - 1) {
                    e.preventDefault();
                    inputs[idx + 1].focus();
                }
            }
        }
    });

    // ── Prevent double-submit ──
    document.querySelectorAll('form').forEach(form => {
        let submitted = false;
        form.addEventListener('submit', e => {
            if (submitted) { e.preventDefault(); return; }
            submitted = true;
            setTimeout(() => { submitted = false; }, 5000);
        });
    });

    console.log(
        '%c⚔ Albion Guild Tracker ⚔',
        'font-family: serif; font-size: 18px; color: #d4a853; text-shadow: 0 0 10px #d4a853;'
    );
    console.log('%cMay your fame never falter, warrior.', 'color: #9ca3af; font-style: italic;');
});
