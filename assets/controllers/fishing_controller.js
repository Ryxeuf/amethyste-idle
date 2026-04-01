import { Controller } from '@hotwired/stimulus';

/**
 * Fishing mini-game controller.
 * Displays a tension bar that oscillates. Player must click/tap
 * when the bar is in the success zone (30-70).
 *
 * Listens for 'harvest:startFishing' events.
 */
export default class extends Controller {
    static targets = ['panel', 'bar', 'indicator', 'message', 'spotName', 'catchBtn'];

    connect() {
        this._spot = null;
        this._tension = 50;
        this._direction = 1;
        this._speed = 1.5;
        this._animFrame = null;
        this._active = false;
    }

    disconnect() {
        this._stopAnimation();
    }

    open(event) {
        const spot = event.detail;
        if (!spot || this._active) return;

        this._spot = spot;
        this._tension = 50;
        this._direction = 1;
        this._active = true;

        // Vitesse basée sur la difficulté
        const items = spot.items || [];
        const difficulty = items[0]?.difficulty || 50;
        this._speed = 0.8 + (difficulty / 50);

        this.spotNameTarget.textContent = spot.name || 'Point de pêche';
        this.messageTarget.textContent = 'Cliquez quand la barre est dans la zone verte !';
        this.messageTarget.className = 'text-gray-300 text-xs mt-2';
        this.catchBtnTarget.disabled = false;
        this.catchBtnTarget.textContent = 'Ferrer !';

        this.panelTarget.classList.remove('hidden');
        this.panelTarget.style.opacity = '0';
        this.panelTarget.style.transition = 'opacity 0.2s ease-out';
        requestAnimationFrame(() => {
            this.panelTarget.style.opacity = '1';
        });

        this._startAnimation();
    }

    /** Close panel when the player starts moving */
    onPlayerMove() {
        if (this._active) {
            this.close();
        }
    }

    close() {
        this._stopAnimation();
        this._active = false;
        this.panelTarget.style.opacity = '0';
        setTimeout(() => {
            this.panelTarget.classList.add('hidden');
        }, 200);
    }

    async catch() {
        if (!this._active || !this._spot) return;

        this._stopAnimation();
        this.catchBtnTarget.disabled = true;
        this.catchBtnTarget.textContent = 'Ferrage...';

        const tension = Math.round(this._tension);

        try {
            const resp = await fetch('/api/gathering/fish', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tension }),
            });
            const result = await resp.json();

            if (result.success) {
                this.messageTarget.textContent = result.message || 'Prise !';
                this.messageTarget.className = 'text-green-400 text-sm mt-2 font-bold';
            } else {
                this.messageTarget.textContent = result.message || 'Raté !';
                this.messageTarget.className = 'text-red-400 text-sm mt-2';
            }

            // Auto-close after 2s
            setTimeout(() => this.close(), 2000);
        } catch (err) {
            console.error('[fishing] Error:', err);
            this.messageTarget.textContent = 'Erreur de connexion.';
            this.messageTarget.className = 'text-red-400 text-sm mt-2';
            this.catchBtnTarget.disabled = false;
            this.catchBtnTarget.textContent = 'Ferrer !';
        }
    }

    _startAnimation() {
        const step = () => {
            if (!this._active) return;

            this._tension += this._direction * this._speed;

            // Bounce at edges
            if (this._tension >= 100) {
                this._tension = 100;
                this._direction = -1;
            } else if (this._tension <= 0) {
                this._tension = 0;
                this._direction = 1;
            }

            // Random direction changes for challenge
            if (Math.random() < 0.02) {
                this._direction *= -1;
            }

            this._updateBar();
            this._animFrame = requestAnimationFrame(step);
        };
        this._animFrame = requestAnimationFrame(step);
    }

    _stopAnimation() {
        if (this._animFrame) {
            cancelAnimationFrame(this._animFrame);
            this._animFrame = null;
        }
    }

    _updateBar() {
        const pct = this._tension;
        this.indicatorTarget.style.left = `${pct}%`;

        // Couleur selon la zone
        if (pct >= 30 && pct <= 70) {
            this.indicatorTarget.className = 'absolute top-0 h-full w-1.5 bg-white rounded transition-none';
        } else {
            this.indicatorTarget.className = 'absolute top-0 h-full w-1.5 bg-red-400 rounded transition-none';
        }
    }
}
