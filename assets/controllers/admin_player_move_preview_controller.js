import { Controller } from '@hotwired/stimulus'

/** Apercu canvas : rayon fixe autour des coordonnees saisies (formulaire deplacement PJ). */
export default class extends Controller {
    static values = {
        previewUrl: String,
        radius: { type: Number, default: 5 },
    }

    static targets = ['canvas', 'coordsInput', 'mapSelect', 'status']

    _debounceTimer = null
    _displayTile = 24
    _tilesets = []
    _tilesetImages = {}
    _loadedTilesetKey = ''
    _lastCenter = { x: 0, y: 0 }

    connect() {
        this._ctx = this.canvasTarget.getContext('2d')
        this._resizeCanvas()
        this.refresh()

        this.coordsInputTarget.addEventListener('input', () => this._scheduleRefresh())
        this.mapSelectTarget.addEventListener('change', () => this.refresh())
    }

    disconnect() {
        if (this._debounceTimer) {
            clearTimeout(this._debounceTimer)
        }
    }

    _scheduleRefresh() {
        if (this._debounceTimer) {
            clearTimeout(this._debounceTimer)
        }
        this._debounceTimer = setTimeout(() => {
            this._debounceTimer = null
            this.refresh()
        }, 200)
    }

    _resizeCanvas() {
        const n = this.radiusValue * 2 + 1
        const s = this._displayTile
        this.canvasTarget.width = n * s
        this.canvasTarget.height = n * s
    }

    _parseCoords(raw) {
        const t = (raw || '').trim()
        const m = t.match(/^(\d+)\.(\d+)$/)
        if (!m) {
            return null
        }

        return { x: parseInt(m[1], 10), y: parseInt(m[2], 10) }
    }

    _mapId() {
        const v = this.mapSelectTarget.value

        return v ? parseInt(v, 10) : NaN
    }

    _tilesetKey(tilesets) {
        return tilesets.map((t) => t.name + ':' + t.firstGid).join('|')
    }

    _imageCacheKey(ts) {
        return `${ts.name}_${ts.firstGid}`
    }

    _resolveImageSrc(url) {
        if (!url) {
            return ''
        }
        if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('//')) {
            if (url.startsWith('//')) {
                return `${window.location.protocol}${url}`
            }

            return url
        }

        const path = url.startsWith('/') ? url : `/${url}`

        return new URL(path, window.location.origin).href
    }

    /** Evite /admin/.../api/... si l URL API est relative sans slash initial. */
    _previewRequestUrl() {
        let p = (this.previewUrlValue || '/api/map/preview').trim()
        if (!p.startsWith('http') && !p.startsWith('/')) {
            p = `/${p}`
        }

        return new URL(p, window.location.origin)
    }

    async _ensureTilesets(tilesets) {
        const sorted = [...tilesets].sort((a, b) => a.firstGid - b.firstGid)
        const key = this._tilesetKey(sorted)
        if (key === this._loadedTilesetKey && Object.keys(this._tilesetImages).length > 0) {
            this._tilesets = sorted

            return
        }

        this._tilesets = sorted
        this._tilesetImages = {}
        this._loadedTilesetKey = key

        await Promise.all(
            sorted.map(
                (ts) =>
                    new Promise((resolve) => {
                        const img = new Image()
                        const cacheKey = this._imageCacheKey(ts)
                        const src = this._resolveImageSrc(ts.image)
                        img.onload = () => {
                            if (img.naturalWidth > 0) {
                                this._tilesetImages[cacheKey] = img
                            }
                            resolve()
                        }
                        img.onerror = () => resolve()
                        img.src = src || ts.image
                    }),
            ),
        )
    }

    _findTileset(gid) {
        const g = Number(gid)
        let result = null
        for (const ts of this._tilesets) {
            const first = Number(ts.firstGid)
            if (first <= g) {
                result = ts
            } else {
                break
            }
        }

        return result
    }

    _movementFillStyle(m) {
        if (m === -1) {
            return '#3d1f1f'
        }
        if (m === 2) {
            return '#1f2d3d'
        }
        if (m === 4) {
            return '#1a3d2a'
        }

        return '#1f3d1f'
    }

    _drawTileLayers(ctx, cell, px, py, destSize) {
        if (!cell.l || cell.l.length === 0) {
            return false
        }
        let drew = false
        for (const gid of cell.l) {
            const g = Number(gid)
            const tileset = this._findTileset(g)
            if (!tileset || tileset.name === 'collisions') {
                continue
            }

            const img = this._tilesetImages[this._imageCacheKey(tileset)]
            if (!img || !img.naturalWidth) {
                continue
            }

            const firstGid = Number(tileset.firstGid)
            const localId = g - firstGid
            if (localId < 0) {
                continue
            }

            const cols = Number(tileset.columns) || 32
            const tw = Number(tileset.tileWidth) || 32
            const th = Number(tileset.tileHeight) || 32
            const srcX = (localId % cols) * tw
            const srcY = Math.floor(localId / cols) * th
            if (srcX >= img.naturalWidth || srcY >= img.naturalHeight) {
                continue
            }

            try {
                ctx.drawImage(
                    img,
                    srcX,
                    srcY,
                    tw,
                    th,
                    px,
                    py,
                    destSize,
                    destSize,
                )
                drew = true
            } catch {
                /* drawImage peut echouer si image taintee / CORS */
            }
        }

        return drew
    }

    _draw(ctx, data, cx, cy) {
        const r = data.radius
        const n = r * 2 + 1
        const s = this._displayTile
        const cellByKey = {}
        for (const c of data.cells) {
            cellByKey[c.x + '.' + c.y] = c
        }

        ctx.fillStyle = '#0d0d1a'
        ctx.fillRect(0, 0, this.canvasTarget.width, this.canvasTarget.height)

        for (let iy = 0; iy < n; iy++) {
            for (let ix = 0; ix < n; ix++) {
                const wx = cx - r + ix
                const wy = cy - r + iy
                const key = wx + '.' + wy
                const cell = cellByKey[key]
                const px = ix * s
                const py = iy * s

                if (!cell) {
                    ctx.fillStyle = '#151525'
                    ctx.fillRect(px, py, s, s)
                    continue
                }

                const m = cell.m !== undefined ? Number(cell.m) : (cell.w ? 0 : -1)
                ctx.fillStyle = this._movementFillStyle(m)
                ctx.fillRect(px, py, s, s)

                if (cell.l && cell.l.length > 0) {
                    this._drawTileLayers(ctx, cell, px, py, s)
                }

                if (cell.m === -1) {
                    ctx.fillStyle = 'rgba(255, 0, 0, 0.28)'
                    ctx.fillRect(px, py, s, s)
                } else if (cell.m === 2) {
                    ctx.fillStyle = 'rgba(0, 100, 255, 0.2)'
                    ctx.fillRect(px, py, s, s)
                } else if (cell.m === 4) {
                    ctx.fillStyle = 'rgba(0, 200, 100, 0.2)'
                    ctx.fillRect(px, py, s, s)
                }
            }
        }

        ctx.strokeStyle = 'rgba(255,255,255,0.12)'
        ctx.lineWidth = 1
        for (let i = 0; i <= n; i++) {
            const p = i * s
            ctx.beginPath()
            ctx.moveTo(p, 0)
            ctx.lineTo(p, n * s)
            ctx.stroke()
            ctx.beginPath()
            ctx.moveTo(0, p)
            ctx.lineTo(n * s, p)
            ctx.stroke()
        }

        const cix = r
        const ciy = r
        const cpx = cix * s
        const cpy = ciy * s
        ctx.strokeStyle = '#facc15'
        ctx.lineWidth = 2
        ctx.strokeRect(cpx + 1, cpy + 1, s - 2, s - 2)
    }

    async refresh() {
        const mapId = this._mapId()
        const coords = this._parseCoords(this.coordsInputTarget.value)

        if (!Number.isFinite(mapId) || mapId <= 0) {
            this._setStatus('Choisissez une carte.')
            this._clearCanvas()

            return
        }

        if (!coords) {
            this._setStatus('Coordonnees invalides (format x.y).')
            this._clearCanvas()

            return
        }

        this._lastCenter = coords
        this._setStatus('Chargement…')

        const url = this._previewRequestUrl()
        url.searchParams.set('mapId', String(mapId))
        url.searchParams.set('x', String(coords.x))
        url.searchParams.set('y', String(coords.y))
        url.searchParams.set('radius', String(this.radiusValue))

        try {
            const res = await fetch(url.toString(), { credentials: 'same-origin' })
            if (!res.ok) {
                const err = await res.json().catch(() => ({}))
                this._setStatus(err.error || 'Erreur ' + res.status)
                this._clearCanvas()

                return
            }

            const data = await res.json()
            const tsList = data.tilesets || []
            const cellCount = (data.cells || []).length
            await this._ensureTilesets(tsList)
            this._draw(this._ctx, data, coords.x, coords.y)
            if (cellCount === 0) {
                this._setStatus(
                    `Aucune case dans cette zone (hors carte ou mauvaises coordonnees). Centre ${coords.x}.${coords.y}.`,
                )
            } else if (tsList.length > 0 && Object.keys(this._tilesetImages).length === 0) {
                this._setStatus(
                    `Centre : ${coords.x}.${coords.y} — images terrain introuvables (deposez les PNG sous assets/styles/images/terrain/ puis asset-map:compile).`,
                )
            } else {
                this._setStatus(`Centre : ${coords.x}.${coords.y} — rayon ${data.radius} cases`)
            }
        } catch (e) {
            this._setStatus('Impossible de charger l apercu.')
            this._clearCanvas()
        }
    }

    _setStatus(msg) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = msg
        }
    }

    _clearCanvas() {
        const ctx = this._ctx
        ctx.fillStyle = '#0d0d1a'
        ctx.fillRect(0, 0, this.canvasTarget.width, this.canvasTarget.height)
    }
}
