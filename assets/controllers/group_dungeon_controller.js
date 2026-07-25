import { Controller } from '@hotwired/stimulus';

/**
 * Donjon de groupe temps reel (pivot PBBG, ZON-19 sous-jalon 3).
 *
 * S'abonne au topic Mercure `dungeon/run/<id>` pour rafraichir la banniere de
 * combat (PV de rencontre, tour actif, bouton Attaquer) sans recharger la page
 * quand le groupe est connecte simultanement. L'etat autoritatif reste resolu
 * paresseusement cote serveur ; Mercure n'est qu'un confort d'affichage.
 */
export default class extends Controller {
    static targets = ['hpBar', 'hpText', 'turn', 'attack', 'waiting', 'cleared', 'combat', 'abandon'];
    static values = {
        mercureUrl: String,
        runId: Number,
        playerId: Number,
        turnLabel: String,
    };

    connect() {
        this._remaining = null;

        if (this.mercureUrlValue && this.runIdValue) {
            const url = new URL(this.mercureUrlValue);
            url.searchParams.append('topic', `dungeon/run/${this.runIdValue}`);
            this._eventSource = new EventSource(url);
            this._eventSource.onmessage = (event) => this._onMessage(event);
        }

        // Decompte local du minuteur de tour (confort visuel entre deux events).
        this._tick = window.setInterval(() => this._renderTurn(), 1000);
    }

    disconnect() {
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
        if (this._tick) {
            window.clearInterval(this._tick);
            this._tick = null;
        }
    }

    _onMessage(event) {
        let data;
        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }
        if (data.type !== 'group_dungeon_state') return;

        if (this.hasHpBarTarget && typeof data.encounterHpPercent === 'number') {
            this.hpBarTarget.style.width = `${data.encounterHpPercent}%`;
        }
        if (this.hasHpTextTarget && typeof data.encounterHpCurrent === 'number') {
            this.hpTextTarget.textContent = `${data.encounterHpCurrent} / ${data.encounterHpMax} PV`;
        }

        this._remaining = typeof data.turnRemainingSeconds === 'number' ? data.turnRemainingSeconds : null;
        this._renderTurn();

        if (data.status === 'completed') {
            this._showCleared();
            return;
        }

        const myTurn = data.activePlayerId === this.playerIdValue;
        this._toggle(this.attackTarget, myTurn);
        this._toggle(this.waitingTarget, !myTurn);
    }

    _renderTurn() {
        if (!this.hasTurnTarget) return;
        if (this._remaining === null) {
            this.turnTarget.textContent = '';
            return;
        }
        if (this._remaining > 0) {
            this._remaining -= 1;
        }
        const label = (this.turnLabelValue || '{s}').replace('{s}', String(Math.max(0, this._remaining)));
        this.turnTarget.textContent = `· ${label}`;
    }

    _showCleared() {
        if (this.hasCombatTarget) this.combatTarget.classList.add('hidden');
        if (this.hasClearedTarget) this.clearedTarget.classList.remove('hidden');
        if (this.hasAbandonTarget) this.abandonTarget.classList.add('hidden');
        this._remaining = null;
    }

    _toggle(el, show) {
        if (!el) return;
        el.classList.toggle('hidden', !show);
    }
}
