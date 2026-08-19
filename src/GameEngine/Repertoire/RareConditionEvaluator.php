<?php

namespace App\GameEngine\Repertoire;

use App\Enum\Element;
use App\Enum\SettlementDoctrine;
use App\Enum\SettlementRank;
use App\Repository\RepertoireReadingRepository;
use App\Repository\SettlementRepository;

/**
 * Les conditions rares du bassin, evaluees (REP-03).
 *
 * REP-02 les a **declarees** et fermees ; ce service les **repond**. Les deux
 * moities sont separees a dessein : le catalogue doit pouvoir se lire sans base
 * de donnees (ses tests le font), et l'evaluation ne se fait qu'ici.
 *
 * **Aucune ne se force par le jeu d'une personne**, et c'est le critere
 * d'admission d'une condition rare : il faut un foyer que tout un serveur a
 * fait monter, une guilde qui a paye une doctrine, ou un Repertoire nourri des
 * huit elements. *L'exclusivite naît des conditions, pas d'un marquage par
 * serveur* (§ 12.3 b).
 *
 * **Une condition inconnue leve** plutot que de rendre `false` : rendre `false`
 * rendrait son geste inatteignable en silence, ce qui est le pire etat pour un
 * contenu rare — indiscernable d'un contenu qu'on n'a pas encore merite. Le
 * catalogue refuse deja les inconnues a la lecture ; cette levee est la seconde
 * porte, pour l'appelant qui construirait une condition autrement.
 */
class RareConditionEvaluator
{
    public function __construct(
        private readonly SettlementRepository $settlements,
        private readonly RepertoireReadingRepository $readings,
    ) {
    }

    public function isMet(string $condition): bool
    {
        return match ($condition) {
            'metropolis_exists' => $this->hasRank(SettlementRank::Metropolis),
            'readers_doctrine' => $this->hasDoctrine(SettlementDoctrine::Readers),
            'every_element_read' => $this->everyElementRead(),
            default => throw new RepertoireDefinitionException(sprintf('Unknown rare condition "%s": admitted conditions are %s.', $condition, implode(', ', RepertoireCatalog::RARE_CONDITIONS))),
        };
    }

    private function hasRank(SettlementRank $rank): bool
    {
        foreach ($this->settlements->findAll() as $settlement) {
            if ($settlement->getRank()->isAtLeast($rank)) {
                return true;
            }
        }

        return false;
    }

    private function hasDoctrine(SettlementDoctrine $doctrine): bool
    {
        foreach ($this->settlements->findAll() as $settlement) {
            if ($settlement->getDoctrine() === $doctrine) {
                return true;
            }
        }

        return false;
    }

    /**
     * Les huit elements qui marquent figurent au Repertoire.
     *
     * `none` en est exclu : ce n'est pas un element mais son absence, aucune
     * materia ne le porte, et l'exiger rendrait la condition impossible — donc
     * son geste inatteignable pour toujours.
     */
    private function everyElementRead(): bool
    {
        $read = array_keys($this->readings->tallyByElement());

        foreach (Element::cases() as $element) {
            if ($element === Element::None) {
                continue;
            }
            if (!\in_array($element->value, $read, true)) {
                return false;
            }
        }

        return true;
    }
}
