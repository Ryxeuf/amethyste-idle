import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['playerMarker', 'updateButton'];
    static values = {
        mercureUrl: String,
        playerId: Number,
        cellSize: { type: Number, default: 32 },
        centerOffset: { type: Number, default: 320 },
        stepDelay: { type: Number, default: 200 },
    };

    connect() {
        if (!this.mercureUrlValue) return;

        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', 'map/move');
        url.searchParams.append('topic', 'map/respawn');

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this._handleEvent(data);
        };
        this._animating = false;
    }

    disconnect() {
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
    }

    _handleEvent(data) {
        const { topic, type } = data;

        if (topic === 'map/move' && type === 'player') {
            this._handlePlayerMove(data);
        }
    }

    _handlePlayerMove(data) {
        const { object: playerId, path, x, y } = data;

        if (playerId !== this.playerIdValue) return;

        if (path && path.length > 0) {
            this._animatePath(path, parseInt(x), parseInt(y));
        } else {
            this._teleportAndRefresh(parseInt(x), parseInt(y));
        }
    }

    async _animatePath(path, finalX, finalY) {
        if (this._animating) return;
        this._animating = true;

        const marker = this.hasPlayerMarkerTarget ? this.playerMarkerTarget : null;

        for (let i = 0; i < path.length; i++) {
            if (marker) {
                const offsetX = (path[i].x - (finalX - 10)) * this.cellSizeValue;
                const offsetY = (path[i].y - (finalY - 10)) * this.cellSizeValue;
                marker.style.left = `${offsetX}px`;
                marker.style.top = `${offsetY}px`;
            }
            if (i < path.length - 1) {
                await this._wait(this.stepDelayValue);
            }
        }

        this._animating = false;
        this._teleportAndRefresh(finalX, finalY);
    }

    _teleportAndRefresh(x, y) {
        if (this.hasUpdateButtonTarget) {
            const btn = this.updateButtonTarget;
            btn.dataset.liveXParam = x;
            btn.dataset.liveYParam = y;
            btn.click();
        }
    }

    _wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
