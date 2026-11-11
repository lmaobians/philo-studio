<?php

namespace App\Entity;

use App\Repository\BlockoutDateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlockoutDateRepository::class)]
#[ORM\Table(name: 'blockout_dates')]
class BlockoutDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'blockout_id', type: 'integer')]
    private ?int $blockoutId = null;

    #[ORM\Column(name: 'blockout_date', type: 'date_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $blockoutDate = null;

    #[ORM\Column(name: 'start_time', type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(name: 'reason', type: 'string', length: 255, nullable: true, options: ['default' => 'Unavailable / Holiday'])]
    #[Assert\Length(max: 255)]
    private ?string $reason = 'Unavailable / Holiday';

    #[ORM\ManyToOne(inversedBy: 'blockoutDates', targetEntity: Admin::class)]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'admin_id', nullable: true, onDelete: 'SET NULL')]
    private ?Admin $admin = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true, columnDefinition: 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getBlockoutId(): ?int
    {
        return $this->blockoutId;
    }

    public function getBlockoutDate(): ?\DateTimeImmutable
    {
        return $this->blockoutDate;
    }

    public function setBlockoutDate(\DateTimeImmutable $blockoutDate): static
    {
        $this->blockoutDate = $blockoutDate;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason ?: 'Unavailable / Holiday';

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Existing get_booked_slots.php treats an entry as full-day when either
     * time is missing or when the start time is exactly 00:00:00.
     */
    public function isFullDay(): bool
    {
        if (!$this->startTime || !$this->endTime) {
            return true;
        }

        return $this->startTime->format('H:i:s') === '00:00:00';
    }

    public function blocksTime(string $time): bool
    {
        if ($this->isFullDay()) {
            return true;
        }

        $normalized = strlen($time) === 5 ? $time . ':00' : $time;
        $slot = new \DateTimeImmutable($normalized);

        return $slot >= $this->startTime && $slot < $this->endTime;
    }
}
