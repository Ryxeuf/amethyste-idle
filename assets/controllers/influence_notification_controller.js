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
        labels: { type: Object, default: {} },
    };

    _label(key, fallback) {
        const value = this.labelsValue?.[key];
        return typeof value === 'string' && value.length > 0 ? value : fallback;
    }

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

        const influenceWord = this._label('influence_word', 'influence');
        const msg = `\u2B50 +${data.pointsEarned} ${influenceWord} (${data.activityLabel}) \u2014 ${data.regionName}`;
        window.Toast.show('info', msg, 5000);
    }

    _onInfluenceOvertake(data) {
        if (!window.Toast) return;

        const verb = this._label('overtook_in', 'vous a depasse en');
        const msg = `\u26A0\uFE0F ${data.overtakenByGuild} [${data.overtakenByTag}] ${verb} ${data.regionName} !`;
        window.Toast.show('warning', msg, 8000);
    }

    _onChallengeCompleted(data) {
        if (!window.Toast) return;

        const label = this._label('challenge_completed', 'Defi complete');
        const ptsLabel = this._label('points_influence', 'pts influence');
        const msg = `${label} : "${data.challengeTitle}" (+${data.bonusPoints} ${ptsLabel})`;
        window.Toast.show('success', msg, 8000);
    }

    _onCityControlChange(data) {
        if (!window.Toast) return;

        if (!data.changes || data.changes.length === 0) return;

        const noGuild = this._label('no_guild', 'Aucune guilde');
        const verb = this._label('takes_control_of', 'prend le controle de');
        const seasonLabel = this._label('season', 'Saison');

        for (const change of data.changes) {
            const guild = change.guild || noGuild;
            const msg = `\uD83C\uDFF0 ${guild} ${verb} ${change.region} (${seasonLabel} ${data.seasonNumber})`;
            window.Toast.show('success', msg, 10000);
        }
    }
}
