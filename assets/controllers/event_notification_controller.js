import { Controller } from '@hotwired/stimulus';

/**
 * Subscribes to Mercure topic event/announce and displays toast notifications
 * when a GameEvent becomes active. Also shows active events in the HUD badge.
 */
export default class extends Controller {
    static targets = ['badge', 'list', 'wrapper', 'trigger', 'panel'];
    static values = {
        mercureUrl: String,
        eventsUrl: String,
    };

    connect() {
        this._activeEvents = [];
        this._loadActiveEvents();
        this._connectMercure();
        this._onDocumentClick = (e) => {
            if (!this.element.contains(e.target)) {
                this._setPanelOpen(false);
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

    togglePanel(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.hasPanelTarget) return;
        const open = this.panelTarget.classList.contains('invisible');
        this._setPanelOpen(open);
    }

    _setPanelOpen(open) {
        if (!this.hasPanelTarget) return;
        this.panelTarget.classList.toggle('invisible', !open);
        this.panelTarget.classList.toggle('opacity-0', !open);
        this.panelTarget.classList.toggle('pointer-events-none', !open);
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    _connectMercure() {
        if (!this.mercureUrlValue) return;

        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', 'event/announce');

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (e) => {
            const data = JSON.parse(e.data);
            if (data.type === 'activated') {
                this._onEventActivated(data.event);
            }
        };
    }

    _onEventActivated(event) {
        // Show toast
        if (window.Toast) {
            const icon = this._typeIcon(event.type);
            window.Toast.show('info', `${icon} ${event.name}`, 6000);
        }

        // Add to active list
        const exists = this._activeEvents.some((e) => e.id === event.id);
        if (!exists) {
            this._activeEvents.push(event);
            this._render();
        }
    }

    async _loadActiveEvents() {
        if (!this.eventsUrlValue) return;

        try {
            const resp = await fetch(this.eventsUrlValue);
            if (resp.ok) {
                this._activeEvents = await resp.json();
                this._render();
            }
        } catch {
            // Silently ignore network errors
        }
    }

    _render() {
        // Filter out expired events
        const now = new Date();
        this._activeEvents = this._activeEvents.filter(
            (e) => new Date(e.endsAt) > now,
        );

        // Update badge
        if (this.hasBadgeTarget) {
            const count = this._activeEvents.length;
            this.badgeTarget.textContent = count;
            this.badgeTarget.classList.toggle('hidden', count === 0);
        }

        // Update list
        if (this.hasListTarget) {
            if (this._activeEvents.length === 0) {
                this.listTarget.innerHTML =
                    '<p class="text-gray-500 text-xs italic">Aucun evenement actif</p>';
                return;
            }

            this.listTarget.innerHTML = this._activeEvents
                .map((e) => {
                    const remaining = this._timeRemaining(e.endsAt);
                    return `<div class="flex items-center gap-2 text-xs">
                        <span class="text-yellow-400">${this._typeIcon(e.type)}</span>
                        <span class="text-gray-200 font-medium">${this._escapeHtml(e.name)}</span>
                        <span class="text-gray-500 ml-auto">${remaining}</span>
                    </div>`;
                })
                .join('');
        }
    }

    _typeIcon(type) {
        switch (type) {
        case 'xp_bonus': return '\u2728';
        case 'drop_bonus': return '\uD83C\uDFB2';
        case 'boss_spawn': return '\uD83D\uDC79';
        case 'invasion': return '\u2694\uFE0F';
        default: return '\uD83D\uDCC5';
        }
    }

    _timeRemaining(endsAt) {
        const diff = Math.max(0, new Date(endsAt) - new Date());
        const minutes = Math.floor(diff / 60000);
        if (minutes < 60) return `${minutes}min`;
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h${mins > 0 ? mins + 'min' : ''}`;
    }

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
