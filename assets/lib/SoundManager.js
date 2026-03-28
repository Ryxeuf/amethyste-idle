/**
 * SoundManager — Gestionnaire audio procedural pour Amethyste-Idle.
 *
 * Utilise la Web Audio API pour generer des sons synthetiques
 * (interface, combat, ambiance) sans fichiers audio externes.
 *
 * API globale : window.Sound.play('hit'), window.Sound.setVolume(0.5), etc.
 */

const STORAGE_KEY_ENABLED = 'sound_enabled';
const STORAGE_KEY_VOLUME = 'sound_volume';

class SoundManager {
    constructor() {
        /** @type {AudioContext|null} */
        this._ctx = null;
        this._masterGain = null;
        this._enabled = this._loadEnabled();
        this._volume = this._loadVolume();
        this._ambientSource = null;
        this._ambientGain = null;
        this._currentBiome = null;
        this._unlocked = false;
    }

    // ─── Public API ───────────────────────────────────────────

    /** Play a named sound effect. */
    play(name) {
        if (!this._enabled) return;
        this._ensureContext();
        if (!this._ctx || !this._unlocked) return;

        const fn = this._sounds[name];
        if (fn) {
            try {
                fn.call(this);
            } catch {
                // Silently ignore audio errors
            }
        }
    }

    /** Start or crossfade ambient loop for a biome. */
    startAmbient(biome) {
        if (biome === this._currentBiome) return;
        this._currentBiome = biome;
        if (!this._enabled) return;
        this._ensureContext();
        if (!this._ctx || !this._unlocked) return;
        this._playAmbient(biome);
    }

    /** Stop ambient loop. */
    stopAmbient() {
        this._currentBiome = null;
        this._fadeOutAmbient();
    }

    /** Set master volume (0-1). */
    setVolume(v) {
        this._volume = Math.max(0, Math.min(1, v));
        localStorage.setItem(STORAGE_KEY_VOLUME, String(this._volume));
        if (this._masterGain) {
            this._masterGain.gain.setTargetAtTime(this._volume, this._ctx.currentTime, 0.05);
        }
    }

    /** Get current volume. */
    getVolume() {
        return this._volume;
    }

    /** Enable/disable all sounds. */
    setEnabled(enabled) {
        this._enabled = enabled;
        localStorage.setItem(STORAGE_KEY_ENABLED, enabled ? '1' : '0');
        if (!enabled) {
            this._fadeOutAmbient();
        } else if (this._currentBiome) {
            this._ensureContext();
            if (this._ctx && this._unlocked) {
                this._playAmbient(this._currentBiome);
            }
        }
    }

    /** Check if sound is enabled. */
    isEnabled() {
        return this._enabled;
    }

    /** Must be called on user gesture to unlock AudioContext. */
    unlock() {
        if (this._unlocked) return;
        this._ensureContext();
        if (this._ctx && this._ctx.state === 'suspended') {
            this._ctx.resume();
        }
        this._unlocked = true;
        // Restart ambient if biome was set before unlock
        if (this._enabled && this._currentBiome) {
            this._playAmbient(this._currentBiome);
        }
    }

    // ─── Internal: context ────────────────────────────────────

    _ensureContext() {
        if (this._ctx) return;
        try {
            this._ctx = new (window.AudioContext || window.webkitAudioContext)();
            this._masterGain = this._ctx.createGain();
            this._masterGain.gain.value = this._volume;
            this._masterGain.connect(this._ctx.destination);
        } catch {
            this._ctx = null;
        }
    }

    // ─── Internal: localStorage ───────────────────────────────

    _loadEnabled() {
        const v = localStorage.getItem(STORAGE_KEY_ENABLED);
        return v === null ? true : v === '1';
    }

    _loadVolume() {
        const v = localStorage.getItem(STORAGE_KEY_VOLUME);
        return v === null ? 0.5 : Math.max(0, Math.min(1, parseFloat(v)));
    }

    // ─── Internal: helpers ────────────────────────────────────

    _osc(type, freq, duration, gainVal = 0.3, detune = 0) {
        const ctx = this._ctx;
        const now = ctx.currentTime;
        const g = ctx.createGain();
        g.gain.setValueAtTime(gainVal, now);
        g.gain.exponentialRampToValueAtTime(0.001, now + duration);
        g.connect(this._masterGain);

        const o = ctx.createOscillator();
        o.type = type;
        o.frequency.setValueAtTime(freq, now);
        if (detune) o.detune.setValueAtTime(detune, now);
        o.connect(g);
        o.start(now);
        o.stop(now + duration);
    }

    _noise(duration, gainVal = 0.15, filterFreq = 4000) {
        const ctx = this._ctx;
        const now = ctx.currentTime;
        const bufferSize = Math.floor(ctx.sampleRate * duration);
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }

        const source = ctx.createBufferSource();
        source.buffer = buffer;

        const filter = ctx.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(filterFreq, now);

        const g = ctx.createGain();
        g.gain.setValueAtTime(gainVal, now);
        g.gain.exponentialRampToValueAtTime(0.001, now + duration);

        source.connect(filter);
        filter.connect(g);
        g.connect(this._masterGain);
        source.start(now);
        source.stop(now + duration);
    }

    // ─── Internal: sound definitions ──────────────────────────

    get _sounds() {
        return {
            // === Interface ===
            click: () => {
                this._osc('sine', 800, 0.06, 0.15);
            },
            menu_open: () => {
                this._osc('sine', 600, 0.08, 0.12);
                this._osc('sine', 900, 0.08, 0.12);
            },
            menu_close: () => {
                this._osc('sine', 900, 0.06, 0.1);
                this._osc('sine', 600, 0.06, 0.1);
            },
            notification: () => {
                this._osc('sine', 880, 0.12, 0.15);
                setTimeout(() => this._osc('sine', 1100, 0.15, 0.15), 120);
            },
            error: () => {
                this._osc('square', 200, 0.15, 0.1);
                setTimeout(() => this._osc('square', 150, 0.2, 0.1), 100);
            },

            // === Combat ===
            hit: () => {
                this._noise(0.08, 0.2, 3000);
                this._osc('sawtooth', 150, 0.1, 0.15);
            },
            miss: () => {
                this._noise(0.12, 0.08, 6000);
            },
            critical: () => {
                this._noise(0.1, 0.25, 5000);
                this._osc('sawtooth', 200, 0.12, 0.2);
                setTimeout(() => this._osc('sine', 1200, 0.08, 0.15), 50);
            },
            spell: () => {
                this._osc('sine', 400, 0.3, 0.15);
                const ctx = this._ctx;
                const now = ctx.currentTime;
                const o = ctx.createOscillator();
                o.type = 'sine';
                o.frequency.setValueAtTime(400, now);
                o.frequency.exponentialRampToValueAtTime(1200, now + 0.25);
                const g = ctx.createGain();
                g.gain.setValueAtTime(0.12, now);
                g.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                o.connect(g);
                g.connect(this._masterGain);
                o.start(now);
                o.stop(now + 0.3);
            },
            heal: () => {
                this._osc('sine', 523, 0.15, 0.12);
                setTimeout(() => this._osc('sine', 659, 0.15, 0.12), 80);
                setTimeout(() => this._osc('sine', 784, 0.2, 0.12), 160);
            },
            death: () => {
                this._osc('sawtooth', 300, 0.4, 0.15);
                const ctx = this._ctx;
                const now = ctx.currentTime;
                const o = ctx.createOscillator();
                o.type = 'sawtooth';
                o.frequency.setValueAtTime(300, now);
                o.frequency.exponentialRampToValueAtTime(60, now + 0.4);
                const g = ctx.createGain();
                g.gain.setValueAtTime(0.12, now);
                g.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                o.connect(g);
                g.connect(this._masterGain);
                o.start(now);
                o.stop(now + 0.5);
            },
            victory: () => {
                const notes = [523, 659, 784, 1047];
                notes.forEach((f, i) => {
                    setTimeout(() => this._osc('sine', f, 0.2, 0.15), i * 100);
                });
            },
            defeat: () => {
                const notes = [400, 350, 300, 200];
                notes.forEach((f, i) => {
                    setTimeout(() => this._osc('sawtooth', f, 0.25, 0.1), i * 120);
                });
            },
            flee: () => {
                this._noise(0.15, 0.1, 8000);
                this._osc('sine', 600, 0.1, 0.1);
                setTimeout(() => this._osc('sine', 800, 0.1, 0.1), 60);
            },
            shield: () => {
                this._osc('triangle', 300, 0.15, 0.1);
                this._osc('triangle', 450, 0.15, 0.1);
            },
            status_apply: () => {
                this._osc('sine', 500, 0.15, 0.1);
                this._osc('sine', 700, 0.1, 0.08);
            },
            summon: () => {
                this._osc('sine', 200, 0.3, 0.1);
                setTimeout(() => this._osc('sine', 400, 0.2, 0.12), 100);
                setTimeout(() => this._osc('sine', 600, 0.15, 0.1), 200);
            },
            fight_start: () => {
                this._noise(0.05, 0.1, 2000);
                this._osc('square', 220, 0.15, 0.1);
                setTimeout(() => this._osc('square', 330, 0.15, 0.12), 100);
            },
            loot: () => {
                const notes = [660, 880, 1100, 1320];
                notes.forEach((f, i) => {
                    setTimeout(() => this._osc('sine', f, 0.1, 0.1), i * 60);
                });
            },

            // === Recolte ===
            harvest: () => {
                this._noise(0.05, 0.1, 2000);
                this._osc('triangle', 400, 0.1, 0.1);
            },
            fish_catch: () => {
                this._osc('sine', 500, 0.1, 0.12);
                setTimeout(() => this._osc('sine', 750, 0.15, 0.12), 80);
            },

            // === Navigation ===
            portal: () => {
                this._osc('sine', 300, 0.4, 0.1);
                const ctx = this._ctx;
                const now = ctx.currentTime;
                const o = ctx.createOscillator();
                o.type = 'sine';
                o.frequency.setValueAtTime(300, now);
                o.frequency.exponentialRampToValueAtTime(900, now + 0.35);
                const g = ctx.createGain();
                g.gain.setValueAtTime(0.1, now);
                g.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
                o.connect(g);
                g.connect(this._masterGain);
                o.start(now);
                o.stop(now + 0.4);
            },
        };
    }

    // ─── Internal: ambient loops ──────────────────────────────

    _playAmbient(biome) {
        this._fadeOutAmbient();
        if (!biome || !this._ctx) return;

        const ctx = this._ctx;
        const now = ctx.currentTime;

        this._ambientGain = ctx.createGain();
        this._ambientGain.gain.setValueAtTime(0.001, now);
        this._ambientGain.gain.linearRampToValueAtTime(0.06, now + 2);
        this._ambientGain.connect(this._masterGain);

        const config = this._biomeAmbient[biome];
        if (!config) return;

        this._ambientSource = config.create.call(this, ctx, this._ambientGain);
    }

    _fadeOutAmbient() {
        if (!this._ambientGain || !this._ctx) return;
        const now = this._ctx.currentTime;
        this._ambientGain.gain.setTargetAtTime(0, now, 0.5);

        const oldSource = this._ambientSource;
        const oldGain = this._ambientGain;
        this._ambientSource = null;
        this._ambientGain = null;

        // Clean up after fade
        setTimeout(() => {
            try {
                if (oldSource) {
                    if (Array.isArray(oldSource)) {
                        oldSource.forEach(s => { try { s.stop(); } catch { /* */ } });
                    } else {
                        oldSource.stop();
                    }
                }
                oldGain.disconnect();
            } catch {
                // Already stopped
            }
        }, 3000);
    }

    get _biomeAmbient() {
        return {
            forest: {
                create(ctx, dest) {
                    // Rustling leaves: filtered noise + gentle oscillators
                    const bufferSize = ctx.sampleRate * 4;
                    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.loop = true;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'bandpass';
                    lp.frequency.value = 800;
                    lp.Q.value = 0.5;

                    // LFO for wind effect
                    const lfo = ctx.createOscillator();
                    lfo.type = 'sine';
                    lfo.frequency.value = 0.3;
                    const lfoGain = ctx.createGain();
                    lfoGain.gain.value = 300;
                    lfo.connect(lfoGain);
                    lfoGain.connect(lp.frequency);
                    lfo.start();

                    source.connect(lp);
                    lp.connect(dest);
                    source.start();
                    return [source, lfo];
                },
            },
            plains: {
                create(ctx, dest) {
                    // Gentle wind
                    const bufferSize = ctx.sampleRate * 4;
                    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.loop = true;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'lowpass';
                    lp.frequency.value = 400;

                    source.connect(lp);
                    lp.connect(dest);
                    source.start();
                    return source;
                },
            },
            swamp: {
                create(ctx, dest) {
                    // Bubbling: low noise + occasional blips
                    const bufferSize = ctx.sampleRate * 4;
                    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.loop = true;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'lowpass';
                    lp.frequency.value = 300;

                    source.connect(lp);
                    lp.connect(dest);
                    source.start();
                    return source;
                },
            },
            village: {
                create(ctx, dest) {
                    // Calm hum
                    const o = ctx.createOscillator();
                    o.type = 'sine';
                    o.frequency.value = 120;

                    const g = ctx.createGain();
                    g.gain.value = 0.4;

                    const lfo = ctx.createOscillator();
                    lfo.type = 'sine';
                    lfo.frequency.value = 0.2;
                    const lfoGain = ctx.createGain();
                    lfoGain.gain.value = 5;
                    lfo.connect(lfoGain);
                    lfoGain.connect(o.frequency);
                    lfo.start();

                    o.connect(g);
                    g.connect(dest);
                    o.start();
                    return [o, lfo];
                },
            },
            dark: {
                create(ctx, dest) {
                    // Ominous drone
                    const o1 = ctx.createOscillator();
                    o1.type = 'sawtooth';
                    o1.frequency.value = 55;
                    const o2 = ctx.createOscillator();
                    o2.type = 'sawtooth';
                    o2.frequency.value = 57;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'lowpass';
                    lp.frequency.value = 200;

                    const g = ctx.createGain();
                    g.gain.value = 0.3;

                    o1.connect(lp);
                    o2.connect(lp);
                    lp.connect(g);
                    g.connect(dest);
                    o1.start();
                    o2.start();
                    return [o1, o2];
                },
            },
            hills: {
                create(ctx, dest) {
                    // Airy wind
                    const bufferSize = ctx.sampleRate * 4;
                    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.loop = true;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'bandpass';
                    lp.frequency.value = 600;
                    lp.Q.value = 0.3;

                    source.connect(lp);
                    lp.connect(dest);
                    source.start();
                    return source;
                },
            },
            cave: {
                create(ctx, dest) {
                    // Reverberant drip
                    const bufferSize = ctx.sampleRate * 4;
                    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.loop = true;

                    const lp = ctx.createBiquadFilter();
                    lp.type = 'lowpass';
                    lp.frequency.value = 250;

                    source.connect(lp);
                    lp.connect(dest);
                    source.start();
                    return source;
                },
            },
        };
    }
}

// Singleton & global access
const soundManager = new SoundManager();
window.Sound = soundManager;

export default soundManager;
