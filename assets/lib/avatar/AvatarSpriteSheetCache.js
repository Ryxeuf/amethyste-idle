/**
 * Cache LRU des sprite sheets composites d'avatar.
 *
 * Clef = hash de composition (AvatarHashGenerator cote backend).
 * Valeur = PIXI.RenderTexture composite prete a etre consommee par SpriteAnimator.
 *
 * Strategie : Map preserve l'ordre d'insertion. Quand on relit une cle, on la re-insere
 * pour la rapprocher de la "tete" (most recently used). Quand la taille depasse
 * `maxEntries`, on expulse la cle la plus ancienne et on detruit sa texture.
 */
export default class AvatarSpriteSheetCache {
    constructor(maxEntries = 128) {
        this.maxEntries = maxEntries;
        this.map = new Map();
    }

    get(key) {
        if (!this.map.has(key)) {
            return null;
        }

        const value = this.map.get(key);
        this.map.delete(key);
        this.map.set(key, value);

        return value;
    }

    set(key, texture) {
        if (this.map.has(key)) {
            const previous = this.map.get(key);
            if (previous && previous !== texture && typeof previous.destroy === 'function') {
                previous.destroy(true);
            }
            this.map.delete(key);
        }

        this.map.set(key, texture);

        if (this.map.size > this.maxEntries) {
            const oldestKey = this.map.keys().next().value;
            const oldestTexture = this.map.get(oldestKey);
            if (oldestTexture && typeof oldestTexture.destroy === 'function') {
                oldestTexture.destroy(true);
            }
            this.map.delete(oldestKey);
        }
    }

    delete(key) {
        if (!this.map.has(key)) {
            return;
        }

        const texture = this.map.get(key);
        if (texture && typeof texture.destroy === 'function') {
            texture.destroy(true);
        }

        this.map.delete(key);
    }

    clear() {
        for (const texture of this.map.values()) {
            if (texture && typeof texture.destroy === 'function') {
                texture.destroy(true);
            }
        }

        this.map.clear();
    }

    get size() {
        return this.map.size;
    }
}
