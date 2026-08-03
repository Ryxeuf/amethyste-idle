<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les rappels de verification : J+1, J+3, puis silence (ONB-04).
 *
 * Deux relances et pas une de plus — GAME_ONBOARDING §3.2 : « une ligne au
 * hub (jamais une modale), e-mail a J+1 et J+3, puis silence ». Le compteur
 * vit sur le compte ; chaque rappel regenere un jeton frais (le precedent,
 * 48 h, peut avoir expire entre-temps).
 *
 * Le service est idempotent a l'echelle du jour : rejouer la commande ne
 * renvoie rien a qui a deja recu son rappel du palier — c'est le compteur
 * qui l'assure, pas l'horaire d'execution.
 */
class VerificationReminder
{
    /**
     * Les paliers : au moins N jours d'age de compte, pour le rappel n° X.
     * Lu dans l'ordre — un compte de 5 jours jamais rappele recoit le rappel
     * n° 1, pas les deux d'un coup : le second partira au passage suivant.
     *
     * @var array<int, int> numero de rappel => age minimal en jours
     */
    private const STAGES = [1 => 1, 2 => 3];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerificationManager $verificationManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Envoie les rappels dus et rend le compte par palier.
     *
     * @return array<int, int> numero de rappel => envois effectues
     */
    public function remindDueAccounts(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $sent = [1 => 0, 2 => 0];

        foreach (self::STAGES as $stage => $minAgeDays) {
            $threshold = $now->modify(sprintf('-%d days', $minAgeDays));
            $due = $this->userRepository->findDueForVerificationReminder($stage, $threshold);

            foreach ($due as $user) {
                $this->verificationManager->sendVerification($user, reminder: true);
                $user->setVerificationReminderCount($stage);
                $this->entityManager->persist($user);
                ++$sent[$stage];
            }
        }

        if ($sent[1] > 0 || $sent[2] > 0) {
            $this->entityManager->flush();
        }

        return $sent;
    }
}
