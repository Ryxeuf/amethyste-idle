import { Controller } from '@hotwired/stimulus';

/**
 * Toast notification controller.
 * Displays stacked toast notifications in the bottom-right corner.
 * Auto-dismisses after a configurable delay (default 4s).
 *
 * Usage from Twig (server-side flash messages):
 *   <div data-toast-target="flash" data-toast-type="success" data-toast-message="Item obtenu !"></div>
 *
 * Usage from JS (global API):
 *   window.Toast.show('success', 'Quete completee !')
 *   window.Toast.show('info', '+50 XP Combat')
 *   window.Toast.show('warning', 'Votre outil est use')
 *   window.Toast.show('error', 'Inventaire plein')
 *
 * Stimulus dispatch from another controller:
 *   this.dispatch('toast', { detail: { type: 'success', message: 'Bravo !' } })
 */
export default class extends Controller {
    static targets = ['container', 'flash'];
    static values = {
        duration: { type: Number, default: 4000 },
        maxVisible: { type: Number, default: 5 },
    };

    connect() {
        // Expose global API
        window.Toast = {
            show: (type, message, duration) => this._addToast(type, message, duration),
        };

        // Process server-side flash messages
        this.flashTargets.forEach((el) => {
            const type = el.dataset.toastType || 'info';
            const message = el.dataset.toastMessage || '';
            if (message) {
                // Slight delay so the page renders first
                setTimeout(() => this._addToast(type, message), 100);
            }
            el.remove();
        });

        // Listen for Stimulus events from other controllers
        this._onToastEvent = (event) => {
            const { type, message, duration } = event.detail || {};
            if (message) this._addToast(type || 'info', message, duration);
        };
        window.addEventListener('toast:show', this._onToastEvent);
    }

    disconnect() {
        window.removeEventListener('toast:show', this._onToastEvent);
        delete window.Toast;
    }

    _addToast(type, message, duration) {
        // Play sound for toast notifications
        if (window.Sound) {
            if (type === 'error') window.Sound.play('error');
            else window.Sound.play('notification');
        }

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="toast-icon">${this._icon(type)}</div>
            <div class="toast-message">${this._escapeHtml(message)}</div>
            <button class="toast-close" aria-label="Fermer">&times;</button>
        `;

        // Close button handler
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this._removeToast(toast);
        });

        this.containerTarget.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('toast-enter');
        });

        // Enforce max visible
        const toasts = this.containerTarget.querySelectorAll('.toast-item');
        if (toasts.length > this.maxVisibleValue) {
            this._removeToast(toasts[0]);
        }

        // Auto-dismiss
        const delay = duration || this.durationValue;
        toast._dismissTimer = setTimeout(() => {
            this._removeToast(toast);
        }, delay);
    }

    _removeToast(toast) {
        if (toast._removing) return;
        toast._removing = true;

        if (toast._dismissTimer) {
            clearTimeout(toast._dismissTimer);
        }

        toast.classList.add('toast-exit');
        toast.addEventListener('animationend', () => {
            toast.remove();
        }, { once: true });

        // Fallback if animationend doesn't fire
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 400);
    }

    _icon(type) {
        switch (type) {
        case 'success':
            return '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
        case 'error':
            return '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
        case 'warning':
            return '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
        default: // info
            return '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>';
        }
    }

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
