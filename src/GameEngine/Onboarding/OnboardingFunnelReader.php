<?php

declare(strict_types=1);

namespace App\GameEngine\Onboarding;

use App\Entity\App\Player;
use App\Entity\App\PlayerDomainAccess;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Lecture des sept indicateurs du tunnel (ONB-19b).
 *
 * **Que des agregats.** Aucune de ces requetes ne remonte de ligne : ce sont
 * des `COUNT` et des `GROUP BY`, ce qui rend l'ecran d'administration
 * indifferent au nombre de comptes. Un ecran de pilotage qui ralentit avec le
 * succes du jeu cesse d'etre consulte exactement quand il devient utile.
 *
 * **Le retour se mesure par l'ecart entre deux dates**, pas par un historique
 * qui n'existe pas. `Player::lastActivityAt` (FOY-17) ne retient que la
 * derniere activite : on ne peut donc pas dire « revenu le lendemain », mais on
 * peut dire **« encore actif un jour apres sa creation »**, ce qui est la
 * question que le plan pose. Le libelle de l'ecran dit exactement cela — un
 * indicateur qui promet plus que sa donnee est pire que pas d'indicateur.
 */
class OnboardingFunnelReader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function read(?\DateTimeImmutable $now = null): OnboardingFunnel
    {
        $now ??= new \DateTimeImmutable();

        $stillActive = [];
        foreach (OnboardingFunnel::RETURN_DAYS as $days) {
            $stillActive[$days] = $this->countStillActiveAfter($days);
        }

        return new OnboardingFunnel(
            accounts: $this->countAll(User::class),
            accountsWithCharacter: $this->countAccountsWithCharacter(),
            characters: $this->countAll(Player::class),
            actOneCompleted: $this->countCharactersWhere('p.homeZoneClaimedAt IS NOT NULL'),
            onboardingSkipped: $this->countCharactersWhere('p.onboardingSkippedAt IS NOT NULL'),
            matureAccounts: $this->countMatureAccounts($now),
            verifiedAmongMature: $this->countMatureAccounts($now, verifiedOnly: true),
            stillActive: $stillActive,
            races: $this->countCharactersByRace(),
            weapons: $this->countOpenedTreesByRegister(),
            elements: $this->countOpenedTreesByElement(),
            crafts: $this->countOpenedCraftTrees(),
        );
    }

    /**
     * @param class-string $entity
     */
    private function countAll(string $entity): int
    {
        return $this->entityManager->getRepository($entity)->count([]);
    }

    private function countAccountsWithCharacter(): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(DISTINCT p.user) FROM ' . Player::class . ' p',
        )->getSingleScalarResult();
    }

    private function countCharactersWhere(string $condition): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(p.id) FROM ' . Player::class . ' p WHERE ' . $condition,
        )->getSingleScalarResult();
    }

    /**
     * Comptes assez vieux pour qu'on ait le droit de juger leur J+7.
     *
     * Un compte cree hier ne peut pas avoir verifie son e-mail « dans les sept
     * jours » : l'inclure dans le denominateur ferait baisser le taux a chaque
     * inscription, c'est-a-dire exactement quand le jeu va bien.
     */
    private function countMatureAccounts(\DateTimeImmutable $now, bool $verifiedOnly = false): int
    {
        $dql = 'SELECT COUNT(u.id) FROM ' . User::class . ' u WHERE u.createdAt <= :threshold';
        if ($verifiedOnly) {
            $dql .= ' AND u.emailVerifiedAt IS NOT NULL';
        }

        return (int) $this->entityManager->createQuery($dql)
            ->setParameter('threshold', $now->modify('-7 days'))
            ->getSingleScalarResult();
    }

    /**
     * Personnages dont l'activite s'est prolongee au-dela de N jours.
     *
     * La comparaison se fait entre deux colonnes du **meme enregistrement**,
     * donc sans parametre de date : ce qui est mesure est la duree de vie du
     * personnage, pas sa position dans le calendrier. Un pic d'inscriptions ne
     * deplace donc pas la mesure.
     */
    private function countStillActiveAfter(int $days): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(p.id) FROM ' . Player::class . ' p '
            . 'WHERE p.lastActivityAt IS NOT NULL '
            . 'AND DATE_DIFF(p.lastActivityAt, p.createdAt) >= :days',
        )
            ->setParameter('days', $days)
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    private function countCharactersByRace(): array
    {
        return $this->pairs(
            'SELECT r.slug AS label, COUNT(p.id) AS total FROM ' . Player::class . ' p '
            . 'JOIN p.race r GROUP BY r.slug ORDER BY total DESC',
        );
    }

    /**
     * L'arme choisie, lue par le **registre** du domaine ouvert.
     *
     * @return array<string, int>
     */
    private function countOpenedTreesByRegister(): array
    {
        return $this->pairs(
            'SELECT d.register AS label, COUNT(a.id) AS total FROM ' . PlayerDomainAccess::class . ' a '
            . 'JOIN a.domain d WHERE d.register IS NOT NULL GROUP BY d.register ORDER BY total DESC',
        );
    }

    /**
     * @return array<string, int>
     */
    private function countOpenedTreesByElement(): array
    {
        return $this->pairs(
            'SELECT d.element AS label, COUNT(a.id) AS total FROM ' . PlayerDomainAccess::class . ' a '
            . 'JOIN a.domain d WHERE d.element IS NOT NULL GROUP BY d.element ORDER BY total DESC',
        );
    }

    /**
     * Le metier choisi : un arbre ouvert **sans registre**.
     *
     * L'absence de registre est la definition meme du hors-combat (DOM-01,
     * `CombatRegister`) — le metier n'a donc pas besoin d'un marqueur a lui,
     * qu'il faudrait tenir a jour.
     *
     * @return array<string, int>
     */
    private function countOpenedCraftTrees(): array
    {
        return $this->pairs(
            'SELECT d.title AS label, COUNT(a.id) AS total FROM ' . PlayerDomainAccess::class . ' a '
            . 'JOIN a.domain d WHERE d.register IS NULL GROUP BY d.title ORDER BY total DESC',
        );
    }

    /**
     * @return array<string, int>
     */
    private function pairs(string $dql): array
    {
        $counts = [];
        /** @var array<int, array{label: mixed, total: mixed}> $rows */
        $rows = $this->entityManager->createQuery($dql)->getArrayResult();

        foreach ($rows as $row) {
            $label = $row['label'];
            $counts[$label instanceof \BackedEnum ? (string) $label->value : (string) $label] = (int) $row['total'];
        }

        return $counts;
    }
}
