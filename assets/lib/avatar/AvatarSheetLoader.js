/**
 * Lazy loader for avatar sprite sheets.
 *
 * Historically all catalog sheets (body/hair/beard/facemark/gear) were preloaded
 * at boot, which was wasteful when only a handful of avatars were visible.
 *
 * This loader keeps track of sheets already loaded in the shared `spriteTextures`
 * map and deduplicates concurrent requests via `_pending`. It accepts a payload
 * of shape `{ baseSheet, layers: [{ sheet }] }` and resolves once every required
 * texture has been registered, ready for composition by AvatarTextureComposer.
 */
export default class AvatarSheetLoader {
    /**
     * @param {object} opts
     * @param {{ load: (path: string) => Promise<any> }} opts.assetsLoader - Usually PIXI.Assets
     * @param {Record<string, any>} opts.spriteTextures - Shared texture cache keyed by sheet path
     * @param {(texture: any) => void} [opts.onTextureLoaded] - Hook called after a texture lands (e.g. to set scaleMode)
     */
    constructor({ assetsLoader, spriteTextures, onTextureLoaded = null }) {
        this._assets = assetsLoader;
        this._textures = spriteTextures;
        this._onTextureLoaded = onTextureLoaded;
        this._pending = new Map();
    }

    /**
     * Ensure a single sheet is loaded. Safe to call concurrently.
     *
     * @param {string} sheetPath
     * @returns {Promise<any|null>} Resolves with the loaded texture, or null on failure.
     */
    ensureSheet(sheetPath) {
        if (!sheetPath) {
            return Promise.resolve(null);
        }

        if (this._textures[sheetPath]) {
            return Promise.resolve(this._textures[sheetPath]);
        }

        const existing = this._pending.get(sheetPath);
        if (existing) {
            return existing;
        }

        const promise = Promise.resolve(this._assets.load(sheetPath))
            .then((texture) => {
                if (this._onTextureLoaded && texture) {
                    this._onTextureLoaded(texture);
                }
                this._textures[sheetPath] = texture;
                this._pending.delete(sheetPath);

                return texture;
            })
            .catch(() => {
                this._pending.delete(sheetPath);

                return null;
            });

        this._pending.set(sheetPath, promise);

        return promise;
    }

    /**
     * Ensure every sheet referenced by an avatar payload is loaded.
     *
     * @param {{ baseSheet?: string, layers?: Array<{ sheet?: string }> }|null|undefined} payload
     * @returns {Promise<void>}
     */
    ensurePayload(payload) {
        if (!payload) {
            return Promise.resolve();
        }

        const sheets = this._collectSheets([payload]);
        if (sheets.length === 0) {
            return Promise.resolve();
        }

        return Promise.all(sheets.map((sheet) => this.ensureSheet(sheet))).then(() => undefined);
    }

    /**
     * Ensure every sheet referenced by a batch of avatar payloads is loaded in parallel.
     *
     * @param {Array<{ baseSheet?: string, layers?: Array<{ sheet?: string }> }|null|undefined>} payloads
     * @returns {Promise<void>}
     */
    ensurePayloads(payloads) {
        const sheets = this._collectSheets(payloads || []);
        if (sheets.length === 0) {
            return Promise.resolve();
        }

        return Promise.all(sheets.map((sheet) => this.ensureSheet(sheet))).then(() => undefined);
    }

    /**
     * @param {Array<{ baseSheet?: string, layers?: Array<{ sheet?: string }> }|null|undefined>} payloads
     * @returns {string[]} unique sheet paths not yet in the texture cache
     */
    _collectSheets(payloads) {
        const unique = new Set();

        for (const payload of payloads) {
            if (!payload) continue;
            if (payload.baseSheet && !this._textures[payload.baseSheet]) {
                unique.add(payload.baseSheet);
            }
            for (const layer of payload.layers || []) {
                if (layer && layer.sheet && !this._textures[layer.sheet]) {
                    unique.add(layer.sheet);
                }
            }
        }

        return Array.from(unique);
    }
}
