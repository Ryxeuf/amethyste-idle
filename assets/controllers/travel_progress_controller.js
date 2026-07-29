import { Controller } from '@hotwired/stimulus';

/**
 * Barre de progression du voyage entre zones.
 *
 * Le serveur reste la seule autorite sur l'arrivee (`ZoneTravelService::settleArrival`,
 * resolue paresseusement au chargement d'ecran) : ce controleur ne fait qu'animer
 * l'attente, puis recharge la page une fois le terme atteint pour que l'arrivee
 * soit reglee. Sans duree totale connue (voyage entame avant le suivi du depart),
 * il se contente d'entretenir le decompte.
 *
 * Les horodatages voyagent en secondes epoch UTC : l'heure de la machine du
 * joueur peut deriver, seul l'ecart entre deux bornes serveur compte. On
 * recale donc le « maintenant » local sur le `now` du serveur au montage.
 */
export default class extends Controller {
    static targets = ['bar', 'remaining', 'elapsed'];
    static values = {
        arrivesAt: Number,
        serverNow: Number,
        total: Number,
    };

    connect() {
        // Decalage entre l'horloge du navigateur et celle du serveur.
        this._clockOffset = this.hasServerNowValue ? this._localNow() - this.serverNowValue : 0;
        this._reloaded = false;

        this._tick();
        this._timer = setInterval(() => this._tick(), 1000);
    }

    disconnect() {
        clearInterval(this._timer);
        clearTimeout(this._reloadTimer);
    }

    _localNow() {
        return Math.floor(Date.now() / 1000);
    }

    _now() {
        return this._localNow() - this._clockOffset;
    }

    _tick() {
        const remaining = Math.max(0, this.arrivesAtValue - this._now());

        if (this.hasRemainingTarget) {
            this.remainingTarget.textContent = this._format(remaining);
        }

        if (this.totalValue > 0) {
            const elapsed = Math.min(this.totalValue, Math.max(0, this.totalValue - remaining));

            if (this.hasElapsedTarget) {
                this.elapsedTarget.textContent = this._format(elapsed);
            }

            if (this.hasBarTarget) {
                const percent = Math.round((elapsed / this.totalValue) * 100);
                this.barTarget.style.width = `${percent}%`;
                this.barTarget.parentElement?.setAttribute('aria-valuenow', String(percent));
            }
        }

        if (remaining <= 0) {
            clearInterval(this._timer);
            this._reload();
        }
    }

    /**
     * Rechargement de l'ecran de zone : c'est ce chargement qui declenche
     * `settleArrival` cote serveur et fait apparaitre la nouvelle zone.
     */
    _reload() {
        if (this._reloaded) {
            return;
        }
        this._reloaded = true;

        // Petite marge : l'arrivee est comparee a l'horloge du serveur, mieux
        // vaut attendre une seconde de trop qu'un rechargement pour rien.
        this._reloadTimer = setTimeout(() => window.location.reload(), 1000);
    }

    /**
     * Duree lisible : `12:34` sous l'heure, `1:02:03` au-dela.
     */
    _format(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        const pad = (n) => String(n).padStart(2, '0');

        return hours > 0 ? `${hours}:${pad(minutes)}:${pad(secs)}` : `${minutes}:${pad(secs)}`;
    }
}
