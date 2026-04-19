import * as PIXI from 'pixi.js';
import SpriteAnimator from '../SpriteAnimator.js';
import AvatarTextureComposer from './AvatarTextureComposer.js';
import AvatarSpriteSheetCache from './AvatarSpriteSheetCache.js';

/**
 * Factory for creating SpriteAnimator instances.
 *
 * Two pipelines coexist:
 * - legacy: spriteKey -> SpriteAnimator (type single/multi) for mobs & NPCs
 * - avatar: baseSheet + layers -> composite texture -> SpriteAnimator (type avatar) for players
 *
 * AVT-37: a persistent IndexedDB cache can be injected to survive sessions.
 * Reads happen via `prefetchFromPersistentCache(hashes)`; writes are async
 * fire-and-forget right after each composition.
 */
export default class AvatarAnimatorFactory {
    /**
     * @param {object} opts
     * @param {PIXI.Renderer} opts.renderer - PixiJS renderer for texture composition
     * @param {object} opts.spriteConfig - Sprite config map from /api/map/config
     * @param {object} opts.spriteTextures - Preloaded textures keyed by sheet path
     * @param {AvatarSpriteSheetCache} [opts.cache] - Optional LRU cache instance
     * @param {{ get: (hash: string) => Promise<Blob|null>, set: (hash: string, blob: Blob) => Promise<boolean>, delete: (hash: string) => Promise<boolean>, clear: () => Promise<boolean> } | null} [opts.persistentCache]
     */
    constructor({ renderer, spriteConfig = {}, spriteTextures = {}, cache = null, persistentCache = null }) {
        this.renderer = renderer;
        this.spriteConfig = spriteConfig;
        this.spriteTextures = spriteTextures;
        this.cache = cache || new AvatarSpriteSheetCache(128);
        this.composer = new AvatarTextureComposer({ renderer });
        this.persistentCache = persistentCache;
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
            this._persistCompositeAsync(avatarHash, compositeTexture);
        }

        return new SpriteAnimator({
            texture: compositeTexture,
            type: 'avatar',
        });
    }

    /**
     * Populate the in-memory cache from the persistent store for a batch of
     * avatar hashes (typically those about to be rendered). Unknown or stale
     * entries are silently skipped so the caller falls back to synchronous
     * composition via createFromAvatarPayload.
     *
     * @param {Iterable<string>} avatarHashes
     * @returns {Promise<number>} Number of hashes successfully hydrated from disk.
     */
    async prefetchFromPersistentCache(avatarHashes) {
        if (!this.persistentCache) return 0;

        const unique = new Set();
        for (const hash of avatarHashes || []) {
            if (hash && !this.cache.get(hash)) unique.add(hash);
        }

        if (unique.size === 0) return 0;

        const results = await Promise.all(
            Array.from(unique).map((hash) => this._hydrateHash(hash)),
        );

        return results.filter(Boolean).length;
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

        if (this.persistentCache) {
            this.persistentCache.delete(avatarHash).catch(() => {});
        }
    }

    /** Clear the entire avatar texture cache (memory + persistent). */
    clear() {
        this.cache.clear();
        if (this.persistentCache) {
            this.persistentCache.clear().catch(() => {});
        }
    }

    async _hydrateHash(avatarHash) {
        try {
            const blob = await this.persistentCache.get(avatarHash);
            if (!blob) return false;

            if (this.cache.get(avatarHash)) return false;

            const texture = await this._textureFromBlob(blob);
            if (!texture) return false;

            this.cache.set(avatarHash, texture);
            return true;
        } catch {
            return false;
        }
    }

    async _textureFromBlob(blob) {
        if (typeof createImageBitmap !== 'function') return null;

        try {
            const bitmap = await createImageBitmap(blob);
            const texture = PIXI.Texture.from(bitmap);
            if (texture && texture.source) {
                texture.source.scaleMode = 'nearest';
            }
            return texture;
        } catch {
            return null;
        }
    }

    _persistCompositeAsync(avatarHash, compositeTexture) {
        if (!this.persistentCache || !this.renderer || !this.renderer.extract) return;

        let canvas;
        try {
            canvas = this.renderer.extract.canvas(compositeTexture);
        } catch {
            return;
        }

        if (!canvas || typeof canvas.toBlob !== 'function') return;

        canvas.toBlob((blob) => {
            if (!blob) return;
            this.persistentCache.set(avatarHash, blob).catch(() => {});
        }, 'image/png');
    }
}
