import { Controller } from '@hotwired/stimulus';

/**
 * Decompte du travail d'atelier en cours (ECO-20).
 *
 * Le serveur reste la seule autorite sur la fin du travail : ce controleur
 * n'active le bouton que lorsque le decompte atteint zero, mais c'est
 * `CraftingManager::collectCraft()` qui refuse une recuperation anticipee.
 */
export default class extends Controller {
    static targets = ['countdown', 'button'];
    static values = { remaining: Number };

    connect() {
        this._remaining = this.remainingValue;
        this._tick();
        this._timer = setInterval(() => this._tick(), 1000);
    }

    disconnect() {
        clearInterval(this._timer);
    }

    _tick() {
        if (this.hasCountdownTarget) {
            this.countdownTarget.textContent = Math.max(0, this._remaining);
        }

        if (this._remaining <= 0) {
            clearInterval(this._timer);
            if (this.hasButtonTarget) {
                this.buttonTarget.disabled = false;
            }
            return;
        }

        this._remaining -= 1;
    }
}
