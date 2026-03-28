/**
 * SoundManager — gestionnaire audio procedural pour Amethyste-Idle.
 *
 * Utilise la Web Audio API pour generer des sons d'interface et de combat
 * sans fichiers audio externes. Toutes les preferences sont persistees
 * dans localStorage.
 *
 * Usage global :
 *   window.SoundManager.play('ui_click');
 *   window.SoundManager.play('combat_hit');
 *   window.SoundManager.setVolume(0.5);
 *   window.SoundManager.setMuted(true);
 */

const STORAGE_KEY_MUTED = 'amethyste_sound_muted';
const STORAGE_KEY_VOLUME = 'amethyste_sound_volume';

let audioCtx = null;
let masterGain = null;
let muted = false;
let volume = 0.6;

function getContext() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        masterGain = audioCtx.createGain();
        masterGain.gain.value = muted ? 0 : volume;
        masterGain.connect(audioCtx.destination);
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
}

function getMasterGain() {
    getContext();
    return masterGain;
}

// ——— Synthese procedurale ———

function playTone(freq, duration, type = 'sine', gainVal = 0.3, fadeOut = true) {
    const ctx = getContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = type;
    osc.frequency.setValueAtTime(freq, ctx.currentTime);
    gain.gain.setValueAtTime(gainVal, ctx.currentTime);

    if (fadeOut) {
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
    }

    osc.connect(gain);
    gain.connect(getMasterGain());
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + duration);
}

function playNoise(duration, gainVal = 0.15) {
    const ctx = getContext();
    const bufferSize = ctx.sampleRate * duration;
    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) {
        data[i] = Math.random() * 2 - 1;
    }

    const source = ctx.createBufferSource();
    source.buffer = buffer;

    const gain = ctx.createGain();
    gain.gain.setValueAtTime(gainVal, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

    const filter = ctx.createBiquadFilter();
    filter.type = 'highpass';
    filter.frequency.value = 2000;

    source.connect(filter);
    filter.connect(gain);
    gain.connect(getMasterGain());
    source.start(ctx.currentTime);
}

// ——— Definitions des sons ———

const SOUNDS = {
    // Interface
    ui_click() {
        playTone(800, 0.08, 'sine', 0.15);
    },
    ui_hover() {
        playTone(600, 0.05, 'sine', 0.08);
    },
    ui_open() {
        playTone(500, 0.1, 'sine', 0.15);
        setTimeout(() => playTone(700, 0.1, 'sine', 0.12), 60);
    },
    ui_close() {
        playTone(700, 0.1, 'sine', 0.12);
        setTimeout(() => playTone(500, 0.1, 'sine', 0.10), 60);
    },
    ui_error() {
        playTone(300, 0.15, 'square', 0.12);
        setTimeout(() => playTone(250, 0.2, 'square', 0.10), 120);
    },
    ui_success() {
        playTone(523, 0.1, 'sine', 0.15);
        setTimeout(() => playTone(659, 0.1, 'sine', 0.15), 100);
        setTimeout(() => playTone(784, 0.15, 'sine', 0.18), 200);
    },
    ui_notification() {
        playTone(880, 0.12, 'sine', 0.12);
        setTimeout(() => playTone(1100, 0.15, 'sine', 0.10), 100);
    },

    // Combat
    combat_hit() {
        playNoise(0.12, 0.2);
        playTone(200, 0.1, 'sawtooth', 0.12);
    },
    combat_critical() {
        playNoise(0.18, 0.3);
        playTone(150, 0.15, 'sawtooth', 0.18);
        setTimeout(() => playTone(300, 0.1, 'square', 0.12), 80);
    },
    combat_miss() {
        playTone(400, 0.15, 'sine', 0.08);
        const ctx = getContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(400, ctx.currentTime);
        osc.frequency.linearRampToValueAtTime(200, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        osc.connect(gain);
        gain.connect(getMasterGain());
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.15);
    },
    combat_spell() {
        playTone(600, 0.2, 'sine', 0.15);
        setTimeout(() => playTone(900, 0.15, 'sine', 0.12), 80);
        setTimeout(() => playTone(1200, 0.1, 'triangle', 0.10), 160);
    },
    combat_heal() {
        playTone(523, 0.15, 'sine', 0.18);
        setTimeout(() => playTone(659, 0.15, 'sine', 0.15), 120);
        setTimeout(() => playTone(784, 0.2, 'sine', 0.18), 240);
    },
    combat_death() {
        playTone(300, 0.3, 'sawtooth', 0.15);
        setTimeout(() => playTone(200, 0.3, 'sawtooth', 0.12), 200);
        setTimeout(() => playTone(100, 0.5, 'sawtooth', 0.10), 400);
    },
    combat_victory() {
        const notes = [523, 659, 784, 1047];
        notes.forEach((freq, i) => {
            setTimeout(() => playTone(freq, 0.2, 'sine', 0.18), i * 120);
        });
    },
    combat_defeat() {
        const notes = [400, 350, 300, 200];
        notes.forEach((freq, i) => {
            setTimeout(() => playTone(freq, 0.25, 'sawtooth', 0.12), i * 180);
        });
    },
    combat_flee() {
        const ctx = getContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(600, ctx.currentTime);
        osc.frequency.linearRampToValueAtTime(300, ctx.currentTime + 0.3);
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(getMasterGain());
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
    },
    combat_shield() {
        playTone(500, 0.15, 'triangle', 0.12);
        setTimeout(() => playTone(700, 0.2, 'triangle', 0.10), 80);
    },
    combat_status() {
        playTone(440, 0.1, 'sine', 0.10);
        setTimeout(() => playTone(550, 0.12, 'sine', 0.10), 80);
    },
    combat_boss_phase() {
        playTone(200, 0.3, 'square', 0.15);
        setTimeout(() => playTone(250, 0.3, 'square', 0.18), 200);
        setTimeout(() => playTone(300, 0.4, 'square', 0.20), 400);
    },

    // Carte / exploration
    map_step() {
        playTone(300, 0.04, 'sine', 0.05);
    },
    harvest_start() {
        playTone(400, 0.1, 'triangle', 0.10);
        setTimeout(() => playTone(500, 0.1, 'triangle', 0.08), 80);
    },
    harvest_success() {
        playTone(600, 0.1, 'sine', 0.12);
        setTimeout(() => playTone(800, 0.12, 'sine', 0.15), 100);
    },
    dialog_open() {
        playTone(440, 0.08, 'sine', 0.10);
        setTimeout(() => playTone(550, 0.1, 'sine', 0.12), 60);
    },
    level_up() {
        const notes = [523, 659, 784, 1047, 1318];
        notes.forEach((freq, i) => {
            setTimeout(() => playTone(freq, 0.2, 'sine', 0.20), i * 100);
        });
    },
    quest_complete() {
        const notes = [660, 784, 880, 1047];
        notes.forEach((freq, i) => {
            setTimeout(() => playTone(freq, 0.18, 'triangle', 0.18), i * 130);
        });
    },
    item_pickup() {
        playTone(800, 0.08, 'sine', 0.12);
        setTimeout(() => playTone(1000, 0.1, 'sine', 0.10), 60);
    },
};

// ——— API publique ———

function loadPreferences() {
    try {
        const storedMuted = localStorage.getItem(STORAGE_KEY_MUTED);
        if (storedMuted !== null) {
            muted = storedMuted === 'true';
        }
        const storedVolume = localStorage.getItem(STORAGE_KEY_VOLUME);
        if (storedVolume !== null) {
            volume = parseFloat(storedVolume);
            if (isNaN(volume) || volume < 0 || volume > 1) volume = 0.6;
        }
    } catch (_e) {
        // localStorage indisponible
    }
}

function savePreferences() {
    try {
        localStorage.setItem(STORAGE_KEY_MUTED, String(muted));
        localStorage.setItem(STORAGE_KEY_VOLUME, String(volume));
    } catch (_e) {
        // localStorage indisponible
    }
}

function updateGain() {
    if (masterGain) {
        masterGain.gain.setValueAtTime(muted ? 0 : volume, audioCtx.currentTime);
    }
}

const SoundManager = {
    play(soundName) {
        if (muted) return;
        const fn = SOUNDS[soundName];
        if (fn) {
            try {
                fn();
            } catch (_e) {
                // Ignore audio errors silently
            }
        }
    },

    setMuted(value) {
        muted = !!value;
        updateGain();
        savePreferences();
    },

    isMuted() {
        return muted;
    },

    setVolume(value) {
        volume = Math.max(0, Math.min(1, value));
        updateGain();
        savePreferences();
    },

    getVolume() {
        return volume;
    },

    toggle() {
        this.setMuted(!muted);
        return !muted;
    },

    getSoundNames() {
        return Object.keys(SOUNDS);
    },
};

// Charger les preferences au demarrage
loadPreferences();

// Debloquer l'AudioContext au premier clic utilisateur (politique navigateur)
let unlocked = false;
function unlockAudio() {
    if (unlocked) return;
    unlocked = true;
    getContext();
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('touchstart', unlockAudio);
    document.removeEventListener('keydown', unlockAudio);
}
document.addEventListener('click', unlockAudio, { once: false });
document.addEventListener('touchstart', unlockAudio, { once: false });
document.addEventListener('keydown', unlockAudio, { once: false });

// Exposer globalement
window.SoundManager = SoundManager;

export default SoundManager;
