import { Controller } from '@hotwired/stimulus';

/**
 * Notification center: bell icon with dropdown panel, badge count,
 * and real-time updates via Mercure SSE.
 */
export default class extends Controller {
    static targets = ['badge', 'panel', 'panelContent', 'trigger'];
    static values = {
        mercureUrl: String,
        playerId: Number,
        panelUrl: String,
        markAllUrl: String,
        unreadCount: Number,
    };

    connect() {
        this._open = false;
        this._panelLoaded = false;
        this._updateBadge(this.unreadCountValue);
        this._connectMercure();

        this._onDocumentClick = (e) => {
            if (!this.element.contains(e.target)) {
                this._close();
            }
        };
        document.addEventListener('click', this._onDocumentClick);
    }

    disconnect() {
        document.removeEventListener('click', this._onDocumentClick);
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();
        if (this._open) {
            this._close();
        } else {
            this._openPanel();
        }
    }

    async markAllRead(event) {
        event.preventDefault();
        event.stopPropagation();

        try {
            const resp = await fetch(this.markAllUrlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok) {
                this._updateBadge(0);
                this._panelLoaded = false;
                if (this._open) {
                    this._loadPanel();
                }
            }
        } catch {
            // Silently ignore
        }
    }

    _openPanel() {
        this._open = true;
        if (this.hasPanelTarget) {
            this.panelTarget.classList.remove('hidden', 'opacity-0', 'translate-y-1');
            this.panelTarget.classList.add('opacity-100', 'translate-y-0');
        }
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
        if (!this._panelLoaded) {
            this._loadPanel();
        }
    }

    _close() {
        this._open = false;
        if (this.hasPanelTarget) {
            this.panelTarget.classList.add('hidden', 'opacity-0', 'translate-y-1');
            this.panelTarget.classList.remove('opacity-100', 'translate-y-0');
        }
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
    }

    async _loadPanel() {
        if (!this.panelUrlValue || !this.hasPanelContentTarget) return;

        try {
            const resp = await fetch(this.panelUrlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok) {
                this.panelContentTarget.innerHTML = await resp.text();
                this._panelLoaded = true;
            }
        } catch {
            // Silently ignore
        }
    }

    _connectMercure() {
        if (!this.mercureUrlValue || !this.playerIdValue) return;

        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', `player/${this.playerIdValue}/notifications`);

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (e) => {
            const data = JSON.parse(e.data);
            if (data.type === 'new_notification') {
                this._onNewNotification(data.notification);
            }
        };
    }

    _onNewNotification(notification) {
        this.unreadCountValue += 1;
        this._updateBadge(this.unreadCountValue);

        // Invalidate panel cache so it reloads next time
        this._panelLoaded = false;
        if (this._open) {
            this._loadPanel();
        }

        // Show toast with type-aware styling
        if (window.Toast) {
            const toastType = this._mapNotificationType(notification.type);
            const duration = toastType === 'celebration' ? 6000 : 4000;
            window.Toast.show(toastType, notification.title, duration);
        }
    }

    _mapNotificationType(notificationType) {
        switch (notificationType) {
        case 'achievement':
        case 'domain_level':
            return 'celebration';
        case 'quest':
        case 'craft_success':
            return 'success';
        case 'system':
            return 'warning';
        default:
            return 'info';
        }
    }

    _updateBadge(count) {
        if (!this.hasBadgeTarget) return;
        this.badgeTarget.textContent = count > 99 ? '99+' : count;
        this.badgeTarget.classList.toggle('hidden', count === 0);
    }
}
