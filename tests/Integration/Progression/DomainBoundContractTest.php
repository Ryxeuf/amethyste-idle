<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Enum\Element;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La double borne, sans fuite (DOM-09).
 *
 * DOM-01 borne un passif par la case `element x registre` de son domaine — mais
 * `CombatSkillResolver::skillAppliesTo()` traite un nœud dont **aucun** domaine
 * n'est un domaine de combat comme **non borne**, donc applicable a *toute*
 * action. C'est une clause de retro-compatibilite, et elle etait exploitee sans
 * qu'on le sache : huit nœuds partages entre metiers portaient des statistiques
 * de combat et les distribuaient partout, hors des 50 points de budget et hors
 * des plafonds par levier.
 *
 * **Ce test dit ce que l'ancien ne disait pas.** `DomainPlanContractTest` lit le
 * fichier de fixtures et verifie qu'un nœud a *un domaine* ; un nœud rattache a
 * quatre metiers le satisfaisait pleinement tout en fuyant. La question juste
 * n'est pas « a-t-il un domaine ? » mais ***son domaine borne-t-il quelque
 * chose ?*** — et elle se lit sur la base, jamais sur un texte source.
 */
class DomainBoundContractTest extends AbstractIntegrationTestCase
{
    /**
     * Tout nœud qui porte une statistique de combat appartient a au moins un
     * domaine **de combat**.
     *
     * Sans cela, il traverse la double borne : il parle sur les gestes de tous
     * les elements et de tous les registres, y compris ceux que son arbre
     * n'enseigne pas.
     */
    public function testEveryCombatStatIsBoundByACombatDomain(): void
    {
        $leaks = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if (!$this->carriesCombatStats($skill)) {
                continue;
            }

            foreach ($skill->getDomains() as $domain) {
                if ($domain->isCombatDomain()) {
                    continue 2;
                }
            }

            $leaks[] = sprintf(
                '%s (%s)',
                $skill->getSlug(),
                implode(', ', array_map(static fn ($d) => $d->getTitle(), $skill->getDomains()->toArray())) ?: 'aucun domaine',
            );
        }

        sort($leaks);
        self::assertSame(
            [],
            $leaks,
            "Ces nœuds portent des statistiques de combat qu'aucun domaine de combat ne borne — "
            . "elles s'appliquent donc a toute action, hors budget et hors plafond :\n" . implode("\n", $leaks),
        );
    }

    /**
     * Aucun nœud n'appartient **exclusivement** a des domaines de metier tout en
     * portant des leviers de combat (ARC-03).
     *
     * Le pendant du precedent pour le vocabulaire d'apres ARC : un levier fuit
     * exactement comme une statistique plate, et il fuit *avec son plafond*.
     */
    public function testNoCraftOnlyNodeCarriesCombatLevers(): void
    {
        $leaks = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if ($skill->getLevers() === []) {
                continue;
            }

            foreach ($skill->getDomains() as $domain) {
                if ($domain->isCombatDomain()) {
                    continue 2;
                }
            }

            $leaks[] = $skill->getSlug();
        }

        sort($leaks);
        self::assertSame([], $leaks, "Ces nœuds accordent des leviers de combat depuis un arbre de metier :\n" . implode("\n", $leaks));
    }

    /**
     * L'element d'un domaine de **combat** appartient a l'enumeration — et lui
     * seul (DOM-09, arbitrage rendu).
     *
     * `Domain::element` est une chaine libre, donc `wood` y entre comme une
     * faute de frappe y entrerait. Le canon annoncait que l'enum « devra tolerer
     * les elements composes » ; la mesure dit l'inverse pour `wood` :
     * `Element::cases()` est parcouru par le **butin de materia**, par les
     * **huit marques** et par la **loi de nommage** des zones. Ajouter un
     * neuvieme cas creerait des materia de bois, une marque de bois et une loi
     * de nommage pour un element qu'**aucun domaine de combat ne porte** —
     * exactement la neuvieme case que le § 9 quater interdit.
     *
     * `wood` reste donc ce qu'il est : la **teinte** de deux metiers (bucheron,
     * charpentier), au meme titre que `water` teinte le pecheur. Ce que ce test
     * ferme, c'est la seule chose qui comptait : *une teinte de metier n'entre
     * jamais dans une case de combat*.
     */
    public function testOnlyCombatDomainsCarryAnElementOfTheEnum(): void
    {
        $offenders = [];
        $craftTints = [];

        foreach ($this->em->getRepository(Domain::class)->findAll() as $domain) {
            $element = $domain->getElement();
            if ($element === null) {
                continue;
            }

            if ($domain->isCombatDomain()) {
                if (Element::tryFrom($element) === null) {
                    $offenders[] = sprintf('%s porte « %s »', $domain->getTitle(), $element);
                }

                continue;
            }

            if (Element::tryFrom($element) === null) {
                $craftTints[] = $element;
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Un domaine de combat porte un element hors enumeration : sa case n'existe pas.\n" . implode("\n", $offenders),
        );

        // Le cliquet de l'arbitrage : `wood` est la seule teinte hors enum, et
        // elle ne concerne que des metiers. Une seconde teinte inventee se
        // signalerait ici plutot que de vivre en base sans que rien ne la lise.
        self::assertSame(['wood'], array_values(array_unique($craftTints)));
    }

    /**
     * Ce que la fermeture de la fuite a laisse derriere elle (DOM-09).
     *
     * Retirer les statistiques de combat de 55 nœuds de metier en laisse
     * certains **sans effet**. La plupart restent legitimes : ils servent de
     * prerequis, donc ce sont des **portes** — exactement ce que sont les
     * echelons de port, et *une porte n'est jamais une recompense*.
     *
     * Neuf ne menent nulle part et ne font rien : ce sont des **peages vides**,
     * et les nommer vaut mieux que de les laisser passer pour un choix. Ils
     * attendent **MET-03**, qui ecrit le vocabulaire des neuf leviers de metier
     * — le seul dans lequel un nœud d'artisanat puisse porter un effet sans
     * rouvrir la fuite.
     *
     * Cliquet : la liste peut retrecir, jamais grandir.
     *
     * @var list<string>
     */
    private const AWAITING_MET_03 = [
        'alchi-purity',
        'carpenter-signature',
        'carpenter-tuning',
        'cook-master',
        'herbalist-preservation',
        'lumber-knotless',
        'miner-master',
        'tailor-dye',
        'tailor-signature',
    ];

    public function testTheEmptiedCraftNodesAreNamedNotHidden(): void
    {
        $deadEnds = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if ($skill->getRequiredPoints() === 0 || $skill->getActions() !== null || $this->carriesCombatStats($skill)) {
                continue;
            }

            $isCraft = false;
            foreach ($skill->getDomains() as $domain) {
                if ($domain->isCombatDomain()) {
                    $isCraft = false;
                    break;
                }
                $isCraft = true;
            }

            if (!$isCraft || $this->opensSomething($skill)) {
                continue;
            }

            $deadEnds[] = $skill->getSlug();
        }

        sort($deadEnds);
        self::assertSame(
            self::AWAITING_MET_03,
            $deadEnds,
            'La liste des peages vides a bouge. Elle peut retrecir — jamais grandir.',
        );
    }

    /**
     * Ce nœud sert-il de prerequis a un autre ? Alors c'est une **porte**.
     */
    private function opensSomething(Skill $skill): bool
    {
        return $this->em->getRepository(Skill::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.requirements', 'r')
            ->where('r.id = :id')
            ->setParameter('id', $skill->getId())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    private function carriesCombatStats(Skill $skill): bool
    {
        return $skill->getDamage() > 0
            || $skill->getHeal() > 0
            || $skill->getHit() > 0
            || $skill->getCritical() > 0
            || $skill->getLife() > 0;
    }
}
