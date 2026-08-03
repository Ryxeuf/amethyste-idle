<?php

namespace App\Entity;

use App\Repository\EmailVerificationRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un jeton de verification d'e-mail en attente de clic (ONB-04).
 *
 * Meme anatomie que la demande de mot de passe oublie (ONB-02), et pour les
 * memes raisons : un seul jeton actif par compte (index unique sur `user_id`,
 * le renvoi remplace), le jeton stocke hache (seul sha256 du verificateur en
 * base), l'expiration lue a la validation. La duree est plus longue — 48 h
 * contre 1 h — parce que la verification n'ouvre pas la porte du compte,
 * seulement celles du marche et du social : l'enjeu d'un vol de jeton est
 * moindre, et l'e-mail peut attendre le retour de week-end.
 */
#[ORM\Entity(repositoryClass: EmailVerificationRequestRepository::class)]
#[ORM\Table(name: 'email_verification_requests')]
class EmailVerificationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 24, unique: true)]
    private string $selector = '';

    /**
     * hash('sha256', verificateur) — jamais le verificateur lui-meme.
     */
    #[ORM\Column(length: 64)]
    private string $hashedVerifier = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }

    public function getHashedVerifier(): string
    {
        return $this->hashedVerifier;
    }

    public function setHashedVerifier(string $hashedVerifier): static
    {
        $this->hashedVerifier = $hashedVerifier;

        return $this;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return ($now ?? new \DateTimeImmutable()) >= $this->expiresAt;
    }
}
