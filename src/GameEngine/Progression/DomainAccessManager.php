<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerDomainAccess;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

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
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
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

        // ONB-09 — l'ouverture est **notifiee**. Un arbre qui apparaitrait
        // simplement dans un menu se lirait comme un changement d'interface,
        // alors que c'est le seul moment de la boucle *parchemin -> arbre ->
        // geste* ou quelque chose se gagne.
        $this->announce($player, $domain);

        return true;
    }

    private function announce(Player $player, Domain $domain): void
    {
        // Une annonce ratee ne doit jamais annuler une ouverture : le joueur a
        // consomme son parchemin, et perdre l'arbre pour un hub injoignable
        // serait un vol.
        try {
            $this->notificationService->notify(
                $player,
                'domain_opened',
                'Un arbre s\'ouvre',
                sprintf('Vous avez déchiffré la voie du %s. Ce qu\'on y apprend reste à apprendre.', $domain->getTitle()),
                '📜',
                '/game/skills',
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Ouverture de domaine non annoncee : {error}', [
                'error' => $e->getMessage(),
                'player' => $player->getId(),
                'domain' => $domain->getSlug(),
            ]);
        }
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
