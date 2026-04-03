import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['display'];

    connect() {
        this._update();
        this._timer = setTimeout(() => this._tick(), 1000);
    }

    disconnect() {
        if (this._timer) {
            clearTimeout(this._timer);
            this._timer = null;
        }
    }

    _tick() {
        this._update();
        this._timer = setTimeout(() => this._tick(), 1000);
    }

    _update() {
        const now = new Date();
        const midnight = new Date(now);
        midnight.setHours(24, 0, 0, 0);

        const diff = Math.max(0, Math.floor((midnight - now) / 1000));
        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;

        const pad = (n) => String(n).padStart(2, '0');
        this.displayTarget.textContent = `${pad(hours)}h${pad(minutes)}m${pad(seconds)}s`;
    }
}
