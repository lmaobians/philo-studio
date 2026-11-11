<?php

namespace App\Entity;

use App\Repository\PackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\Table(name: 'packages')]
class Package
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'package_id', type: 'integer')]
    private ?int $packageId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(name: 'category', type: 'string', length: 50, nullable: true, options: ['default' => 'Standard'])]
    #[Assert\Length(max: 50)]
    private ?string $category = 'Standard';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'inclusions', type: 'text', nullable: true)]
    private ?string $inclusions = null;

    #[ORM\Column(name: 'duration', type: 'string', length: 50, nullable: true, options: ['default' => '30 mins'])]
    #[Assert\Length(max: 50)]
    private ?string $duration = '30 mins';

    #[ORM\Column(name: 'min_pax', type: 'integer', nullable: true, options: ['default' => 1])]
    #[Assert\PositiveOrZero]
    private ?int $minPax = 1;

    #[ORM\Column(name: 'max_pax', type: 'integer', nullable: true, options: ['default' => 2])]
    #[Assert\PositiveOrZero]
    private ?int $maxPax = 2;

    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Please enter a valid price.')]
    private ?string $price = '0.00';

    #[ORM\Column(name: 'status', type: 'string', nullable: true, columnDefinition: "ENUM('active','inactive') DEFAULT 'active'")]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_INACTIVE])]
    private ?string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'image_url', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $imageUrl = null;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(mappedBy: 'package', targetEntity: Booking::class)]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getPackageId(): ?int
    {
        return $this->packageId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInclusions(): ?string
    {
        return $this->inclusions;
    }

    public function setInclusions(?string $inclusions): static
    {
        $this->inclusions = $inclusions;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getMinPax(): ?int
    {
        return $this->minPax;
    }

    public function setMinPax(?int $minPax): static
    {
        $this->minPax = $minPax;

        return $this;
    }

    public function getMaxPax(): ?int
    {
        return $this->maxPax;
    }

    public function setMaxPax(?int $maxPax): static
    {
        $this->maxPax = $maxPax;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string|int|float $price): static
    {
        $this->price = number_format((float) $price, 2, '.', '');

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Legacy package grouping rule used by packages.php and booking.php:
     * group/full grid/studio access packages occupy a 60-minute slot,
     * everything else uses 30 minutes.
     *
     * This value is display/metadata only. The Symfony booking wizard and
     * its availability checks use the fixed hourly slots in
     * BookingController (10:00-19:00), not this per-package duration.
     */
    public function getSlotMinutes(): int
    {
        $name = strtolower((string) $this->name);
        $category = strtolower((string) $this->category);

        if (str_contains($name, 'group') || str_contains($name, 'full grid') || str_contains($category, 'studio access')) {
            return 60;
        }

        return 30;
    }

    public function getDisplayImageUrl(): string
    {
        return $this->imageUrl ?: 'images/solo-package.jpg';
    }
}
