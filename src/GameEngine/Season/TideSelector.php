<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;

/**
 * Le tireur de marees (NAR-15).
 *
 * GAME_SEASONS § 0 : l'an 1 n'est pas un calendrier de treize episodes, c'est
 * une **partition a trois voix**, et l'ordre de priorite en est la regle :
 *
 *     consequence declenchee  >  colonne vertebrale datee  >  rotation
 *
 * Ce service est le **seul** endroit ou cet ordre existe. Le plan mettait en
 * garde contre « un second selecteur concurrent de celui que FOY-15 a pose » ;
 * la reponse n'est pas une cohabitation mais une subordination : chaque voix
 * garde son propre service pour repondre a sa propre question — celui de FOY-15
 * pour les consequences, `RotationTideSelector` pour le tirage —, et c'est ici,
 * et nulle part ailleurs, qu'on decide laquelle parle.
 *
 * ## Un creneau deja pris est deja pris
 *
 * Une saison qui porte deja un theme n'est jamais bousculee : c'est la regle que
 * FOY-15 a posee, et la colonne vertebrale l'etend. Un creneau canon est
 * **reserve** avant meme d'etre ecrit — sans quoi une rotation prendrait le
 * creneau M2 et « La Premiere Pierre » n'arriverait jamais.
 *
 * ## La colonne reserve, elle n'improvise pas
 *
 * A ce jalon, aucune maree canon n'a d'arc : leur contenu arrive avec NAR-16 a
 * NAR-19. Le creneau reste donc **vide plutot que pris** — ce qui est le seul
 * comportement honnete. Composer quelque chose sous le nom de « La Premiere
 * Pierre » reviendrait a livrer, sous un nom que le canon a promis, un arc que
 * personne n'a ecrit.
 */
class TideSelector
{
    public function __construct(
        private readonly ConsequenceTideSelector $consequences,
        private readonly RotationTideSelector $rotation,
        private readonly TideDefinitionLoader $loader,
    ) {
    }

    /**
     * Ce que la partition decide pour cette saison.
     *
     * **Le releve du repere n'est pas fait ici.** Il appartient a l'appelant,
     * parce qu'il doit avancer meme quand aucune consequence n'est retenue —
     * y compris quand le creneau etait deja pris. Le glisser dans une lecture en
     * ferait un effet de bord, et une lecture qui modifie l'etat du monde ne se
     * rejoue pas deux fois de la meme facon.
     */
    public function selectFor(InfluenceSeason $season): TideChoice
    {
        // Un creneau qui porte deja son theme n'est bouscule par personne.
        if ($season->getTheme() !== null) {
            return TideChoice::nothing();
        }

        $canon = $this->loader->load()['canon'][$season->getSeasonNumber()] ?? null;

        $tide = $this->consequences->select();

        // Voix 1 — la consequence preempte le prochain creneau **libre**. Un
        // creneau que la colonne a reserve n'est pas libre : la consequence se
        // represente au creneau suivant, sa condition etant un etat du monde et
        // non un rendez-vous.
        if ($tide !== null && $canon === null) {
            return TideChoice::consequence($tide->value);
        }

        // Voix 2 — la colonne vertebrale. Elle tient le creneau, meme vide.
        if ($canon !== null) {
            return TideChoice::reserved($canon['theme'], $canon['milestone']);
        }

        // Voix 3 — la rotation.
        $template = $this->rotation->select();

        return $template !== null ? TideChoice::rotation($template) : TideChoice::nothing();
    }
}
