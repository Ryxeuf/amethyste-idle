import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['playerMarker', 'updateButton'];
    static values = {
        mercureUrl: String,
        playerId: Number,
        cellSize: { type: Number, default: 32 },
        centerOffset: { type: Number, default: 320 },
        stepDelay: { type: Number, default: 500 },
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
        const finalX = parseInt(x);
        const finalY = parseInt(y);

        if (playerId === this.playerIdValue) {
            if (path && path.length > 0) {
                requestAnimationFrame(() => this._animatePath(path, finalX, finalY));
            } else {
                this._teleportAndRefresh(finalX, finalY);
            }
        } else {
            this._handleOtherPlayerMove(playerId, path, finalX, finalY);
        }
    }

    async _animatePath(path, finalX, finalY) {
        if (this._animating) return;
        this._animating = true;

        const marker = this.hasPlayerMarkerTarget ? this.playerMarkerTarget : null;
        if (!marker) {
            this._animating = false;
            this._teleportAndRefresh(finalX, finalY);
            return;
        }

        marker.setAttribute('data-live-ignore', '');

        const currentX = parseInt(this.element.querySelector('input[name="x"]')?.value ?? '0');
        const currentY = parseInt(this.element.querySelector('input[name="y"]')?.value ?? '0');
        const viewStartX = currentX - 10;
        const viewStartY = currentY - 10;

        for (let i = 0; i < path.length; i++) {
            const stepX = parseInt(path[i].x);
            const stepY = parseInt(path[i].y);
            const left = (stepX - viewStartX) * this.cellSizeValue;
            const top = (stepY - viewStartY) * this.cellSizeValue;
            marker.style.left = `${left}px`;
            marker.style.top = `${top}px`;
            await this._wait(this.stepDelayValue);
        }

        this._animating = false;
        marker.removeAttribute('data-live-ignore');

        this._teleportAndRefresh(finalX, finalY);
    }

    _handleOtherPlayerMove(playerId, path, finalX, finalY) {
        const otherMarker = this.element.querySelector(`.other-player[data-player-id="${playerId}"]`);
        if (!otherMarker) return;

        const currentX = parseInt(this.element.querySelector('input[name="x"]')?.value ?? '0');
        const currentY = parseInt(this.element.querySelector('input[name="y"]')?.value ?? '0');
        const viewStartX = currentX - 10;
        const viewStartY = currentY - 10;

        const offsetX = (finalX - viewStartX) * this.cellSizeValue;
        const offsetY = (finalY - viewStartY) * this.cellSizeValue;
        otherMarker.style.left = `${offsetX}px`;
        otherMarker.style.top = `${offsetY}px`;
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
