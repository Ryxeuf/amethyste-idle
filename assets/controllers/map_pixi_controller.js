import { Controller } from '@hotwired/stimulus';
import * as PIXI from 'pixi.js';
import SpriteAnimator, { directionFromDelta } from '../lib/SpriteAnimator.js';

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

        this._playerAnimator = null;

        await this._initPixi();
        await this._loadConfig();
        await this._loadCells(this._playerX, this._playerY);
        await this._loadEntities();
        this._setupMercure();
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
        if (this._app) {
            this._app.destroy(true);
            this._app = null;
        }
    }

    // --- Initialization ---

    async _initPixi() {
        // Responsive: use container size, clamped to max 672px
        const containerRect = this.element.getBoundingClientRect();
        const size = Math.min(Math.floor(containerRect.width), this._viewportPx);
        this._currentSize = size;

        this._app = new PIXI.Application();
        await this._app.init({
            width: size,
            height: size,
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

        this._entityContainer = new PIXI.Container();
        this._entityContainer.zIndex = 10;
        this._entityContainer.sortableChildren = true;

        this._playerContainer = new PIXI.Container();
        this._playerContainer.zIndex = 20;

        this._worldContainer.addChild(this._tileContainer);
        this._worldContainer.addChild(this._entityContainer);
        this._worldContainer.addChild(this._playerContainer);
        this._app.stage.addChild(this._worldContainer);

        this._app.canvas.addEventListener('pointerdown', (e) => this._onPointerDown(e));

        this._onKeyDown = (e) => this._handleKeyDown(e);
        document.addEventListener('keydown', this._onKeyDown);

        this._app.ticker.add((ticker) => this._tick(ticker));

        // Resize observer for responsive canvas
        this._resizeObserver = new ResizeObserver(() => this._onResize());
        this._resizeObserver.observe(this.element);
    }

    _onResize() {
        if (!this._app) return;
        const containerRect = this.element.getBoundingClientRect();
        const size = Math.min(Math.floor(containerRect.width), this._viewportPx);
        if (size === this._currentSize || size < 64) return;

        this._currentSize = size;
        this._app.renderer.resize(size, size);
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

            const localId = gid - ts.firstGid;
            const col = localId % ts.columns;
            const row = Math.floor(localId / ts.columns);

            const frame = new PIXI.Rectangle(
                col * ts.tileWidth,
                row * ts.tileHeight,
                ts.tileWidth,
                ts.tileHeight,
            );

            let texture;
            try {
                texture = new PIXI.Texture({ source: baseTexture.source, frame });
            } catch {
                continue;
            }

            const sprite = new PIXI.Sprite(texture);
            sprite.roundPixels = true;
            sprite.position.set(px, py);
            this._tileContainer.addChild(sprite);
            sprites.push(sprite);
        }

        this._tileSprites.set(key, sprites);
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
                    this._tileContainer.removeChild(s);
                    s.destroy();
                }
                this._tileSprites.delete(key);
            }
        }
    }

    _findTileset(gid) {
        let selected = null;
        for (const ts of this._tilesets) {
            if (ts.firstGid <= gid) {
                selected = ts;
            } else {
                break;
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
        const resp = await fetch('/api/map/entities');
        const data = await resp.json();

        this._clearEntities();

        for (const p of data.players) {
            if (p.self) continue;
            this._createEntitySprite('player', p.id, p.x, p.y, p.spriteKey, p.name);
        }

        for (const mob of data.mobs) {
            this._createEntitySprite('mob', mob.id, mob.x, mob.y, mob.spriteKey, 'M');
        }

        for (const pnj of data.pnjs) {
            this._createEntitySprite('pnj', pnj.id, pnj.x, pnj.y, pnj.spriteKey, pnj.name?.[0] ?? 'N');
        }

        this._createPlayerMarker();
    }

    _createEntitySprite(type, id, x, y, spriteKey, label) {
        const key = `${type}_${id}`;
        const animator = this._createAnimator(spriteKey);

        if (animator) {
            const container = new PIXI.Container();
            const sprite = animator.sprite;
            // Center sprite horizontally on tile, align bottom to tile bottom
            sprite.anchor.set(0.5, 1);
            sprite.position.set(this._tileSize / 2, this._tileSize);
            container.addChild(sprite);
            container.position.set(x * this._tileSize, y * this._tileSize);
            container.zIndex = y * this._tileSize;
            this._entityContainer.addChild(container);
            this._entitySprites[key] = { container, x, y, type, animator };
        } else {
            // Fallback: colored marker
            const colorMap = { 'mob': '#8B0000', 'player': '#3333ff', 'pnj': '#4B0082' };
            const hex = colorMap[type] || '#888888';
            const shape = type === 'mob' ? 'rect' : 'circle';
            const texture = this._createMarkerTexture(hex, '#000000', shape);
            const markerSprite = new PIXI.Sprite(texture);

            const container = new PIXI.Container();
            container.addChild(markerSprite);
            container.position.set(x * this._tileSize, y * this._tileSize);
            container.zIndex = y * this._tileSize;
            this._entityContainer.addChild(container);
            this._entitySprites[key] = { container, x, y, type, animator: null };
        }
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

    _createPlayerMarker() {
        const animator = this._createAnimator('player_default');

        this._playerMarker = new PIXI.Container();

        if (animator) {
            this._playerAnimator = animator;
            const sprite = animator.sprite;
            sprite.anchor.set(0.5, 1);
            sprite.position.set(this._tileSize / 2, this._tileSize);
            this._playerMarker.addChild(sprite);
        } else {
            // Fallback: red circle
            const texture = this._createMarkerTexture('#ff0000', '#ffffff', 'circle');
            const sprite = new PIXI.Sprite(texture);
            this._playerMarker.addChild(sprite);
        }

        const px = Math.floor(this._playerX) * this._tileSize;
        const py = Math.floor(this._playerY) * this._tileSize;
        this._playerMarker.position.set(px, py);

        this._playerContainer.addChild(this._playerMarker);
    }

    _clearEntities() {
        for (const key of Object.keys(this._entitySprites)) {
            const entity = this._entitySprites[key];
            if (entity.animator) {
                entity.animator.destroy();
            }
        }
        this._entityContainer.removeChildren();
        this._playerContainer.removeChildren();
        this._entitySprites = {};
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

        const viewSize = this._currentSize || this._viewportPx;
        this._worldContainer.position.set(
            Math.round(viewSize / 2 - this._cameraX),
            Math.round(viewSize / 2 - this._cameraY),
        );
    }

    _tick(ticker) {
        const dt = ticker.deltaMS;

        this._updateCamera(false);

        // Update player animation
        if (this._playerAnimator) {
            this._playerAnimator.update(dt);
        }

        // Update entity animations
        for (const entity of Object.values(this._entitySprites)) {
            if (entity.animator) {
                entity.animator.update(dt);
            }
        }
    }

    // --- Keyboard Movement ---

    _handleKeyDown(e) {
        if (this._animating) return;
        const px = Math.floor(this._playerX);
        const py = Math.floor(this._playerY);
        let targetX = px, targetY = py;

        switch (e.key) {
            case 'ArrowUp':    targetY = py - 1; break;
            case 'ArrowDown':  targetY = py + 1; break;
            case 'ArrowLeft':  targetX = px - 1; break;
            case 'ArrowRight': targetX = px + 1; break;
            default: return;
        }
        e.preventDefault();
        this._requestMove(targetX, targetY);
    }

    // --- Click to Move ---

    _onPointerDown(e) {
        e.preventDefault();
        const rect = this._app.canvas.getBoundingClientRect();
        // Account for CSS scaling: map screen coords to canvas coords
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

        if (this._animating) {
            this._cancelRequested = true;
            this._pendingNewTarget = isWalkable ? { x: tileX, y: tileY } : null;
            if (typeof console !== 'undefined' && console.debug) {
                console.debug('[map_pixi] Clic pendant déplacement → annulation', isWalkable ? `nouvelle cible (${tileX},${tileY})` : 'arrêt uniquement');
            }
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

            // Set direction based on movement delta
            if (this._playerAnimator) {
                const dx = to.x - from.x;
                const dy = to.y - from.y;
                if (dx !== 0 || dy !== 0) {
                    this._playerAnimator.setDirection(directionFromDelta(dx, dy));
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

        await this._loadCells(this._playerX, this._playerY);
        await this._loadEntities();
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

    _wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}
