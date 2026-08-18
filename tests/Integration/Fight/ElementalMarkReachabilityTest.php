<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\GameEngine\Fight\ElementalMark;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La marque est-elle a portee des le jour 1 ? (ARC-13b-a).
 *
 * GAME_ARCHETYPES § 1.1. ARC-13a a pose les huit marques ; elles ne servaient
 * a rien tant qu'aucun geste ne les appliquait. **Trois pieces deja ecrites du
 * systeme en dependent** — le capstone d'assaut (« contre une cible qui porte
 * votre marque »), le levier `grip` (« les statuts appliques ») et la palette
 * de controle (deux accords d'`entrave`) —, et toutes les trois pointaient
 * vers un vide.
 *
 * Le jalon les branche a l'endroit qui compte : **un des deux accords d'entree**
 * de chaque arbre. Les accords d'entree coutent 0 point (GAME_MATERIA § 3), donc
 * un joueur les a le jour ou il ouvre l'arbre — et le capstone de son arbre
 * cesse d'etre une promesse conditionnee a un geste qu'il n'a pas.
 */
class ElementalMarkReachabilityTest extends AbstractIntegrationTestCase
{
    /**
     * Les trois arbres qui ne peuvent pas encore porter leur marque.
     *
     * **Le Guerisseur en est sorti avec ARC-07b**, et la maniere compte : on ne
     * lui a pas ajoute un geste offensif, on a **remplace** son second accord
     * d'entree — il portait un soin (`water-heal`), il porte le Jet d'eau, qui
     * blesse et applique Trempe. Un arbre d'entretien a besoin d'un geste qui
     * finisse le combat (§ 5.1, loi 1) autant que d'un geste qui le tienne.
     *
     * **Ce n'est pas un oubli, c'est une mesure** : aucun de leurs deux accords
     * d'entree ne fait de degats, et le § 1.1 veut qu'une marque soit portee
     * par un geste de degat — sans quoi elle coute un tour plein pour un tour
     * vole, ce que le § 9 quinquies a montre arithmetiquement nul.
     *
     * Les trois premiers sont **defendables** : `upkeep` et `bulwark` n'ont pas
     * d'intention de degat dans leur palette, et marquer un ennemi avec un soin
     * serait une fiction fausse. Le quatrieme ne l'est pas — **le Vagabond est
     * `control`, et la palette du controle exige une intention de degat** : son
     * absence de geste offensif a l'entree est un ecart qui lui prexiste, et
     * qu'ARC-08 referme en lui ecrivant ses nœuds manquants.
     *
     * La liste est un **cliquet** : elle peut retrecir, jamais grandir.
     *
     * @var list<string>
     */
    private const WAITING_ON_ARC_08 = ['Prêtre', 'Vagabond'];

    /**
     * Tout arbre de combat applique sa marque des son entree — ou figure sur
     * la liste d'attente, nommement.
     */
    public function testEveryCombatTreeReachesItsMarkOnDayOne(): void
    {
        $missing = [];

        foreach ($this->combatDomains() as $domain) {
            $mark = ElementalMark::forElement(Element::from((string) $domain->getElement()));
            self::assertNotNull($mark, sprintf('%s : un arbre de combat a un element marque.', $domain->getTitle()));

            if (!$this->hasEntryAccordApplying($domain, $mark)) {
                $missing[] = $domain->getTitle();
            }
        }

        sort($missing);
        self::assertSame(
            self::WAITING_ON_ARC_08,
            $missing,
            "La liste d'attente a bouge. Elle peut retrecir — jamais grandir."
        );
    }

    /**
     * Une marque posee a l'entree tient **la loi de duree**, pas une version
     * plus stricte d'elle-meme.
     *
     * ARC-13a enonce la loi en deux membres : *une marque dure au moins deux
     * tours, **ou** elle est portee par un geste de degat* — auquel cas le tour
     * n'a pas ete echange, il a servi deux fois. Le § 9 quinquies l'a montre
     * arithmetiquement : en duel, une entrave d'un tour laisse les degats subis
     * rigoureusement identiques, le combat s'allongeant de ce qu'on a vole.
     *
     * **Ce test n'exigeait que le second membre** (ARC-13b-a), et c'etait plus
     * strict que la loi qu'il citait : tous les arbres convertis se trouvaient
     * alors marquer avec un geste offensif, si bien que l'ecart ne coutait
     * rien. **ARC-08a a livre le premier arbre dont l'ouverture est une entrave
     * pure** — le Voile de cendre du Necromancien, zero degat, Aveugle deux
     * tours — et la fonction controle n'est pas une exception a faire : *ses
     * trois premiers tours ne tuent rien* est sa definition.
     *
     * Il **appelle** donc la loi au lieu de la reecrire. Une regle recopiee
     * derive de son original sans que rien ne le dise ; c'est le meme defaut
     * qu'ARC-11b-b avait trouve dans un test qui ne parcourait que sa propre
     * liste.
     */
    public function testAnEntryMarkHoldsTheDurationLaw(): void
    {
        $offenders = [];

        foreach ($this->combatDomains() as $domain) {
            $mark = ElementalMark::forElement(Element::from((string) $domain->getElement()));

            foreach ($this->entryAccordSpells($domain) as $spell) {
                if ($spell->getStatusEffectSlug() !== $mark) {
                    continue;
                }

                $effect = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $mark]);
                self::assertInstanceOf(StatusEffect::class, $effect, sprintf('La marque "%s" n\'existe pas en base.', (string) $mark));

                $duration = $effect->getDuration();
                $wounds = (int) ($spell->getDamage() ?? 0) > 0;

                if (!ElementalMark::durationIsLegal($duration, $wounds)) {
                    $offenders[] = sprintf(
                        '%s : %s ne blesse pas et %s ne dure que %d tour(s).',
                        (string) $domain->getTitle(),
                        (string) $spell->getSlug(),
                        (string) $mark,
                        $duration,
                    );
                }
            }
        }

        self::assertSame([], $offenders, implode("\n", $offenders));
    }

    /**
     * Trois gestes appliquent la Brulure sans etre du feu.
     *
     * **Trouve par cet invariant, et anterieur au jalon** : `holy-fire`
     * (lumiere), `dark-forge-blast` (metal) et `amethyst-shatter` (tenebres)
     * portaient deja `burn` avant qu'ARC-13a n'en fasse la marque du feu.
     * Depuis, ils allument le capstone d'un **Pyromancien** — le capstone d'un
     * arbre s'allume sur le geste d'un autre.
     *
     * **Ce n'est pas reparable sans une decision.** La Brulure est
     * volontairement deux choses (ARC-13a : *la mark-ness vit dans un
     * catalogue, pas dans le type* — elle est un DOT **et** la marque du feu),
     * et ces trois gestes veulent le DOT. Leur donner la marque de leur propre
     * element leur **retirerait** leur degat sur la duree ; il n'existe pas de
     * DOT neutre pour les accueillir. Il faut donc soit en creer un, soit
     * separer la Brulure-marque de la brulure-DOT — dans les deux cas, une
     * decision de conception que ce jalon n'a pas a prendre.
     *
     * La liste est un **cliquet** : elle peut retrecir, jamais grandir.
     *
     * @var list<string>
     */
    private const BURN_OUTSIDE_FIRE = [
        'amethyst-shatter (dark) applique burn (fire)',
        'dark-forge-blast (metal) applique burn (fire)',
        'holy-fire (light) applique burn (fire)',
    ];

    /**
     * Une marque ne se pose jamais hors de son element — sauf les trois connus.
     *
     * L'invariant qui protege la grille : un geste de feu ne marque pas d'eau,
     * sinon le capstone d'un arbre s'allumerait sur le geste d'un autre. Les
     * trois exceptions sont nommees plutot que tolerees, pour qu'aucune
     * quatrieme n'apparaisse en silence.
     */
    public function testAMarkIsNeverAppliedOutsideItsOwnElement(): void
    {
        $offenders = [];

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            $slug = $spell->getStatusEffectSlug();
            if ($slug === null || !ElementalMark::isMark($slug)) {
                continue;
            }

            $markElement = ElementalMark::elementOf($slug);
            if ($markElement !== null && $spell->getElement() !== $markElement) {
                $offenders[] = sprintf('%s (%s) applique %s (%s)', $spell->getSlug(), $spell->getElement()->value, $slug, $markElement->value);
            }
        }

        sort($offenders);
        self::assertSame(
            self::BURN_OUTSIDE_FIRE,
            $offenders,
            'La liste des marques hors element a bouge. Elle peut retrecir — jamais grandir.'
        );
    }

    private function hasEntryAccordApplying(Domain $domain, string $mark): bool
    {
        foreach ($this->entryAccordSpells($domain) as $spell) {
            if ($spell->getStatusEffectSlug() === $mark) {
                return true;
            }
        }

        return false;
    }

    /**
     * Les gestes que les accords d'entree de cet arbre ouvrent.
     *
     * Un accord d'entree est un nœud a **0 point** qui ouvre une materia : la
     * regle du jour 1 de GAME_MATERIA § 3, et les 24 arbres en portent
     * exactement deux chacun.
     *
     * @return list<Spell>
     */
    private function entryAccordSpells(Domain $domain): array
    {
        $spells = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if ($skill->getRequiredPoints() !== 0 || !$skill->getDomains()->contains($domain)) {
                continue;
            }

            $unlock = $skill->getActions()['materia']['unlock'] ?? null;
            if (!\is_string($unlock)) {
                continue;
            }

            $spell = $this->em->getRepository(Spell::class)->findOneBy(['slug' => $unlock]);
            if ($spell !== null) {
                $spells[] = $spell;
            }
        }

        return $spells;
    }

    /**
     * @return list<Domain>
     */
    private function combatDomains(): array
    {
        return array_values(array_filter(
            $this->em->getRepository(Domain::class)->findAll(),
            fn (Domain $domain) => $domain->getRegister() !== null,
        ));
    }
}
