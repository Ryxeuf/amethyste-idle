<?php

namespace App\Entity;

use App\Repository\PasswordResetRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une demande de reinitialisation de mot de passe (ONB-02).
 *
 * Trois lois, toutes portees par le schema plutot que promises par le code :
 *
 * - **un seul jeton actif par compte** — `user_id` est unique : une nouvelle
 *   demande remplace la precedente, elle ne s'y ajoute pas ;
 * - **le jeton ne se relit pas** — seul le hachage du verificateur est stocke.
 *   Une fuite de la base ne donne aucun jeton utilisable, exactement comme la
 *   colonne `password` ne donne aucun mot de passe ;
 * - **une heure, puis rien** — `expiresAt` est verifie a la lecture, jamais
 *   nettoye par un cron : une ligne perimee est un refus, pas un danger.
 *
 * Le jeton envoye par e-mail est `selecteur . verificateur`. Le selecteur sert
 * a retrouver la ligne (il peut s'indexer sans fuir), le verificateur se
 * compare en temps constant a son hachage. C'est le schema classique qui evite
 * a la fois l'enumeration par temps de reponse et le stockage en clair.
 */
#[ORM\Entity(repositoryClass: PasswordResetRequestRepository::class)]
#[ORM\Table(name: 'password_reset_requests')]
class PasswordResetRequest
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
