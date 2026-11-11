<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customers')]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'customer_id', type: 'integer')]
    private ?int $customerId = null;

    #[ORM\Column(name: 'full_name', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Please provide your full name.')]
    #[Assert\Length(max: 100)]
    private ?string $fullName = null;

    #[ORM\Column(name: 'email', type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Please provide an email address.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    #[Assert\Length(max: 100)]
    private ?string $email = null;

    /**
     * The real column is named "password" (legacy schema), not "password_hash".
     * It stores the password_hash()/bcrypt value.
     */
    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 20)]
    #[Assert\Length(max: 20)]
    private ?string $phone = '';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Booking::class)]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone ?? '';

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setCustomer($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking) && $booking->getCustomer() === $this) {
            $booking->setCustomer(null);
        }

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_CUSTOMER'];
    }

    public function eraseCredentials(): void
    {
        // No temporary, plain-text credentials are stored on this object.
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
