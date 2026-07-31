<?php

namespace App\Tests\Unit\GameEngine\Player;

use PHPUnit\Framework\TestCase;

/**
 * Les dettes d'ecran du hub, une fois soldees, le restent (RET-10).
 *
 * GAME_DASHBOARD § 7 en releve quatre. Deux se tiennent par le gabarit et le
 * catalogue plutot que par du code, et rien n'empeche un jalon futur de les
 * rouvrir sans le vouloir — un `ds-empty` recopie depuis un autre ecran, une
 * mention d'XP « remise pour la lisibilite ». Ce fichier les verrouille au
 * niveau ou elles se rouvriraient.
 *
 * Les deux autres dettes (le loyer date, l'enchantement expire) vivent dans le
 * digest et sont couvertes par `PlayerHubDigestTest` : elles se testent par le
 * comportement, pas par la forme.
 */
class HubScreenDebtsTest extends TestCase
{
    private const HUB_TEMPLATE = 'templates/game/index.html.twig';

    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    private function hub(): string
    {
        $source = file_get_contents($this->root() . '/' . self::HUB_TEMPLATE);
        self::assertIsString($source, 'Le gabarit du hub est illisible : le test ne verifie rien.');

        return $source;
    }

    /**
     * Dette 2 : plus aucun etat vide plein sur le hub.
     *
     * Un compte neuf y lisait quatre encarts qui parlaient chacun de ce qui
     * n'existe pas encore. La maquette 5D les replie sur une ligne — qui dit
     * quoi faire et mene la ou le contenu naitra.
     *
     * Le controle porte sur `ds-empty` et non sur le nombre de lignes repliees :
     * c'est le composant qui deplie l'encart, et le recopier est le geste par
     * lequel la dette reviendrait.
     */
    public function testTheHubHasNoUnfoldedEmptyState(): void
    {
        self::assertStringNotContainsString(
            'ds-empty',
            $this->hub(),
            'Un etat vide plein est revenu sur le hub. Un bloc sans contenu se replie sur une ligne : '
            . 'l\'encart deplie occupe l\'ecran pour ne rien annoncer.',
        );
    }

    /**
     * Dette 2, l'autre moitie : chaque bloc repliable a bien sa ligne.
     *
     * Sans ce controle, supprimer les `ds-empty` sans rien mettre a la place
     * passerait le test precedent — et un bloc vide deviendrait invisible au
     * lieu d'etre replie.
     */
    public function testEveryFoldableBlockHasItsFoldedLine(): void
    {
        $hub = $this->hub();

        foreach (['pending', 'recap', 'domains', 'quests'] as $block) {
            self::assertStringContainsString(
                sprintf("folded('%s'", $block),
                $hub,
                sprintf('Le bloc « %s » n\'a plus de ligne repliee : vide, il disparaitrait sans un mot.', $block),
            );
        }
    }

    /**
     * Dette 1 : l'XP disponible ne se dit qu'a un endroit.
     *
     * Elle etait annoncee par la ligne d'attente `talent_xp` **et** repetee sous
     * chaque barre de domaine. La ligne d'attente gagne — elle est actionnable
     * et disparait une fois l'XP depensee. La cle du second affichage a quitte
     * les catalogues, ce qui rend son retour visible : il faudrait la recreer.
     */
    public function testTheAvailableExperienceIsNamedInExactlyOnePlace(): void
    {
        foreach (['fr', 'en'] as $locale) {
            $catalog = json_decode(
                (string) file_get_contents($this->root() . '/translations/messages.' . $locale . '.json'),
                true,
            );

            self::assertIsArray($catalog, 'Le catalogue est illisible : le test ne verifie rien.');
            self::assertArrayHasKey('talent_xp', $catalog['game']['home']['pending'] ?? []);
            self::assertArrayNotHasKey(
                'available_xp',
                $catalog['game']['home']['domains'] ?? [],
                'L\'XP disponible est de nouveau annoncee deux fois. Le bloc domaines ne garde que ses jauges.',
            );
        }
    }
}
