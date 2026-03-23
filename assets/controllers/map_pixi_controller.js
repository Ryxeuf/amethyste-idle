import { Controller } from '@hotwired/stimulus';
import * as PIXI from 'pixi.js';
import SpriteAnimator, { directionFromDelta, EMOTE_TYPES } from '../lib/SpriteAnimator.js';

export default class extends Controller {
    static values = {
        mercureUrl: String,
        playerId: Number,
        mapId: Number,
        playerX: Number,
        playerY: Number,
        stepDelay: { type: Number, default: 150 },
    };

    async connect() {
        this._tileSize = 32;
        this._viewRadius = 25;
        this._viewportTiles = 21;
        this._viewportPx = this._viewportTiles * this._tileSize;

        this._playerX = this.playerXValue;
        this._playerY = this.playerYValue;
        this._animating = false;
        this._cancelRequested = false;
        this._pendingNewTarget = null;

        this._cellCache = new Map();
        this._tileSprites = new Map();
        this._tilesets = [];
        this._tilesetTextures = {};
        this._entitySprites = {};
        this._spriteConfig = {};
        this._spriteTextures = {};

        // Performance: texture cache per GID to avoid recreating PIXI.Texture
        this._tileTextureCache = new Map();
        // Performance: sprite object pool for reuse
        this._spritePool = [];
        // Performance: entity container pool for reuse
        this._entityPool = [];
        // Performance: cached marker textures
        this._markerTextureCache = new Map();
        // Performance: track animated entities separately
        this._animatedEntities = [];
        // Performance: spatial hash for entity lookup
        this._entitySpatialHash = new Map();
        // Performance: frame budget monitoring
        this._frameBudgetMs = 16.67; // 60fps target
        this._slowFrameCount = 0;
        this._frameCount = 0;

        this._playerAnimator = null;
        this._playerDirection = 'down';
        this._lastEntityLoadX = this.playerXValue;
        this._lastEntityLoadY = this.playerYValue;
        this._entityLoadThreshold = 5; // Reload entities every 5 tiles moved
        this._pendingPortal = null; // Portal data from move response

        // Camera shake system
        this._shakeIntensity = 0;
        this._shakeDuration = 0;
        this._shakeElapsed = 0;

        // Day/night ambient overlay (driven by server GameTimeService)
        this._dayNightEnabled = true;
        this._ambientOverlay = null;
        this._timeOfDay = 'day';
        this._gameTimeData = null; // { hour, minute, timeOfDay, season, day, timeRatio }

        // Particle system for environmental effects
        this._particles = [];
        this._particleContainer = null;

        // Minimap state
        this._minimapVisible = true;
        this._minimapSize = 150;
        this._minimapPadding = 8;
        this._minimapContainer = null;

        await this._initPixi();
        await this._loadConfig();
        await this._loadCells(this._playerX, this._playerY);
        await this._loadEntities();
        this._setupMercure();
        await this._fetchGameTime();
        this._initDayNight();
        this._initTimeHud();
        this._initMinimap();
        this._updateCamera(true);
        if (typeof console !== 'undefined' && console.debug) {
            console.debug('[map_pixi] Carte chargée (annulation au clic activée)');
        }
    }

    disconnect() {
        if (this._resizeObserver) {
            this._resizeObserver.disconnect();
            this._resizeObserver = null;
        }
        if (this._onKeyDown) {
            document.removeEventListener('keydown', this._onKeyDown);
        }
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
        if (this._playerAnimator) {
            this._playerAnimator.destroy();
            this._playerAnimator = null;
        }
        for (const key of Object.keys(this._entitySprites)) {
            const entity = this._entitySprites[key];
            if (entity.animator) {
                entity.animator.destroy();
            }
        }
        // Release pooled sprites
        for (const s of this._spritePool) {
            s.destroy();
        }
        this._spritePool = [];
        // Release pooled entity containers
        for (const c of this._entityPool) {
            c.destroy({ children: true });
        }
        this._entityPool = [];
        this._tileTextureCache.clear();
        this._markerTextureCache.clear();
        this._entitySpatialHash.clear();
        this._animatedEntities = [];
        this._particles = [];
        if (this._minimapContainer) {
            this._minimapContainer.destroy({ children: true });
            this._minimapContainer = null;
        }
        if (this._timeHudText) {
            this._timeHudText.destroy();
            this._timeHudText = null;
        }

        if (this._tooltipEl && this._tooltipEl.parentNode) {
            this._tooltipEl.parentNode.removeChild(this._tooltipEl);
            this._tooltipEl = null;
        }

        if (this._app) {
            this._app.destroy(true);
            this._app = null;
        }
    }

    // --- Initialization ---

    async _initPixi() {
        // Responsive: use container size, clamped to max 672px
        const containerRect = this.element.getBoundingClientRect();
        const w = Math.min(Math.floor(containerRect.width), this._viewportPx);
        const h = Math.min(Math.floor(containerRect.height || containerRect.width), this._viewportPx * 2);
        this._currentWidth = w;
        this._currentHeight = h;

        this._app = new PIXI.Application();
        await this._app.init({
            width: w,
            height: h,
            backgroundColor: 0x2d5a1b,
            antialias: false,
            roundPixels: true,
            resolution: 1,
        });

        this.element.innerHTML = '';
        this.element.appendChild(this._app.canvas);
        this._app.canvas.style.cursor = 'pointer';
        this._app.canvas.style.touchAction = 'none';
        this._app.canvas.style.width = '100%';
        this._app.canvas.style.height = '100%';

        this._worldContainer = new PIXI.Container();
        this._worldContainer.sortableChildren = true;

        this._tileContainer = new PIXI.Container();
        this._tileContainer.zIndex = 0;
        this._tileContainer.cullable = true;

        this._entityContainer = new PIXI.Container();
        this._entityContainer.zIndex = 10;
        this._entityContainer.sortableChildren = true;

        this._playerContainer = new PIXI.Container();
        this._playerContainer.zIndex = 20;

        this._worldContainer.addChild(this._tileContainer);
        this._worldContainer.addChild(this._entityContainer);
        this._worldContainer.addChild(this._playerContainer);
        this._app.stage.addChild(this._worldContainer);

        this._initTooltip();

        this._app.canvas.addEventListener('pointerdown', (e) => this._onPointerDown(e));
        this._app.canvas.addEventListener('pointermove', (e) => this._onPointerMove(e));
        this._app.canvas.addEventListener('pointerup', (e) => this._onPointerUp(e));
        this._app.canvas.addEventListener('pointerleave', () => this._hideTooltip());

        this._onKeyDown = (e) => this._handleKeyDown(e);
        document.addEventListener('keydown', this._onKeyDown);

        // Swipe tracking
        this._swipeStart = null;
        this._swipeThreshold = 30;

        this._app.ticker.add((ticker) => this._tick(ticker));

        // Resize observer for responsive canvas
        this._resizeObserver = new ResizeObserver(() => this._onResize());
        this._resizeObserver.observe(this.element);
    }

    _onResize() {
        if (!this._app) return;
        const containerRect = this.element.getBoundingClientRect();
        const w = Math.min(Math.floor(containerRect.width), this._viewportPx);
        const h = Math.min(Math.floor(containerRect.height || containerRect.width), this._viewportPx * 2);
        if ((w === this._currentWidth && h === this._currentHeight) || w < 64 || h < 64) return;

        this._currentWidth = w;
        this._currentHeight = h;
        this._app.renderer.resize(w, h);
        this._updateCamera(true);
    }

    async _loadConfig() {
        const resp = await fetch('/api/map/config');
        const config = await resp.json();
        this._tilesets = config.tilesets || [];
        this._tileSize = config.tileSize || 32;
        this._spriteConfig = config.sprites || {};

        this._tilesets.sort((a, b) => a.firstGid - b.firstGid);

        // Load tileset textures
        const loadPromises = this._tilesets.map(async (ts) => {
            const texture = await PIXI.Assets.load(ts.image);
            texture.source.scaleMode = 'nearest';
            this._tilesetTextures[ts.name] = texture;
        });

        // Preload all sprite sheet textures
        const spriteSheets = new Set();
        for (const cfg of Object.values(this._spriteConfig)) {
            spriteSheets.add(cfg.sheet);
        }
        for (const sheet of spriteSheets) {
            loadPromises.push(
                PIXI.Assets.load(sheet).then((texture) => {
                    texture.source.scaleMode = 'nearest';
                    this._spriteTextures[sheet] = texture;
                })
            );
        }

        await Promise.all(loadPromises);
    }

    // --- Cell Loading & Rendering ---

    async _loadCells(x, y) {
        const resp = await fetch(`/api/map/cells?x=${x}&y=${y}&radius=${this._viewRadius}&mapId=${this.mapIdValue}`);
        const data = await resp.json();

        for (const cell of data.cells) {
            const key = `${cell.x},${cell.y}`;
            if (!this._cellCache.has(key)) {
                this._cellCache.set(key, cell);
                this._renderCell(cell);
            }
        }

        this._pruneDistantCells(x, y);
    }

    _renderCell(cell) {
        const key = `${cell.x},${cell.y}`;
        const sprites = [];
        const px = cell.x * this._tileSize;
        const py = cell.y * this._tileSize;

        for (const gid of cell.l) {
            const ts = this._findTileset(gid);
            if (!ts || ts.name === 'collisions') continue;

            const baseTexture = this._tilesetTextures[ts.name];
            if (!baseTexture) continue;

            // Cache texture per GID to avoid recreating PIXI.Texture each cell
            let texture = this._tileTextureCache.get(gid);
            if (!texture) {
                const localId = gid - ts.firstGid;
                const col = localId % ts.columns;
                const row = Math.floor(localId / ts.columns);
                const frame = new PIXI.Rectangle(
                    col * ts.tileWidth,
                    row * ts.tileHeight,
                    ts.tileWidth,
                    ts.tileHeight,
                );
                try {
                    texture = new PIXI.Texture({ source: baseTexture.source, frame });
                } catch {
                    continue;
                }
                this._tileTextureCache.set(gid, texture);
            }

            // Reuse pooled sprite or create new one
            const sprite = this._acquireSprite(texture);
            sprite.position.set(px, py);
            this._tileContainer.addChild(sprite);
            sprites.push(sprite);
        }

        this._tileSprites.set(key, sprites);
    }

    _acquireSprite(texture) {
        if (this._spritePool.length > 0) {
            const sprite = this._spritePool.pop();
            sprite.texture = texture;
            sprite.visible = true;
            return sprite;
        }
        const sprite = new PIXI.Sprite(texture);
        sprite.roundPixels = true;
        return sprite;
    }

    _releaseSprite(sprite) {
        sprite.visible = false;
        this._tileContainer.removeChild(sprite);
        this._spritePool.push(sprite);
    }

    _pruneDistantCells(centerX, centerY) {
        const maxDist = this._viewRadius * 3;
        const keysToRemove = [];

        for (const [key, cell] of this._cellCache) {
            if (Math.abs(cell.x - centerX) > maxDist || Math.abs(cell.y - centerY) > maxDist) {
                keysToRemove.push(key);
            }
        }

        for (const key of keysToRemove) {
            this._cellCache.delete(key);
            const sprites = this._tileSprites.get(key);
            if (sprites) {
                for (const s of sprites) {
                    this._releaseSprite(s);
                }
                this._tileSprites.delete(key);
            }
        }
    }

    _findTileset(gid) {
        const tilesets = this._tilesets;
        let lo = 0, hi = tilesets.length - 1;
        let selected = null;
        while (lo <= hi) {
            const mid = (lo + hi) >> 1;
            if (tilesets[mid].firstGid <= gid) {
                selected = tilesets[mid];
                lo = mid + 1;
            } else {
                hi = mid - 1;
            }
        }
        return selected;
    }

    // --- Sprite Animator Factory ---

    _createAnimator(spriteKey) {
        const cfg = this._spriteConfig[spriteKey];
        if (!cfg) return null;

        const texture = this._spriteTextures[cfg.sheet];
        if (!texture) return null;

        return new SpriteAnimator({
            texture,
            type: cfg.type || 'single',
            charIndex: cfg.charIndex || 0,
        });
    }

    // --- Entity Rendering ---

    async _loadEntities() {
        const resp = await fetch(`/api/map/entities?radius=${this._viewRadius}`);
        const data = await resp.json();

        this._clearEntities();

        for (const p of data.players) {
            if (p.self) continue;
            this._createEntitySprite('player', p.id, p.x, p.y, p.spriteKey, p.name, { name: p.name });
        }

        for (const mob of data.mobs) {
            this._createEntitySprite('mob', mob.id, mob.x, mob.y, mob.spriteKey, 'M', { name: mob.name, level: mob.level });
        }

        for (const pnj of data.pnjs) {
            this._createEntitySprite('pnj', pnj.id, pnj.x, pnj.y, pnj.spriteKey, pnj.name?.[0] ?? 'N', { name: pnj.name, questIndicator: pnj.questIndicator });
        }

        for (const portal of (data.portals || [])) {
            this._createPortalMarker(portal);
        }

        for (const spot of (data.harvestSpots || [])) {
            this._createHarvestSpotMarker(spot);
        }

        this._createPlayerMarker();
    }

    _createEntitySprite(type, id, x, y, spriteKey, label, meta = {}) {
        const key = `${type}_${id}`;
        const animator = this._createAnimator(spriteKey);

        const container = this._acquireEntityContainer();

        if (animator) {
            const sprite = animator.sprite;
            sprite.anchor.set(0.5, 1);
            sprite.position.set(this._tileSize / 2, this._tileSize);
            animator.setBaseY(this._tileSize);
            container.addChild(sprite);
            container.position.set(x * this._tileSize, y * this._tileSize);
            container.zIndex = y * this._tileSize;
            this._entityContainer.addChild(container);
            this._entitySprites[key] = { container, x, y, type, animator, meta };
            this._animatedEntities.push(animator);
        } else {
            // Fallback: cached colored marker
            const colorMap = { 'mob': '#8B0000', 'player': '#3333ff', 'pnj': '#4B0082' };
            const hex = colorMap[type] || '#888888';
            const shape = type === 'mob' ? 'rect' : 'circle';
            const texture = this._getCachedMarkerTexture(hex, '#000000', shape);
            const markerSprite = new PIXI.Sprite(texture);

            container.addChild(markerSprite);
            container.position.set(x * this._tileSize, y * this._tileSize);
            container.zIndex = y * this._tileSize;
            this._entityContainer.addChild(container);
            this._entitySprites[key] = { container, x, y, type, animator: null, meta };
        }

        // Quest indicator for PNJs
        if (type === 'pnj' && meta.questIndicator) {
            this._addQuestIndicator(container, meta.questIndicator);
        }

        // Spatial hash registration
        this._addToSpatialHash(key, x, y);
    }

    _getCachedMarkerTexture(color, strokeColor, shape) {
        const cacheKey = `${color}_${strokeColor}_${shape}`;
        if (this._markerTextureCache.has(cacheKey)) {
            return this._markerTextureCache.get(cacheKey);
        }
        const texture = this._createMarkerTexture(color, strokeColor, shape);
        this._markerTextureCache.set(cacheKey, texture);
        return texture;
    }

    _createMarkerTexture(color, strokeColor, shape) {
        const s = this._tileSize;
        const canvas = document.createElement('canvas');
        canvas.width = s;
        canvas.height = s;
        const ctx = canvas.getContext('2d');
        const half = s / 2;

        ctx.fillStyle = color;
        ctx.strokeStyle = strokeColor;
        ctx.lineWidth = 2;

        if (shape === 'rect') {
            ctx.fillRect(4, 4, s - 8, s - 8);
            ctx.strokeRect(4, 4, s - 8, s - 8);
        } else {
            ctx.beginPath();
            ctx.arc(half, half, half - 3, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        }

        return PIXI.Texture.from(canvas);
    }

    _addQuestIndicator(container, indicatorType) {
        const texture = this._getQuestIndicatorTexture(indicatorType);
        const indicator = new PIXI.Sprite(texture);
        indicator.anchor.set(0.5, 1);
        indicator.position.set(this._tileSize / 2, -2);
        container.addChild(indicator);
    }

    _getQuestIndicatorTexture(indicatorType) {
        const cacheKey = `quest_indicator_${indicatorType}`;
        if (this._markerTextureCache.has(cacheKey)) {
            return this._markerTextureCache.get(cacheKey);
        }

        const size = 16;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');

        const symbol = indicatorType === 'available' ? '!' : '?';
        const color = indicatorType === 'available' ? '#FFD700' : '#C0C0C0';

        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2 - 1, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = color;
        ctx.font = 'bold 12px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(symbol, size / 2, size / 2);

        const texture = PIXI.Texture.from(canvas);
        this._markerTextureCache.set(cacheKey, texture);
        return texture;
    }

    _createPlayerMarker() {
        const animator = this._createAnimator('player_default');

        this._playerMarker = new PIXI.Container();

        if (animator) {
            this._playerAnimator = animator;
            this._playerAnimator.setDirection(this._playerDirection);
            this._playerAnimator.setBaseY(this._tileSize);
            const sprite = animator.sprite;
            sprite.anchor.set(0.5, 1);
            sprite.position.set(this._tileSize / 2, this._tileSize);
            this._playerMarker.addChild(sprite);
        } else {
            // Fallback: red circle
            const texture = this._getCachedMarkerTexture('#ff0000', '#ffffff', 'circle');
            const sprite = new PIXI.Sprite(texture);
            this._playerMarker.addChild(sprite);
        }

        const px = Math.floor(this._playerX) * this._tileSize;
        const py = Math.floor(this._playerY) * this._tileSize;
        this._playerMarker.position.set(px, py);

        this._playerContainer.addChild(this._playerMarker);
    }

    _createPortalMarker(portal) {
        const texture = this._getPortalTexture();
        const sprite = new PIXI.Sprite(texture);
        const s = this._tileSize;
        sprite.position.set(portal.x * s, portal.y * s);
        sprite.zIndex = portal.y * s - 1;
        this._entityContainer.addChild(sprite);

        const key = `portal_${portal.id}`;
        this._entitySprites[key] = { container: sprite, x: portal.x, y: portal.y, type: 'portal', animator: null, meta: { name: portal.name } };
        this._addToSpatialHash(key, portal.x, portal.y);
    }

    _getPortalTexture() {
        if (this._portalTexture) return this._portalTexture;
        const s = this._tileSize;
        const canvas = document.createElement('canvas');
        canvas.width = s;
        canvas.height = s;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = 'rgba(109, 40, 217, 0.4)';
        ctx.beginPath();
        ctx.arc(s / 2, s / 2, s / 2 - 2, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = '#a855f7';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(s / 2, s / 2, s / 2 - 4, 0, Math.PI * 2);
        ctx.stroke();

        ctx.fillStyle = 'rgba(168, 85, 247, 0.6)';
        ctx.beginPath();
        ctx.arc(s / 2, s / 2, 4, 0, Math.PI * 2);
        ctx.fill();

        this._portalTexture = PIXI.Texture.from(canvas);
        return this._portalTexture;
    }

    _createHarvestSpotMarker(spot) {
        const s = this._tileSize;
        const texture = this._getHarvestSpotTexture(spot.toolType, spot.available);
        const sprite = new PIXI.Sprite(texture);
        sprite.position.set(spot.x * s, spot.y * s);
        sprite.zIndex = spot.y * s - 1;
        sprite.alpha = spot.available ? 1.0 : 0.3;
        this._entityContainer.addChild(sprite);

        const key = `harvest_${spot.id}`;
        this._entitySprites[key] = {
            container: sprite, x: spot.x, y: spot.y,
            type: 'harvest', animator: null, spotData: spot,
            meta: { name: spot.name, available: spot.available, remainingSeconds: spot.remainingSeconds },
        };
        this._addToSpatialHash(key, spot.x, spot.y);
    }

    _getHarvestSpotTexture(toolType, available) {
        const cacheKey = `harvest_${toolType}_${available}`;
        if (this._markerTextureCache.has(cacheKey)) {
            return this._markerTextureCache.get(cacheKey);
        }

        const s = this._tileSize;
        const canvas = document.createElement('canvas');
        canvas.width = s;
        canvas.height = s;
        const ctx = canvas.getContext('2d');
        const half = s / 2;

        // Couleur par type d'outil
        const colors = {
            pickaxe: { fill: 'rgba(139, 90, 43, 0.7)', stroke: '#b8860b' },     // brun/or (minerai)
            sickle: { fill: 'rgba(34, 139, 34, 0.7)', stroke: '#32cd32' },       // vert (plante)
            fishing_rod: { fill: 'rgba(30, 144, 255, 0.7)', stroke: '#00bfff' }, // bleu (eau)
            skinning_knife: { fill: 'rgba(178, 34, 34, 0.7)', stroke: '#ff4500' }, // rouge
        };
        const c = colors[toolType] || { fill: 'rgba(200, 200, 200, 0.7)', stroke: '#ccc' };

        // Diamant pour spots de récolte
        ctx.fillStyle = c.fill;
        ctx.strokeStyle = c.stroke;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(half, 3);
        ctx.lineTo(s - 3, half);
        ctx.lineTo(half, s - 3);
        ctx.lineTo(3, half);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // Point central lumineux
        if (available) {
            ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            ctx.beginPath();
            ctx.arc(half, half, 3, 0, Math.PI * 2);
            ctx.fill();
        }

        const texture = PIXI.Texture.from(canvas);
        this._markerTextureCache.set(cacheKey, texture);
        return texture;
    }

    _clearEntities() {
        for (const key of Object.keys(this._entitySprites)) {
            const entity = this._entitySprites[key];
            if (entity.animator) {
                entity.animator.destroy();
            }
            this._removeFromSpatialHash(key, entity.x, entity.y);
            this._releaseEntityContainer(entity.container);
        }
        this._entityContainer.removeChildren();
        this._playerContainer.removeChildren();
        this._entitySprites = {};
        this._animatedEntities = [];
        this._entitySpatialHash.clear();
        if (this._playerAnimator) {
            this._playerAnimator.destroy();
            this._playerAnimator = null;
        }
        this._playerMarker = null;
    }

    // --- Camera ---

    _cameraX = 0;
    _cameraY = 0;

    _updateCamera(instant = false) {
        const targetX = this._playerX * this._tileSize + this._tileSize / 2;
        const targetY = this._playerY * this._tileSize + this._tileSize / 2;

        if (instant) {
            this._cameraX = targetX;
            this._cameraY = targetY;
        } else {
            const dx = targetX - this._cameraX;
            const dy = targetY - this._cameraY;
            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
                this._cameraX = targetX;
                this._cameraY = targetY;
            } else {
                this._cameraX += dx * 0.15;
                this._cameraY += dy * 0.15;
            }
        }

        const viewW = this._currentWidth || this._viewportPx;
        const viewH = this._currentHeight || this._viewportPx;
        this._worldContainer.position.set(
            Math.round(viewW / 2 - this._cameraX),
            Math.round(viewH / 2 - this._cameraY),
        );
    }

    _tick(ticker) {
        const dt = ticker.deltaMS;

        // Performance monitoring
        this._frameCount++;
        if (dt > this._frameBudgetMs * 1.5) {
            this._slowFrameCount++;
        }

        this._updateCamera(false);

        // Camera shake
        if (this._shakeDuration > 0) {
            this._shakeElapsed += dt;
            if (this._shakeElapsed < this._shakeDuration) {
                const decay = 1 - this._shakeElapsed / this._shakeDuration;
                const offsetX = (Math.random() - 0.5) * 2 * this._shakeIntensity * decay;
                const offsetY = (Math.random() - 0.5) * 2 * this._shakeIntensity * decay;
                this._worldContainer.position.x += Math.round(offsetX);
                this._worldContainer.position.y += Math.round(offsetY);
            } else {
                this._shakeDuration = 0;
                this._shakeElapsed = 0;
            }
        }

        // Update player animation
        if (this._playerAnimator) {
            this._playerAnimator.update(dt);
        }

        // Update only animated entities (skip static markers)
        for (const animator of this._animatedEntities) {
            animator.update(dt);
        }

        // Update day/night cycle (check every 60s)
        if (this._dayNightEnabled && this._ambientOverlay) {
            this._updateDayNight(dt);
        }

        // Update minimap (throttled to every 500ms)
        if (this._minimapVisible && this._minimapContainer) {
            this._minimapTimer = (this._minimapTimer || 0) + dt;
            if (this._minimapTimer >= 500 || this._minimapDirty) {
                this._minimapTimer = 0;
                this._minimapDirty = false;
                this._updateMinimap();
            }
        }

        // Update particles
        this._updateParticles(dt);
    }

    // --- Camera Shake ---

    /**
     * Trigger a camera shake effect (e.g. on hit, portal, explosion).
     * @param {number} intensity - Max pixel offset
     * @param {number} durationMs - Total shake duration
     */
    shakeCamera(intensity = 4, durationMs = 300) {
        this._shakeIntensity = intensity;
        this._shakeDuration = durationMs;
        this._shakeElapsed = 0;
    }

    // --- Day/Night Cycle ---

    async _fetchGameTime() {
        try {
            const resp = await fetch('/api/game/time');
            if (resp.ok) {
                this._gameTimeData = await resp.json();
                this._timeOfDay = this._gameTimeData.timeOfDay;
                this._gameTimeFetchedAt = Date.now();
            }
        } catch (e) {
            console.warn('[map_pixi] Failed to fetch game time, using fallback');
        }
    }

    _computeTimeOfDay() {
        if (this._gameTimeData && this._gameTimeFetchedAt) {
            // Extrapolate from last fetch using timeRatio
            const elapsed = (Date.now() - this._gameTimeFetchedAt) / 1000; // real seconds since fetch
            const ratio = this._gameTimeData.timeRatio || 24;
            const inGameSecondsSinceFetch = elapsed * ratio;
            const baseInGameSeconds = this._gameTimeData.hour * 3600 + this._gameTimeData.minute * 60;
            const totalInGameSeconds = baseInGameSeconds + inGameSecondsSinceFetch;
            const hour = Math.floor(totalInGameSeconds / 3600) % 24;
            const minute = Math.floor(totalInGameSeconds / 60) % 60;

            // Update cached display values
            this._gameTimeData.hour = hour;
            this._gameTimeData.minute = minute;

            if (hour >= 8 && hour < 18) return 'day';
            if (hour >= 6 && hour < 8) return 'dawn';
            if (hour >= 18 && hour < 20) return 'dusk';
            return 'night';
        }
        // Fallback to local time
        const hour = new Date().getHours();
        if (hour >= 8 && hour < 18) return 'day';
        if (hour >= 6 && hour < 8) return 'dawn';
        if (hour >= 18 && hour < 20) return 'dusk';
        return 'night';
    }

    _initDayNight() {
        if (!this._dayNightEnabled || !this._app) return;

        this._ambientOverlay = new PIXI.Graphics();
        this._ambientOverlay.zIndex = 500;
        this._app.stage.addChild(this._ambientOverlay);
        this._dayNightCheckTimer = 0;
        this._applyTimeOfDay(this._timeOfDay);
    }

    _initTimeHud() {
        if (!this._app) return;
        this._timeHudText = new PIXI.Text({
            text: '',
            style: {
                fontFamily: 'monospace',
                fontSize: 11,
                fill: 0xd4d4d8,
                dropShadow: true,
                dropShadowColor: 0x000000,
                dropShadowDistance: 1,
                dropShadowAlpha: 0.8,
            },
        });
        this._timeHudText.zIndex = 600;
        this._timeHudText.position.set(6, 4);
        this._app.stage.addChild(this._timeHudText);
        this._updateTimeHud();
    }

    _updateTimeHud() {
        if (!this._timeHudText) return;
        const data = this._gameTimeData;
        if (!data) {
            this._timeHudText.text = '';
            return;
        }
        const h = String(data.hour).padStart(2, '0');
        const m = String(data.minute).padStart(2, '0');
        const seasonIcons = { spring: '\u2741', summer: '\u2600', autumn: '\u2618', winter: '\u2744' };
        const icon = seasonIcons[data.season] || '';
        this._timeHudText.text = `${h}:${m} ${icon}`;
    }

    _updateDayNight(dt) {
        this._dayNightCheckTimer = (this._dayNightCheckTimer || 0) + dt;

        // Re-fetch from server every 5 minutes to stay in sync
        this._gameTimeRefetchTimer = (this._gameTimeRefetchTimer || 0) + dt;
        if (this._gameTimeRefetchTimer >= 300000) {
            this._gameTimeRefetchTimer = 0;
            this._fetchGameTime().then(() => this._updateTimeHud());
        }

        if (this._dayNightCheckTimer < 10000) return; // Recompute every 10s for smoother HUD
        this._dayNightCheckTimer = 0;

        const newTime = this._computeTimeOfDay();
        this._updateTimeHud();
        if (newTime !== this._timeOfDay) {
            this._timeOfDay = newTime;
            this._applyTimeOfDay(newTime);
        }
    }

    _applyTimeOfDay(time) {
        if (!this._ambientOverlay) return;
        const viewW = this._currentWidth || this._viewportPx;
        const viewH = this._currentHeight || this._viewportPx;

        this._ambientOverlay.clear();
        this._ambientOverlay.rect(0, 0, viewW, viewH);

        const colors = {
            day:   { color: 0x000000, alpha: 0 },
            dawn:  { color: 0xff8c42, alpha: 0.12 },
            dusk:  { color: 0x1a0533, alpha: 0.2 },
            night: { color: 0x0a0a2e, alpha: 0.45 },
        };
        const c = colors[time] || colors.day;
        this._ambientOverlay.fill({ color: c.color, alpha: c.alpha });
    }

    // --- Particle System ---

    _updateParticles(dt) {
        for (let i = this._particles.length - 1; i >= 0; i--) {
            const p = this._particles[i];
            p.life -= dt;
            if (p.life <= 0) {
                if (p.sprite.parent) p.sprite.parent.removeChild(p.sprite);
                p.sprite.destroy();
                this._particles.splice(i, 1);
                continue;
            }
            p.sprite.position.x += p.vx * dt;
            p.sprite.position.y += p.vy * dt;
            p.sprite.alpha = Math.max(0, p.life / p.maxLife);
        }
    }

    /**
     * Spawn environmental particles (portal sparkle, etc.)
     * @param {number} worldX - World position X
     * @param {number} worldY - World position Y
     * @param {object} [opts] - Particle options
     */
    spawnParticles(worldX, worldY, { count = 6, color = 0xa855f7, life = 800, spread = 16 } = {}) {
        for (let i = 0; i < count; i++) {
            const g = new PIXI.Graphics();
            g.circle(0, 0, 1.5 + Math.random() * 1.5);
            g.fill({ color, alpha: 0.8 });
            g.position.set(
                worldX + (Math.random() - 0.5) * spread,
                worldY + (Math.random() - 0.5) * spread,
            );
            this._worldContainer.addChild(g);
            this._particles.push({
                sprite: g,
                vx: (Math.random() - 0.5) * 0.02,
                vy: -0.02 - Math.random() * 0.03,
                life,
                maxLife: life,
            });
        }
    }

    /**
     * Called when harvest controller reports a successful harvest.
     * Spawns green sparkle particles + gold XP stars at the player's position.
     */
    onHarvestSuccess() {
        const cx = this._playerX * this._tileSize + this._tileSize / 2;
        const cy = this._playerY * this._tileSize + this._tileSize / 2;
        // Green sparkles for harvest
        this.spawnParticles(cx, cy, { count: 10, color: 0x22c55e, life: 900, spread: 20 });
        // Gold stars for domain XP gain
        this.spawnParticles(cx, cy, { count: 5, color: 0xfbbf24, life: 1200, spread: 12 });
    }

    // --- Spatial Hash for Entity Lookup ---

    _spatialHashKey(x, y) {
        return `${x},${y}`;
    }

    _addToSpatialHash(key, x, y) {
        const hk = this._spatialHashKey(x, y);
        if (!this._entitySpatialHash.has(hk)) {
            this._entitySpatialHash.set(hk, new Set());
        }
        this._entitySpatialHash.get(hk).add(key);
    }

    _removeFromSpatialHash(key, x, y) {
        const hk = this._spatialHashKey(x, y);
        const set = this._entitySpatialHash.get(hk);
        if (set) {
            set.delete(key);
            if (set.size === 0) this._entitySpatialHash.delete(hk);
        }
    }

    _getEntitiesAt(x, y) {
        return this._entitySpatialHash.get(this._spatialHashKey(x, y)) || new Set();
    }

    // --- Entity Container Pooling ---

    _acquireEntityContainer() {
        if (this._entityPool.length > 0) {
            const container = this._entityPool.pop();
            container.visible = true;
            container.alpha = 1;
            container.removeChildren();
            return container;
        }
        return new PIXI.Container();
    }

    _releaseEntityContainer(container) {
        container.visible = false;
        if (container.parent) container.parent.removeChild(container);
        this._entityPool.push(container);
    }

    // --- Hover Tooltip (desktop) ---

    _initTooltip() {
        this._tooltipEl = document.createElement('div');
        Object.assign(this._tooltipEl.style, {
            position: 'absolute',
            pointerEvents: 'none',
            zIndex: '1000',
            display: 'none',
            padding: '4px 8px',
            background: 'rgba(15, 10, 30, 0.92)',
            border: '1px solid #b8860b',
            borderRadius: '4px',
            color: '#f0e6d2',
            fontFamily: '"Press Start 2P", "Courier New", monospace',
            fontSize: '9px',
            lineHeight: '1.4',
            whiteSpace: 'nowrap',
            boxShadow: '0 2px 8px rgba(0,0,0,0.6), inset 0 0 6px rgba(184,134,11,0.15)',
            maxWidth: '200px',
            transition: 'opacity 0.15s',
        });
        this.element.style.position = 'relative';
        this.element.appendChild(this._tooltipEl);
        this._tooltipTileKey = null;
    }

    _updateTooltip(e) {
        const rect = this._app.canvas.getBoundingClientRect();
        const scaleX = this._app.canvas.width / rect.width;
        const scaleY = this._app.canvas.height / rect.height;
        const screenX = (e.clientX - rect.left) * scaleX;
        const screenY = (e.clientY - rect.top) * scaleY;

        const worldX = screenX - this._worldContainer.position.x;
        const worldY = screenY - this._worldContainer.position.y;

        const tileX = Math.floor(worldX / this._tileSize);
        const tileY = Math.floor(worldY / this._tileSize);
        const tileKey = `${tileX},${tileY}`;

        // Skip if same tile
        if (tileKey === this._tooltipTileKey) {
            // Just reposition
            if (this._tooltipEl.style.display !== 'none') {
                this._positionTooltip(e, rect);
            }
            return;
        }
        this._tooltipTileKey = tileKey;

        // Check for entities on this tile
        const entities = this._getEntitiesAt(tileX, tileY);
        if (entities.size === 0) {
            // Check if it's the player's own tile
            if (tileX === Math.floor(this._playerX) && tileY === Math.floor(this._playerY)) {
                this._showTooltip(this._buildPlayerSelfTooltip(), e, rect);
                return;
            }
            this._hideTooltip();
            return;
        }

        // Build tooltip content from first entity found
        let html = '';
        for (const key of entities) {
            const entry = this._entitySprites[key];
            if (!entry) continue;
            const line = this._buildTooltipLine(entry);
            if (line) {
                if (html) html += '<br>';
                html += line;
            }
        }

        if (html) {
            this._showTooltip(html, e, rect);
        } else {
            this._hideTooltip();
        }
    }

    _buildTooltipLine(entry) {
        const meta = entry.meta || {};
        const type = entry.type;

        const icons = { player: '👤', mob: '💀', pnj: '💬', portal: '🌀', harvest: '✦' };
        const colors = { player: '#7cb3ff', mob: '#ff6b6b', pnj: '#c084fc', portal: '#a855f7', harvest: '#fbbf24' };
        const icon = icons[type] || '';
        const color = colors[type] || '#f0e6d2';

        if (type === 'player') {
            return `<span style="color:${color}">${icon} ${this._escHtml(meta.name || '???')}</span>`;
        }
        if (type === 'mob') {
            const lvl = meta.level ? ` <span style="color:#aaa;font-size:8px">Nv.${meta.level}</span>` : '';
            return `<span style="color:${color}">${icon} ${this._escHtml(meta.name || '???')}${lvl}</span>`;
        }
        if (type === 'pnj') {
            return `<span style="color:${color}">${icon} ${this._escHtml(meta.name || '???')}</span>`;
        }
        if (type === 'portal') {
            return `<span style="color:${color}">${icon} ${this._escHtml(meta.name || 'Portail')}</span>`;
        }
        if (type === 'harvest') {
            const spot = entry.spotData || meta;
            const name = this._escHtml(spot.name || '???');
            if (spot.available === false && spot.remainingSeconds > 0) {
                return `<span style="color:#888">${icon} ${name} <span style="font-size:8px">(${spot.remainingSeconds}s)</span></span>`;
            }
            return `<span style="color:${color}">${icon} ${name}</span>`;
        }
        return null;
    }

    _buildPlayerSelfTooltip() {
        return '<span style="color:#7cb3ff">👤 Vous</span>';
    }

    _showTooltip(html, e, rect) {
        this._tooltipEl.innerHTML = html;
        this._tooltipEl.style.display = 'block';
        this._tooltipEl.style.opacity = '1';
        this._positionTooltip(e, rect);
        this._app.canvas.style.cursor = 'default';
    }

    _positionTooltip(e, rect) {
        const offsetX = 12;
        const offsetY = -8;
        let left = e.clientX - rect.left + offsetX;
        let top = e.clientY - rect.top + offsetY;

        // Clamp to stay inside the container
        const tipW = this._tooltipEl.offsetWidth;
        const tipH = this._tooltipEl.offsetHeight;
        if (left + tipW > rect.width) left = left - tipW - offsetX * 2;
        if (top + tipH > rect.height) top = rect.height - tipH - 4;
        if (top < 0) top = 4;

        this._tooltipEl.style.left = `${left}px`;
        this._tooltipEl.style.top = `${top}px`;
    }

    _hideTooltip() {
        if (this._tooltipEl) {
            this._tooltipEl.style.display = 'none';
            this._tooltipTileKey = null;
        }
        if (this._app) {
            this._app.canvas.style.cursor = 'pointer';
        }
    }

    _escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // --- Haptic Feedback (mobile) ---

    _vibrate(pattern = 15) {
        if (navigator.vibrate) {
            navigator.vibrate(pattern);
        }
    }

    // --- Keyboard Movement ---

    _handleKeyDown(e) {
        // Toggle minimap with M key (works even during animation)
        if (e.key === 'm' || e.key === 'M') {
            this._toggleMinimap();
            return;
        }

        if (this._animating || this._dialogOpen) return;
        const dir = {
            ArrowUp: 'up', ArrowDown: 'down', ArrowLeft: 'left', ArrowRight: 'right',
            w: 'up', W: 'up', z: 'up', Z: 'up', // WASD + ZQSD (FR layout)
            s: 'down', S: 'down',
            a: 'left', A: 'left', q: 'left', Q: 'left',
            d: 'right', D: 'right',
        }[e.key];
        if (!dir) return;
        e.preventDefault();
        this._moveInDirection(dir);
    }

    // --- Joystick & Directional Movement ---

    onJoystickMove(event) {
        if (this._animating || this._dialogOpen) return;
        this._moveInDirection(event.detail.direction);
    }

    _moveInDirection(dir) {
        const px = Math.floor(this._playerX);
        const py = Math.floor(this._playerY);
        const offsets = { up: [0, -1], down: [0, 1], left: [-1, 0], right: [1, 0] };
        const [dx, dy] = offsets[dir];
        this._vibrate(10);
        this._requestMove(px + dx, py + dy);
    }

    // --- Click to Move ---

    _onPointerDown(e) {
        e.preventDefault();
        // Track swipe start for touch gestures
        this._swipeStart = { x: e.clientX, y: e.clientY, time: performance.now() };
    }

    _onPointerMove(e) {
        // Tooltip hover (desktop only, skip touch)
        if (e.pointerType !== 'mouse') return;
        this._updateTooltip(e);
    }

    _onPointerUp(e) {
        if (!this._swipeStart) return;

        const dx = e.clientX - this._swipeStart.x;
        const dy = e.clientY - this._swipeStart.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const elapsed = performance.now() - this._swipeStart.time;
        this._swipeStart = null;

        // Swipe detected: fast short gesture → directional move
        if (dist > this._swipeThreshold && elapsed < 400) {
            if (this._dialogOpen) return;
            let dir;
            if (Math.abs(dx) > Math.abs(dy)) {
                dir = dx > 0 ? 'right' : 'left';
            } else {
                dir = dy > 0 ? 'down' : 'up';
            }
            this._moveInDirection(dir);
            return;
        }

        // Otherwise treat as click-to-move (tap)
        this._handleTap(e);
    }

    _handleTap(e) {
        const rect = this._app.canvas.getBoundingClientRect();
        const scaleX = this._app.canvas.width / rect.width;
        const scaleY = this._app.canvas.height / rect.height;
        const screenX = (e.clientX - rect.left) * scaleX;
        const screenY = (e.clientY - rect.top) * scaleY;

        const worldX = screenX - this._worldContainer.position.x;
        const worldY = screenY - this._worldContainer.position.y;

        const tileX = Math.floor(worldX / this._tileSize);
        const tileY = Math.floor(worldY / this._tileSize);

        const cellKey = `${tileX},${tileY}`;
        const cell = this._cellCache.get(cellKey);
        const isWalkable = cell && cell.w;

        if (this._dialogOpen) return;

        if (this._animating) {
            this._cancelRequested = true;
            this._pendingNewTarget = isWalkable ? { x: tileX, y: tileY } : null;
            return;
        }

        if (!isWalkable) return;
        this._requestMove(tileX, tileY);
    }

    async _requestMove(targetX, targetY, fromX = null, fromY = null) {
        if (this._animating && !this._cancelRequested) return;

        const body = { targetX, targetY };
        if (fromX !== null && fromY !== null) {
            body.fromX = fromX;
            body.fromY = fromY;
        }

        try {
            const resp = await fetch('/api/map/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await resp.json();

            if (data.error) {
                if (fromX !== null && fromY !== null) {
                    this._playerX = fromX;
                    this._playerY = fromY;
                    if (this._playerMarker) {
                        this._playerMarker.position.set(fromX * this._tileSize, fromY * this._tileSize);
                    }
                    this._updateCamera(true);
                    await this._loadCells(fromX, fromY);
                    await this._loadEntities();
                }
                return;
            }

            // Store fight info if present
            if (data.fight) {
                this._pendingFight = data.fight;
            }

            // Store portal info if present
            if (data.portal) {
                this._pendingPortal = data.portal;
            }

            if (data.path && data.path.length > 0) {
                const finalStep = data.path[data.path.length - 1];
                this._preloadCells(finalStep.x, finalStep.y);
                await this._animateAlongPath(data.path);
            } else if (fromX !== null && fromY !== null) {
                await this._loadCells(fromX, fromY);
                await this._loadEntities();
            }
        } catch (err) {
            console.error('[map_pixi] Move error:', err);
            if (fromX !== null && fromY !== null) {
                this._playerX = fromX;
                this._playerY = fromY;
                if (this._playerMarker) {
                    this._playerMarker.position.set(fromX * this._tileSize, fromY * this._tileSize);
                }
                this._updateCamera(true);
                this._loadCells(fromX, fromY).then(() => this._loadEntities());
            }
        }
    }

    _preloadCells(x, y) {
        fetch(`/api/map/cells?x=${x}&y=${y}&radius=${this._viewRadius}&mapId=${this.mapIdValue}`)
            .then(r => r.json())
            .then(data => {
                for (const cell of data.cells) {
                    const key = `${cell.x},${cell.y}`;
                    if (!this._cellCache.has(key)) {
                        this._cellCache.set(key, cell);
                        this._renderCell(cell);
                    }
                }
            })
            .catch(() => {});
    }

    // --- Movement Animation ---

    async _animateAlongPath(path) {
        if (this._animating) return;
        this._animating = true;
        this._cancelRequested = false;
        this._pendingNewTarget = null;

        // Start walk animation
        if (this._playerAnimator) {
            this._playerAnimator.play();
        }

        for (let i = 0; i < path.length; i++) {
            if (this._cancelRequested) break;

            const from = { x: this._playerX, y: this._playerY };
            const to = { x: path[i].x, y: path[i].y };

            // Preload cells mid-path every 10 steps
            if (i > 0 && i % 10 === 0) {
                this._preloadCells(to.x, to.y);
            }

            // Set direction based on movement delta
            const dx = to.x - from.x;
            const dy = to.y - from.y;
            if (dx !== 0 || dy !== 0) {
                this._playerDirection = directionFromDelta(dx, dy);
                if (this._playerAnimator) {
                    this._playerAnimator.setDirection(this._playerDirection);
                }
            }

            await this._tweenTo(from, to, this.stepDelayValue);
            this._playerX = to.x;
            this._playerY = to.y;
        }

        // Stop walk animation
        if (this._playerAnimator) {
            this._playerAnimator.stop();
        }

        const hadCancel = this._cancelRequested;
        const pending = this._pendingNewTarget;
        this._animating = false;
        this._cancelRequested = false;
        this._pendingNewTarget = null;

        this._updateCamera(true);

        if (hadCancel) {
            const fromX = Math.floor(this._playerX);
            const fromY = Math.floor(this._playerY);
            if (pending) {
                await this._requestMove(pending.x, pending.y, fromX, fromY);
            } else {
                await this._requestMove(fromX, fromY, fromX, fromY);
            }
            return;
        }

        // Check for fight transition
        if (this._pendingFight) {
            this._pendingFight = null;
            window.location.href = '/game/fight';
            return;
        }

        // Check for portal transition
        if (this._pendingPortal) {
            await this._handlePortalTransition(this._pendingPortal);
            this._pendingPortal = null;
            return;
        }

        await this._loadCells(this._playerX, this._playerY);
        await this._refreshEntitiesIfNeeded();
        this._checkPnjInteraction();
        this._checkHarvestSpotInteraction();
    }

    async _handlePortalTransition(portal) {
        console.debug('[map_pixi] Portal triggered → map', portal.destinationMapId, 'at', portal.destinationCoordinates);

        // Sparkle effect + camera shake on portal
        this.spawnParticles(
            this._playerX * this._tileSize + this._tileSize / 2,
            this._playerY * this._tileSize + this._tileSize / 2,
            { count: 12, color: 0xa855f7, life: 600 }
        );
        this.shakeCamera(3, 200);
        this._vibrate([20, 50, 20]);

        // Fade out effect
        await this._fadeTransition(true);

        try {
            const resp = await fetch('/api/map/teleport', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mapId: portal.destinationMapId,
                    coordinates: portal.destinationCoordinates,
                }),
            });
            const data = await resp.json();

            if (data.success) {
                // Update local state
                this.mapIdValue = data.mapId;
                this._playerX = data.x;
                this._playerY = data.y;

                // Clear all cached data for old map
                this._clearEntities();
                for (const [key, sprites] of this._tileSprites) {
                    for (const s of sprites) {
                        this._releaseSprite(s);
                    }
                }
                this._tileSprites.clear();
                this._cellCache.clear();
                this._tileTextureCache.clear();

                // Reload config, cells, and entities for the new map
                await this._loadConfig();
                await this._loadCells(this._playerX, this._playerY);
                await this._loadEntities();
                this._updateCamera(true);
                this._lastEntityLoadX = this._playerX;
                this._lastEntityLoadY = this._playerY;
            } else {
                console.error('[map_pixi] Teleport failed:', data.error);
            }
        } catch (err) {
            console.error('[map_pixi] Teleport error:', err);
        }

        // Fade in
        await this._fadeTransition(false);
    }

    _fadeTransition(fadeOut) {
        return new Promise((resolve) => {
            // Create a full-screen overlay for fade effect
            if (!this._fadeOverlay) {
                this._fadeOverlay = new PIXI.Graphics();
                this._app.stage.addChild(this._fadeOverlay);
            }

            const viewW = this._currentWidth || this._viewportPx;
            const viewH = this._currentHeight || this._viewportPx;
            this._fadeOverlay.clear();
            this._fadeOverlay.rect(0, 0, viewW, viewH);
            this._fadeOverlay.fill({ color: 0x000000 });
            this._fadeOverlay.zIndex = 1000;

            const duration = 300; // ms
            const startTime = performance.now();
            const startAlpha = fadeOut ? 0 : 1;
            const endAlpha = fadeOut ? 1 : 0;

            const step = (now) => {
                const t = Math.min((now - startTime) / duration, 1);
                this._fadeOverlay.alpha = startAlpha + (endAlpha - startAlpha) * t;

                if (t < 1) {
                    requestAnimationFrame(step);
                } else {
                    if (!fadeOut) {
                        this._fadeOverlay.alpha = 0;
                    }
                    resolve();
                }
            };

            requestAnimationFrame(step);
        });
    }

    async _refreshEntitiesIfNeeded(force = false) {
        const dx = Math.abs(this._playerX - this._lastEntityLoadX);
        const dy = Math.abs(this._playerY - this._lastEntityLoadY);
        if (!force && dx < this._entityLoadThreshold && dy < this._entityLoadThreshold) {
            return;
        }
        this._lastEntityLoadX = this._playerX;
        this._lastEntityLoadY = this._playerY;
        await this._loadEntities();
    }

    _checkPnjInteraction() {
        const px = Math.floor(this._playerX);
        const py = Math.floor(this._playerY);

        // Use spatial hash for O(1) nearby entity lookup
        const neighbors = [
            [px, py], [px - 1, py], [px + 1, py], [px, py - 1], [px, py + 1],
        ];
        for (const [nx, ny] of neighbors) {
            const entities = this._getEntitiesAt(nx, ny);
            for (const key of entities) {
                if (!key.startsWith('pnj_')) continue;
                const pnjId = parseInt(key.replace('pnj_', ''));
                this._openPnjDialog(pnjId);
                return;
            }
        }
    }

    _checkHarvestSpotInteraction() {
        const px = Math.floor(this._playerX);
        const py = Math.floor(this._playerY);

        const neighbors = [
            [px, py], [px - 1, py], [px + 1, py], [px, py - 1], [px, py + 1],
        ];
        for (const [nx, ny] of neighbors) {
            const entities = this._getEntitiesAt(nx, ny);
            for (const key of entities) {
                if (!key.startsWith('harvest_')) continue;
                const entry = this._entitySprites[key];
                if (!entry || !entry.spotData) continue;
                const spot = entry.spotData;
                if (spot.available) {
                    this.dispatch('harvestSpot', { detail: spot });
                    return;
                }
            }
        }
    }

    async _openPnjDialog(pnjId) {
        try {
            const resp = await fetch(`/api/map/pnj/${pnjId}/dialog`);
            const data = await resp.json();
            if (data.sentences && data.sentences.length > 0) {
                this._dialogOpen = true;
                this.dispatch('pnjDialog', { detail: { sentences: data.sentences, pnjName: data.pnjName, portrait: data.portrait || null, classType: data.classType || null } });
            }
        } catch (err) {
            console.error('[map_pixi] Dialog fetch error:', err);
        }
    }

    onDialogClosed() {
        this._dialogOpen = false;
    }

    _tweenTo(from, to, durationMs) {
        return new Promise((resolve) => {
            const startTime = performance.now();
            const fromPx = { x: from.x * this._tileSize, y: from.y * this._tileSize };
            const toPx = { x: to.x * this._tileSize, y: to.y * this._tileSize };

            const step = (now) => {
                if (this._cancelRequested) {
                    const t = Math.min((now - startTime) / durationMs, 1);
                    const curX = from.x + (to.x - from.x) * t;
                    const curY = from.y + (to.y - from.y) * t;
                    this._playerX = curX;
                    this._playerY = curY;
                    if (this._playerMarker) {
                        this._playerMarker.position.set(
                            Math.round(curX * this._tileSize),
                            Math.round(curY * this._tileSize)
                        );
                    }
                    this._updateCamera(false);
                    resolve();
                    return;
                }

                const elapsed = now - startTime;
                const t = Math.min(elapsed / durationMs, 1);

                const cx = Math.round(fromPx.x + (toPx.x - fromPx.x) * t);
                const cy = Math.round(fromPx.y + (toPx.y - fromPx.y) * t);

                if (this._playerMarker) {
                    this._playerMarker.position.set(cx, cy);
                }

                this._playerX = from.x + (to.x - from.x) * t;
                this._playerY = from.y + (to.y - from.y) * t;

                if (t < 1) {
                    requestAnimationFrame(step);
                } else {
                    if (this._playerMarker) {
                        this._playerMarker.position.set(toPx.x, toPx.y);
                    }
                    resolve();
                }
            };

            requestAnimationFrame(step);
        });
    }

    // --- Mercure SSE ---

    _setupMercure() {
        if (!this.mercureUrlValue) return;

        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', 'map/move');
        url.searchParams.append('topic', 'map/respawn');

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this._handleMercureEvent(data);
            } catch (err) {
                console.error('[map_pixi] Mercure parse error:', err);
            }
        };
        this._eventSource.onerror = () => {
            console.warn('[map_pixi] Mercure connexion refusée. URL utilisée:', url.toString(), '- Utilisez HTTPS (https://game.amethyste.best) si vous êtes en production.');
        };
    }

    _handleMercureEvent(data) {
        const { topic, type } = data;

        if (topic === 'map/move') {
            if (type === 'player') {
                this._handlePlayerMoveEvent(data);
            } else if (type === 'mob') {
                this._handleEntityMoveEvent('mob', data);
            }
        } else if (topic === 'map/respawn') {
            this._loadEntities();
        }
    }

    _handlePlayerMoveEvent(data) {
        const playerId = data.object;
        const finalX = parseInt(data.x);
        const finalY = parseInt(data.y);

        if (playerId === this.playerIdValue) {
            return;
        }

        const key = `player_${playerId}`;
        const entity = this._entitySprites[key];

        if (entity && data.path && data.path.length > 0) {
            this._animateEntity(key, data.path, finalX, finalY);
        } else if (entity) {
            entity.container.position.set(finalX * this._tileSize, finalY * this._tileSize);
            entity.container.zIndex = finalY * this._tileSize;
            entity.x = finalX;
            entity.y = finalY;
        }
    }

    _handleEntityMoveEvent(type, data) {
        const entityId = data.object;
        const finalX = parseInt(data.x);
        const finalY = parseInt(data.y);
        const key = `${type}_${entityId}`;
        const entity = this._entitySprites[key];

        if (entity) {
            entity.container.position.set(finalX * this._tileSize, finalY * this._tileSize);
            entity.container.zIndex = finalY * this._tileSize;
            entity.x = finalX;
            entity.y = finalY;
        }
    }

    async _animateEntity(key, path, finalX, finalY) {
        const entity = this._entitySprites[key];
        if (!entity) return;

        if (entity.animator) {
            entity.animator.play();
        }

        let prevX = entity.x;
        let prevY = entity.y;

        for (const step of path) {
            const sx = parseInt(step.x);
            const sy = parseInt(step.y);

            // Set direction for animated entities
            if (entity.animator) {
                const dx = sx - prevX;
                const dy = sy - prevY;
                if (dx !== 0 || dy !== 0) {
                    entity.animator.setDirection(directionFromDelta(dx, dy));
                }
            }

            const targetPx = { x: sx * this._tileSize, y: sy * this._tileSize };
            entity.container.position.set(targetPx.x, targetPx.y);
            entity.container.zIndex = sy * this._tileSize;
            prevX = sx;
            prevY = sy;
            await this._wait(this.stepDelayValue);
        }

        if (entity.animator) {
            entity.animator.stop();
        }

        entity.container.position.set(finalX * this._tileSize, finalY * this._tileSize);
        entity.container.zIndex = finalY * this._tileSize;
        entity.x = finalX;
        entity.y = finalY;
    }

    // --- Minimap ---

    _initMinimap() {
        if (!this._app) return;

        const size = this._minimapSize;
        const pad = this._minimapPadding;

        this._minimapContainer = new PIXI.Container();
        this._minimapContainer.zIndex = 700;
        this._minimapContainer.visible = this._minimapVisible;

        // Position top-right
        const viewW = this._currentWidth || this._viewportPx;
        this._minimapContainer.position.set(viewW - size - pad, pad);

        // Background (dark semi-transparent)
        this._minimapBg = new PIXI.Graphics();
        this._minimapBg.roundRect(0, 0, size, size, 4);
        this._minimapBg.fill({ color: 0x111111, alpha: 0.7 });
        this._minimapBg.stroke({ color: 0x555555, width: 1, alpha: 0.8 });
        this._minimapContainer.addChild(this._minimapBg);

        // Entity dots layer
        this._minimapDots = new PIXI.Graphics();
        this._minimapContainer.addChild(this._minimapDots);

        // Viewport rectangle
        this._minimapViewport = new PIXI.Graphics();
        this._minimapContainer.addChild(this._minimapViewport);

        this._app.stage.addChild(this._minimapContainer);

        this._updateMinimap();
    }

    _toggleMinimap() {
        this._minimapVisible = !this._minimapVisible;
        if (this._minimapContainer) {
            this._minimapContainer.visible = this._minimapVisible;
        }
    }

    _updateMinimap() {
        if (!this._minimapContainer || !this._minimapDots) return;

        const size = this._minimapSize;
        const pad = this._minimapPadding;

        // Reposition on resize
        const viewW = this._currentWidth || this._viewportPx;
        this._minimapContainer.position.set(viewW - size - pad, pad);

        // Determine the world extent from cached cells + entities
        const radius = this._viewRadius;
        const px = Math.floor(this._playerX);
        const py = Math.floor(this._playerY);

        // Map area to display: centered on player, covering loaded area
        const mapMinX = px - radius;
        const mapMinY = py - radius;
        const mapRange = radius * 2 + 1;
        const scale = size / mapRange; // pixels per tile on minimap

        // Clear dots
        this._minimapDots.clear();

        // Draw terrain background dots for cached cells (subtle)
        for (const [, cell] of this._cellCache) {
            const cx = (cell.x - mapMinX) * scale;
            const cy = (cell.y - mapMinY) * scale;
            if (cx < 0 || cx >= size || cy < 0 || cy >= size) continue;
            this._minimapDots.rect(cx, cy, Math.max(scale, 1), Math.max(scale, 1));
        }
        this._minimapDots.fill({ color: 0x2d5a1b, alpha: 0.5 });

        // Entity colors
        const entityColors = {
            mob: 0xff4444,     // red
            player: 0xffffff,  // white
            pnj: 0x4488ff,    // blue
            portal: 0xaa55ff,  // violet
            harvest: 0xffbb33, // yellow
        };

        // Draw entity dots
        for (const [, entity] of Object.entries(this._entitySprites)) {
            const ex = (entity.x - mapMinX) * scale;
            const ey = (entity.y - mapMinY) * scale;
            if (ex < -2 || ex >= size + 2 || ey < -2 || ey >= size + 2) continue;

            const color = entityColors[entity.type] || 0x888888;
            const dotSize = entity.type === 'mob' ? 2 : 2.5;

            this._minimapDots.circle(ex + scale / 2, ey + scale / 2, dotSize);
            this._minimapDots.fill({ color, alpha: 0.9 });
        }

        // Draw current player (larger, bright white)
        const playerMX = (px - mapMinX) * scale + scale / 2;
        const playerMY = (py - mapMinY) * scale + scale / 2;
        this._minimapDots.circle(playerMX, playerMY, 3.5);
        this._minimapDots.fill({ color: 0xffffff, alpha: 1 });
        this._minimapDots.circle(playerMX, playerMY, 3.5);
        this._minimapDots.stroke({ color: 0x000000, width: 0.8, alpha: 0.6 });

        // Viewport rectangle (visible area)
        this._minimapViewport.clear();
        const vpTiles = this._viewportTiles;
        const vpW = vpTiles * scale;
        const vpH = vpTiles * scale;
        const vpX = (px - mapMinX - vpTiles / 2) * scale;
        const vpY = (py - mapMinY - vpTiles / 2) * scale;
        this._minimapViewport.rect(vpX, vpY, vpW, vpH);
        this._minimapViewport.stroke({ color: 0xffffff, width: 1, alpha: 0.5 });
    }

    _wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}
