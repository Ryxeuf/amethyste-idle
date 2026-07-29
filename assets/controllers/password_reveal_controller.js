import { Controller } from '@hotwired/stimulus';

/*
 * ONB-01 — le bouton « afficher » remplace la confirmation de mot de passe.
 *
 * Une double saisie corrige peu de fautes de frappe et fait abandonner : on
 * montre plutot ce qui vient d'etre tape, a la demande.
 */
export default class extends Controller {
    static targets = ['input', 'show', 'hide'];

    toggle() {
        const input = this.inputTarget;
        const revealed = input.type === 'text';

        input.type = revealed ? 'password' : 'text';

        if (this.hasShowTarget) {
            this.showTarget.classList.toggle('hidden', !revealed);
        }
        if (this.hasHideTarget) {
            this.hideTarget.classList.toggle('hidden', revealed);
        }
    }
}
