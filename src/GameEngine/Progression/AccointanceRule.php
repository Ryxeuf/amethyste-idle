<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\DomainSynergy;

/**
 * Ce qu'une accointance a le droit de declarer (ARC-16b).
 *
 * `AccointanceForm` ferme le vocabulaire des formes ; cette regle ferme leur
 * **grammaire de sujet** — la meme discipline en deux temps que `CombatLever`
 * puis `SkillLeverReader`. Refuser a la lecture vaut mieux qu'une revue de
 * code : une famille mal orthographiee (`subject: bwo`) laisserait sa remise
 * silencieusement morte, et un elargissement vers une famille inconnue serait
 * une promesse d'ecran que rien ne tient.
 *
 * Quatre grammaires, une par forme :
 *
 * - `domain_expression`, `slot_acceptance` — **aucun sujet** : leur payload EST
 *   la paire, et un sujet qu'aucun lecteur ne lit serait un mensonge de donnees ;
 * - `access_discount` — le sujet nomme une **famille de l'echelle de port**, et
 *   rien d'autre : la remise est fixe par la regle, il n'y a pas de nombre ;
 * - `condition_widening` — le sujet et l'elargissement sont **deux conditions de
 *   build a famille**, de la **meme ligne** (un cuir s'elargit a une plaque,
 *   jamais a une epee) et **distinctes** (s'elargir a soi-meme ne dirait rien).
 */
class AccointanceRule
{
    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    /**
     * Les manquements de cette accointance — vide quand elle est legale.
     *
     * Rendre la liste plutot que lever suit `PactRule` : les fixtures levent au
     * premier manquement, le contrat les veut tous.
     *
     * @return list<string>
     */
    public function failuresOf(DomainSynergy $synergy): array
    {
        $form = $synergy->getForm();
        $subject = $synergy->getSubject();
        $widenedBy = $synergy->getWidenedBy();
        $failures = [];

        if (!$form->needsSubject()) {
            if ($subject !== null) {
                $failures[] = sprintf('« %s » (%s) porte un sujet « %s » que sa forme ne lit pas : la paire est son seul payload.', $synergy->getName(), $form->value, $subject);
            }
            if ($widenedBy !== null) {
                $failures[] = sprintf('« %s » (%s) porte un elargissement « %s » que sa forme ne lit pas.', $synergy->getName(), $form->value, $widenedBy);
            }

            return $failures;
        }

        if ($subject === null) {
            return [sprintf('« %s » (%s) ne nomme pas son sujet : le lecteur n\'aurait rien a lire.', $synergy->getName(), $form->value)];
        }

        if (!$form->needsWidenedBy()) {
            if ($widenedBy !== null) {
                $failures[] = sprintf('« %s » (%s) porte un elargissement « %s » que sa forme ne lit pas.', $synergy->getName(), $form->value, $widenedBy);
            }

            if (!isset($this->portCatalog->families()[$subject])) {
                $failures[] = sprintf('« %s » remise la famille « %s », que l\'echelle de port ne connait pas. Familles connues : %s.', $synergy->getName(), $subject, implode(', ', array_keys($this->portCatalog->families())));
            }

            return $failures;
        }

        if ($widenedBy === null) {
            return [sprintf('« %s » elargit « %s » vers rien : un elargissement nomme ce qui satisfait desormais aussi.', $synergy->getName(), $subject)];
        }

        $subjectCondition = $this->parseFamilyCondition($synergy, $subject, $failures);
        $widenedByCondition = $this->parseFamilyCondition($synergy, $widenedBy, $failures);
        if ($subjectCondition === null || $widenedByCondition === null) {
            return $failures;
        }

        if ($subject === $widenedBy) {
            $failures[] = sprintf('« %s » elargit « %s » a lui-meme : cela ne dit rien.', $synergy->getName(), $subject);
        }

        $families = $this->portCatalog->families();
        $subjectLine = $families[$subjectCondition->subject]['line'] ?? null;
        $widenedLine = $families[$widenedByCondition->subject]['line'] ?? null;
        if ($subjectLine !== null && $widenedLine !== null && $subjectLine !== $widenedLine) {
            $failures[] = sprintf('« %s » elargit « %s » (%s) par « %s » (%s) : un elargissement reste sur sa ligne — un cuir s\'elargit a une plaque, jamais a une epee.', $synergy->getName(), $subject, $subjectLine, $widenedBy, $widenedLine);
        }

        return $failures;
    }

    /**
     * Une condition de build **a famille**, ou un manquement de plus.
     *
     * Les conditions sans sujet (`shield`, `dual_wield`, `offhand_free`) sont
     * refusees ici : elargir « bouclier » n'aurait pas de famille a comparer, et
     * le canon n'ecrit l'elargissement que sur des familles.
     *
     * @param list<string> $failures
     */
    private function parseFamilyCondition(DomainSynergy $synergy, string $raw, array &$failures): ?SkillCondition
    {
        try {
            $condition = SkillCondition::parse($raw);
        } catch (CombatLeverDefinitionException $e) {
            $failures[] = sprintf('« %s » : %s', $synergy->getName(), $e->getMessage());

            return null;
        }

        if (!$condition->isBuild() || $condition->subject === null) {
            $failures[] = sprintf('« %s » : « %s » n\'est pas une condition de build a famille — l\'elargissement ne parle que de familles.', $synergy->getName(), $raw);

            return null;
        }

        if (!isset($this->portCatalog->families()[$condition->subject])) {
            $failures[] = sprintf('« %s » : « %s » nomme une famille que l\'echelle de port ne connait pas.', $synergy->getName(), $raw);

            return null;
        }

        return $condition;
    }
}
