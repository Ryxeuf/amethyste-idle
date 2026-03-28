import { Controller } from '@hotwired/stimulus'

/**
 * MED-03 — Tileset Picker
 *
 * Panneau lateral avec les tilesets en grille cliquable.
 * Permet de selectionner un stamp 1x1 ou NxM pour peindre sur la carte.
 *
 * Communique avec admin_map_editor_controller via des evenements customs
 * dispatches sur l'element racine du controller editeur.
 */
export default class extends Controller {
    static values = {
        tilesetsUrl: String,
    }

    static targets = ['canvas', 'tabs', 'gidDisplay', 'layerRadio', 'stampPreview']

    // Tilesets editables : on exclut "collisions" qui est gere automatiquement
    // Les tilesets custom sont automatiquement inclus (detectes dynamiquement)
    _excludedTilesets = ['collisions']
    _tilesets = []
    _tilesetImages = {}
    _activeTileset = null
    _tileSize = 32
    _canvasScale = 1

    // Selection
    _selectedGid = 0
    _stampWidth = 1
    _stampHeight = 1
    _stampGids = [] // Pour les stamps NxM

    // Drag selection
    _isDragging = false
    _dragStart = null // {col, row}
    _dragEnd = null

    // Layer actif (0=background, 1=ground, 2=decoration, 3=overlay)
    _activeLayer = 1

    // Scroll
    _scrollY = 0

    connect() {
        this._loadTilesets()
    }

    async _loadTilesets() {
        const res = await fetch(this.tilesetsUrlValue)
        const data = await res.json()
        this._tilesets = data.tilesets.filter(ts => !this._excludedTilesets.includes(ts.name))

        await this._loadTilesetImages()

        if (this._tilesets.length > 0) {
            this._activeTileset = this._tilesets[0]
            this._renderTabs()
            this._renderCanvas()
        }
    }

    async _loadTilesetImages() {
        const promises = this._tilesets.map(ts => {
            return new Promise((resolve) => {
                const img = new Image()
                img.crossOrigin = 'anonymous'
                img.onload = () => {
                    this._tilesetImages[ts.name] = img
                    resolve()
                }
                img.onerror = () => resolve()
                img.src = ts.image
            })
        })
        await Promise.all(promises)
    }

    _renderTabs() {
        const container = this.tabsTarget
        container.innerHTML = ''

        for (const ts of this._tilesets) {
            const btn = document.createElement('button')
            btn.textContent = ts.name
            btn.dataset.tilesetName = ts.name
            const isActive = this._activeTileset && this._activeTileset.name === ts.name
            btn.className = isActive
                ? 'px-3 py-1.5 rounded-t text-xs font-semibold bg-gray-800 text-purple-300 border border-b-0 border-gray-700'
                : 'px-3 py-1.5 rounded-t text-xs bg-gray-900 text-gray-400 hover:text-gray-200 border border-b-0 border-gray-800'
            btn.addEventListener('click', () => this._switchTileset(ts.name))
            container.appendChild(btn)
        }
    }

    _switchTileset(name) {
        this._activeTileset = this._tilesets.find(ts => ts.name === name)
        this._scrollY = 0
        this._renderTabs()
        this._renderCanvas()
    }

    _renderCanvas() {
        if (!this._activeTileset) return
        const canvas = this.canvasTarget
        const img = this._tilesetImages[this._activeTileset.name]
        if (!img) return

        const ts = this._activeTileset
        const cols = ts.columns
        const rows = Math.ceil(ts.tileCount / cols)
        const containerWidth = canvas.parentElement.clientWidth

        // Scale to fit container width
        this._canvasScale = containerWidth / (cols * this._tileSize)
        const scaledTile = this._tileSize * this._canvasScale
        const visibleHeight = Math.min(rows * scaledTile, 400)

        canvas.width = cols * scaledTile
        canvas.height = visibleHeight
        canvas.style.width = canvas.width + 'px'
        canvas.style.height = canvas.height + 'px'

        const ctx = canvas.getContext('2d')
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        ctx.imageSmoothingEnabled = false

        // Draw visible tiles
        const startRow = Math.floor(this._scrollY / scaledTile)
        const endRow = Math.min(rows, startRow + Math.ceil(visibleHeight / scaledTile) + 1)

        for (let row = startRow; row < endRow; row++) {
            for (let col = 0; col < cols; col++) {
                const tileIdx = row * cols + col
                if (tileIdx >= ts.tileCount) break

                const srcX = col * ts.tileWidth
                const srcY = row * ts.tileHeight
                const destX = col * scaledTile
                const destY = row * scaledTile - this._scrollY

                if (destY + scaledTile < 0 || destY > visibleHeight) continue

                ctx.drawImage(img, srcX, srcY, ts.tileWidth, ts.tileHeight, destX, destY, scaledTile, scaledTile)
            }
        }

        // Grid
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)'
        ctx.lineWidth = 0.5
        for (let col = 0; col <= cols; col++) {
            const x = col * scaledTile
            ctx.beginPath()
            ctx.moveTo(x, 0)
            ctx.lineTo(x, visibleHeight)
            ctx.stroke()
        }
        for (let row = startRow; row <= endRow; row++) {
            const y = row * scaledTile - this._scrollY
            ctx.beginPath()
            ctx.moveTo(0, y)
            ctx.lineTo(canvas.width, y)
            ctx.stroke()
        }

        // Selection highlight
        this._drawSelectionHighlight(ctx, scaledTile, cols)

        // Drag selection preview
        if (this._isDragging && this._dragStart && this._dragEnd) {
            this._drawDragPreview(ctx, scaledTile)
        }
    }

    _drawSelectionHighlight(ctx, scaledTile, cols) {
        if (this._stampGids.length === 0 && this._selectedGid === 0) return

        ctx.strokeStyle = '#a855f7'
        ctx.lineWidth = 2

        if (this._stampWidth === 1 && this._stampHeight === 1 && this._selectedGid > 0) {
            // Single tile selection
            const localId = this._selectedGid - this._activeTileset.firstGid
            if (localId < 0 || localId >= this._activeTileset.tileCount) return
            const col = localId % cols
            const row = Math.floor(localId / cols)
            const x = col * scaledTile
            const y = row * scaledTile - this._scrollY
            ctx.fillStyle = 'rgba(168, 85, 247, 0.3)'
            ctx.fillRect(x, y, scaledTile, scaledTile)
            ctx.strokeRect(x, y, scaledTile, scaledTile)
        } else if (this._stampGids.length > 0) {
            // Multi-tile stamp
            const firstLocalId = this._stampGids[0] - this._activeTileset.firstGid
            if (firstLocalId < 0) return
            const startCol = firstLocalId % cols
            const startRow = Math.floor(firstLocalId / cols)
            const x = startCol * scaledTile
            const y = startRow * scaledTile - this._scrollY
            ctx.fillStyle = 'rgba(168, 85, 247, 0.3)'
            ctx.fillRect(x, y, this._stampWidth * scaledTile, this._stampHeight * scaledTile)
            ctx.strokeRect(x, y, this._stampWidth * scaledTile, this._stampHeight * scaledTile)
        }
    }

    _drawDragPreview(ctx, scaledTile) {
        const minCol = Math.min(this._dragStart.col, this._dragEnd.col)
        const maxCol = Math.max(this._dragStart.col, this._dragEnd.col)
        const minRow = Math.min(this._dragStart.row, this._dragEnd.row)
        const maxRow = Math.max(this._dragStart.row, this._dragEnd.row)

        const x = minCol * scaledTile
        const y = minRow * scaledTile - this._scrollY
        const w = (maxCol - minCol + 1) * scaledTile
        const h = (maxRow - minRow + 1) * scaledTile

        ctx.fillStyle = 'rgba(59, 130, 246, 0.2)'
        ctx.fillRect(x, y, w, h)
        ctx.strokeStyle = '#3b82f6'
        ctx.lineWidth = 2
        ctx.setLineDash([4, 4])
        ctx.strokeRect(x, y, w, h)
        ctx.setLineDash([])
    }

    // --- Canvas events ---

    canvasMouseDown(e) {
        const pos = this._canvasToTile(e)
        if (!pos) return

        this._isDragging = true
        this._dragStart = pos
        this._dragEnd = pos
    }

    canvasMouseMove(e) {
        if (!this._isDragging) return
        const pos = this._canvasToTile(e)
        if (!pos) return

        this._dragEnd = pos
        this._renderCanvas()
    }

    canvasMouseUp(e) {
        if (!this._isDragging) return
        this._isDragging = false

        const start = this._dragStart
        const end = this._dragEnd || start
        if (!start || !end) return

        const minCol = Math.min(start.col, end.col)
        const maxCol = Math.max(start.col, end.col)
        const minRow = Math.min(start.row, end.row)
        const maxRow = Math.max(start.row, end.row)

        const cols = this._activeTileset.columns

        if (minCol === maxCol && minRow === maxRow) {
            // Single tile selection
            const localId = minRow * cols + minCol
            if (localId >= this._activeTileset.tileCount) return

            this._selectedGid = this._activeTileset.firstGid + localId
            this._stampWidth = 1
            this._stampHeight = 1
            this._stampGids = [this._selectedGid]
        } else {
            // Multi-tile stamp
            this._stampWidth = maxCol - minCol + 1
            this._stampHeight = maxRow - minRow + 1
            this._stampGids = []

            for (let row = minRow; row <= maxRow; row++) {
                for (let col = minCol; col <= maxCol; col++) {
                    const localId = row * cols + col
                    if (localId < this._activeTileset.tileCount) {
                        this._stampGids.push(this._activeTileset.firstGid + localId)
                    } else {
                        this._stampGids.push(0)
                    }
                }
            }
            this._selectedGid = this._stampGids[0]
        }

        this._updateGidDisplay()
        this._updateStampPreview()
        this._renderCanvas()
        this._dispatchSelection()
    }

    canvasScroll(e) {
        e.preventDefault()
        if (!this._activeTileset) return

        const cols = this._activeTileset.columns
        const rows = Math.ceil(this._activeTileset.tileCount / cols)
        const scaledTile = this._tileSize * this._canvasScale
        const maxScroll = Math.max(0, rows * scaledTile - this.canvasTarget.height)

        this._scrollY = Math.max(0, Math.min(maxScroll, this._scrollY + e.deltaY))
        this._renderCanvas()
    }

    _canvasToTile(e) {
        if (!this._activeTileset) return null
        const rect = this.canvasTarget.getBoundingClientRect()
        const x = e.clientX - rect.left
        const y = e.clientY - rect.top

        const scaledTile = this._tileSize * this._canvasScale
        const col = Math.floor(x / scaledTile)
        const row = Math.floor((y + this._scrollY) / scaledTile)

        if (col < 0 || col >= this._activeTileset.columns) return null
        const rows = Math.ceil(this._activeTileset.tileCount / this._activeTileset.columns)
        if (row < 0 || row >= rows) return null

        return { col, row }
    }

    // --- Layer selection ---

    selectLayer(e) {
        this._activeLayer = parseInt(e.target.value, 10)
        this._dispatchLayerChange()
    }

    // --- GID display & stamp preview ---

    _updateGidDisplay() {
        if (!this.hasGidDisplayTarget) return
        if (this._stampWidth === 1 && this._stampHeight === 1) {
            this.gidDisplayTarget.textContent = `GID: ${this._selectedGid}`
        } else {
            this.gidDisplayTarget.textContent = `Stamp: ${this._stampWidth}x${this._stampHeight} (${this._stampGids.length} tiles)`
        }
    }

    _updateStampPreview() {
        if (!this.hasStampPreviewTarget) return
        const container = this.stampPreviewTarget

        if (this._stampGids.length === 0) {
            container.innerHTML = ''
            return
        }

        const previewSize = 48
        const tileSize = Math.min(previewSize / Math.max(this._stampWidth, this._stampHeight), 32)
        const canvas = document.createElement('canvas')
        canvas.width = this._stampWidth * tileSize
        canvas.height = this._stampHeight * tileSize
        canvas.style.border = '1px solid rgba(168, 85, 247, 0.5)'
        canvas.style.borderRadius = '4px'

        const ctx = canvas.getContext('2d')
        ctx.imageSmoothingEnabled = false

        for (let i = 0; i < this._stampGids.length; i++) {
            const gid = this._stampGids[i]
            if (gid === 0) continue

            const ts = this._findTilesetForGid(gid)
            if (!ts) continue
            const img = this._tilesetImages[ts.name]
            if (!img) continue

            const localId = gid - ts.firstGid
            const srcX = (localId % ts.columns) * ts.tileWidth
            const srcY = Math.floor(localId / ts.columns) * ts.tileHeight

            const col = i % this._stampWidth
            const row = Math.floor(i / this._stampWidth)
            ctx.drawImage(img, srcX, srcY, ts.tileWidth, ts.tileHeight, col * tileSize, row * tileSize, tileSize, tileSize)
        }

        container.innerHTML = ''
        container.appendChild(canvas)
    }

    _findTilesetForGid(gid) {
        let result = null
        for (const ts of this._tilesets) {
            if (ts.firstGid <= gid) {
                result = ts
            } else {
                break
            }
        }
        return result
    }

    // --- Communication avec le map editor ---

    _dispatchSelection() {
        this.dispatch('tileSelected', {
            detail: {
                gid: this._selectedGid,
                stampWidth: this._stampWidth,
                stampHeight: this._stampHeight,
                stampGids: this._stampGids,
                layer: this._activeLayer,
            },
        })
    }

    _dispatchLayerChange() {
        this.dispatch('layerChanged', {
            detail: { layer: this._activeLayer },
        })
    }

    // --- Public API (called by map editor controller) ---

    /**
     * Select a specific GID (for eyedropper tool).
     */
    selectGid(gid) {
        if (gid <= 0) return

        // Find which tileset this GID belongs to
        const ts = this._findTilesetForGid(gid)
        if (!ts || !this._editableTilesets.includes(ts.name)) return

        this._selectedGid = gid
        this._stampWidth = 1
        this._stampHeight = 1
        this._stampGids = [gid]

        // Switch to the right tileset tab
        if (!this._activeTileset || this._activeTileset.name !== ts.name) {
            this._activeTileset = ts
            this._renderTabs()
        }

        // Scroll to make the tile visible
        const localId = gid - ts.firstGid
        const row = Math.floor(localId / ts.columns)
        const scaledTile = this._tileSize * this._canvasScale
        const tileY = row * scaledTile
        if (tileY < this._scrollY || tileY > this._scrollY + this.canvasTarget.height - scaledTile) {
            this._scrollY = Math.max(0, tileY - this.canvasTarget.height / 2)
        }

        this._updateGidDisplay()
        this._updateStampPreview()
        this._renderCanvas()
    }

    get activeLayer() {
        return this._activeLayer
    }

    get selectedStamp() {
        return {
            gid: this._selectedGid,
            width: this._stampWidth,
            height: this._stampHeight,
            gids: this._stampGids,
        }
    }
}
