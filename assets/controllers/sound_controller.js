import { Controller } from '@hotwired/stimulus';
import '../lib/SoundManager.js';

/**
 * Stimulus controller for global sound integration.
 *
 * Place on the <body> or game layout wrapper to hook sounds into:
 * - Toast notifications (success/error/warning/info)
 * - Navigation clicks
 * - Custom sound events via `window.dispatchEvent(new CustomEvent('sound:play', { detail: { sound: 'combat_hit' } }))`
 *
 * Usage:
 *   <body data-controller="sound">
 */
export default class extends Controller {
    connect() {
        this._onSoundPlay = this._handleSoundPlay.bind(this);
        this._onToastShow = this._handleToastSound.bind(this);
        this._onClick = this._handleClick.bind(this);

        window.addEventListener('sound:play', this._onSoundPlay);
        window.addEventListener('toast:show', this._onToastShow);
        this.element.addEventListener('click', this._onClick, true);

        // Patch Toast.show to play sounds on notification
        this._patchToast();
    }

    disconnect() {
        window.removeEventListener('sound:play', this._onSoundPlay);
        window.removeEventListener('toast:show', this._onToastShow);
        this.element.removeEventListener('click', this._onClick, true);
        this._unpatchToast();
    }

    _handleSoundPlay(event) {
        const sound = event.detail?.sound;
        if (sound && window.SoundManager) {
            window.SoundManager.play(sound);
        }
    }

    _handleToastSound(event) {
        const type = event.detail?.type;
        if (!window.SoundManager) return;
        switch (type) {
        case 'success':
            window.SoundManager.play('ui_success');
            break;
        case 'error':
            window.SoundManager.play('ui_error');
            break;
        case 'warning':
            window.SoundManager.play('ui_notification');
            break;
        default:
            window.SoundManager.play('ui_notification');
        }
    }

    _handleClick(event) {
        if (!window.SoundManager) return;
        const target = event.target.closest('button, a.nav-link, a.settings-tab, .fight-action, .spell-btn, .touch-target');
        if (target) {
            window.SoundManager.play('ui_click');
        }
    }

    _patchToast() {
        if (window.Toast && !window.Toast._soundPatched) {
            const originalShow = window.Toast.show;
            window.Toast.show = (type, message, duration) => {
                if (window.SoundManager) {
                    switch (type) {
                    case 'success':
                        window.SoundManager.play('ui_success');
                        break;
                    case 'error':
                        window.SoundManager.play('ui_error');
                        break;
                    default:
                        window.SoundManager.play('ui_notification');
                    }
                }
                return originalShow(type, message, duration);
            };
            window.Toast._soundPatched = true;
            this._originalToastShow = originalShow;
        }
    }

    _unpatchToast() {
        if (window.Toast && window.Toast._soundPatched && this._originalToastShow) {
            window.Toast.show = this._originalToastShow;
            delete window.Toast._soundPatched;
        }
    }
}
