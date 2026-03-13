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
 */

const DIRECTION_ROW = { down: 0, left: 1, right: 2, up: 3 };
const WALK_FRAMES = [0, 1, 2, 1]; // left foot, stand, right foot, stand
const DEFAULT_ANIM_FPS = 8;

export default class SpriteAnimator {
    /**
     * @param {object} opts
     * @param {PIXI.Texture} opts.texture - The loaded sprite sheet base texture
     * @param {string} opts.type - "single" or "multi"
     * @param {number} [opts.charIndex=0] - Character index for multi sheets (0-7)
     * @param {number} [opts.animFps=8] - Animation speed in frames per second
     */
    constructor({ texture, type = 'single', charIndex = 0, animFps = DEFAULT_ANIM_FPS }) {
        this._baseTexture = texture;
        this._type = type;
        this._charIndex = charIndex;
        this._animFps = animFps;

        this._direction = 'down';
        this._playing = false;
        this._elapsed = 0;
        this._frameIndex = 0;

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
        this._elapsed = 0;
        this._frameIndex = 0;
    }

    /** Stop walk animation and return to idle frame */
    stop() {
        this._playing = false;
        this._elapsed = 0;
        this._frameIndex = 0;
        this._sprite.texture = this._getTexture(DIRECTION_ROW[this._direction], 1);
    }

    /** @returns {boolean} */
    get isPlaying() {
        return this._playing;
    }

    /**
     * Update animation state. Call every frame.
     * @param {number} deltaMs - Time elapsed since last frame in milliseconds
     */
    update(deltaMs) {
        if (!this._playing) return;

        this._elapsed += deltaMs;
        const frameDuration = 1000 / this._animFps;
        const totalFrames = WALK_FRAMES.length;

        this._frameIndex = Math.floor(this._elapsed / frameDuration) % totalFrames;
        const col = WALK_FRAMES[this._frameIndex];
        const row = DIRECTION_ROW[this._direction];

        this._sprite.texture = this._getTexture(row, col);
    }

    /** Clean up textures */
    destroy() {
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
