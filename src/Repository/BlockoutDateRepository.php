<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\BlockoutDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlockoutDate>
 */
class BlockoutDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockoutDate::class);
    }

    /**
     * @return BlockoutDate[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.blockoutDate', 'ASC')
            ->addOrderBy('b.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return BlockoutDate[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.blockoutDate >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('b.blockoutDate', 'ASC')
            ->addOrderBy('b.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return BlockoutDate[]
     */
    public function findByCreator(Admin $creator): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.admin = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('b.blockoutDate', 'DESC')
            ->addOrderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByCreator(Admin $creator): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.blockoutId)')
            ->andWhere('b.admin = :creator')
            ->setParameter('creator', $creator)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.blockoutId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return BlockoutDate[]
     */
    public function findForDate(\DateTimeInterface|string $date): array
    {
        $dateValue = $date instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)
            : new \DateTimeImmutable($date);

        return $this->createQueryBuilder('b')
            ->andWhere('b.blockoutDate = :date')
            ->setParameter('date', $dateValue)
            ->orderBy('b.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mirrors the rules in get_booked_slots.php: a blockout is a full-day
     * blockout when either time is missing or start_time is exactly 00:00:00;
     * otherwise it blocks [start_time, end_time).
     */
    public function isBlocked(\DateTimeInterface|string $date, \DateTimeInterface|string $time): bool
    {
        foreach ($this->findForDate($date) as $blockout) {
            if ($blockout->isFullDay()) {
                return true;
            }

            $timeValue = $time instanceof \DateTimeInterface
                ? $time->format('H:i:s')
                : (strlen($time) === 5 ? $time . ':00' : $time);

            if ($blockout->blocksTime($timeValue)) {
                return true;
            }
        }

        return false;
    }

    public function existsForDate(\DateTimeInterface|string $date): bool
    {
        return $this->findForDate($date) !== [];
    }
}
