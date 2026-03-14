import * as PIXI from 'pixi.js';

/**
 * Animated sprite system for RPG Maker VX/MV sprite sheets.
 *
 * Supports two layouts:
 * - "single": one character per sheet (3 columns × 4 rows)
 * - "multi": multiple characters per sheet (12 columns × 8 rows),
 *   each character is a 3×4 block identified by `charIndex` (0-7).
 *
 * Row order: 0=down, 1=left, 2=right, 3=up
 * Column order: 0,1,2 = walk cycle (column 1 = idle/stand frame)
 *
 * Features:
 * - Idle breathing animation (subtle Y oscillation when stationary)
 * - Emote bubbles above sprites (!, ?, ♥, ...)
 * - Animation states: idle, walk, interact
 */

const DIRECTION_ROW = { down: 0, left: 1, right: 2, up: 3 };
const WALK_FRAMES = [0, 1, 2, 1]; // left foot, stand, right foot, stand
const DEFAULT_ANIM_FPS = 8;

/** Available emote types with their display character */
const EMOTE_TYPES = {
    exclamation: '!',
    question: '?',
    heart: '♥',
    anger: '✦',
    sweat: '~',
    music: '♪',
    ellipsis: '…',
    star: '★',
};

/** Animation states */
const ANIM_STATE = {
    IDLE: 'idle',
    WALK: 'walk',
    INTERACT: 'interact',
};

export { EMOTE_TYPES, ANIM_STATE };

export default class SpriteAnimator {
    /**
     * @param {object} opts
     * @param {PIXI.Texture} opts.texture - The loaded sprite sheet base texture
     * @param {string} opts.type - "single" or "multi"
     * @param {number} [opts.charIndex=0] - Character index for multi sheets (0-7)
     * @param {number} [opts.animFps=8] - Animation speed in frames per second
     * @param {boolean} [opts.enableBreathing=true] - Enable idle breathing effect
     */
    constructor({ texture, type = 'single', charIndex = 0, animFps = DEFAULT_ANIM_FPS, enableBreathing = true }) {
        this._baseTexture = texture;
        this._type = type;
        this._charIndex = charIndex;
        this._animFps = animFps;

        this._direction = 'down';
        this._playing = false;
        this._elapsed = 0;
        this._frameIndex = 0;
        this._state = ANIM_STATE.IDLE;

        // Idle breathing effect
        this._breathingEnabled = enableBreathing;
        this._breathElapsed = Math.random() * 3000; // desync between sprites
        this._breathAmplitude = 1.5; // pixels
        this._breathSpeed = 0.0015; // radians per ms
        this._baseY = 0;

        // Emote system
        this._emoteContainer = null;
        this._emoteTimer = 0;
        this._emoteDuration = 0;

        this._computeFrameSize();
        this._buildFrames();

        this._sprite = new PIXI.Sprite(this._getTexture(DIRECTION_ROW.down, 1));
        this._sprite.roundPixels = true;
    }

    /** The PixiJS Sprite to add to a container */
    get sprite() {
        return this._sprite;
    }

    /** Frame width in pixels */
    get frameWidth() {
        return this._frameW;
    }

    /** Frame height in pixels */
    get frameHeight() {
        return this._frameH;
    }

    /** Current animation state */
    get state() {
        return this._state;
    }

    /**
     * Set facing direction.
     * @param {'up'|'down'|'left'|'right'} dir
     */
    setDirection(dir) {
        if (DIRECTION_ROW[dir] !== undefined && dir !== this._direction) {
            this._direction = dir;
            if (!this._playing) {
                this._sprite.texture = this._getTexture(DIRECTION_ROW[dir], 1);
            }
        }
    }

    /** Start walk animation */
    play() {
        if (this._playing) return;
        this._playing = true;
        this._state = ANIM_STATE.WALK;
        this._elapsed = 0;
        this._frameIndex = 0;
        // Reset breathing offset when walking
        this._sprite.position.y = this._baseY;
    }

    /** Stop walk animation and return to idle frame */
    stop() {
        this._playing = false;
        this._state = ANIM_STATE.IDLE;
        this._elapsed = 0;
        this._frameIndex = 0;
        this._sprite.texture = this._getTexture(DIRECTION_ROW[this._direction], 1);
    }

    /** @returns {boolean} */
    get isPlaying() {
        return this._playing;
    }

    /**
     * Show an emote bubble above the sprite.
     * @param {string} emoteType - Key from EMOTE_TYPES
     * @param {number} [durationMs=2000] - Duration to show the emote
     * @returns {PIXI.Container|null} The emote container (add to parent)
     */
    showEmote(emoteType, durationMs = 2000) {
        const char = EMOTE_TYPES[emoteType];
        if (!char) return null;

        this.hideEmote();

        const container = new PIXI.Container();

        // Background bubble
        const bg = new PIXI.Graphics();
        bg.roundRect(-10, -24, 20, 20, 4);
        bg.fill({ color: 0xffffff, alpha: 0.9 });
        bg.stroke({ color: 0x9333ea, width: 1.5 });
        container.addChild(bg);

        // Emote text
        const text = new PIXI.Text({
            text: char,
            style: {
                fontFamily: 'Arial',
                fontSize: 14,
                fill: emoteType === 'heart' ? 0xe11d48 : (emoteType === 'anger' ? 0xef4444 : 0x7c3aed),
                fontWeight: 'bold',
            },
        });
        text.anchor.set(0.5, 0.5);
        text.position.set(0, -14);
        container.addChild(text);

        // Small triangle pointer at bottom
        const pointer = new PIXI.Graphics();
        pointer.moveTo(-3, -4);
        pointer.lineTo(3, -4);
        pointer.lineTo(0, 0);
        pointer.closePath();
        pointer.fill({ color: 0xffffff, alpha: 0.9 });
        container.addChild(pointer);

        this._emoteContainer = container;
        this._emoteTimer = 0;
        this._emoteDuration = durationMs;

        return container;
    }

    /** Remove current emote */
    hideEmote() {
        if (this._emoteContainer) {
            if (this._emoteContainer.parent) {
                this._emoteContainer.parent.removeChild(this._emoteContainer);
            }
            this._emoteContainer.destroy({ children: true });
            this._emoteContainer = null;
            this._emoteTimer = 0;
        }
    }

    /**
     * Set animation state for interaction (e.g. talking to NPC).
     * Uses a slower walk animation to simulate a "shifting" idle.
     */
    setInteracting(active) {
        if (active) {
            this._state = ANIM_STATE.INTERACT;
            this._playing = true;
            this._animFps = 3; // Slow animation for interact
            this._elapsed = 0;
        } else {
            this._animFps = DEFAULT_ANIM_FPS;
            this.stop();
        }
    }

    /**
     * Store base Y position for breathing offset calculations.
     * Call after positioning the sprite in its container.
     */
    setBaseY(y) {
        this._baseY = y;
    }

    /**
     * Update animation state. Call every frame.
     * @param {number} deltaMs - Time elapsed since last frame in milliseconds
     */
    update(deltaMs) {
        // Emote timer
        if (this._emoteContainer && this._emoteDuration > 0) {
            this._emoteTimer += deltaMs;
            // Floating bob effect on emote
            if (this._emoteContainer) {
                this._emoteContainer.position.y = -this._frameH - 4 + Math.sin(this._emoteTimer * 0.005) * 2;
                this._emoteContainer.position.x = this._frameW / 2;
            }
            if (this._emoteTimer >= this._emoteDuration) {
                // Fade out emote
                const fadeProgress = Math.min((this._emoteTimer - this._emoteDuration) / 300, 1);
                if (this._emoteContainer) {
                    this._emoteContainer.alpha = 1 - fadeProgress;
                }
                if (fadeProgress >= 1) {
                    this.hideEmote();
                }
            }
        }

        if (this._playing) {
            this._elapsed += deltaMs;
            const frameDuration = 1000 / this._animFps;
            const totalFrames = WALK_FRAMES.length;

            this._frameIndex = Math.floor(this._elapsed / frameDuration) % totalFrames;
            const col = WALK_FRAMES[this._frameIndex];
            const row = DIRECTION_ROW[this._direction];

            this._sprite.texture = this._getTexture(row, col);
            return;
        }

        // Idle breathing animation
        if (this._breathingEnabled) {
            this._breathElapsed += deltaMs;
            const breathOffset = Math.sin(this._breathElapsed * this._breathSpeed) * this._breathAmplitude;
            this._sprite.position.y = this._baseY + breathOffset;
        }
    }

    /** Clean up textures */
    destroy() {
        this.hideEmote();
        for (const row of this._frames) {
            for (const tex of row) {
                tex.destroy();
            }
        }
        this._frames = null;
        this._sprite.destroy();
    }

    // --- Private ---

    _computeFrameSize() {
        const source = this._baseTexture.source || this._baseTexture;
        const w = source.width;
        const h = source.height;

        if (this._type === 'multi') {
            // 12 columns × 8 rows total, each char is 3×4 block
            this._frameW = Math.floor(w / 12);
            this._frameH = Math.floor(h / 8);
        } else {
            // Single: 3 columns × 4 rows
            this._frameW = Math.floor(w / 3);
            this._frameH = Math.floor(h / 4);
        }
    }

    _buildFrames() {
        // Pre-build all 4 rows × 3 columns of textures for this character
        this._frames = [];

        let offsetX = 0;
        let offsetY = 0;

        if (this._type === 'multi') {
            // charIndex 0-3 = top half, 4-7 = bottom half
            const charCol = this._charIndex % 4;
            const charRow = Math.floor(this._charIndex / 4);
            offsetX = charCol * 3 * this._frameW;
            offsetY = charRow * 4 * this._frameH;
        }

        const source = this._baseTexture.source || this._baseTexture;

        for (let row = 0; row < 4; row++) {
            const rowTextures = [];
            for (let col = 0; col < 3; col++) {
                const frame = new PIXI.Rectangle(
                    offsetX + col * this._frameW,
                    offsetY + row * this._frameH,
                    this._frameW,
                    this._frameH,
                );
                const texture = new PIXI.Texture({ source, frame });
                rowTextures.push(texture);
            }
            this._frames.push(rowTextures);
        }
    }

    _getTexture(row, col) {
        return this._frames[row][col];
    }
}

/**
 * Compute direction from movement delta.
 * @param {number} dx - X delta (positive = right)
 * @param {number} dy - Y delta (positive = down)
 * @returns {'up'|'down'|'left'|'right'}
 */
export function directionFromDelta(dx, dy) {
    if (Math.abs(dy) >= Math.abs(dx)) {
        return dy > 0 ? 'down' : 'up';
    }
    return dx > 0 ? 'right' : 'left';
}
