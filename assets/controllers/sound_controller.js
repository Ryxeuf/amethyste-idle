import { Controller } from '@hotwired/stimulus';
import soundManager from '../lib/SoundManager.js';

/**
 * Stimulus controller: unlocks Web Audio on first user gesture,
 * plays UI sounds on button clicks, and listens for game events.
 *
 * Usage: <body data-controller="sound">
 */
export default class extends Controller {
    connect() {
        this._unlockHandler = () => this._unlock();

        // Unlock AudioContext on first user interaction
        document.addEventListener('click', this._unlockHandler, { once: true });
        document.addEventListener('keydown', this._unlockHandler, { once: true });
        document.addEventListener('touchstart', this._unlockHandler, { once: true });

        // Intercept clicks on interactive elements for UI sound
        this._clickHandler = (e) => this._onGlobalClick(e);
        document.addEventListener('click', this._clickHandler);
    }

    disconnect() {
        document.removeEventListener('click', this._unlockHandler);
        document.removeEventListener('keydown', this._unlockHandler);
        document.removeEventListener('touchstart', this._unlockHandler);
        document.removeEventListener('click', this._clickHandler);
    }

    _unlock() {
        soundManager.unlock();
    }

    _onGlobalClick(e) {
        const target = e.target.closest('button, a, [role="button"], .touch-target');
        if (!target) return;

        // Skip if it's a fight action (handled by fight sound logic)
        if (target.classList.contains('fight-action')) return;

        soundManager.play('click');
    }
}
