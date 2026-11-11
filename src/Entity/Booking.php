<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
#[ORM\UniqueConstraint(name: 'booking_reference', columns: ['booking_reference'])]
class Booking
{
    public const STATUS_PENDING = 'Pending';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_CANCELLED = 'Cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'booking_id', type: 'integer')]
    private ?int $bookingId = null;

    #[ORM\Column(name: 'booking_reference', type: 'string', length: 20, nullable: true, unique: true)]
    #[Assert\Length(max: 20)]
    private ?string $bookingReference = null;

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'customer_id', nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: Admin::class)]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'admin_id', nullable: true, onDelete: 'SET NULL')]
    private ?Admin $admin = null;

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: Package::class)]
    #[ORM\JoinColumn(name: 'package_id', referencedColumnName: 'package_id', nullable: false, onDelete: 'CASCADE')]
    private ?Package $package = null;

    #[ORM\Column(name: 'participants', type: 'integer', nullable: true, options: ['default' => 1])]
    #[Assert\PositiveOrZero]
    private ?int $participants = 1;

    #[ORM\Column(name: 'schedule_date', type: 'date_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $scheduleDate = null;

    #[ORM\Column(name: 'schedule_time', type: 'time_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $scheduleTime = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'payment_proof', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $paymentProof = null;

    #[ORM\Column(name: 'booking_status', type: 'string', nullable: true, columnDefinition: "ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending'")]
    #[Assert\Choice(choices: self::ALLOWED_STATUSES)]
    private ?string $bookingStatus = self::STATUS_PENDING;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    public function getBookingReference(): ?string
    {
        return $this->bookingReference;
    }

    public function setBookingReference(?string $bookingReference): static
    {
        $this->bookingReference = $bookingReference;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function setAdmin(?Admin $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(?Package $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getParticipants(): ?int
    {
        return $this->participants;
    }

    public function setParticipants(?int $participants): static
    {
        $this->participants = $participants;

        return $this;
    }

    public function getScheduleDate(): ?\DateTimeImmutable
    {
        return $this->scheduleDate;
    }

    public function setScheduleDate(\DateTimeImmutable $scheduleDate): static
    {
        $this->scheduleDate = $scheduleDate;

        return $this;
    }

    public function getScheduleTime(): ?\DateTimeImmutable
    {
        return $this->scheduleTime;
    }

    public function setScheduleTime(\DateTimeImmutable $scheduleTime): static
    {
        $this->scheduleTime = $scheduleTime;

        return $this;
    }

    /**
     * Existing wizard/admin forms submit time values such as "10:00" or "10:00:00".
     */
    public function setScheduleTimeFromString(string $time): static
    {
        $normalized = strlen($time) === 5 ? $time . ':00' : $time;
        $this->scheduleTime = new \DateTimeImmutable($normalized);

        return $this;
    }

    public function getScheduleTimeString(): string
    {
        return $this->scheduleTime?->format('H:i:s') ?? '';
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPaymentProof(): ?string
    {
        return $this->paymentProof;
    }

    public function setPaymentProof(?string $paymentProof): static
    {
        $this->paymentProof = $paymentProof;

        return $this;
    }

    public function getBookingStatus(): ?string
    {
        return $this->bookingStatus;
    }

    public function setBookingStatus(?string $bookingStatus): static
    {
        $this->bookingStatus = $bookingStatus;

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

    public function getDisplayReference(): string
    {
        return $this->bookingReference ?: 'BK-' . $this->bookingId;
    }

    public function isCancelled(): bool
    {
        return strtoupper(trim((string) $this->bookingStatus)) === 'CANCELLED';
    }

    /**
     * The legacy bookings table has no total_amount/payment_status columns.
     * The dashboard has always displayed the selected package price instead.
     */
    public function getTotalAmount(): string
    {
        return $this->package?->getPrice() ?? '0.00';
    }

    /**
     * Normalize legacy stored proof paths:
     *   ./uploads/x.jpg, ../uploads/x.jpg, uploads/x.jpg  ->  /uploads/x.jpg
     */
    public function getPaymentProofUrl(): ?string
    {
        if (!$this->paymentProof) {
            return null;
        }

        $path = str_replace('\\', '/', $this->paymentProof);
        $path = preg_replace('#^(\.\.?/)+#', '', $path) ?? $path;
        $path = ltrim($path, '/');

        return '/' . $path;
    }

    /**
     * Preserve dashboard.php's parseNotesToTags() behavior.
     *
     * @return array<string, string>
     */
    public function getNotesTags(): array
    {
        $tags = [];
        if (!$this->notes) {
            return $tags;
        }

        foreach (explode('|', $this->notes) as $part) {
            $pair = explode(':', trim($part), 2);
            if (count($pair) === 2) {
                $tags[trim($pair[0])] = trim($pair[1]);
            }
        }

        return $tags;
    }
}
