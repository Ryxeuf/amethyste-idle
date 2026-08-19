<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\DomainSynergy;
use App\Enum\AccointanceForm;
use App\GameEngine\Progression\AccointanceRule;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Une accointance ne donne jamais de puissance (ARC-16).
 *
 * GAME_ARCHETYPES § 9.7, decision 15. Les synergies livrees ajoutaient des
 * statistiques plates dans `CombatSkillResolver`, **hors de tout arbre** — donc
 * hors des 50 points de budget, hors des plafonds par levier, hors des palettes
 * de fonction.
 *
 * > *Un systeme qui compte soigneusement 50 points et laisse une porte de
 * > service a +10 ne compte rien.*
 *
 * Le canon donne trois regles ; les voici en trois tests, plus celui qui ferme
 * la porte pour de bon.
 */
class AccointanceContractTest extends AbstractIntegrationTestCase
{
    /**
     * **La porte de service est murée** — l'invariant qui distingue une
     * accointance d'un bonus deguise, et le canon le voulait « testable en une
     * ligne ».
     *
     * On l'ecrit sur le **type** plutot que sur les donnees : tant que la forme
     * est une valeur d'une enumeration fermee, aucune accointance ne peut
     * porter un chiffre — il n'y a pas de champ ou l'ecrire. C'est plus fort
     * qu'une verification de valeurs, qui ne dirait rien du jour ou quelqu'un
     * rajoute une colonne.
     */
    public function testNoAccointanceCanCarryAStatAtAll(): void
    {
        $properties = array_map(
            fn (\ReflectionProperty $property) => $property->getName(),
            (new \ReflectionClass(DomainSynergy::class))->getProperties(),
        );

        foreach (['bonusType', 'bonusValue', 'damage', 'heal', 'hit', 'critical', 'life', 'levers'] as $forbidden) {
            self::assertNotContains(
                $forbidden,
                $properties,
                sprintf('Une accointance porte « %s » : c\'est un bonus deguise, pas une accointance.', $forbidden),
            );
        }
    }

    /**
     * **Le moteur de combat n'additionne plus rien d'elles.**.
     *
     * L'invariant precedent dit qu'une accointance n'a pas de chiffre a donner ;
     * celui-ci dit que le combat ne va pas en chercher un. Les deux ensemble
     * ferment la fuite des deux cotes — la donnee et le lecteur.
     */
    public function testTheCombatResolverNoLongerReadsAccointances(): void
    {
        $source = file_get_contents(\dirname(__DIR__, 3) . '/src/GameEngine/Fight/CombatSkillResolver.php');
        self::assertIsString($source);

        self::assertStringNotContainsString(
            'SynergyCalculator',
            $source,
            'Le resolveur de combat lit a nouveau les accointances : c\'est exactement la porte de service que la decision 15 ferme.',
        );
    }

    /**
     * **Une accointance par paire** (regle 1), et l'ordre des deux domaines ne
     * doit pas servir a en glisser une seconde.
     *
     * La contrainte d'unicite du schema porte sur `(domain_a, domain_b)` : elle
     * laisserait passer la paire inversee, qui serait deux accointances pour un
     * seul melange.
     */
    public function testOneAccointancePerPairWhicheverWayRound(): void
    {
        $seen = [];

        foreach ($this->em->getRepository(DomainSynergy::class)->findAll() as $synergy) {
            $ids = [$synergy->getDomainA()->getId(), $synergy->getDomainB()->getId()];
            sort($ids);
            $key = implode('-', $ids);

            self::assertArrayNotHasKey(
                $key,
                $seen,
                sprintf('La paire %s porte deux accointances : « %s » et « %s ».', $key, $seen[$key] ?? '', $synergy->getName()),
            );
            $seen[$key] = $synergy->getName();
        }

        self::assertNotSame([], $seen, 'Aucune accointance chargee : le contrat ne mesure rien.');
    }

    /**
     * Aucune accointance n'est ecrite dans une forme que personne ne lit.
     *
     * **Ce n'est pas une regle du canon, c'est un garde-fou d'honnetete.**
     * ARC-16b a branche les trois lecteurs qui manquaient
     * (`BuildConditionEvaluator`, `SlotAcceptanceWidener`,
     * `PortAccessDiscount`) : les quatre formes se lisent. Le test reste —
     * une cinquieme forme n'entrerait pas sans lecteur, et le supprimer
     * laisserait croire que la question ne s'est jamais posee.
     */
    public function testNoAccointanceIsWrittenInAFormNobodyReads(): void
    {
        foreach ($this->em->getRepository(DomainSynergy::class)->findAll() as $synergy) {
            self::assertTrue(
                $synergy->getForm()->hasReader(),
                sprintf('« %s » est de forme %s, que rien ne lit : elle serait inerte sans le dire.', $synergy->getName(), $synergy->getForm()->value),
            );
        }
    }

    /**
     * La grammaire de sujet tient sur toutes les lignes chargees (ARC-16b).
     *
     * Une famille mal orthographiee laisserait sa remise silencieusement morte,
     * un elargissement hors ligne promettrait a l'ecran ce que l'evaluateur ne
     * rendra jamais. La regle est la meme qu'aux fixtures — c'est le meme
     * service —, et le contrat la rejoue sur la base reelle.
     */
    public function testEverySubjectHoldsItsFormGrammar(): void
    {
        $rule = new AccointanceRule(new EquipmentPortCatalog(\dirname(__DIR__, 3)));

        foreach ($this->em->getRepository(DomainSynergy::class)->findAll() as $synergy) {
            self::assertSame([], $rule->failuresOf($synergy));
        }
    }

    /**
     * Les quatre formes existent en donnees — chacune avec son exemple du
     * canon, maintenant que son lecteur existe.
     *
     * Sans cette ligne, ARC-16b aurait livre trois lecteurs que rien
     * n'exerce : des accointances toutes `domain_expression` laisseraient les
     * trois formes neuves aussi inertes qu'avant, en silence.
     */
    public function testEachOfTheFourFormsIsExercisedByTheData(): void
    {
        $present = [];
        foreach ($this->em->getRepository(DomainSynergy::class)->findAll() as $synergy) {
            $present[$synergy->getForm()->value] = true;
        }
        ksort($present);

        self::assertSame(
            ['access_discount', 'condition_widening', 'domain_expression', 'slot_acceptance'],
            array_keys($present),
        );
    }

    /**
     * Les quatre formes du canon, et elles seules.
     *
     * Le cliquet : la liste peut voir ses lecteurs arriver (ARC-16b), jamais
     * s'allonger d'une cinquieme forme — c'est ainsi qu'un vocabulaire ferme
     * cesse d'etre une intention.
     */
    public function testTheVocabularyIsExactlyTheCanonFour(): void
    {
        self::assertSame(
            ['access_discount', 'condition_widening', 'domain_expression', 'slot_acceptance'],
            $this->sortedFormValues(),
        );
    }

    /**
     * @return list<string>
     */
    private function sortedFormValues(): array
    {
        $values = array_map(fn (AccointanceForm $form) => $form->value, AccointanceForm::cases());
        sort($values);

        return $values;
    }
}
