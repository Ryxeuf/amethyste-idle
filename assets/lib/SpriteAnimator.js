import * as PIXI from 'pixi.js';

/**
 * Animated sprite system for RPG Maker VX/MV sprite sheets.
 *
 * Supports three layouts:
 * - "single": one character per sheet (3 columns × 4 rows)
 * - "multi": multiple characters per sheet (12 columns × 8 rows),
 *   each character is a 3×4 block identified by `charIndex` (0-7).
 * - "avatar": modular avatar system (8 columns × 8+ rows, 64×64 frames),
 *   supports multiple animations (walk, stand, run, jump, push, pull)
 *   auto-detected from sheet height.
 *
 * Row order: 0=down, 1=left, 2=right, 3=up
 * Column order (single/multi): 0,1,2 = walk cycle (column 1 = idle/stand frame)
 * Column order (avatar): 0-7 = sequential animation frames
 *
 * Features:
 * - Idle breathing animation (subtle Y oscillation when stationary) — legacy types
 * - Animated idle (stand) from spritesheet frames — avatar type
 * - Emote bubbles above sprites (!, ?, ♥, ...)
 * - Animation states: idle, walk, interact
 */

const DIRECTION_ROW = { down: 0, left: 1, right: 2, up: 3 };
const WALK_FRAMES = [0, 1, 2, 1]; // left foot, stand, right foot, stand
const DEFAULT_ANIM_FPS = 8;

/** Avatar spritesheet constants */
const AVATAR_FRAME_SIZE = 64;
const AVATAR_COLS = 8;
const AVATAR_IDLE_FPS = 4;

/**
 * Animation mapping for avatar type.
 * Each animation occupies 4 consecutive rows (one per direction).
 * Available animations are auto-detected from sheet height.
 */
const AVATAR_ANIMATIONS = {
    walk:  { startRow: 0,  frames: 8 },
    stand: { startRow: 4,  frames: 8 },
    run:   { startRow: 8,  frames: 8 },
    jump:  { startRow: 12, frames: 8 },
    push:  { startRow: 16, frames: 8 },
    pull:  { startRow: 20, frames: 8 },
};

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

export { EMOTE_TYPES, ANIM_STATE, AVATAR_ANIMATIONS };

export default class SpriteAnimator {
    /**
     * @param {object} opts
     * @param {PIXI.Texture} opts.texture - The loaded sprite sheet base texture
     * @param {string} opts.type - "single", "multi", or "avatar"
     * @param {number} [opts.charIndex=0] - Character index for multi sheets (0-7)
     * @param {number} [opts.animFps=8] - Animation speed in frames per second
     * @param {boolean} [opts.enableBreathing=true] - Enable idle breathing effect (legacy types only)
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

        // Avatar animation tracking
        this._currentAnimation = 'walk';
        this._availableAnimations = null;

        // Idle breathing effect (legacy types only)
        this._breathingEnabled = enableBreathing && type !== 'avatar';
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

        if (this._type === 'avatar') {
            const row = AVATAR_ANIMATIONS.stand.startRow + DIRECTION_ROW.down;
            this._sprite = new PIXI.Sprite(this._getTexture(row, 0));
        } else {
            this._sprite = new PIXI.Sprite(this._getTexture(DIRECTION_ROW.down, 1));
        }
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

    /** Available animations for avatar type (null for legacy types) */
    get availableAnimations() {
        return this._availableAnimations;
    }

    /** Current animation name (avatar type only) */
    get currentAnimation() {
        return this._currentAnimation;
    }

    /**
     * Switch the active animation for avatar type sprites.
     * Only available for 'avatar' type. The animation must exist in the spritesheet.
     * When playing, immediately switches to the new animation from frame 0.
     * When idle, the change takes effect on the next play() call.
     *
     * @param {string} name - Animation name ('walk', 'stand', 'run', 'jump', 'push', 'pull')
     * @returns {boolean} true if animation was set, false if unavailable or not avatar type
     */
    setAnimation(name) {
        if (this._type !== 'avatar') return false;
        if (!this._availableAnimations || !this._availableAnimations[name]) return false;

        if (name === this._currentAnimation) return true;

        this._currentAnimation = name;
        this._elapsed = 0;
        this._frameIndex = 0;

        if (!this._playing) {
            const anim = AVATAR_ANIMATIONS[name];
            const row = anim.startRow + DIRECTION_ROW[this._direction];
            this._sprite.texture = this._getTexture(row, 0);
        }

        return true;
    }

    /**
     * Set facing direction.
     * @param {'up'|'down'|'left'|'right'} dir
     */
    setDirection(dir) {
        if (DIRECTION_ROW[dir] !== undefined && dir !== this._direction) {
            this._direction = dir;
            if (!this._playing) {
                if (this._type === 'avatar') {
                    const row = AVATAR_ANIMATIONS.stand.startRow + DIRECTION_ROW[dir];
                    this._sprite.texture = this._getTexture(row, 0);
                } else {
                    this._sprite.texture = this._getTexture(DIRECTION_ROW[dir], 1);
                }
            }
        }
    }

    /** Start animation (walk by default, or current animation set via setAnimation) */
    play() {
        if (this._playing) return;
        this._playing = true;
        this._state = ANIM_STATE.WALK;
        this._elapsed = 0;
        this._frameIndex = 0;
        // Reset breathing offset when walking
        this._sprite.position.y = this._baseY;
    }

    /** Stop animation and return to idle frame */
    stop() {
        this._playing = false;
        this._state = ANIM_STATE.IDLE;
        this._elapsed = 0;
        this._frameIndex = 0;
        if (this._type === 'avatar') {
            const row = AVATAR_ANIMATIONS.stand.startRow + DIRECTION_ROW[this._direction];
            this._sprite.texture = this._getTexture(row, 0);
        } else {
            this._sprite.texture = this._getTexture(DIRECTION_ROW[this._direction], 1);
        }
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
            // Floating bob effect on emote (use visual size accounting for scale)
            if (this._emoteContainer) {
                const visualH = this._frameH * this._sprite.scale.y;
                const visualW = this._frameW * this._sprite.scale.x;
                this._emoteContainer.position.y = -visualH - 4 + Math.sin(this._emoteTimer * 0.005) * 2;
                this._emoteContainer.position.x = visualW / 2;
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

            if (this._type === 'avatar') {
                const anim = AVATAR_ANIMATIONS[this._currentAnimation] || AVATAR_ANIMATIONS.walk;
                const frameDuration = 1000 / this._animFps;
                this._frameIndex = Math.floor(this._elapsed / frameDuration) % anim.frames;
                const row = anim.startRow + DIRECTION_ROW[this._direction];
                this._sprite.texture = this._getTexture(row, this._frameIndex);
                return;
            }

            const frameDuration = 1000 / this._animFps;
            const totalFrames = WALK_FRAMES.length;

            this._frameIndex = Math.floor(this._elapsed / frameDuration) % totalFrames;
            const col = WALK_FRAMES[this._frameIndex];
            const row = DIRECTION_ROW[this._direction];

            this._sprite.texture = this._getTexture(row, col);
            return;
        }

        // Avatar idle: animated stand from spritesheet frames
        if (this._type === 'avatar') {
            this._elapsed += deltaMs;
            const anim = AVATAR_ANIMATIONS.stand;
            const frameDuration = 1000 / AVATAR_IDLE_FPS;
            this._frameIndex = Math.floor(this._elapsed / frameDuration) % anim.frames;
            const row = anim.startRow + DIRECTION_ROW[this._direction];
            this._sprite.texture = this._getTexture(row, this._frameIndex);
            return;
        }

        // Legacy idle breathing animation
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

        if (this._type === 'avatar') {
            this._frameW = AVATAR_FRAME_SIZE;
            this._frameH = AVATAR_FRAME_SIZE;
            this._cols = AVATAR_COLS;
            this._totalRows = Math.floor(h / AVATAR_FRAME_SIZE);
        } else if (this._type === 'multi') {
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
        this._frames = [];
        const source = this._baseTexture.source || this._baseTexture;

        if (this._type === 'avatar') {
            // Build full grid: totalRows × 8 columns
            for (let row = 0; row < this._totalRows; row++) {
                const rowTextures = [];
                for (let col = 0; col < AVATAR_COLS; col++) {
                    const frame = new PIXI.Rectangle(
                        col * this._frameW,
                        row * this._frameH,
                        this._frameW,
                        this._frameH,
                    );
                    rowTextures.push(new PIXI.Texture({ source, frame }));
                }
                this._frames.push(rowTextures);
            }
            this._detectAvailableAnimations();
            return;
        }

        // Legacy: pre-build 4 rows × 3 columns for this character
        let offsetX = 0;
        let offsetY = 0;

        if (this._type === 'multi') {
            // charIndex 0-3 = top half, 4-7 = bottom half
            const charCol = this._charIndex % 4;
            const charRow = Math.floor(this._charIndex / 4);
            offsetX = charCol * 3 * this._frameW;
            offsetY = charRow * 4 * this._frameH;
        }

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

    /** Detect which animations are available based on sheet height */
    _detectAvailableAnimations() {
        this._availableAnimations = {};
        for (const [name, def] of Object.entries(AVATAR_ANIMATIONS)) {
            if (def.startRow + 4 <= this._totalRows) {
                this._availableAnimations[name] = def;
            }
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
