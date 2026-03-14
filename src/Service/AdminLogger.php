<?php

namespace App\Service;

use App\Entity\App\AdminLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function log(string $action, string $entityType, ?int $entityId = null, ?string $entityLabel = null, ?array $details = null): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $log = new AdminLog();
        $log->setAdminUser($user);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setEntityLabel($entityLabel);
        $log->setDetails($details);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}
