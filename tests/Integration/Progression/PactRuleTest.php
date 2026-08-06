<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Enum\DomainRole;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\DomainRoleDefinitionLoader;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\PactRule;
use App\GameEngine\Progression\SkillLeverReader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Les regles du pacte qui portent sur l'arbre (ARC-15).
 *
 * GAME_ARCHETYPES § 6.5. Le nœud sait dire son cran et son poids ; le reste ne
 * se voit qu'en regardant l'arbre entier, et c'est la partie facile a
 * degenerer — un pacte de plus, un malus dans la palette, ou un pacte pose sur
 * le chemin du capstone suffisent a vider la mecanique de son sens.
 */
class PactRuleTest extends AbstractIntegrationTestCase
{
    /**
     * Aucun arbre livre ne porte de pacte — et c'est normal.
     *
     * Le jalon pose la grammaire **avant** qu'il y ait quoi que ce soit a
     * relire, comme ARC-12a l'avait fait pour les conditions. Ce test dit que
     * la regle s'applique aux 24 arbres sans en accuser aucun, et il devient
     * mordant le jour ou ARC-07/08 ecrit le premier pacte.
     */
    public function testNoDeliveredTreeBreaksThePactRules(): void
    {
        $rule = $this->rule();
        $violations = [];

        foreach ($this->combatDomains() as $domain) {
            foreach ($rule->violationsOf($domain, $this->skillsOf($domain)) as $violation) {
                $violations[] = $violation;
            }
        }

        self::assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * Regle 1 — un seul pacte par arbre.
     *
     * *C'est une signature, pas un outil.* Deux pactes ne feraient pas un
     * archetype deux fois plus tranche, ils feraient un arbre qui achete sa
     * puissance en morceaux.
     */
    public function testASecondPactInATreeIsRefused(): void
    {
        $rule = $this->rule();
        $domain = $this->assaultDomain();

        $violations = $rule->violationsOf($domain, [
            $this->pactNode('pacte-un', 'power', 'life'),
            // Un second pacte majeur ne tient que sur un levier plafonne a 20 —
            // le premier refus serait sinon celui du plafond, pas celui de la
            // regle qu'on veut eprouver ici.
            $this->pactNode('pacte-deux', 'mending', 'guard'),
        ]);

        self::assertNotEmpty($violations);
        self::assertStringContainsString('2 pactes', implode("\n", $violations));
    }

    /**
     * Regle 2 — le malus est hors de la palette de la fonction.
     *
     * *L'assaut paie en survie, l'encaisse paie en degats.* Un assaut qui
     * paierait en `pierce` — un de ses propres leviers — echangerait de la
     * monnaie contre elle-meme, et ne renoncerait a rien.
     */
    public function testAMalusInsideTheFunctionPaletteIsRefused(): void
    {
        $rule = $this->rule();

        $violations = $rule->violationsOf($this->assaultDomain(), [
            $this->pactNode('pacte-monnaie', 'power', 'pierce'),
        ]);

        self::assertNotEmpty($violations);
        self::assertStringContainsString('palette', implode("\n", $violations));
    }

    /**
     * Regle 2, l'autre cote — un malus hors palette passe.
     *
     * Le test qui empeche la regle 2 d'etre un refus general : l'assaut peut
     * payer en `life`, qui n'est pas dans sa palette.
     */
    public function testAMalusOutsideTheFunctionPaletteIsAccepted(): void
    {
        $rule = $this->rule();

        $violations = $rule->violationsOf($this->assaultDomain(), [
            $this->pactNode('pacte-sang', 'power', 'life'),
        ]);

        self::assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * Regle 4 — le nœud de pacte est une feuille.
     *
     * *Un arbre ou le pacte est sur le chemin du capstone n'offre pas un
     * choix, il pose un peage.*
     */
    public function testAPactRequiredByAnotherNodeIsRefused(): void
    {
        $rule = $this->rule();
        $pact = $this->pactNode('pacte-peage', 'power', 'life');

        $downstream = new Skill();
        $downstream->setSlug('capstone');
        $downstream->addRequirement($pact);

        $violations = $rule->violationsOf($this->assaultDomain(), [$pact, $downstream]);

        self::assertNotEmpty($violations);
        self::assertStringContainsString('peage', implode("\n", $violations));
    }

    /**
     * Le juge, construit a la main.
     *
     * `PactRule` n'est utilise que par ce contrat : le conteneur l'inline, et
     * le rendre public pour un test le ferait exister en production sans
     * raison. Ses deux dependances se construisent depuis la racine du projet.
     */
    private function rule(): PactRule
    {
        $root = \dirname(__DIR__, 3);

        return new PactRule(
            new SkillLeverReader(
                new CombatLeverScale(new CombatLeverDefinitionLoader($root)),
                new EquipmentPortCatalog($root),
            ),
            new DomainRoleDefinitionLoader($root),
        );
    }

    private function pactNode(string $slug, string $lever, string $malus): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setLevers([['lever' => $lever, 'points' => 19, 'pact' => ['lever' => $malus, 'points' => 10]]]);

        return $skill;
    }

    private function assaultDomain(): Domain
    {
        foreach ($this->combatDomains() as $domain) {
            if ($domain->getRole() === DomainRole::Assault) {
                return $domain;
            }
        }

        self::fail('Aucun arbre d\'assaut : la grille des 24 a change.');
    }

    /**
     * @return list<Skill>
     */
    private function skillsOf(Domain $domain): array
    {
        return array_values(array_filter(
            $this->em->getRepository(Skill::class)->findAll(),
            fn (Skill $skill) => $skill->getDomains()->contains($domain),
        ));
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
