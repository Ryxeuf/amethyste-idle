import * as PIXI from 'pixi.js';
import SpriteAnimator from '../SpriteAnimator.js';
import AvatarTextureComposer from './AvatarTextureComposer.js';
import AvatarSpriteSheetCache from './AvatarSpriteSheetCache.js';

/**
 * Factory adaptée à amethyste-idle.
 *
 * Deux chemins :
 * - legacy : spriteKey -> SpriteAnimator classique
 * - avatar : baseSheet + layers -> texture composite -> SpriteAnimator classique
 */
export default class AvatarAnimatorFactory {
    constructor({ renderer, spriteConfig = {}, spriteTextures = {}, cache = null }) {
        this.renderer = renderer;
        this.spriteConfig = spriteConfig;
        this.spriteTextures = spriteTextures;
        this.cache = cache || new AvatarSpriteSheetCache(128);
        this.composer = new AvatarTextureComposer({ renderer });
    }

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

    createFromAvatarPayload(avatarHash, avatarPayload) {
        if (!avatarPayload || !avatarPayload.baseSheet) {
            return null;
        }

        const cachedTexture = avatarHash ? this.cache.get(avatarHash) : null;

        if (cachedTexture) {
            return new SpriteAnimator({
                texture: cachedTexture,
                type: 'single',
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
            type: 'single',
        });
    }

    invalidateAvatarHash(avatarHash) {
        if (!avatarHash) {
            return;
        }

        this.cache.delete(avatarHash);
    }

    clear() {
        this.cache.clear();
    }
}
