/**
 * Persistent cache for composite avatar textures, backed by IndexedDB.
 *
 * Complements the in-memory LRU in AvatarSpriteSheetCache by keeping
 * composites across sessions. Keyed by avatarHash; invalidated whenever the
 * backend recomputes the hash (equipment changes, appearance updates).
 *
 * Every method is defensive: if IndexedDB is unavailable or an operation
 * fails, reads resolve to null and writes are silently skipped so the caller
 * always falls back to synchronous composition.
 *
 * Schema:
 *   store `avatar_textures` { hash (key), blob: Blob, updatedAt: number }
 */
const DB_NAME = 'amethyste_avatar_cache';
const DB_VERSION = 1;
const STORE = 'avatar_textures';
const DEFAULT_MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000; // 30 days

export default class AvatarTexturePersistentCache {
    constructor({ dbName = DB_NAME, storeName = STORE, maxAgeMs = DEFAULT_MAX_AGE_MS } = {}) {
        this._dbName = dbName;
        this._storeName = storeName;
        this._maxAgeMs = maxAgeMs;
        this._dbPromise = null;
        this._disabled = typeof indexedDB === 'undefined';
    }

    /**
     * @param {string} hash
     * @returns {Promise<Blob|null>}
     */
    async get(hash) {
        if (this._disabled || !hash) return null;

        try {
            const db = await this._openDb();
            if (!db) return null;

            const entry = await this._wrap(
                db.transaction(this._storeName, 'readonly').objectStore(this._storeName).get(hash),
            );

            if (!entry || !entry.blob) return null;

            if (this._isStale(entry)) {
                this.delete(hash);
                return null;
            }

            return entry.blob;
        } catch {
            return null;
        }
    }

    /**
     * @param {string} hash
     * @param {Blob} blob
     * @returns {Promise<boolean>} Resolves true on success, false on failure (non-throwing).
     */
    async set(hash, blob) {
        if (this._disabled || !hash || !blob) return false;

        try {
            const db = await this._openDb();
            if (!db) return false;

            await this._wrap(
                db.transaction(this._storeName, 'readwrite').objectStore(this._storeName).put({
                    hash,
                    blob,
                    updatedAt: Date.now(),
                }),
            );

            return true;
        } catch {
            return false;
        }
    }

    /**
     * @param {string} hash
     * @returns {Promise<boolean>}
     */
    async delete(hash) {
        if (this._disabled || !hash) return false;

        try {
            const db = await this._openDb();
            if (!db) return false;

            await this._wrap(
                db.transaction(this._storeName, 'readwrite').objectStore(this._storeName).delete(hash),
            );

            return true;
        } catch {
            return false;
        }
    }

    /**
     * @returns {Promise<boolean>}
     */
    async clear() {
        if (this._disabled) return false;

        try {
            const db = await this._openDb();
            if (!db) return false;

            await this._wrap(
                db.transaction(this._storeName, 'readwrite').objectStore(this._storeName).clear(),
            );

            return true;
        } catch {
            return false;
        }
    }

    _isStale(entry) {
        if (!this._maxAgeMs || this._maxAgeMs <= 0) return false;
        const updatedAt = typeof entry.updatedAt === 'number' ? entry.updatedAt : 0;
        return Date.now() - updatedAt > this._maxAgeMs;
    }

    _openDb() {
        if (this._disabled) return Promise.resolve(null);

        if (this._dbPromise) return this._dbPromise;

        this._dbPromise = new Promise((resolve) => {
            let request;
            try {
                request = indexedDB.open(this._dbName, DB_VERSION);
            } catch {
                this._disabled = true;
                resolve(null);
                return;
            }

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(this._storeName)) {
                    db.createObjectStore(this._storeName, { keyPath: 'hash' });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => {
                this._disabled = true;
                resolve(null);
            };
            request.onblocked = () => resolve(null);
        });

        return this._dbPromise;
    }

    _wrap(request) {
        return new Promise((resolve, reject) => {
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
}
