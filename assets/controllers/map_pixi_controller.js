import { Controller } from '@hotwired/stimulus';
import * as PIXI from 'pixi.js';

export default class extends Controller {
    static values = {
        mercureUrl: String,
        playerId: Number,
        mapId: Number,
        playerX: Number,
        playerY: Number,
        stepDelay: { type: Number, default: 500 },
    };

    async connect() {
        this._tileSize = 32;
        this._viewRadius = 15;
        this._viewportTiles = 21;
        this._viewportPx = this._viewportTiles * this._tileSize;

        this._playerX = this.playerXValue;
        this._playerY = this.playerYValue;
        this._animating = false;

        this._cellCache = new Map();
        this._tileSprites = new Map();
        this._tilesets = [];
        this._tilesetTextures = {};
        this._entitySprites = {};

        await this._initPixi();
        await this._loadConfig();
        await this._loadCells(this._playerX, this._playerY);
        await this._loadEntities();
        this._setupMercure();
        this._updateCamera(true);
    }

    disconnect() {
        if (this._onKeyDown) {
            document.removeEventListener('keydown', this._onKeyDown);
        }
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
        if (this._app) {
            this._app.destroy(true);
            this._app = null;
        }
    }

    // --- Initialization ---

    async _initPixi() {
        this._app = new PIXI.Application();
        await this._app.init({
            width: this._viewportPx,
            height: this._viewportPx,
            backgroundColor: 0x111111,
            antialias: false,
            resolution: 1,
        });

        this.element.innerHTML = '';
        this.element.appendChild(this._app.canvas);
        this._app.canvas.style.cursor = 'pointer';

        this._worldContainer = new PIXI.Container();
        this._worldContainer.sortableChildren = true;

        this._tileContainer = new PIXI.Container();
        this._tileContainer.zIndex = 0;

        this._entityContainer = new PIXI.Container();
        this._entityContainer.zIndex = 10;

        this._playerContainer = new PIXI.Container();
        this._playerContainer.zIndex = 20;

        this._worldContainer.addChild(this._tileContainer);
        this._worldContainer.addChild(this._entityContainer);
        this._worldContainer.addChild(this._playerContainer);
        this._app.stage.addChild(this._worldContainer);

        this._app.canvas.addEventListener('pointerdown', (e) => this._onPointerDown(e));

        this._onKeyDown = (e) => this._handleKeyDown(e);
        document.addEventListener('keydown', this._onKeyDown);

        this._app.ticker.add(() => this._tick());
    }

    async _loadConfig() {
        const resp = await fetch('/api/map/config');
        const config = await resp.json();
        this._tilesets = config.tilesets || [];
        this._tileSize = config.tileSize || 32;
        this._viewRadius = config.viewRadius || 15;

        this._tilesets.sort((a, b) => a.firstGid - b.firstGid);

        const loadPromises = this._tilesets.map(async (ts) => {
            const texture = await PIXI.Assets.load(ts.image);
            this._tilesetTextures[ts.name] = texture;
        });

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
            sprite.position.set(cell.x * this._tileSize, cell.y * this._tileSize);
            this._tileContainer.addChild(sprite);
            sprites.push(sprite);
        }

        this._tileSprites.set(key, sprites);
    }

    _pruneDistantCells(centerX, centerY) {
        const maxDist = this._viewRadius * 2;
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

    // --- Entity Rendering ---

    async _loadEntities() {
        const resp = await fetch('/api/map/entities');
        const data = await resp.json();

        this._clearEntities();

        for (const p of data.players) {
            if (p.self) continue;
            this._createEntitySprite('player', p.id, p.x, p.y, 0x3333ff, p.name);
        }

        for (const mob of data.mobs) {
            this._createEntitySprite('mob', mob.id, mob.x, mob.y, 0x8B0000, 'M');
        }

        for (const pnj of data.pnjs) {
            this._createEntitySprite('pnj', pnj.id, pnj.x, pnj.y, 0x4B0082, pnj.name?.[0] ?? 'N');
        }

        this._createPlayerMarker();
    }

    _createEntitySprite(type, id, x, y, color, label) {
        const colorMap = {
            'mob': '#8B0000',
            'player': '#3333ff',
            'pnj': '#4B0082',
        };
        const hex = typeof color === 'number'
            ? '#' + color.toString(16).padStart(6, '0')
            : (colorMap[type] || '#888888');
        const shape = type === 'mob' ? 'rect' : 'circle';
        const texture = this._createMarkerTexture(hex, '#000000', shape);
        const sprite = new PIXI.Sprite(texture);

        const container = new PIXI.Container();
        container.addChild(sprite);
        container.position.set(x * this._tileSize, y * this._tileSize);
        this._entityContainer.addChild(container);

        const key = `${type}_${id}`;
        this._entitySprites[key] = { container, x, y, type };
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
        const texture = this._createMarkerTexture('#ff0000', '#ffffff', 'circle');
        const sprite = new PIXI.Sprite(texture);

        this._playerMarker = new PIXI.Container();
        this._playerMarker.addChild(sprite);

        const px = Math.floor(this._playerX) * this._tileSize;
        const py = Math.floor(this._playerY) * this._tileSize;
        this._playerMarker.position.set(px, py);

        this._playerContainer.addChild(this._playerMarker);
    }

    _clearEntities() {
        this._entityContainer.removeChildren();
        this._playerContainer.removeChildren();
        this._entitySprites = {};
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
            this._cameraX += (targetX - this._cameraX) * 0.15;
            this._cameraY += (targetY - this._cameraY) * 0.15;
        }

        this._worldContainer.position.set(
            this._viewportPx / 2 - this._cameraX,
            this._viewportPx / 2 - this._cameraY,
        );
    }

    _tick() {
        if (!this._animating) return;
        this._updateCamera(false);
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
        if (this._animating) return;

        const rect = this._app.canvas.getBoundingClientRect();
        const screenX = e.clientX - rect.left;
        const screenY = e.clientY - rect.top;

        const worldX = screenX - this._worldContainer.position.x;
        const worldY = screenY - this._worldContainer.position.y;

        const tileX = Math.floor(worldX / this._tileSize);
        const tileY = Math.floor(worldY / this._tileSize);

        const cellKey = `${tileX},${tileY}`;
        const cell = this._cellCache.get(cellKey);
        if (!cell || !cell.w) return;

        this._requestMove(tileX, tileY);
    }

    async _requestMove(targetX, targetY) {
        if (this._animating) return;

        try {
            const resp = await fetch('/api/map/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targetX, targetY }),
            });
            const data = await resp.json();

            if (data.path && data.path.length > 0) {
                await this._animateAlongPath(data.path);
            }
        } catch (err) {
            console.error('[map_pixi] Move error:', err);
        }
    }

    // --- Movement Animation ---

    async _animateAlongPath(path) {
        if (this._animating) return;
        this._animating = true;

        for (let i = 0; i < path.length; i++) {
            const from = { x: this._playerX, y: this._playerY };
            const to = { x: path[i].x, y: path[i].y };
            await this._tweenTo(from, to, this.stepDelayValue);
            this._playerX = to.x;
            this._playerY = to.y;
        }

        this._animating = false;
        this._updateCamera(true);

        await this._loadCells(this._playerX, this._playerY);
        await this._loadEntities();
    }

    _tweenTo(from, to, durationMs) {
        return new Promise((resolve) => {
            const startTime = performance.now();
            const fromPx = { x: from.x * this._tileSize, y: from.y * this._tileSize };
            const toPx = { x: to.x * this._tileSize, y: to.y * this._tileSize };

            const step = (now) => {
                const elapsed = now - startTime;
                const t = Math.min(elapsed / durationMs, 1);
                const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

                const cx = fromPx.x + (toPx.x - fromPx.x) * eased;
                const cy = fromPx.y + (toPx.y - fromPx.y) * eased;

                if (this._playerMarker) {
                    this._playerMarker.position.set(cx, cy);
                }

                this._playerX = from.x + (to.x - from.x) * t;
                this._playerY = from.y + (to.y - from.y) * t;
                this._updateCamera(false);

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
            entity.x = finalX;
            entity.y = finalY;
        }
    }

    async _animateEntity(key, path, finalX, finalY) {
        const entity = this._entitySprites[key];
        if (!entity) return;

        for (const step of path) {
            const targetPx = { x: parseInt(step.x) * this._tileSize, y: parseInt(step.y) * this._tileSize };
            entity.container.position.set(targetPx.x, targetPx.y);
            await this._wait(this.stepDelayValue);
        }

        entity.container.position.set(finalX * this._tileSize, finalY * this._tileSize);
        entity.x = finalX;
        entity.y = finalY;
    }

    _wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}
