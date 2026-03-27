/**
 * WangTileResolverJs — client-side auto-tiling engine.
 *
 * Mirrors the PHP WangTileResolver logic:
 * - 4-corners corner-based Wang tiles
 * - Bitfield: TL<<3 | TR<<2 | BR<<1 | BL
 * - Standard 3x5 layout offsets from center tile
 * - Support for 25 terrain types + brick_road
 *
 * Usage:
 *   const resolver = new WangTileResolverJs()
 *   await resolver.loadFromServer(wangsetsUrl)  // or use built-in defaults
 *   const changes = resolver.resolveNeighbors(cells, x, y, layer, terrainSlug)
 */

const FIRST_GID_TERRAIN = 1
const FIRST_GID_FOREST = 1025

/**
 * Standard offsets from center tile in the 3x5 grid (32-column tileset).
 * bitfield => offset from centerLocalId
 */
const STANDARD_OFFSETS = {
    13: -96,  // inner-corner-missing-BR
    14: -95,  // inner-corner-missing-BL
    11: -64,  // inner-corner-missing-TR
    7: -63,   // inner-corner-missing-TL
    2: -33,   // corner-BR
    3: -32,   // top-edge
    1: -31,   // corner-BL
    6: -1,    // right-edge
    15: 0,    // FULL (center)
    9: 1,     // left-edge
    4: 31,    // corner-TR
    12: 32,   // bottom-edge
    8: 33,    // corner-TL
}

const TERRAIN_TYPES = {
    dark_dirt: { colorId: 1, centerLocalId: 100 },
    red_dirt: { colorId: 2, centerLocalId: 103 },
    black_dirt: { colorId: 3, centerLocalId: 106 },
    grey_dirt: { colorId: 4, centerLocalId: 109 },
    lava: { colorId: 5, centerLocalId: 112 },
    hole: { colorId: 6, centerLocalId: 115 },
    red_hole: { colorId: 7, centerLocalId: 118 },
    black_hole: { colorId: 8, centerLocalId: 121 },
    water: { colorId: 9, centerLocalId: 124 },
    trans_dirt: { colorId: 12, centerLocalId: 97 },
    grass: { colorId: 13, centerLocalId: 289 },
    dark_grass: { colorId: 14, centerLocalId: 295 },
    short_grass: { colorId: 15, centerLocalId: 298 },
    long_grass: { colorId: 16, centerLocalId: 301 },
    wheat: { colorId: 17, centerLocalId: 304 },
    earth: { colorId: 18, centerLocalId: 676 },
    sand: { colorId: 19, centerLocalId: 307 },
    sand_water: { colorId: 20, centerLocalId: 310 },
    snow: { colorId: 21, centerLocalId: 499 },
    snow_water: { colorId: 22, centerLocalId: 662 },
    snow_ice: { colorId: 23, centerLocalId: 502 },
    ice: { colorId: 24, centerLocalId: 496 },
    sewer: { colorId: 26, centerLocalId: 484 },
    sewer_water: { colorId: 27, centerLocalId: 481 },
}

const BRICK_ROAD_TILES = {
    1: 525, 2: 524, 3: 460, 4: 556, 6: 429, 7: 461, 8: 557,
    9: 427, 11: 459, 12: 396, 13: 395, 14: 397, 15: 491,
}

/** Variants: centerLocalId => [variantLocalIds] */
const VARIANTS = {
    100: [163, 164, 165],
    103: [166, 167, 168],
    106: [169, 170, 171],
    109: [172, 173, 174],
    112: [175, 176, 177],
    121: [184, 185, 186],
    124: [187, 188, 189],
    289: [292, 352, 353, 354],
    295: [358, 359, 360],
    307: [370, 371, 372],
    491: [398, 430, 462, 492, 493, 494],
    484: [547, 548, 549],
    481: [544, 545, 546],
    496: [559, 560, 561],
    499: [562, 563, 564],
    676: [],
    662: [],
}

export default class WangTileResolverJs {
    constructor() {
        // lookupCache: centerLocalId => { bitfield => localTileId }
        this._lookupCache = {}
        // tileToCenter: localTileId => centerLocalId
        this._tileToCenter = {}
        // centerToSlug: centerLocalId => slug
        this._centerToSlug = {}
        // fullTiles: centerLocalId => Set of localTileIds considered "full"
        this._fullTiles = {}

        this._buildLookupTables()
    }

    /**
     * Optionally load terrain data from server (overrides built-in tables).
     * Not required — the built-in constants match the PHP exactly.
     */
    async loadFromServer(wangsetsUrl) {
        try {
            const res = await fetch(wangsetsUrl)
            const data = await res.json()
            this._serverTerrains = data.terrains || {}
            this._serverSlugs = data.supportedSlugs || []
        } catch {
            // Fall back to built-in definitions
        }
    }

    /**
     * Detect the terrain slug for a given GID.
     * @param {number} gid - Global tile ID
     * @returns {string|null}
     */
    detectTerrainSlug(gid) {
        if (gid < FIRST_GID_TERRAIN || gid >= FIRST_GID_FOREST) {
            return null
        }
        const localId = gid - FIRST_GID_TERRAIN
        const centerId = this._tileToCenter[localId]
        if (centerId !== undefined) {
            return this._centerToSlug[centerId] || null
        }
        return null
    }

    /**
     * Get the center (full) GID for a terrain slug.
     * @param {string} slug
     * @returns {number}
     */
    getCenterGid(slug) {
        if (slug === 'brick_road') {
            return FIRST_GID_TERRAIN + 491
        }
        const config = TERRAIN_TYPES[slug]
        return config ? FIRST_GID_TERRAIN + config.centerLocalId : 0
    }

    /**
     * Get all supported terrain slugs.
     * @returns {string[]}
     */
    getSupportedTerrains() {
        return [...Object.keys(TERRAIN_TYPES), 'brick_road']
    }

    /**
     * Resolve a single cell to its correct transition GID.
     *
     * @param {Object} cells - Map of "x.y" => {l: [gid0, gid1, gid2, gid3], ...}
     * @param {number} x
     * @param {number} y
     * @param {number} layer
     * @param {string} terrainSlug
     * @returns {number} The resolved GID, or 0 if no transition
     */
    resolve(cells, x, y, layer, terrainSlug) {
        const centerLocalId = this._getCenterLocalId(terrainSlug)
        if (centerLocalId === null) return 0

        const lookup = this._lookupCache[centerLocalId]
        if (!lookup) return 0

        const bitfield = this._computeCornerBitfield(cells, x, y, layer, centerLocalId)
        if (bitfield === 0) return 0

        const localTileId = lookup[bitfield] !== undefined ? lookup[bitfield] : lookup[15]
        return FIRST_GID_TERRAIN + localTileId
    }

    /**
     * After painting a cell with a terrain's center GID, recalculate transitions
     * for the painted cell and its 8 neighbors.
     *
     * @param {Object} cells - The local cell map (will be read, NOT mutated)
     * @param {number} x - Center cell X
     * @param {number} y - Center cell Y
     * @param {number} layer
     * @param {string} terrainSlug
     * @returns {Array<{x: number, y: number, layer: number, gid: number}>} List of changes to apply
     */
    resolveNeighbors(cells, x, y, layer, terrainSlug) {
        const changes = []

        // Resolve a 3x3 area centered on (x, y), which covers all affected neighbors
        for (let dy = -1; dy <= 1; dy++) {
            for (let dx = -1; dx <= 1; dx++) {
                const nx = x + dx
                const ny = y + dy
                const key = nx + '.' + ny
                if (!cells[key]) continue

                const gid = this.resolve(cells, nx, ny, layer, terrainSlug)
                if (gid === 0) continue

                const currentGid = this._getCellGid(cells, nx, ny, layer)
                if (currentGid === gid) continue

                changes.push({ x: nx, y: ny, layer, gid })
            }
        }

        return changes
    }

    /**
     * Resolve auto-tiling for a rectangular zone + 1-cell border.
     *
     * @param {Object} cells
     * @param {number} startX
     * @param {number} startY
     * @param {number} endX
     * @param {number} endY
     * @param {number} layer
     * @param {string} terrainSlug
     * @returns {Array<{x: number, y: number, layer: number, gid: number}>}
     */
    resolveZone(cells, startX, startY, endX, endY, layer, terrainSlug) {
        const changes = []
        const resolveStartX = startX - 1
        const resolveStartY = startY - 1
        const resolveEndX = endX + 1
        const resolveEndY = endY + 1

        for (let y = resolveStartY; y <= resolveEndY; y++) {
            for (let x = resolveStartX; x <= resolveEndX; x++) {
                const key = x + '.' + y
                if (!cells[key]) continue

                const gid = this.resolve(cells, x, y, layer, terrainSlug)
                if (gid === 0) continue

                const currentGid = this._getCellGid(cells, x, y, layer)
                if (currentGid === gid) continue

                changes.push({ x, y, layer, gid })
            }
        }

        return changes
    }

    // --- Private ---

    _getCenterLocalId(slug) {
        if (slug === 'brick_road') return 491
        const config = TERRAIN_TYPES[slug]
        return config ? config.centerLocalId : null
    }

    _computeCornerBitfield(cells, x, y, layer, centerLocalId) {
        let bitfield = 0

        // Top-Left corner: cells (x-1,y-1), (x,y-1), (x-1,y), (x,y)
        if (this._isTerrainAt(cells, x, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x - 1, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x, y - 1, layer, centerLocalId)
            || this._isTerrainAt(cells, x - 1, y - 1, layer, centerLocalId)) {
            bitfield |= 8 // TL
        }

        // Top-Right corner: cells (x,y-1), (x+1,y-1), (x,y), (x+1,y)
        if (this._isTerrainAt(cells, x, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x + 1, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x, y - 1, layer, centerLocalId)
            || this._isTerrainAt(cells, x + 1, y - 1, layer, centerLocalId)) {
            bitfield |= 4 // TR
        }

        // Bottom-Right corner: cells (x,y), (x+1,y), (x,y+1), (x+1,y+1)
        if (this._isTerrainAt(cells, x, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x + 1, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x, y + 1, layer, centerLocalId)
            || this._isTerrainAt(cells, x + 1, y + 1, layer, centerLocalId)) {
            bitfield |= 2 // BR
        }

        // Bottom-Left corner: cells (x-1,y), (x,y), (x-1,y+1), (x,y+1)
        if (this._isTerrainAt(cells, x, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x - 1, y, layer, centerLocalId)
            || this._isTerrainAt(cells, x, y + 1, layer, centerLocalId)
            || this._isTerrainAt(cells, x - 1, y + 1, layer, centerLocalId)) {
            bitfield |= 1 // BL
        }

        return bitfield
    }

    _isTerrainAt(cells, x, y, layer, centerLocalId) {
        const key = x + '.' + y
        const cell = cells[key]
        if (!cell || !cell.l || cell.l.length <= layer) return false

        const gid = cell.l[layer]
        if (!gid || gid < FIRST_GID_TERRAIN || gid >= FIRST_GID_FOREST) return false

        const localId = gid - FIRST_GID_TERRAIN
        const fullSet = this._fullTiles[centerLocalId]
        return fullSet ? fullSet.has(localId) : false
    }

    _getCellGid(cells, x, y, layer) {
        const key = x + '.' + y
        const cell = cells[key]
        if (!cell || !cell.l || cell.l.length <= layer) return 0
        return cell.l[layer] || 0
    }

    _buildLookupTables() {
        // Standard terrains
        for (const [slug, config] of Object.entries(TERRAIN_TYPES)) {
            const centerId = config.centerLocalId
            this._centerToSlug[centerId] = slug
            const lookup = {}

            for (const [bitfieldStr, offset] of Object.entries(STANDARD_OFFSETS)) {
                const bitfield = parseInt(bitfieldStr, 10)
                const localId = centerId + offset
                if (localId >= 0) {
                    lookup[bitfield] = localId
                    this._tileToCenter[localId] = centerId
                }
            }

            // Diagonals: use center tile
            lookup[5] = centerId
            lookup[10] = centerId

            this._lookupCache[centerId] = lookup
            this._fullTiles[centerId] = new Set([centerId])
        }

        // Brick Road (non-standard layout)
        const brickCenterId = 491
        this._centerToSlug[brickCenterId] = 'brick_road'
        const brickLookup = {}

        for (const [bitfieldStr, localId] of Object.entries(BRICK_ROAD_TILES)) {
            const bitfield = parseInt(bitfieldStr, 10)
            brickLookup[bitfield] = localId
            this._tileToCenter[localId] = brickCenterId
        }

        brickLookup[5] = brickCenterId
        brickLookup[10] = brickCenterId

        this._lookupCache[brickCenterId] = brickLookup
        this._fullTiles[brickCenterId] = new Set([brickCenterId])

        // Register variant tiles
        for (const [centerIdStr, variantIds] of Object.entries(VARIANTS)) {
            const centerId = parseInt(centerIdStr, 10)
            if (!this._fullTiles[centerId]) {
                this._fullTiles[centerId] = new Set([centerId])
            }
            for (const variantId of variantIds) {
                this._tileToCenter[variantId] = centerId
                this._fullTiles[centerId].add(variantId)
            }
        }
    }
}
