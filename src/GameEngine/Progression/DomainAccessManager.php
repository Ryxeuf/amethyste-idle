<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerDomainAccess;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ouvrir un arbre, et savoir s'il l'est (ONB-08).
 *
 * Le seul endroit qui ecrit dans `player_domain_access`, et le seul qu'on
 * interroge pour repondre « ce personnage peut-il apprendre dans cet arbre ? ».
 *
 * La doctrine que ce service doit tenir, litteralement (GAME_ONBOARDING § 6.3) :
 *
 * - **le parchemin est un cout, jamais un verrou** — rien ici ne consulte le
 *   peuple, la faction, la progression ni un choix anterieur ;
 * - **les 32 sont cumulables** — ouvrir n'a aucun effet de bord sur les autres ;
 * - **l'ouverture est idempotente** — la relire ne cree pas de doublon ;
 * - **les verbes elementaires restent libres** — marcher, voyager, explorer,
 *   parler, ramasser et se battre a mains nues ne passent jamais par ici.
 *
 * Un nœud **partage** entre plusieurs arbres (`Skill::domains` est un
 * ManyToMany) se prend des qu'**un seul** de ses arbres est ouvert : c'est la
 * regle « plusieurs chemins pour la meme chose » de ONB-20b, et elle vaut deja
 * pour l'apprentissage.
 */
class DomainAccessManager
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function isOpen(Player $player, Domain $domain): bool
    {
        return $player->hasOpenedDomain($domain);
    }

    /**
     * Ouvre l'arbre. Retourne `true` si c'est cette lecture qui l'a ouvert.
     *
     * Le booleen n'est pas cosmetique : l'ouverture est un **moment** qu'on
     * notifie (ONB-09), et un parchemin relu ne doit pas rejouer l'annonce.
     */
    public function open(Player $player, Domain $domain): bool
    {
        if ($player->hasOpenedDomain($domain)) {
            return false;
        }

        $access = new PlayerDomainAccess($player, $domain);
        $player->addDomainAccess($access);
        $this->entityManager->persist($access);

        return true;
    }

    /**
     * @return Domain[] les arbres ouverts, dans l'ordre ou ils l'ont ete
     */
    public function openedDomains(Player $player): array
    {
        $domains = [];
        foreach ($player->getDomainAccesses() as $access) {
            $domains[] = $access->getDomain();
        }

        return $domains;
    }

    /**
     * Ce nœud est-il accessible, du seul point de vue de l'ouverture d'arbre ?
     *
     * Une competence **sans domaine** reste apprenable par tout le monde : c'est
     * la frontiere, et elle est volontairement large. Fermer ce cas ferait de la
     * moindre competence transverse un peage, et le jeu deviendrait « une parade
     * de verrous » que le cadrage refuse explicitement.
     */
    public function isSkillReachable(Player $player, Skill $skill): bool
    {
        $domains = $skill->getDomains();
        if (\count($domains) === 0) {
            return true;
        }

        foreach ($domains as $domain) {
            if ($player->hasOpenedDomain($domain)) {
                return true;
            }
        }

        return false;
    }
}
