import { Controller } from '@hotwired/stimulus';

/**
 * Carte du monde : voyager en cliquant le territoire, pas seulement sa pastille.
 *
 * Une pastille de 24 px sur une carte de 640 px est une cible minuscule, et
 * plus petite encore au doigt. Les contours de zone (`Zone.mapShape`, tracés
 * dans `config/game/zones/world_1.yaml`) donnent une cible a la taille du
 * territoire.
 *
 * Le voyage reste un POST protege par jeton CSRF : le contour ne refait pas la
 * requete, il **soumet le formulaire deja rendu** pour cette connexion. D'ou le
 * couplage par `data-connection` plutot qu'une URL reconstruite en JS — le
 * jeton n'a jamais a transiter par l'attribut d'un polygone.
 *
 * Sans JS, les pastilles restent des boutons de formulaire ordinaires : les
 * contours sont un enrichissement, jamais le seul chemin.
 *
 * Usage :
 *   <div data-controller="world-map">
 *     <polygon data-action="click->world-map#travel" data-world-map-connection-param="99" />
 *     <form data-world-map-target="form" data-connection="99">…</form>
 *   </div>
 */
export default class extends Controller {
    static targets = ['form'];

    travel(event) {
        const connectionId = event.params.connection;
        if (connectionId === undefined) return;

        const form = this.formTargets.find((el) => el.dataset.connection === String(connectionId));
        if (!form) return;

        event.preventDefault();
        // `requestSubmit` et non `submit` : il declenche les evenements de
        // soumission, donc Turbo intercepte la navigation comme pour un clic
        // sur la pastille. `submit()` court-circuiterait Turbo et rechargerait
        // la page entiere.
        form.requestSubmit();
    }
}
