import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = {
        mapId: Number,
        cellsUrl: String,
        tilesetsUrl: String,
        entitiesUrl: String,
        updateCellUrl: String,
        updateCellsUrl: String,
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
    _showCollisions = true
    _showEntities = true
    _showGrid = false
    _tilesetsLoaded = false
    _renderTiles = true
    _tool = 'select' // select, block, unblock, paint
    _paintMovement = -1
    _pendingChanges = {}
    _ctx = null
    _animFrame = null
    _hoveredCell = null

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

                // Pending changes highlight
                if (this._pendingChanges[key] !== undefined) {
                    ctx.fillStyle = 'rgba(255, 255, 0, 0.3)'
                    ctx.fillRect(px, py, ts, ts)
                }
            }
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

        // Hovered cell
        if (this._hoveredCell) {
            const hx = this._hoveredCell.x * ts + this._offsetX
            const hy = this._hoveredCell.y * ts + this._offsetY
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.6)'
            ctx.lineWidth = 2
            ctx.strokeRect(hx, hy, ts, ts)
        }

        // Selected cell
        if (this._selectedCell) {
            const sx = this._selectedCell.x * ts + this._offsetX
            const sy = this._selectedCell.y * ts + this._offsetY
            ctx.strokeStyle = '#a855f7'
            ctx.lineWidth = 3
            ctx.strokeRect(sx, sy, ts, ts)
        }
    }

    _drawTileLayers(ctx, cell, px, py, ts) {
        for (const gid of cell.l) {
            const tileset = this._findTileset(gid)
            if (!tileset) continue

            const img = this._tilesetImages[tileset.name]
            if (!img) continue

            const localId = gid - tileset.firstGid
            const srcX = (localId % tileset.columns) * tileset.tileWidth
            const srcY = Math.floor(localId / tileset.columns) * tileset.tileHeight

            ctx.drawImage(img, srcX, srcY, tileset.tileWidth, tileset.tileHeight, px, py, ts, ts)
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

        if (e.button === 1 || e.button === 2 || (e.button === 0 && e.altKey)) {
            // Middle click or right click or alt+click: pan
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

            if (this._tool === 'select') {
                this._selectCell(tileCoords.x, tileCoords.y)
            } else if (this._tool === 'block') {
                this._paintCell(tileCoords.x, tileCoords.y, -1)
                this._isPainting = true
            } else if (this._tool === 'unblock') {
                this._paintCell(tileCoords.x, tileCoords.y, 0)
                this._isPainting = true
            } else if (this._tool === 'water') {
                this._paintCell(tileCoords.x, tileCoords.y, 2)
                this._isPainting = true
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

            if (this._isPainting) {
                const movement = this._tool === 'block' ? -1 : (this._tool === 'water' ? 2 : 0)
                this._paintCell(tileCoords.x, tileCoords.y, movement)
            }
        } else {
            this._hoveredCell = null
        }

        this._render()
    }

    _onMouseUp = () => {
        this._isDragging = false
        this._isPainting = false
        this._canvas.style.cursor = 'default'
    }

    _onMouseLeave = () => {
        this._isDragging = false
        this._isPainting = false
        this._hoveredCell = null
        this._canvas.style.cursor = 'default'
        this._render()
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

    _selectCell(x, y) {
        const key = x + '.' + y
        const cell = this._cells[key]
        this._selectedCell = { x, y }
        this._render()

        if (cell) {
            const movLabel = cell.m === -1 ? 'Bloque (mur)' : (cell.m === 2 ? 'Eau' : 'Libre')
            const pendingMov = this._pendingChanges[key]
            let pendingLabel = ''
            if (pendingMov !== undefined) {
                const pl = pendingMov === -1 ? 'Bloque' : (pendingMov === 2 ? 'Eau' : 'Libre')
                pendingLabel = ` → <span class="text-yellow-400">${pl} (non sauvegarde)</span>`
            }

            const entitiesHere = this._getEntitiesAt(x, y)
            let entitiesHtml = ''
            if (entitiesHere.length > 0) {
                entitiesHtml = '<div class="mt-2 text-xs">'
                for (const e of entitiesHere) {
                    entitiesHtml += `<span class="inline-block px-2 py-0.5 rounded mr-1 mb-1" style="background:${e.color}">${e.type}: ${e.name}</span>`
                }
                entitiesHtml += '</div>'
            }

            this.infoTarget.innerHTML = `
                <div class="text-sm">
                    <p><span class="text-gray-400">Position:</span> <strong>${x}, ${y}</strong></p>
                    <p><span class="text-gray-400">Mouvement:</span> <strong>${movLabel}</strong> (${cell.m})${pendingLabel}</p>
                    <p><span class="text-gray-400">Layers:</span> ${cell.l ? cell.l.join(', ') : 'aucun'}</p>
                    ${entitiesHtml}
                    <div class="mt-3 flex gap-2">
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, 0)"
                                class="px-2 py-1 bg-green-700 hover:bg-green-600 rounded text-xs">Debloquer</button>
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, -1)"
                                class="px-2 py-1 bg-red-700 hover:bg-red-600 rounded text-xs">Bloquer</button>
                        <button onclick="this.closest('[data-controller]').__stimulus_controller._setCellMovement(${x}, ${y}, 2)"
                                class="px-2 py-1 bg-blue-700 hover:bg-blue-600 rounded text-xs">Eau</button>
                    </div>
                </div>
            `
        } else {
            this.infoTarget.innerHTML = `<p class="text-gray-500 text-sm">Cellule vide (${x}, ${y})</p>`
        }
    }

    _getEntitiesAt(x, y) {
        const results = []
        for (const mob of (this._entities.mobs || [])) {
            if (mob.x === x && mob.y === y) results.push({ type: 'Mob', name: `${mob.name} (nv.${mob.level})`, color: '#991b1b' })
        }
        for (const pnj of (this._entities.pnjs || [])) {
            if (pnj.x === x && pnj.y === y) results.push({ type: 'PNJ', name: pnj.name, color: '#166534' })
        }
        for (const portal of (this._entities.portals || [])) {
            if (portal.x === x && portal.y === y) results.push({ type: 'Portail', name: `${portal.name} → map ${portal.destMapId}`, color: '#1e40af' })
        }
        for (const spot of (this._entities.harvestSpots || [])) {
            if (spot.x === x && spot.y === y) results.push({ type: 'Recolte', name: spot.name, color: '#854d0e' })
        }
        for (const st of (this._entities.craftStations || [])) {
            if (st.x === x && st.y === y) results.push({ type: 'Station', name: `${st.name} (${st.type})`, color: '#9a3412' })
        }
        return results
    }

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

    _updatePendingCount() {
        const count = Object.keys(this._pendingChanges).length
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

    setToolSelect() { this._tool = 'select'; this._updateToolButtons() }
    setToolBlock() { this._tool = 'block'; this._updateToolButtons() }
    setToolUnblock() { this._tool = 'unblock'; this._updateToolButtons() }
    setToolWater() { this._tool = 'water'; this._updateToolButtons() }

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
        const changes = Object.entries(this._pendingChanges)
        if (changes.length === 0) return

        const cells = changes.map(([key, movement]) => {
            const [x, y] = key.split('.').map(Number)
            return { x, y, movement }
        })

        const btn = document.getElementById('save-btn')
        if (btn) {
            btn.disabled = true
            btn.textContent = 'Sauvegarde...'
        }

        try {
            const res = await fetch(this.updateCellsUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cells }),
            })

            const data = await res.json()
            if (data.success) {
                this._pendingChanges = {}
                this._updatePendingCount()
                this._showFlash(`${data.count} cellule(s) sauvegardee(s)`, 'success')
            } else {
                this._showFlash('Erreur: ' + (data.error || 'inconnue'), 'error')
            }
        } catch (err) {
            this._showFlash('Erreur reseau: ' + err.message, 'error')
        }

        if (btn) {
            btn.disabled = Object.keys(this._pendingChanges).length === 0
            btn.textContent = 'Sauvegarder'
        }

        this._render()
    }

    discardChanges() {
        if (Object.keys(this._pendingChanges).length === 0) return
        if (!confirm('Annuler toutes les modifications non sauvegardees ?')) return

        // Reload to reset
        this._pendingChanges = {}
        this._updatePendingCount()
        this._loadData()
    }

    _showFlash(message, type) {
        const container = document.getElementById('editor-flash')
        if (!container) return

        const colors = type === 'success'
            ? 'bg-green-900 text-green-200 border-green-800'
            : 'bg-red-900 text-red-200 border-red-800'

        container.innerHTML = `<div class="px-4 py-2 rounded-lg text-sm border ${colors}">${message}</div>`
        setTimeout(() => { container.innerHTML = '' }, 4000)
    }
}
