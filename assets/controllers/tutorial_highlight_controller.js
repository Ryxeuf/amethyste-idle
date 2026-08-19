import { Controller } from '@hotwired/stimulus'

/**
 * Souligne, dans la navigation, le lien qui mene la ou le tutoriel envoie.
 *
 * **Ce qu'il faisait avant.** Il portait une table `etape -> route` ecrite du
 * temps ou les cinq etapes s'appelaient Deplacement / Combat / Butin / Quetes /
 * Artisanat. ONB-14 les a redefinies (Arme / Materia / Metier / Depart /
 * Expedition) sans que la table suive : elle mettait donc en valeur « Quetes »
 * pendant l'etape du Depart, « Artisanat » pendant celle de l'Expedition, et
 * visait une route `map` supprimee avec la carte navigable (ZON-21).
 *
 * **Pourquoi ca ne peut plus vieillir.** Il n'y a plus de table. La destination
 * est calculee cote serveur par `TutorialGuide` et passee ici comme un chemin ;
 * on met en valeur le lien de navigation qui pointe dessus, quel qu'il soit.
 * Une etape ajoutee, un PNJ deplace ou un ecran renomme n'ont plus rien a
 * mettre a jour ici.
 */
export default class extends Controller {
    static values = { path: String }

    connect() {
        this._applyHighlights()
    }

    disconnect() {
        document.querySelectorAll('.tutorial-highlight').forEach(el => {
            el.classList.remove('tutorial-highlight')
        })
    }

    _applyHighlights() {
        const path = this.pathValue
        if (!path) return

        // On compare l'attribut plutot que d'assembler un selecteur : un chemin
        // contient des `/` et parfois des chiffres, que `CSS.escape` echapperait
        // au point de ne plus rien trouver.
        //
        // Le bandeau porte deja son propre lien : le mettre en valeur une
        // seconde fois ferait clignoter la meme phrase deux fois.
        document.querySelectorAll('a[href]').forEach(el => {
            if (el.getAttribute('href') !== path) return
            if (this.element.contains(el)) return
            el.classList.add('tutorial-highlight')
        })
    }
}
