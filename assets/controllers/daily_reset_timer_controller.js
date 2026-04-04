import { Controller } from '@hotwired/stimulus';

/**
 * Countdown timer until daily quest reset (midnight).
 *
 * Usage:
 *   <div data-controller="daily-reset-timer"
 *        data-daily-reset-timer-target="display">
 *   </div>
 */
export default class extends Controller {
    static targets = ['display'];

    connect() {
        this._update();
        this._interval = setInterval(() => this._update(), 1000);
    }

    disconnect() {
        if (this._interval) {
            clearInterval(this._interval);
        }
    }

    _update() {
        const now = new Date();
        const midnight = new Date(now);
        midnight.setDate(midnight.getDate() + 1);
        midnight.setHours(0, 0, 0, 0);

        const diffMs = midnight - now;
        const totalSeconds = Math.floor(diffMs / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        const pad = (n) => String(n).padStart(2, '0');

        this.displayTarget.textContent = `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
    }
}
