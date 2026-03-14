<?php

namespace App\Entity\App;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "admin_log")]
#[ORM\Index(columns: ["action"], name: "idx_admin_log_action")]
#[ORM\Index(columns: ["entity_type"], name: "idx_admin_log_entity_type")]
class AdminLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "admin_user_id", referencedColumnName: "id")]
    private User $adminUser;

    #[ORM\Column(name: "action", type: "string", length: 50)]
    private string $action;

    #[ORM\Column(name: "entity_type", type: "string", length: 100)]
    private string $entityType;

    #[ORM\Column(name: "entity_id", type: "integer", nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(name: "entity_label", type: "string", length: 255, nullable: true)]
    private ?string $entityLabel = null;

    #[ORM\Column(name: "details", type: "json", nullable: true)]
    private ?array $details = null;

    #[ORM\Column(name: "ip_address", type: "string", length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdminUser(): User
    {
        return $this->adminUser;
    }

    public function setAdminUser(User $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getEntityLabel(): ?string
    {
        return $this->entityLabel;
    }

    public function setEntityLabel(?string $entityLabel): void
    {
        $this->entityLabel = $entityLabel;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): void
    {
        $this->details = $details;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }
}
