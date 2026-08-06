<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\DomainRole;
use App\Enum\SpellIntent;
use App\Enum\SpellScope;
use App\GameEngine\Progression\DomainRoleDefinitionLoader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La palette d'intentions, tenue par arbre (ARC-11b-b).
 *
 * GAME_ARCHETYPES § 5.1. ARC-01 a livre les palettes en configuration —
 * `domain_roles.yaml` declare depuis lors qu'un arbre d'assaut ouvre au moins
 * trois accords de degat, un arbre de controle deux entraves, un arbre
 * d'entretien deux soins ou protections dont un de portee `le groupe`. **Rien
 * ne les lisait.** Un fichier de contraintes que personne n'oppose est un
 * commentaire, et c'est ce test qui en fait une regle.
 *
 * Ce que la palette ferme, c'est la boucle que le § 0 affirme depuis le debut :
 * *l'archetype vit dans le couple (arbre, materia)* — mais rien n'imposait
 * **quelles** materia un arbre ouvre. Un arbre d'entretien qui n'ouvrirait que
 * des gestes de degat serait un arbre d'assaut sous un autre nom.
 *
 * **La mesure est le livrable, pas la conformite.** Le contenu des 24 arbres
 * s'ecrit a ARC-07 et ARC-08 ; ce test dit ou ils en sont, en cliquet — les
 * listes peuvent retrecir, jamais grandir.
 */
class DomainIntentPaletteContractTest extends AbstractIntegrationTestCase
{
    /**
     * Les dix arbres qui n'ouvrent **que** des gestes de degat.
     *
     * C'est le plan B du test du jour 1 (§ 8.4) qui manque : *le tour ou
     * frapper n'est pas la reponse*. Un arbre qui n'ouvre que des coups n'a
     * qu'une seule chose a proposer, et le joueur qui le mene n'a jamais de
     * decision a prendre en combat.
     *
     * Neuf des dix sont des arbres d'assaut ou de controle en registre
     * offensif, ce qui rend l'ecart lisible : leurs auteurs ont ecrit ce que
     * l'archetype fait le plus souvent, et rien d'autre. ARC-07 et ARC-08 leur
     * ecrivent le second geste.
     *
     * La liste est un **cliquet** : elle peut retrecir, jamais grandir.
     *
     * @var list<string>
     */
    private const ONLY_DAMAGE = [
        'Archer',
        'Artificier',
        'Assassin',
        'Berserker',
        'Chasseur',
        'Foudromancien',
        'Nécromancien',
        'Pyromancien',
        'Soldat',
        'Sorcier',
    ];

    /**
     * Ce que les palettes de `domain_roles.yaml` reclament et n'ont pas.
     *
     * **Quinze ecarts, et ils disent tous la meme chose** : aucun geste livre
     * ne derive vers `entrave`, `protection` ni `amelioration`. Les 253 sorts
     * se rangent en 194 degats et 59 soins, parce que **l'ordre des questions
     * de la derivation** (ARC-11a) fait gagner le degat puis le soin : un
     * bouclier qui rend des PV est un soin, une fleche empoisonnee est un
     * degat. C'est voulu — le § 1.1 exige qu'une marque soit portee par un
     * geste de degat — mais cela veut dire que **la protection et l'entrave
     * n'existeront que le jour ou un auteur les declarera**, sur la colonne
     * `Spell::intent` qu'ARC-11a a laissee nullable pour exactement cela.
     *
     * De meme pour `group_scoped` : `SpellScope::Group` ne se derive jamais
     * (ARC-11a), donc aucun des quatre arbres d'entretien ne peut aujourd'hui
     * tenir sa portee de groupe — la seule qui rende la loi du depot utile.
     *
     * La liste est un **cliquet** : elle peut retrecir, jamais grandir.
     *
     * @var list<string>
     */
    private const WAITING_ON_ARC_07_08 = [
        'Artificier : 0 hinder sur 2',
        'Dompteur : 0 hinder sur 2',
        'Druide : 0 group_scoped sur 1',
        'Défenseur : 0 protect sur 2',
        'Gardien : 0 group_scoped sur 1',
        'Guérisseur : 0 group_scoped sur 1',
        'Géomancien : 0 hinder sur 2',
        'Hydromancien : 0 hinder sur 2',
        'Ingénieur : 0 hinder sur 2',
        'Nécromancien : 0 hinder sur 2',
        'Paladin : 0 protect sur 2',
        'Prêtre : 0 group_scoped sur 1',
        'Soldat : 0 ally_or_group_scoped sur 1',
        'Soldat : 0 protect sur 2',
        'Vagabond : 0 hinder sur 2',
    ];

    /**
     * **Tout arbre ouvre au moins un accord de degat** (§ 5.1, loi 1).
     *
     * Sans lui, un combat ne finit jamais — et l'archetype est injouable seul,
     * c'est-a-dire 95 % du temps. C'est la seule des deux lois transverses qui
     * soit deja tenue par les 24 arbres, et il faut qu'elle le reste : elle est
     * plus facile a casser qu'a satisfaire, puisqu'ARC-08 va precisement
     * ajouter des gestes non offensifs.
     */
    public function testEveryTreeOpensADamagingAccord(): void
    {
        $offenders = [];

        foreach ($this->combatDomains() as $domain) {
            if (!\in_array(SpellIntent::Damage, $this->intentsOpenedBy($domain), true)) {
                $offenders[] = (string) $domain->getTitle();
            }
        }

        sort($offenders);
        self::assertSame([], $offenders, 'Un arbre n\'ouvre aucun geste de degat : son combat ne finit jamais.');
    }

    /**
     * **Tout arbre ouvre au moins un accord qui n'est pas un degat** (loi 2).
     */
    public function testEveryTreeOpensSomethingOtherThanDamage(): void
    {
        $offenders = [];

        foreach ($this->combatDomains() as $domain) {
            $intents = array_filter($this->intentsOpenedBy($domain), fn (SpellIntent $i) => $i !== SpellIntent::Damage);
            if ($intents === []) {
                $offenders[] = (string) $domain->getTitle();
            }
        }

        sort($offenders);
        self::assertSame(
            self::ONLY_DAMAGE,
            $offenders,
            'La liste des arbres qui ne savent que frapper a bouge. Elle peut retrecir — jamais grandir.'
        );
    }

    /**
     * La palette de chaque fonction, opposee arbre par arbre.
     */
    public function testEachFunctionPaletteIsHeldByItsTrees(): void
    {
        $loader = new DomainRoleDefinitionLoader(\dirname(__DIR__, 3));
        $roles = $loader->load()['roles'];

        $gaps = [];

        foreach ($this->combatDomains() as $domain) {
            $role = $domain->getRole();
            if ($role === null) {
                continue;
            }

            $counts = $this->paletteCountsOf($domain);

            foreach ($roles[$role->value]['intents'] as $requirement => $needed) {
                $found = $counts[$requirement] ?? 0;
                if ($found < $needed) {
                    $gaps[] = sprintf('%s : %d %s sur %d', $domain->getTitle(), $found, $requirement, $needed);
                }
            }
        }

        sort($gaps);
        self::assertSame(
            self::WAITING_ON_ARC_07_08,
            $gaps,
            "La liste d'attente des palettes a bouge. Elle peut retrecir — jamais grandir."
        );
    }

    /**
     * Aucun geste livre n'a d'intention illisible.
     *
     * C'est le garde-fou du repli de `LeverIntentLaw` : une intention `null` ne
     * borne aucun levier, donc un geste muet serait le seul du jeu a qualifier
     * les quinze. La branche existe pour un geste a venir ; ce test verifie
     * qu'aucun de ceux qui sont la ne l'emprunte.
     */
    public function testNoDeliveredGestureHasAnUnreadableIntent(): void
    {
        $mute = [];

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            if ($this->intentOf($spell) === null) {
                $mute[] = (string) $spell->getSlug();
            }
        }

        sort($mute);
        self::assertSame([], $mute, 'Un geste ne dit pas ce qu\'il fait : il echapperait a la borne d\'intention.');
    }

    /**
     * Les quatre fonctions sont peuplees — sinon la grille n'a que trois axes.
     */
    public function testEveryFunctionHasTrees(): void
    {
        $byRole = [];
        foreach ($this->combatDomains() as $domain) {
            $role = $domain->getRole();
            if ($role !== null) {
                $byRole[$role->value] = ($byRole[$role->value] ?? 0) + 1;
            }
        }

        foreach (DomainRole::cases() as $role) {
            self::assertGreaterThan(0, $byRole[$role->value] ?? 0, $role->value);
        }
    }

    /**
     * Ce que les accords d'un arbre comptent, dans le vocabulaire des palettes.
     *
     * Les cles ne sont pas toutes des intentions : `heal_or_protect` en lit
     * deux ensemble et `group_scoped` lit une **portee**. C'est voulu — le
     * canon formule ses exigences ainsi (« deux soins ou protections, dont un
     * de portee le groupe »), et les eclater en cles atomiques obligerait le
     * fichier de configuration a dire deux fois ce que la palette dit une fois.
     *
     * @return array<string, int>
     */
    private function paletteCountsOf(Domain $domain): array
    {
        $counts = [];

        foreach ($this->accordSpellsOf($domain) as $spell) {
            $intent = $this->intentOf($spell);
            if ($intent === null) {
                continue;
            }

            $counts[$intent->value] = ($counts[$intent->value] ?? 0) + 1;

            if ($intent === SpellIntent::Protection) {
                $counts['protect'] = ($counts['protect'] ?? 0) + 1;
            }
            if ($intent === SpellIntent::Heal || $intent === SpellIntent::Protection) {
                $counts['heal_or_protect'] = ($counts['heal_or_protect'] ?? 0) + 1;
            }

            $scope = $this->scopeOf($spell);
            if ($scope === SpellScope::Group) {
                $counts['group_scoped'] = ($counts['group_scoped'] ?? 0) + 1;
            }
            if ($scope === SpellScope::Group || $scope === SpellScope::Ally) {
                $counts['ally_or_group_scoped'] = ($counts['ally_or_group_scoped'] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return list<SpellIntent>
     */
    private function intentsOpenedBy(Domain $domain): array
    {
        $intents = [];

        foreach ($this->accordSpellsOf($domain) as $spell) {
            $intent = $this->intentOf($spell);
            if ($intent !== null && !\in_array($intent, $intents, true)) {
                $intents[] = $intent;
            }
        }

        return $intents;
    }

    private function intentOf(Spell $spell): ?SpellIntent
    {
        return $spell->resolveIntent($this->statusTypeOf($spell));
    }

    private function scopeOf(Spell $spell): ?SpellScope
    {
        return $spell->resolveScope($this->statusTypeOf($spell));
    }

    private function statusTypeOf(Spell $spell): ?string
    {
        $slug = $spell->getStatusEffectSlug();
        if ($slug === null) {
            return null;
        }

        $effect = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug]);

        return $effect?->getType();
    }

    /**
     * Les gestes que les accords de cet arbre ouvrent, quel qu'en soit le prix.
     *
     * Tous les accords, pas seulement les deux d'entree : la palette borne ce
     * que l'arbre **enseigne**, et un geste appris au sommet compte autant
     * qu'un geste du jour 1 pour dire ce qu'un archetype sait faire.
     *
     * @return list<Spell>
     */
    private function accordSpellsOf(Domain $domain): array
    {
        $spells = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if (!$skill->getDomains()->contains($domain)) {
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
