import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = {
        mapId: Number,
        cellsUrl: String,
        tilesetsUrl: String,
        entitiesUrl: String,
        updateCellUrl: String,
        updateCellsUrl: String,
        updateBordersUrl: String,
        paintTilesUrl: String,
        deleteEntityUrl: String,
        moveEntityUrl: String,
    }

    static targets = ['canvas', 'info', 'coords', 'legend', 'stats']

    _tileSize = 32
    _zoom = 1
    _minZoom = 0.1
    _maxZoom = 4
    _offsetX = 0
    _offsetY = 0
    _cells = {}
    _mapWidth = 0
    _mapHeight = 0
    _tilesets = []
    _tilesetImages = {}
    _entities = {}
    _isDragging = false
    _dragStartX = 0
    _dragStartY = 0
    _dragStartOffsetX = 0
    _dragStartOffsetY = 0
    _selectedCell = null
    _selection = new Set() // Set of "x.y" keys for multi-selection
    _selectionStart = null // {x, y} drag start tile
    _selectionEnd = null // {x, y} drag end tile (while dragging)
    _isSelecting = false
    _selectedEntity = null // {type, id, name, x, y, listKey}
    _showCollisions = true
    _showEntities = true
    _showGrid = false
    _showWalls = true
    _tilesetsLoaded = false
    _renderTiles = true
    _tool = 'select' // select, block, unblock, water, wall, eraseWall, paint, eraser, fill
    _pendingChanges = {}
    _pendingBorderChanges = {} // key: "x.y", value: [n, e, s, w]
    _pendingTileChanges = {} // key: "x.y", value: {layer, gid} — tile GID changes
    _ctx = null
    _animFrame = null
    _hoveredCell = null

    // Tileset picker state (received via events)
    _pickerGid = 0
    _pickerStampWidth = 1
    _pickerStampHeight = 1
    _pickerStampGids = []
    _pickerLayer = 1 // 0=background, 1=ground, 2=decoration, 3=overlay
    _layerVisibility = [true, true, true, true] // per-layer visibility
    _layerOpacity = [1, 1, 1, 1] // per-layer opacity (0..1)

    connect() {
        this._canvas = this.canvasTarget
        this._ctx = this._canvas.getContext('2d')
        this._resizeCanvas()

        // Expose controller instance for inline onclick handlers
        this.element.__stimulus_controller = this

        this._bindEvents()
        this._loadData()

        window.addEventListener('resize', this._onResize)
    }

    disconnect() {
        window.removeEventListener('resize', this._onResize)
        document.removeEventListener('keydown', this._onKeyDown)
        if (this._animFrame) {
            cancelAnimationFrame(this._animFrame)
        }
    }

    _onResize = () => {
        this._resizeCanvas()
        this._render()
    }

    _resizeCanvas() {
        const container = this._canvas.parentElement
        this._canvas.width = container.clientWidth
        this._canvas.height = container.clientHeight - 4
    }

    _bindEvents() {
        this._canvas.addEventListener('wheel', this._onWheel, { passive: false })
        this._canvas.addEventListener('mousedown', this._onMouseDown)
        this._canvas.addEventListener('mousemove', this._onMouseMove)
        this._canvas.addEventListener('mouseup', this._onMouseUp)
        this._canvas.addEventListener('mouseleave', this._onMouseLeave)
        this._canvas.addEventListener('contextmenu', e => e.preventDefault())
        document.addEventListener('keydown', this._onKeyDown)
    }

    async _loadData() {
        this._updateStats('Chargement des donnees...')

        const [cellsRes, tilesetsRes, entitiesRes] = await Promise.all([
            fetch(this.cellsUrlValue),
            fetch(this.tilesetsUrlValue),
            fetch(this.entitiesUrlValue),
        ])

        const cellsData = await cellsRes.json()
        const tilesetsData = await tilesetsRes.json()
        const entitiesData = await entitiesRes.json()

        this._mapWidth = cellsData.mapWidth
        this._mapHeight = cellsData.mapHeight

        this._cells = {}
        for (const cell of cellsData.cells) {
            this._cells[cell.x + '.' + cell.y] = cell
        }

        this._tilesets = tilesetsData.tilesets
        this._entities = entitiesData

        this._updateStats(`Carte ${this._mapWidth}x${this._mapHeight} — ${cellsData.cells.length} cellules`)

        await this._loadTilesetImages()

        // Center view
        this._zoom = Math.min(
            this._canvas.width / (this._mapWidth * this._tileSize),
            this._canvas.height / (this._mapHeight * this._tileSize),
            1
        )
        this._offsetX = (this._canvas.width - this._mapWidth * this._tileSize * this._zoom) / 2
        this._offsetY = (this._canvas.height - this._mapHeight * this._tileSize * this._zoom) / 2

        this._render()
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
                img.onerror = () => {
                    resolve()
                }
                img.src = ts.image
            })
        })
        await Promise.all(promises)
        this._tilesetsLoaded = true
    }

    _render = () => {
        if (this._animFrame) {
            cancelAnimationFrame(this._animFrame)
        }
        this._animFrame = requestAnimationFrame(() => this._draw())
    }

    _draw() {
        const ctx = this._ctx
        const w = this._canvas.width
        const h = this._canvas.height
        const ts = this._tileSize * this._zoom

        ctx.clearRect(0, 0, w, h)
        ctx.fillStyle = '#1a1a2e'
        ctx.fillRect(0, 0, w, h)

        // Visible bounds
        const minTileX = Math.max(0, Math.floor(-this._offsetX / ts))
        const minTileY = Math.max(0, Math.floor(-this._offsetY / ts))
        const maxTileX = Math.min(this._mapWidth, Math.ceil((w - this._offsetX) / ts))
        const maxTileY = Math.min(this._mapHeight, Math.ceil((h - this._offsetY) / ts))

        // Draw tiles
        for (let tx = minTileX; tx < maxTileX; tx++) {
            for (let ty = minTileY; ty < maxTileY; ty++) {
                const key = tx + '.' + ty
                const cell = this._cells[key]
                const px = tx * ts + this._offsetX
                const py = ty * ts + this._offsetY

                if (!cell) {
                    ctx.fillStyle = '#0d0d1a'
                    ctx.fillRect(px, py, ts, ts)
                    continue
                }

                // Render tile layers
                if (this._renderTiles && this._tilesetsLoaded && cell.l && cell.l.length > 0) {
                    this._drawTileLayers(ctx, cell, px, py, ts)
                } else {
                    // Fallback: color based on movement
                    ctx.fillStyle = cell.m === -1 ? '#3d1f1f' : (cell.m === 2 ? '#1f2d3d' : '#1f3d1f')
                    ctx.fillRect(px, py, ts, ts)
                }

                // Collision overlay
                if (this._showCollisions && cell.m === -1) {
                    ctx.fillStyle = 'rgba(255, 0, 0, 0.35)'
                    ctx.fillRect(px, py, ts, ts)
                    // X pattern
                    ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)'
                    ctx.lineWidth = 1
                    ctx.beginPath()
                    ctx.moveTo(px + 2, py + 2)
                    ctx.lineTo(px + ts - 2, py + ts - 2)
                    ctx.moveTo(px + ts - 2, py + 2)
                    ctx.lineTo(px + 2, py + ts - 2)
                    ctx.stroke()
                }

                // Water overlay
                if (this._showCollisions && cell.m === 2) {
                    ctx.fillStyle = 'rgba(0, 100, 255, 0.25)'
                    ctx.fillRect(px, py, ts, ts)
                }

                // Climb overlay
                if (this._showCollisions && cell.m === 4) {
                    ctx.fillStyle = 'rgba(0, 200, 100, 0.25)'
                    ctx.fillRect(px, py, ts, ts)
                    // Upward arrow pattern
                    if (ts >= 12) {
                        ctx.strokeStyle = 'rgba(0, 200, 100, 0.5)'
                        ctx.lineWidth = 1
                        const cx = px + ts / 2
                        const cy = py + ts / 2
                        ctx.beginPath()
                        ctx.moveTo(cx, cy + ts * 0.2)
                        ctx.lineTo(cx, cy - ts * 0.2)
                        ctx.moveTo(cx - ts * 0.12, cy - ts * 0.08)
                        ctx.lineTo(cx, cy - ts * 0.2)
                        ctx.lineTo(cx + ts * 0.12, cy - ts * 0.08)
                        ctx.stroke()
                    }
                }

                // Pending changes highlight
                if (this._pendingChanges[key] !== undefined || this._pendingBorderChanges[key] !== undefined) {
                    ctx.fillStyle = 'rgba(255, 255, 0, 0.3)'
                    ctx.fillRect(px, py, ts, ts)
                }
            }
        }

        // Walls (directional borders)
        if (this._showWalls) {
            this._drawWalls(ctx, ts, minTileX, minTileY, maxTileX, maxTileY)
        }

        // Grid
        if (this._showGrid && this._zoom >= 0.3) {
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)'
            ctx.lineWidth = 1
            for (let tx = minTileX; tx <= maxTileX; tx++) {
                const px = Math.floor(tx * ts + this._offsetX) + 0.5
                ctx.beginPath()
                ctx.moveTo(px, minTileY * ts + this._offsetY)
                ctx.lineTo(px, maxTileY * ts + this._offsetY)
                ctx.stroke()
            }
            for (let ty = minTileY; ty <= maxTileY; ty++) {
                const py = Math.floor(ty * ts + this._offsetY) + 0.5
                ctx.beginPath()
                ctx.moveTo(minTileX * ts + this._offsetX, py)
                ctx.lineTo(maxTileX * ts + this._offsetX, py)
                ctx.stroke()
            }
        }

        // Entities
        if (this._showEntities) {
            this._drawEntities(ctx, ts, minTileX, minTileY, maxTileX, maxTileY)
        }

        // Hovered cell + paint ghost preview
        if (this._hoveredCell) {
            const hx = this._hoveredCell.x * ts + this._offsetX
            const hy = this._hoveredCell.y * ts + this._offsetY

            if (this._tool === 'eraser') {
                // Eraser ghost preview: red overlay
                ctx.fillStyle = 'rgba(239, 68, 68, 0.3)'
                ctx.fillRect(hx, hy, ts, ts)
                ctx.strokeStyle = '#ef4444'
                ctx.lineWidth = 2
                ctx.strokeRect(hx, hy, ts, ts)
                // X mark
                ctx.beginPath()
                ctx.moveTo(hx + ts * 0.25, hy + ts * 0.25)
                ctx.lineTo(hx + ts * 0.75, hy + ts * 0.75)
                ctx.moveTo(hx + ts * 0.75, hy + ts * 0.25)
                ctx.lineTo(hx + ts * 0.25, hy + ts * 0.75)
                ctx.stroke()
            } else if (this._tool === 'paint' && this._pickerGid > 0 && this._tilesetsLoaded) {
                // Ghost preview of stamp
                ctx.globalAlpha = 0.5
                for (let dy = 0; dy < this._pickerStampHeight; dy++) {
                    for (let dx = 0; dx < this._pickerStampWidth; dx++) {
                        const stampIdx = dy * this._pickerStampWidth + dx
                        const gid = this._pickerStampGids[stampIdx]
                        if (!gid) continue

                        const tileset = this._findTileset(gid)
                        if (!tileset) continue
                        const img = this._tilesetImages[tileset.name]
                        if (!img) continue

                        const localId = gid - tileset.firstGid
                        const srcX = (localId % tileset.columns) * tileset.tileWidth
                        const srcY = Math.floor(localId / tileset.columns) * tileset.tileHeight
                        const px = (this._hoveredCell.x + dx) * ts + this._offsetX
                        const py = (this._hoveredCell.y + dy) * ts + this._offsetY
                        ctx.drawImage(img, srcX, srcY, tileset.tileWidth, tileset.tileHeight, px, py, ts, ts)
                    }
                }
                ctx.globalAlpha = 1
                ctx.strokeStyle = '#a855f7'
                ctx.lineWidth = 2
                ctx.strokeRect(hx, hy, this._pickerStampWidth * ts, this._pickerStampHeight * ts)
            } else {
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.6)'
                ctx.lineWidth = 2
                ctx.strokeRect(hx, hy, ts, ts)
            }
        }

        // Multi-selection overlay
        if (this._selection.size > 0) {
            ctx.fillStyle = 'rgba(168, 85, 247, 0.2)'
            ctx.strokeStyle = '#a855f7'
            ctx.lineWidth = 2
            for (const key of this._selection) {
                const [sx, sy] = key.split('.').map(Number)
                if (sx >= minTileX && sx < maxTileX && sy >= minTileY && sy < maxTileY) {
                    const px = sx * ts + this._offsetX
                    const py = sy * ts + this._offsetY
                    ctx.fillRect(px, py, ts, ts)
                    ctx.strokeRect(px, py, ts, ts)
                }
            }
        }

        // Selection rectangle (while dragging)
        if (this._isSelecting && this._selectionStart && this._selectionEnd) {
            const s = this._selectionStart
            const e = this._selectionEnd
            const rx = Math.min(s.x, e.x) * ts + this._offsetX
            const ry = Math.min(s.y, e.y) * ts + this._offsetY
            const rw = (Math.abs(e.x - s.x) + 1) * ts
            const rh = (Math.abs(e.y - s.y) + 1) * ts
            ctx.fillStyle = 'rgba(168, 85, 247, 0.15)'
            ctx.fillRect(rx, ry, rw, rh)
            ctx.strokeStyle = '#a855f7'
            ctx.lineWidth = 2
            ctx.setLineDash([6, 3])
            ctx.strokeRect(rx, ry, rw, rh)
            ctx.setLineDash([])
        }

        // Selected entity highlight
        if (this._selectedEntity) {
            const ex = this._selectedEntity.x * ts + this._offsetX
            const ey = this._selectedEntity.y * ts + this._offsetY
            ctx.strokeStyle = '#facc15'
            ctx.lineWidth = 2
            ctx.setLineDash([4, 4])
            ctx.strokeRect(ex + 2, ey + 2, ts - 4, ts - 4)
            ctx.setLineDash([])
        }
    }

    _drawWalls(ctx, ts, minX, minY, maxX, maxY) {
        const wallWidth = Math.max(3, ts * 0.12)
        ctx.lineCap = 'round'

        // Pre-build neighbor border lookup for one-way detection
        const getBorders = (tx, ty) => {
            const k = tx + '.' + ty
            const c = this._cells[k]
            if (!c || !c.b) return null
            return this._pendingBorderChanges[k] || c.b
        }

        const oppositeIdx = { 0: 2, 1: 3, 2: 0, 3: 1 } // n↔s, e↔w
        const neighborOff = [[0, -1], [1, 0], [0, 1], [-1, 0]] // n, e, s, w

        for (let tx = minX; tx < maxX; tx++) {
            for (let ty = minY; ty < maxY; ty++) {
                const key = tx + '.' + ty
                const cell = this._cells[key]
                if (!cell || !cell.b) continue

                const borders = this._pendingBorderChanges[key] || cell.b
                const px = tx * ts + this._offsetX
                const py = ty * ts + this._offsetY

                const segments = [
                    // [dirIdx, x1, y1, x2, y2]
                    [0, px + 1, py, px + ts - 1, py],           // north
                    [1, px + ts, py + 1, px + ts, py + ts - 1], // east
                    [2, px + 1, py + ts, px + ts - 1, py + ts], // south
                    [3, px, py + 1, px, py + ts - 1],           // west
                ]

                for (const [di, x1, y1, x2, y2] of segments) {
                    if (borders[di] === 0) continue

                    // Check if neighbor has the reciprocal wall → two-way; otherwise one-way
                    const [ndx, ndy] = neighborOff[di]
                    const neighborB = getBorders(tx + ndx, ty + ndy)
                    const isOneWay = !neighborB || neighborB[oppositeIdx[di]] === 0

                    ctx.lineWidth = wallWidth
                    ctx.strokeStyle = isOneWay ? '#ef4444' : '#f97316' // red = one-way, orange = two-way
                    ctx.beginPath()
                    ctx.moveTo(x1, y1)
                    ctx.lineTo(x2, y2)
                    ctx.stroke()

                    // Draw arrow for one-way walls (points inward = blocked direction)
                    if (isOneWay && ts >= 16) {
                        const mx = (x1 + x2) / 2
                        const my = (y1 + y2) / 2
                        const arrowSize = Math.max(4, ts * 0.2)
                        // Arrow points INTO the cell (blocked from leaving in this direction)
                        const dirs = [[0, 1], [-1, 0], [0, -1], [1, 0]] // n→down, e→left, s→up, w→right
                        const [ax, ay] = dirs[di]
                        ctx.fillStyle = '#ef4444'
                        ctx.beginPath()
                        ctx.moveTo(mx + ax * arrowSize, my + ay * arrowSize)
                        ctx.lineTo(mx - ay * arrowSize * 0.5, my + ax * arrowSize * 0.5)
                        ctx.lineTo(mx + ay * arrowSize * 0.5, my - ax * arrowSize * 0.5)
                        ctx.closePath()
                        ctx.fill()
                    }
                }
            }
        }
    }

    _drawTileLayers(ctx, cell, px, py, ts) {
        for (let i = 0; i < cell.l.length; i++) {
            if (!this._layerVisibility[i]) continue

            const gid = cell.l[i]
            const tileset = this._findTileset(gid)
            if (!tileset) continue

            const img = this._tilesetImages[tileset.name]
            if (!img) continue

            const localId = gid - tileset.firstGid
            const srcX = (localId % tileset.columns) * tileset.tileWidth
            const srcY = Math.floor(localId / tileset.columns) * tileset.tileHeight

            const opacity = this._layerOpacity[i]
            if (opacity < 1) {
                ctx.globalAlpha = opacity
            }
            ctx.drawImage(img, srcX, srcY, tileset.tileWidth, tileset.tileHeight, px, py, ts, ts)
            if (opacity < 1) {
                ctx.globalAlpha = 1
            }
        }
    }

    _findTileset(gid) {
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

    _drawEntities(ctx, ts, minX, minY, maxX, maxY) {
        const drawMarker = (x, y, color, label, shape) => {
            if (x < minX || x >= maxX || y < minY || y >= maxY) return
            const px = x * ts + this._offsetX
            const py = y * ts + this._offsetY
            const cx = px + ts / 2
            const cy = py + ts / 2
            const r = Math.max(ts * 0.35, 4)

            ctx.fillStyle = color
            ctx.globalAlpha = 0.85

            if (shape === 'diamond') {
                ctx.beginPath()
                ctx.moveTo(cx, cy - r)
                ctx.lineTo(cx + r, cy)
                ctx.lineTo(cx, cy + r)
                ctx.lineTo(cx - r, cy)
                ctx.closePath()
                ctx.fill()
            } else if (shape === 'triangle') {
                ctx.beginPath()
                ctx.moveTo(cx, cy - r)
                ctx.lineTo(cx + r, cy + r * 0.7)
                ctx.lineTo(cx - r, cy + r * 0.7)
                ctx.closePath()
                ctx.fill()
            } else {
                ctx.beginPath()
                ctx.arc(cx, cy, r, 0, Math.PI * 2)
                ctx.fill()
            }

            ctx.globalAlpha = 1

            // Label
            if (this._zoom >= 0.5 && label) {
                ctx.fillStyle = '#fff'
                ctx.font = `${Math.max(9, 11 * this._zoom)}px sans-serif`
                ctx.textAlign = 'center'
                ctx.fillText(label, cx, py - 3)
            }
        }

        // Mobs (red circles)
        for (const mob of (this._entities.mobs || [])) {
            drawMarker(mob.x, mob.y, '#ef4444', mob.name, 'circle')
        }

        // PNJs (green diamonds)
        for (const pnj of (this._entities.pnjs || [])) {
            drawMarker(pnj.x, pnj.y, '#22c55e', pnj.name, 'diamond')
        }

        // Portals (blue triangles)
        for (const portal of (this._entities.portals || [])) {
            drawMarker(portal.x, portal.y, '#3b82f6', portal.name, 'triangle')
        }

        // Harvest spots (yellow circles)
        for (const spot of (this._entities.harvestSpots || [])) {
            drawMarker(spot.x, spot.y, '#eab308', spot.name, 'circle')
        }

        // Craft stations (orange diamonds)
        for (const station of (this._entities.craftStations || [])) {
            drawMarker(station.x, station.y, '#f97316', station.name, 'diamond')
        }
    }

    // --- Events ---

    _onWheel = (e) => {
        e.preventDefault()
        const rect = this._canvas.getBoundingClientRect()
        const mouseX = e.clientX - rect.left
        const mouseY = e.clientY - rect.top

        const worldX = (mouseX - this._offsetX) / this._zoom
        const worldY = (mouseY - this._offsetY) / this._zoom

        const factor = e.deltaY < 0 ? 1.15 : 1 / 1.15
        this._zoom = Math.max(this._minZoom, Math.min(this._maxZoom, this._zoom * factor))

        this._offsetX = mouseX - worldX * this._zoom
        this._offsetY = mouseY - worldY * this._zoom

        this._render()
    }

    _onMouseDown = (e) => {
        const rect = this._canvas.getBoundingClientRect()
        const mouseX = e.clientX - rect.left
        const mouseY = e.clientY - rect.top

        // Alt+click: eyedropper (pick tile GID under cursor)
        if (e.button === 0 && e.altKey) {
            const tileCoords = this._screenToTile(mouseX, mouseY)
            if (tileCoords) {
                this._eyedropperPick(tileCoords.x, tileCoords.y)
            }
            return
        }

        if (e.button === 1 || e.button === 2) {
            // Middle click or right click: pan
            this._isDragging = true
            this._dragStartX = mouseX
            this._dragStartY = mouseY
            this._dragStartOffsetX = this._offsetX
            this._dragStartOffsetY = this._offsetY
            this._canvas.style.cursor = 'grabbing'
            return
        }

        if (e.button === 0) {
            const tileCoords = this._screenToTile(mouseX, mouseY)
            if (!tileCoords) return

            // Ctrl+click: move selected entity
            if (e.ctrlKey && this._selectedEntity) {
                this._moveEntity(tileCoords.x, tileCoords.y)
                return
            }

            if (this._tool === 'select') {
                this._selectionStart = { x: tileCoords.x, y: tileCoords.y }
                this._selectionEnd = { x: tileCoords.x, y: tileCoords.y }
                this._isSelecting = true
                // Single click handled on mouseup
            } else if (this._tool === 'block') {
                this._paintCell(tileCoords.x, tileCoords.y, -1)
                this._isPainting = true
            } else if (this._tool === 'unblock') {
                this._paintCell(tileCoords.x, tileCoords.y, 0)
                this._isPainting = true
            } else if (this._tool === 'water') {
                this._paintCell(tileCoords.x, tileCoords.y, 2)
                this._isPainting = true
            } else if (this._tool === 'climb') {
                this._paintCell(tileCoords.x, tileCoords.y, 4)
                this._isPainting = true
            } else if (this._tool === 'wall' || this._tool === 'eraseWall') {
                this._paintWallAtMouse(mouseX, mouseY, this._tool === 'wall' ? -1 : 0, e.shiftKey)
                this._isPainting = true
                this._isPaintingOneWay = e.shiftKey
            } else if (this._tool === 'paint') {
                this._paintTile(tileCoords.x, tileCoords.y)
                this._isPainting = true
            } else if (this._tool === 'eraser') {
                this._eraseTile(tileCoords.x, tileCoords.y)
                this._isPainting = true
            } else if (this._tool === 'fill') {
                this._fillBucket(tileCoords.x, tileCoords.y)
            }
        }
    }

    _onMouseMove = (e) => {
        const rect = this._canvas.getBoundingClientRect()
        const mouseX = e.clientX - rect.left
        const mouseY = e.clientY - rect.top

        if (this._isDragging) {
            this._offsetX = this._dragStartOffsetX + (mouseX - this._dragStartX)
            this._offsetY = this._dragStartOffsetY + (mouseY - this._dragStartY)
            this._render()
            return
        }

        const tileCoords = this._screenToTile(mouseX, mouseY)
        if (tileCoords) {
            this._hoveredCell = tileCoords
            this._updateCoords(tileCoords.x, tileCoords.y)

            if (this._isSelecting && this._selectionStart) {
                this._selectionEnd = { x: tileCoords.x, y: tileCoords.y }
            } else if (this._isPainting) {
                if (this._tool === 'wall' || this._tool === 'eraseWall') {
                    this._paintWallAtMouse(mouseX, mouseY, this._tool === 'wall' ? -1 : 0, this._isPaintingOneWay)
                } else if (this._tool === 'paint') {
                    this._paintTile(tileCoords.x, tileCoords.y)
                } else if (this._tool === 'eraser') {
                    this._eraseTile(tileCoords.x, tileCoords.y)
                } else {
                    const movementMap = { block: -1, unblock: 0, water: 2, climb: 4 }
                    const movement = movementMap[this._tool] ?? 0
                    this._paintCell(tileCoords.x, tileCoords.y, movement)
                }
            }
        } else {
            this._hoveredCell = null
        }

        this._render()
    }

    _onMouseUp = (e) => {
        if (this._isSelecting && this._selectionStart) {
            const start = this._selectionStart
            const end = this._selectionEnd || start
            const minX = Math.min(start.x, end.x)
            const maxX = Math.max(start.x, end.x)
            const minY = Math.min(start.y, end.y)
            const maxY = Math.max(start.y, end.y)

            const isSingleClick = (minX === maxX && minY === maxY)

            if (isSingleClick) {
                // Single click: select one cell
                this._selection = new Set([minX + '.' + minY])
                this._selectedCell = { x: minX, y: minY }
                this._selectCell(minX, minY)
            } else {
                // Rectangle drag: select all cells in rect
                this._selection = new Set()
                for (let x = minX; x <= maxX; x++) {
                    for (let y = minY; y <= maxY; y++) {
                        const key = x + '.' + y
                        if (this._cells[key]) {
                            this._selection.add(key)
                        }
                    }
                }
                this._selectedCell = null
                this._selectedEntity = null
                this._updateSelectionPanel()
            }

            this._isSelecting = false
            this._selectionStart = null
            this._selectionEnd = null
            this._render()
            return
        }

        this._isDragging = false
        this._isPainting = false
        this._canvas.style.cursor = 'default'
    }

    _onMouseLeave = () => {
        this._isDragging = false
        this._isPainting = false
        this._isSelecting = false
        this._selectionStart = null
        this._selectionEnd = null
        this._hoveredCell = null
        this._canvas.style.cursor = 'default'
        this._render()
    }

    _onKeyDown = (e) => {
        // Ignore shortcuts when typing in an input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return

        if (e.key === 'Delete' && this._selectedEntity) {
            e.preventDefault()
            this._deleteSelectedEntity()
        }
        if (e.key === 'Escape') {
            this._selection = new Set()
            this._selectedCell = null
            this._selectedEntity = null
            this.infoTarget.innerHTML = '<p class="text-gray-500 text-sm">Cliquez sur une cellule pour voir ses details.</p>'
            this._render()
        }

        // Tool shortcuts
        if (e.key === 'e' || e.key === 'E') { this.setToolEraser(); e.preventDefault() }
        if (e.key === 'p' || e.key === 'P') { this.setToolPaint(); e.preventDefault() }
        if (e.key === 'v' || e.key === 'V') { this.setToolSelect(); e.preventDefault() }
        if (e.key === 'b' || e.key === 'B') { this.setToolBlock(); e.preventDefault() }
        if (e.key === 'u' || e.key === 'U') { this.setToolUnblock(); e.preventDefault() }
        if (e.key === 'w' || e.key === 'W') { this.setToolWall(); e.preventDefault() }
        if (e.key === 'g' || e.key === 'G') { this.setToolFill(); e.preventDefault() }

        // Layer shortcuts: 1/2/3/4 (select active layer)
        if (e.key === '1' || e.key === '2' || e.key === '3' || e.key === '4') {
            const layer = parseInt(e.key, 10) - 1
            this._pickerLayer = layer
            // Notify the tileset picker
            const pickerEl = this.element.querySelector('[data-controller="admin-tileset-picker"]')
            if (pickerEl) {
                const pickerCtrl = this.application.getControllerForElementAndIdentifier(pickerEl, 'admin-tileset-picker')
                if (pickerCtrl) {
                    pickerCtrl._activeLayer = layer
                }
            }
            this._updateLayerPanel()
            e.preventDefault()
        }

        // Ctrl+S: save
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault()
            this.saveChanges()
        }
    }

    _screenToTile(screenX, screenY) {
        const ts = this._tileSize * this._zoom
        const tileX = Math.floor((screenX - this._offsetX) / ts)
        const tileY = Math.floor((screenY - this._offsetY) / ts)

        if (tileX < 0 || tileY < 0 || tileX >= this._mapWidth || tileY >= this._mapHeight) {
            return null
        }
        return { x: tileX, y: tileY }
    }

    // Detect which edge of a cell the mouse is closest to
    _detectEdge(mouseX, mouseY) {
        const ts = this._tileSize * this._zoom
        const worldX = mouseX - this._offsetX
        const worldY = mouseY - this._offsetY
        const tileX = Math.floor(worldX / ts)
        const tileY = Math.floor(worldY / ts)

        if (tileX < 0 || tileY < 0 || tileX >= this._mapWidth || tileY >= this._mapHeight) {
            return null
        }

        // Position within tile (0-1)
        const localX = (worldX / ts) - tileX
        const localY = (worldY / ts) - tileY

        // Determine closest edge using diagonal split
        // Top-left to bottom-right diagonal: localY = localX
        // Top-right to bottom-left diagonal: localY = 1 - localX
        let direction
        if (localY < localX && localY < (1 - localX)) {
            direction = 'north'
        } else if (localY > localX && localY > (1 - localX)) {
            direction = 'south'
        } else if (localX > 0.5) {
            direction = 'east'
        } else {
            direction = 'west'
        }

        return { x: tileX, y: tileY, direction }
    }

    // oneWay: if true, only paints the source cell's border (Shift held)
    _paintWallAtMouse(mouseX, mouseY, value, oneWay = false) {
        const edge = this._detectEdge(mouseX, mouseY)
        if (!edge) return

        const key = edge.x + '.' + edge.y
        const cell = this._cells[key]
        if (!cell || !cell.b) return

        const dirIndex = { north: 0, east: 1, south: 2, west: 3 }
        const idx = dirIndex[edge.direction]

        // Update this cell's border
        const borders = this._pendingBorderChanges[key] || [...cell.b]
        borders[idx] = value
        this._pendingBorderChanges[key] = borders

        // Also update the neighbor's reciprocal border (unless one-way)
        if (!oneWay) {
            const opposites = { north: 'south', south: 'north', east: 'west', west: 'east' }
            const neighborOffsets = { north: [0, -1], south: [0, 1], east: [1, 0], west: [-1, 0] }
            const offset = neighborOffsets[edge.direction]
            const nx = edge.x + offset[0]
            const ny = edge.y + offset[1]
            const neighborKey = nx + '.' + ny
            const neighborCell = this._cells[neighborKey]

            if (neighborCell && neighborCell.b) {
                const neighborIdx = dirIndex[opposites[edge.direction]]
                const neighborBorders = this._pendingBorderChanges[neighborKey] || [...neighborCell.b]
                neighborBorders[neighborIdx] = value
                this._pendingBorderChanges[neighborKey] = neighborBorders
            }
        }

        this._updatePendingCount()
        this._render()
    }

    _selectCell(x, y) {
        const key = x + '.' + y
        const cell = this._cells[key]
        this._selectedCell = { x, y }
        this._selection = new Set([key])

        // Check if there's an entity to auto-select
        const entitiesHere = this._getEntitiesAt(x, y)
        if (entitiesHere.length > 0) {
            this._selectedEntity = entitiesHere[0]
        } else {
            this._selectedEntity = null
        }

        this._render()

        if (cell) {
            const movLabels = { '-1': 'Bloque (mur)', '0': 'Libre', '2': 'Eau (nage)', '4': 'Escalade' }
            const movLabel = movLabels[String(cell.m)] || `Type ${cell.m}`
            const pendingMov = this._pendingChanges[key]
            let pendingLabel = ''
            if (pendingMov !== undefined) {
                const pl = movLabels[String(pendingMov)] || `Type ${pendingMov}`
                pendingLabel = ` → <span class="text-yellow-400">${pl} (non sauvegarde)</span>`
            }

            // Border info
            const borders = this._pendingBorderChanges[key] || cell.b || [0, 0, 0, 0]
            const dirNames = ['N', 'E', 'S', 'W']
            const borderStr = borders.map((v, i) => {
                const cls = v !== 0 ? 'text-orange-400 font-bold' : 'text-gray-500'
                return `<span class="${cls}">${dirNames[i]}:${v}</span>`
            }).join(' ')

            // Entity list with action buttons
            let entitiesHtml = ''
            if (entitiesHere.length > 0) {
                entitiesHtml = '<div class="mt-2 space-y-1">'
                for (const ent of entitiesHere) {
                    const isSelected = this._selectedEntity && this._selectedEntity.id === ent.id && this._selectedEntity.type === ent.type
                    const selClass = isSelected ? 'ring-2 ring-yellow-400' : ''
                    entitiesHtml += `<div class="flex items-center gap-1 text-xs">
                        <span class="inline-block px-2 py-0.5 rounded ${selClass}" style="background:${ent.color}">${ent.label}: ${ent.name}</span>`

                    if (ent.canDelete) {
                        entitiesHtml += `<button onclick="this.closest('[data-controller]').__stimulus_controller._deleteEntityById('${ent.type}', ${ent.id})"
                            class="px-1.5 py-0.5 bg-red-800 hover:bg-red-700 rounded text-xs" title="Supprimer">✕</button>`
                    }

                    if (ent.canMove) {
                        entitiesHtml += `<button onclick="this.closest('[data-controller]').__stimulus_controller._startMoveEntity('${ent.type}', ${ent.id}, '${ent.name.replace(/'/g, "\\'")}', ${ent.x}, ${ent.y}, '${ent.listKey}')"
                            class="px-1.5 py-0.5 bg-blue-800 hover:bg-blue-700 rounded text-xs" title="Deplacer (puis Ctrl+clic)">↗</button>`
                    }

                    entitiesHtml += '</div>'
                }
                entitiesHtml += '</div>'
            }

            this.infoTarget.innerHTML = `
                <div class="text-sm">
                    <p><span class="text-gray-400">Position:</span> <strong>${x}, ${y}</strong></p>
                    <p><span class="text-gray-400">Mouvement:</span> <strong>${movLabel}</strong> (${cell.m})${pendingLabel}</p>
                    <p><span class="text-gray-400">Murs:</span> ${borderStr}</p>
                    <p><span class="text-gray-400">Layers:</span> ${cell.l ? cell.l.join(', ') : 'aucun'}</p>
                    ${entitiesHtml}
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, 0)"
                                class="px-2 py-1 bg-green-700 hover:bg-green-600 rounded text-xs">Libre</button>
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, -1)"
                                class="px-2 py-1 bg-red-700 hover:bg-red-600 rounded text-xs">Bloquer</button>
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, 2)"
                                class="px-2 py-1 bg-blue-700 hover:bg-blue-600 rounded text-xs">Eau</button>
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, 4)"
                                class="px-2 py-1 bg-emerald-700 hover:bg-emerald-600 rounded text-xs">Escalade</button>
                    </div>
                </div>
            `
        } else {
            this.infoTarget.innerHTML = `<p class="text-gray-500 text-sm">Cellule vide (${x}, ${y})</p>`
        }
    }

    _updateSelectionPanel() {
        const count = this._selection.size
        if (count === 0) {
            this.infoTarget.innerHTML = '<p class="text-gray-500 text-sm">Cliquez sur une cellule pour voir ses details.</p>'
            return
        }

        // Count movement types in selection
        let blocked = 0, water = 0, climb = 0, free = 0
        for (const key of this._selection) {
            const cell = this._cells[key]
            if (!cell) continue
            if (cell.m === -1) blocked++
            else if (cell.m === 2) water++
            else if (cell.m === 4) climb++
            else free++
        }

        // Collect all entities in selection
        const allEntities = []
        for (const key of this._selection) {
            const [x, y] = key.split('.').map(Number)
            allEntities.push(...this._getEntitiesAt(x, y))
        }

        let entitiesHtml = ''
        if (allEntities.length > 0) {
            entitiesHtml = `<p class="text-gray-400 mt-2 text-xs">${allEntities.length} entite(s) dans la selection</p>`
        }

        this.infoTarget.innerHTML = `
            <div class="text-sm">
                <p><span class="text-gray-400">Selection:</span> <strong>${count} cellules</strong></p>
                <p class="text-xs text-gray-500">${free} libres, ${blocked} bloquees, ${water} eau, ${climb} escalade</p>
                ${entitiesHtml}
                <div class="mt-3 flex flex-wrap gap-2">
                    <button onclick="this.closest('[data-controller]').__stimulus_controller._applyToSelection(0)"
                            class="px-2 py-1 bg-green-700 hover:bg-green-600 rounded text-xs">Libre</button>
                    <button onclick="this.closest('[data-controller]').__stimulus_controller._applyToSelection(-1)"
                            class="px-2 py-1 bg-red-700 hover:bg-red-600 rounded text-xs">Bloquer</button>
                    <button onclick="this.closest('[data-controller]').__stimulus_controller._applyToSelection(2)"
                            class="px-2 py-1 bg-blue-700 hover:bg-blue-600 rounded text-xs">Eau</button>
                    <button onclick="this.closest('[data-controller]').__stimulus_controller._applyToSelection(4)"
                            class="px-2 py-1 bg-emerald-700 hover:bg-emerald-600 rounded text-xs">Escalade</button>
                </div>
            </div>
        `
    }

    _applyToSelection(movement) {
        for (const key of this._selection) {
            const cell = this._cells[key]
            if (!cell) continue
            if (cell.m === movement && this._pendingChanges[key] === undefined) continue
            this._pendingChanges[key] = movement
            cell.m = movement
        }
        this._updatePendingCount()
        this._updateSelectionPanel()
        this._render()
    }

    _getEntitiesAt(x, y) {
        const results = []
        for (const mob of (this._entities.mobs || [])) {
            if (mob.x === x && mob.y === y) results.push({
                type: 'mob', id: mob.id, label: 'Mob', name: `${mob.name} (nv.${mob.level})`,
                color: '#991b1b', x, y, listKey: 'mobs', canDelete: true, canMove: true
            })
        }
        for (const pnj of (this._entities.pnjs || [])) {
            if (pnj.x === x && pnj.y === y) results.push({
                type: 'pnj', id: pnj.id, label: 'PNJ', name: pnj.name,
                color: '#166534', x, y, listKey: 'pnjs', canDelete: false, canMove: true
            })
        }
        for (const portal of (this._entities.portals || [])) {
            if (portal.x === x && portal.y === y) results.push({
                type: 'portal', id: portal.id, label: 'Portail', name: `${portal.name} → map ${portal.destMapId}`,
                color: '#1e40af', x, y, listKey: 'portals', canDelete: false, canMove: true
            })
        }
        for (const spot of (this._entities.harvestSpots || [])) {
            if (spot.x === x && spot.y === y) results.push({
                type: 'harvestSpot', id: spot.id, label: 'Recolte', name: spot.name,
                color: '#854d0e', x, y, listKey: 'harvestSpots', canDelete: true, canMove: true
            })
        }
        for (const st of (this._entities.craftStations || [])) {
            if (st.x === x && st.y === y) results.push({
                type: 'craftStation', id: st.id, label: 'Station', name: `${st.name} (${st.type})`,
                color: '#9a3412', x, y, listKey: 'craftStations', canDelete: false, canMove: true
            })
        }
        return results
    }

    // --- Entity management ---

    _startMoveEntity(type, id, name, x, y, listKey) {
        this._selectedEntity = { type, id, name, x, y, listKey }
        this._showFlash(`Ctrl+clic sur la case cible pour deplacer "${name}"`, 'info')
        this._render()
    }

    async _moveEntity(targetX, targetY) {
        const ent = this._selectedEntity
        if (!ent) return

        try {
            const res = await fetch(this.moveEntityUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entityType: ent.type, entityId: ent.id, x: targetX, y: targetY }),
            })
            const data = await res.json()
            if (data.success) {
                // Update local entity data
                const list = this._entities[ent.listKey] || []
                const item = list.find(e => e.id === ent.id)
                if (item) {
                    item.x = targetX
                    item.y = targetY
                }
                this._selectedEntity = { ...ent, x: targetX, y: targetY }
                this._showFlash(`"${ent.name}" deplace en ${targetX},${targetY}`, 'success')
                this._selectCell(targetX, targetY)
            } else {
                this._showFlash('Erreur: ' + (data.error || 'inconnue'), 'error')
            }
        } catch (err) {
            this._showFlash('Erreur reseau: ' + err.message, 'error')
        }
        this._render()
    }

    async _deleteEntityById(type, id) {
        const list = this._entities[this._entityListKey(type)] || []
        const item = list.find(e => e.id === id)
        const name = item ? (item.name || '') : ''

        if (!confirm(`Supprimer ${type} "${name}" ?`)) return

        try {
            const res = await fetch(this.deleteEntityUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entityType: type, entityId: id }),
            })
            const data = await res.json()
            if (data.success) {
                // Remove from local data
                const listKey = this._entityListKey(type)
                this._entities[listKey] = (this._entities[listKey] || []).filter(e => e.id !== id)
                if (this._selectedEntity && this._selectedEntity.id === id && this._selectedEntity.type === type) {
                    this._selectedEntity = null
                }
                this._showFlash(`"${name}" supprime`, 'success')
                // Refresh cell info
                if (this._selectedCell) {
                    this._selectCell(this._selectedCell.x, this._selectedCell.y)
                }
            } else {
                this._showFlash('Erreur: ' + (data.error || 'inconnue'), 'error')
            }
        } catch (err) {
            this._showFlash('Erreur reseau: ' + err.message, 'error')
        }
        this._render()
    }

    _deleteSelectedEntity() {
        if (!this._selectedEntity) return
        this._deleteEntityById(this._selectedEntity.type, this._selectedEntity.id)
    }

    _entityListKey(type) {
        return {
            mob: 'mobs',
            pnj: 'pnjs',
            portal: 'portals',
            harvestSpot: 'harvestSpots',
            craftStation: 'craftStations',
        }[type] || type
    }

    // --- Cell painting ---

    _paintCell(x, y, movement) {
        const key = x + '.' + y
        const cell = this._cells[key]
        if (!cell) return
        if (cell.m === movement && this._pendingChanges[key] === undefined) return

        this._pendingChanges[key] = movement
        cell.m = movement
        this._updatePendingCount()
        this._render()
    }

    _setCellMovement(x, y, movement) {
        this._paintCell(x, y, movement)
        this._selectCell(x, y)
    }

    _paintTile(x, y) {
        if (this._pickerGid === 0 || this._pickerStampGids.length === 0) return

        for (let dy = 0; dy < this._pickerStampHeight; dy++) {
            for (let dx = 0; dx < this._pickerStampWidth; dx++) {
                const tx = x + dx
                const ty = y + dy
                const key = tx + '.' + ty
                const cell = this._cells[key]
                if (!cell) continue

                const stampIdx = dy * this._pickerStampWidth + dx
                const gid = this._pickerStampGids[stampIdx] || 0

                // Update the layer GID in the local cell data
                if (!cell.l) cell.l = []
                // Ensure the layers array is long enough
                while (cell.l.length <= this._pickerLayer) {
                    cell.l.push(0)
                }
                cell.l[this._pickerLayer] = gid

                // Track tile changes for save
                if (!this._pendingTileChanges[key]) {
                    this._pendingTileChanges[key] = {}
                }
                this._pendingTileChanges[key][this._pickerLayer] = gid
            }
        }

        this._updatePendingCount()
        this._render()
    }

    _eyedropperPick(x, y) {
        const key = x + '.' + y
        const cell = this._cells[key]
        if (!cell || !cell.l) return

        const gid = cell.l[this._pickerLayer] || 0
        if (gid <= 0) return

        // Find the tileset picker controller and update its selection
        const pickerEl = this.element.querySelector('[data-controller="admin-tileset-picker"]')
        if (pickerEl) {
            const pickerCtrl = this.application.getControllerForElementAndIdentifier(pickerEl, 'admin-tileset-picker')
            if (pickerCtrl) {
                pickerCtrl.selectGid(gid)
            }
        }

        // Update local state
        this._pickerGid = gid
        this._pickerStampWidth = 1
        this._pickerStampHeight = 1
        this._pickerStampGids = [gid]

        // Switch to paint tool
        this._tool = 'paint'
        this._updateToolButtons()
        this._render()
    }

    _eraseTile(x, y) {
        const key = x + '.' + y
        const cell = this._cells[key]
        if (!cell) return

        // Set GID to 0 on the active layer
        if (!cell.l) cell.l = []
        while (cell.l.length <= this._pickerLayer) {
            cell.l.push(0)
        }
        cell.l[this._pickerLayer] = 0

        // Track tile change for save
        if (!this._pendingTileChanges[key]) {
            this._pendingTileChanges[key] = {}
        }
        this._pendingTileChanges[key][this._pickerLayer] = 0

        this._updatePendingCount()
        this._render()
    }

    _fillBucket(x, y) {
        if (this._pickerGid === 0 || this._pickerStampGids.length === 0) return

        const layer = this._pickerLayer
        const fillGid = this._pickerStampGids[0]
        const startKey = x + '.' + y
        const startCell = this._cells[startKey]
        if (!startCell) return

        const targetGid = (startCell.l && startCell.l[layer]) || 0
        if (targetGid === fillGid) return

        const visited = new Set()
        const queue = [startKey]
        visited.add(startKey)

        const maxFill = 10000

        while (queue.length > 0 && visited.size <= maxFill) {
            const key = queue.shift()
            const cell = this._cells[key]
            if (!cell) continue

            if (!cell.l) cell.l = []
            while (cell.l.length <= layer) {
                cell.l.push(0)
            }
            cell.l[layer] = fillGid

            if (!this._pendingTileChanges[key]) {
                this._pendingTileChanges[key] = {}
            }
            this._pendingTileChanges[key][layer] = fillGid

            const [cx, cy] = key.split('.').map(Number)
            const neighbors = [
                (cx - 1) + '.' + cy,
                (cx + 1) + '.' + cy,
                cx + '.' + (cy - 1),
                cx + '.' + (cy + 1),
            ]

            for (const nk of neighbors) {
                if (visited.has(nk)) continue
                const nc = this._cells[nk]
                if (!nc) continue
                const ncGid = (nc.l && nc.l[layer]) || 0
                if (ncGid !== targetGid) continue
                visited.add(nk)
                queue.push(nk)
            }
        }

        this._updatePendingCount()
        this._render()
    }

    _updatePendingCount() {
        const count = Object.keys(this._pendingChanges).length + Object.keys(this._pendingBorderChanges).length + Object.keys(this._pendingTileChanges).length
        const btn = document.getElementById('save-btn')
        const badge = document.getElementById('pending-badge')
        if (btn) btn.disabled = count === 0
        if (badge) badge.textContent = count > 0 ? count : ''
    }

    _updateCoords(x, y) {
        if (this.hasCoordsTarget) {
            this.coordsTarget.textContent = `${x}, ${y}`
        }
    }

    _updateStats(text) {
        if (this.hasStatsTarget) {
            this.statsTarget.textContent = text
        }
    }

    // --- Public actions (called from HTML) ---

    toggleCollisions() {
        this._showCollisions = !this._showCollisions
        this._render()
    }

    toggleEntities() {
        this._showEntities = !this._showEntities
        this._render()
    }

    toggleGrid() {
        this._showGrid = !this._showGrid
        this._render()
    }

    toggleTiles() {
        this._renderTiles = !this._renderTiles
        this._render()
    }

    toggleWalls() {
        this._showWalls = !this._showWalls
        this._render()
    }

    toggleLayerVisibility(e) {
        const layer = parseInt(e.currentTarget.dataset.layer, 10)
        this._layerVisibility[layer] = !this._layerVisibility[layer]
        this._updateLayerPanel()
        this._render()
    }

    setLayerOpacity(e) {
        const layer = parseInt(e.currentTarget.dataset.layer, 10)
        this._layerOpacity[layer] = parseFloat(e.currentTarget.value)
        this._render()
    }

    selectActiveLayer(e) {
        const layer = parseInt(e.currentTarget.dataset.layer, 10)
        this._pickerLayer = layer
        this._updateLayerPanel()
        // Sync with tileset picker
        const pickerEl = this.element.querySelector('[data-controller="admin-tileset-picker"]')
        if (pickerEl) {
            const pickerCtrl = this.application.getControllerForElementAndIdentifier(pickerEl, 'admin-tileset-picker')
            if (pickerCtrl) {
                pickerCtrl._activeLayer = layer
            }
        }
    }

    _updateLayerPanel() {
        const panel = this.element.querySelector('#layer-panel')
        if (!panel) return

        const layerNames = ['Background', 'Ground', 'Decoration', 'Overlay']
        panel.querySelectorAll('[data-layer-row]').forEach(row => {
            const layer = parseInt(row.dataset.layerRow, 10)
            const isActive = layer === this._pickerLayer
            const isVisible = this._layerVisibility[layer]

            // Active layer highlight
            row.classList.toggle('bg-purple-900/30', isActive)
            row.classList.toggle('border-purple-500/50', isActive)
            row.classList.toggle('border-transparent', !isActive)

            // Visibility icon
            const eyeBtn = row.querySelector('[data-eye-btn]')
            if (eyeBtn) {
                eyeBtn.innerHTML = isVisible ? '👁' : '—'
                eyeBtn.classList.toggle('text-gray-300', isVisible)
                eyeBtn.classList.toggle('text-gray-600', !isVisible)
            }

            // Opacity slider
            const slider = row.querySelector('input[type="range"]')
            if (slider) {
                slider.disabled = !isVisible
                slider.classList.toggle('opacity-30', !isVisible)
            }
        })
    }

    setToolSelect() { this._tool = 'select'; this._updateToolButtons() }
    setToolBlock() { this._tool = 'block'; this._updateToolButtons() }
    setToolUnblock() { this._tool = 'unblock'; this._updateToolButtons() }
    setToolWater() { this._tool = 'water'; this._updateToolButtons() }
    setToolClimb() { this._tool = 'climb'; this._updateToolButtons() }
    setToolWall() { this._tool = 'wall'; this._updateToolButtons() }
    setToolEraseWall() { this._tool = 'eraseWall'; this._updateToolButtons() }
    setToolPaint() { this._tool = 'paint'; this._updateToolButtons() }
    setToolEraser() { this._tool = 'eraser'; this._updateToolButtons() }
    setToolFill() { this._tool = 'fill'; this._updateToolButtons() }

    // --- Tileset picker events ---

    onTileSelected(e) {
        const { gid, stampWidth, stampHeight, stampGids, layer } = e.detail
        this._pickerGid = gid
        this._pickerStampWidth = stampWidth
        this._pickerStampHeight = stampHeight
        this._pickerStampGids = stampGids
        this._pickerLayer = layer

        // Auto-switch to paint tool when a tile is selected
        if (gid > 0) {
            this._tool = 'paint'
            this._updateToolButtons()
        }
    }

    onLayerChanged(e) {
        this._pickerLayer = e.detail.layer
    }

    _updateToolButtons() {
        document.querySelectorAll('[data-tool]').forEach(btn => {
            const isActive = btn.dataset.tool === this._tool
            btn.classList.toggle('bg-purple-700', isActive)
            btn.classList.toggle('bg-gray-700', !isActive)
        })
    }

    zoomIn() {
        const cx = this._canvas.width / 2
        const cy = this._canvas.height / 2
        const worldX = (cx - this._offsetX) / this._zoom
        const worldY = (cy - this._offsetY) / this._zoom
        this._zoom = Math.min(this._maxZoom, this._zoom * 1.3)
        this._offsetX = cx - worldX * this._zoom
        this._offsetY = cy - worldY * this._zoom
        this._render()
    }

    zoomOut() {
        const cx = this._canvas.width / 2
        const cy = this._canvas.height / 2
        const worldX = (cx - this._offsetX) / this._zoom
        const worldY = (cy - this._offsetY) / this._zoom
        this._zoom = Math.max(this._minZoom, this._zoom / 1.3)
        this._offsetX = cx - worldX * this._zoom
        this._offsetY = cy - worldY * this._zoom
        this._render()
    }

    fitView() {
        this._zoom = Math.min(
            this._canvas.width / (this._mapWidth * this._tileSize),
            this._canvas.height / (this._mapHeight * this._tileSize),
            1
        )
        this._offsetX = (this._canvas.width - this._mapWidth * this._tileSize * this._zoom) / 2
        this._offsetY = (this._canvas.height - this._mapHeight * this._tileSize * this._zoom) / 2
        this._render()
    }

    async saveChanges() {
        const cellChanges = Object.entries(this._pendingChanges)
        const borderChanges = Object.entries(this._pendingBorderChanges)
        const tileChanges = Object.entries(this._pendingTileChanges)
        if (cellChanges.length === 0 && borderChanges.length === 0 && tileChanges.length === 0) return

        const btn = document.getElementById('save-btn')
        if (btn) {
            btn.disabled = true
            btn.textContent = 'Sauvegarde...'
        }

        let totalSaved = 0

        try {
            // Save cell movement changes
            if (cellChanges.length > 0) {
                const cells = cellChanges.map(([key, movement]) => {
                    const [x, y] = key.split('.').map(Number)
                    return { x, y, movement }
                })
                const res = await fetch(this.updateCellsUrlValue, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cells }),
                })
                const data = await res.json()
                if (data.success) {
                    totalSaved += data.count
                    this._pendingChanges = {}
                } else {
                    this._showFlash('Erreur collisions: ' + (data.error || 'inconnue'), 'error')
                }
            }

            // Save border changes
            if (borderChanges.length > 0) {
                const cells = borderChanges.map(([key, borders]) => {
                    const [x, y] = key.split('.').map(Number)
                    return { x, y, borders }
                })
                const res = await fetch(this.updateBordersUrlValue, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cells }),
                })
                const data = await res.json()
                if (data.success) {
                    totalSaved += data.count
                    // Update cell data with saved borders
                    for (const [key, borders] of borderChanges) {
                        if (this._cells[key]) {
                            this._cells[key].b = borders
                        }
                    }
                    this._pendingBorderChanges = {}
                } else {
                    this._showFlash('Erreur bordures: ' + (data.error || 'inconnue'), 'error')
                }
            }

            // Save tile changes
            if (tileChanges.length > 0) {
                const cells = []
                for (const [key, layers] of tileChanges) {
                    const [x, y] = key.split('.').map(Number)
                    for (const [layer, gid] of Object.entries(layers)) {
                        cells.push({ x, y, layer: parseInt(layer, 10), gid })
                    }
                }
                const res = await fetch(this.paintTilesUrlValue, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cells }),
                })
                const data = await res.json()
                if (data.success) {
                    totalSaved += data.count
                    this._pendingTileChanges = {}
                } else {
                    this._showFlash('Erreur tiles: ' + (data.error || 'inconnue'), 'error')
                }
            }

            this._updatePendingCount()
            if (totalSaved > 0) {
                this._showFlash(`${totalSaved} modification(s) sauvegardee(s)`, 'success')
            }
        } catch (err) {
            this._showFlash('Erreur reseau: ' + err.message, 'error')
        }

        if (btn) {
            btn.disabled = Object.keys(this._pendingChanges).length + Object.keys(this._pendingBorderChanges).length + Object.keys(this._pendingTileChanges).length === 0
            btn.textContent = 'Sauvegarder'
        }

        this._render()
    }

    discardChanges() {
        if (Object.keys(this._pendingChanges).length + Object.keys(this._pendingBorderChanges).length + Object.keys(this._pendingTileChanges).length === 0) return
        if (!confirm('Annuler toutes les modifications non sauvegardees ?')) return

        // Reload to reset
        this._pendingChanges = {}
        this._pendingBorderChanges = {}
        this._pendingTileChanges = {}
        this._updatePendingCount()
        this._loadData()
    }

    _showFlash(message, type) {
        const container = document.getElementById('editor-flash')
        if (!container) return

        const colors = {
            success: 'bg-green-900 text-green-200 border-green-800',
            error: 'bg-red-900 text-red-200 border-red-800',
            info: 'bg-blue-900 text-blue-200 border-blue-800',
        }[type] || 'bg-gray-900 text-gray-200 border-gray-800'

        container.innerHTML = `<div class="px-4 py-2 rounded-lg text-sm border ${colors}">${message}</div>`
        setTimeout(() => { container.innerHTML = '' }, 4000)
    }
}
