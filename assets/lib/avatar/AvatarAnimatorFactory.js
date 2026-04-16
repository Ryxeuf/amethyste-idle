import SpriteAnimator from '../SpriteAnimator.js';
import AvatarTextureComposer from './AvatarTextureComposer.js';
import AvatarSpriteSheetCache from './AvatarSpriteSheetCache.js';

/**
 * Factory for creating SpriteAnimator instances.
 *
 * Two pipelines coexist:
 * - legacy: spriteKey -> SpriteAnimator (type single/multi) for mobs & NPCs
 * - avatar: baseSheet + layers -> composite texture -> SpriteAnimator (type avatar) for players
 */
export default class AvatarAnimatorFactory {
    /**
     * @param {object} opts
     * @param {PIXI.Renderer} opts.renderer - PixiJS renderer for texture composition
     * @param {object} opts.spriteConfig - Sprite config map from /api/map/config
     * @param {object} opts.spriteTextures - Preloaded textures keyed by sheet path
     * @param {AvatarSpriteSheetCache} [opts.cache] - Optional LRU cache instance
     */
    constructor({ renderer, spriteConfig = {}, spriteTextures = {}, cache = null }) {
        this.renderer = renderer;
        this.spriteConfig = spriteConfig;
        this.spriteTextures = spriteTextures;
        this.cache = cache || new AvatarSpriteSheetCache(128);
        this.composer = new AvatarTextureComposer({ renderer });
    }

    /**
     * Create a SpriteAnimator from a legacy spriteKey (mobs, NPCs).
     * Uses the existing single/multi pipeline — no composition.
     *
     * @param {string} spriteKey - Key in spriteConfig (e.g. 'slime_green', 'pnj_elder')
     * @returns {SpriteAnimator|null}
     */
    createFromLegacySpriteKey(spriteKey) {
        const cfg = this.spriteConfig[spriteKey];
        if (!cfg) {
            return null;
        }

        const texture = this.spriteTextures[cfg.sheet];
        if (!texture) {
            return null;
        }

        return new SpriteAnimator({
            texture,
            type: cfg.type || 'single',
            charIndex: cfg.charIndex || 0,
        });
    }

    /**
     * Create a SpriteAnimator from an avatar payload (players).
     * Composes baseSheet + layers into a single texture, caches by hash.
     *
     * @param {string|null} avatarHash - Unique hash for this avatar composition
     * @param {object} avatarPayload - { baseSheet: string, layers: [{ sheet, tint?, alpha?, visible? }] }
     * @returns {SpriteAnimator|null}
     */
    createFromAvatarPayload(avatarHash, avatarPayload) {
        if (!avatarPayload || !avatarPayload.baseSheet) {
            return null;
        }

        const cachedTexture = avatarHash ? this.cache.get(avatarHash) : null;

        if (cachedTexture) {
            return new SpriteAnimator({
                texture: cachedTexture,
                type: 'avatar',
            });
        }

        const baseTexture = this.spriteTextures[avatarPayload.baseSheet];
        if (!baseTexture) {
            return null;
        }

        const layers = [];

        for (const layer of avatarPayload.layers || []) {
            const texture = this.spriteTextures[layer.sheet];
            if (!texture) {
                continue;
            }

            layers.push({
                texture,
                tint: layer.tint ?? null,
                alpha: layer.alpha ?? 1,
                visible: layer.visible !== false,
            });
        }

        const compositeTexture = this.composer.compose({
            baseTexture,
            layers,
        });

        if (avatarHash) {
            this.cache.set(avatarHash, compositeTexture);
        }

        return new SpriteAnimator({
            texture: compositeTexture,
            type: 'avatar',
        });
    }

    /**
     * Invalidate a cached avatar texture (e.g. when equipment changes).
     *
     * @param {string} avatarHash
     */
    invalidateAvatarHash(avatarHash) {
        if (!avatarHash) {
            return;
        }

        this.cache.delete(avatarHash);
    }

    /** Clear the entire avatar texture cache. */
    clear() {
        this.cache.clear();
    }
}
