import { Controller } from '@hotwired/stimulus';

/**
 * Ferme un encart de coach (ONB-17).
 *
 * L'encart disparait tout de suite et la requete part derriere : le joueur a
 * ferme, il n'a pas a attendre le serveur pour en avoir la preuve. Si l'appel
 * echoue, l'encart reviendra a la prochaine visite — c'est la bonne degradation,
 * bien meilleure qu'une croix qui ne fait rien.
 */
export default class extends Controller {
    static values = { url: String };

    dismiss() {
        this.element.remove();

        fetch(this.urlValue, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).catch(() => {});
    }
}
