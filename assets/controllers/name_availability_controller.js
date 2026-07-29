import { Controller } from '@hotwired/stimulus';

/*
 * ONB-06 — « libre / pris », au fil de la frappe.
 *
 * Perdre un formulaire entier — nom, peuple, visage — parce que le nom etait
 * pris est le genre d'accident qui fait fermer l'onglet. On repond avant
 * l'envoi.
 *
 * Le verdict n'est jamais une garantie : deux creations simultanees passent
 * toutes deux ici avant qu'aucune n'ait ecrit. Seul l'index unique tranche,
 * et le controleur rattrape la collision.
 */
export default class extends Controller {
    static targets = ['input', 'status'];
    static values = { url: String, free: String, taken: String, forbidden: String, tooShort: String };

    connect() {
        this.timer = null;
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    check() {
        clearTimeout(this.timer);
        // On attend une pause dans la frappe : interroger a chaque touche
        // ferait dire « pris » a des prefixes que personne n'a tapes en entier.
        this.timer = setTimeout(() => this.ask(), 350);
    }

    async ask() {
        const name = this.inputTarget.value.trim();

        if (name.length < 3) {
            this.say(this.tooShortValue, false);
            return;
        }

        try {
            const response = await fetch(`${this.urlValue}?name=${encodeURIComponent(name)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                this.say('', null);
                return;
            }

            const { available, reason } = await response.json();

            if (available) {
                this.say(this.freeValue, true);
            } else if (reason === 'forbidden') {
                this.say(this.forbiddenValue, false);
            } else if (reason === 'too_short') {
                this.say(this.tooShortValue, false);
            } else {
                this.say(this.takenValue, false);
            }
        } catch {
            // Le reseau a flanche : on se tait plutot que d'annoncer a tort.
            this.say('', null);
        }
    }

    say(message, isFree) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
        this.statusTarget.classList.toggle('text-emerald-400', isFree === true);
        this.statusTarget.classList.toggle('text-red-400', isFree === false);
    }
}
