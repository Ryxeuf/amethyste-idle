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
}
