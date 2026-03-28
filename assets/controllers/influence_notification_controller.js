import { Controller } from '@hotwired/stimulus';

/**
 * Subscribes to Mercure topics for guild influence notifications.
 * - guild/influence/{guildId} — batched points & overtake alerts
 * - guild/city_control — global city control change announcements
 */
export default class extends Controller {
    static values = {
        mercureUrl: String,
        guildId: { type: Number, default: 0 },
    };

    connect() {
        this._connectMercure();
    }

    disconnect() {
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
    }

    _connectMercure() {
        if (!this.mercureUrlValue) return;

        const url = new URL(this.mercureUrlValue);

        // Always subscribe to global city control
        url.searchParams.append('topic', 'guild/city_control');

        // Subscribe to guild-specific influence if player has a guild
        if (this.guildIdValue) {
            url.searchParams.append('topic', `guild/influence/${this.guildIdValue}`);
        }

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (e) => {
            try {
                const data = JSON.parse(e.data);
                this._handleEvent(data);
            } catch {
                // Silently ignore parse errors
            }
        };
    }

    _handleEvent(data) {
        switch (data.type) {
        case 'influence_awarded':
            this._onInfluenceAwarded(data);
            break;
        case 'influence_overtake':
            this._onInfluenceOvertake(data);
            break;
        case 'city_control_change':
            this._onCityControlChange(data);
            break;
        case 'challenge_completed':
            this._onChallengeCompleted(data);
            break;
        }
    }

    _onInfluenceAwarded(data) {
        if (!window.Toast) return;

        const msg = `\u2B50 +${data.pointsEarned} influence (${data.activityLabel}) \u2014 ${data.regionName}`;
        window.Toast.show('info', msg, 5000);
    }

    _onInfluenceOvertake(data) {
        if (!window.Toast) return;

        const msg = `\u26A0\uFE0F ${data.overtakenByGuild} [${data.overtakenByTag}] vous a depasse en ${data.regionName} !`;
        window.Toast.show('warning', msg, 8000);
    }

    _onChallengeCompleted(data) {
        if (!window.Toast) return;

        const msg = `Defi complete : "${data.challengeTitle}" (+${data.bonusPoints} pts influence)`;
        window.Toast.show('success', msg, 8000);
    }

    _onCityControlChange(data) {
        if (!window.Toast) return;

        if (!data.changes || data.changes.length === 0) return;

        for (const change of data.changes) {
            const guild = change.guild || 'Aucune guilde';
            const msg = `\uD83C\uDFF0 ${guild} prend le controle de ${change.region} (Saison ${data.seasonNumber})`;
            window.Toast.show('success', msg, 10000);
        }
    }
}
