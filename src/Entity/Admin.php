<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admins')]
#[ORM\UniqueConstraint(name: 'username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_ADMINS_EMAIL', columns: ['email'])]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_STAFF = 'ROLE_STAFF';

    public const ALLOWED_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_STAFF,
    ];

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_DISABLED = 'Disabled';
    public const STATUS_ARCHIVED = 'Archived';

    public const ALLOWED_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DISABLED,
        self::STATUS_ARCHIVED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'admin_id', type: 'integer')]
    private ?int $adminId = null;

    #[ORM\Column(name: 'username', type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Please enter a username.')]
    #[Assert\Length(max: 50)]
    private ?string $username = null;

    #[ORM\Column(name: 'email', type: 'string', length: 180, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    /**
     * The real column is named "password" and stores the legacy password_hash() value.
     */
    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'full_name', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Please enter the full name.')]
    #[Assert\Length(max: 100)]
    private ?string $fullName = null;

    /**
     * ROLE_ADMIN or ROLE_STAFF. The legacy admins table had no role column.
     */
    #[ORM\Column(name: 'role', type: 'string', length: 20, options: ['default' => self::ROLE_ADMIN])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::ALLOWED_ROLES, message: 'Please choose a valid role.')]
    private string $role = self::ROLE_ADMIN;

    /**
     * Active, Disabled or Archived. Disabled/archived accounts cannot sign in.
     */
    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => self::STATUS_ACTIVE])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::ALLOWED_STATUSES, message: 'Please choose a valid account status.')]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Booking::class)]
    private Collection $bookings;

    /** @var Collection<int, BlockoutDate> */
    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: BlockoutDate::class)]
    private Collection $blockoutDates;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->blockoutDates = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getAdminId(): ?int
    {
        return $this->adminId;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email !== null ? trim($email) : null;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role ?: self::ROLE_ADMIN;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException('Invalid account role.');
        }

        $this->role = $role;

        return $this;
    }

    public function getRoleLabel(): string
    {
        return $this->getRole() === self::ROLE_ADMIN ? 'Administrator' : 'Staff';
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->getRole() === self::ROLE_STAFF;
    }

    public function getStatus(): string
    {
        return $this->status ?: self::STATUS_ACTIVE;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid account status.');
        }

        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    /** @return Collection<int, BlockoutDate> */
    public function getBlockoutDates(): Collection
    {
        return $this->blockoutDates;
    }

    public function getRoles(): array
    {
        return [$this->getRole()];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }
}
