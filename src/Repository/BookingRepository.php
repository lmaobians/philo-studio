<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Booking;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Preserves the "upcoming" concept used by the admin area.
     *
     * @return Booking[]
     */
    public function findUpcomingBookings(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.scheduleDate >= :today')
            ->andWhere('LOWER(b.bookingStatus) != :cancelled')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelled', strtolower(Booking::STATUS_CANCELLED))
            ->orderBy('b.scheduleDate', 'ASC')
            ->addOrderBy('b.scheduleTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * A slot is occupied only by Pending or Confirmed bookings. Cancelled
     * bookings must never block a slot (same rule as get_booked_slots.php).
     */
    public function checkSlotAvailability(\DateTimeInterface|string $date, \DateTimeInterface|string $time): bool
    {
        [$dateValue, $timeValue] = $this->normalizeDateTime($date, $time);

        $count = (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->andWhere('b.scheduleDate = :date')
            ->andWhere('b.scheduleTime = :time')
            ->andWhere('LOWER(b.bookingStatus) IN (:statuses)')
            ->setParameter('date', $dateValue)
            ->setParameter('time', $timeValue)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->getQuery()
            ->getSingleScalarResult();

        return $count === 0;
    }

    public function updateBookingStatus(int $bookingId, string $status): bool
    {
        $booking = $this->find($bookingId);
        if (!$booking) {
            return false;
        }

        $booking->setBookingStatus($status);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Preserves admin/booking_helpers.php::getFilteredBookings().
     *
     * When $creator is supplied, only records created by that administrator or
     * staff member are returned. Passing null keeps the system-wide view.
     *
     * @return Booking[]
     */
    public function findFilteredBookings(string $search = '', string $status = 'All', ?Admin $creator = null): array
    {
        $qb = $this->baseAdminQuery();

        if ($creator instanceof Admin) {
            $qb->andWhere('b.admin = :creator')
                ->setParameter('creator', $creator);
        }

        $search = trim($search);
        if ($search !== '') {
            $this->applyBookingSearch($qb, $search);
        }

        $status = trim($status);
        if ($status !== '' && strcasecmp($status, 'All') !== 0) {
            $qb->andWhere('b.bookingStatus = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('b.bookingId', 'DESC')->getQuery()->getResult();
    }

    /**
     * @return Booking[]
     */
    public function findFilteredBookingsForCreator(Admin $creator, string $search = '', string $status = 'All'): array
    {
        return $this->findFilteredBookings($search, $status, $creator);
    }

    /**
     * Preserves admin/booking_helpers.php::getDailySchedule().
     *
     * @return Booking[]
     */
    public function findDailySchedule(\DateTimeInterface|string $date, string $search = '', string $status = 'All', ?Admin $creator = null): array
    {
        $dateValue = $date instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)
            : new \DateTimeImmutable($date);

        $qb = $this->baseAdminQuery()
            ->andWhere('b.scheduleDate = :date')
            ->setParameter('date', $dateValue);

        if ($creator instanceof Admin) {
            $qb->andWhere('b.admin = :creator')
                ->setParameter('creator', $creator);
        }

        $search = trim($search);
        if ($search !== '') {
            $this->applyBookingSearch($qb, $search);
        }

        $status = trim($status);
        if ($status !== '' && strcasecmp($status, 'All') !== 0) {
            $qb->andWhere('b.bookingStatus = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('b.scheduleTime', 'ASC')->getQuery()->getResult();
    }

    /** @return Booking[] */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('b')
            ->addSelect('p', 'c')
            ->leftJoin('b.package', 'p')
            ->leftJoin('b.customer', 'c')
            ->andWhere('b.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('b.bookingId', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the raw schedule times for a date used by the slot endpoint.
     * Cancelled bookings are intentionally excluded.
     *
     * @return string[]
     */
    public function findBookedTimesForDate(\DateTimeInterface|string $date): array
    {
        $dateValue = $date instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)
            : new \DateTimeImmutable($date);

        $rows = $this->createQueryBuilder('b')
            ->select('b.scheduleTime')
            ->andWhere('b.scheduleDate = :date')
            ->andWhere('LOWER(b.bookingStatus) IN (:statuses)')
            ->setParameter('date', $dateValue)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->getQuery()
            ->getArrayResult();

        $times = [];
        foreach ($rows as $row) {
            $time = $row['scheduleTime'] ?? null;
            if ($time instanceof \DateTimeInterface) {
                $times[] = $time->format('H:i:s');
            } elseif (is_string($time) && $time !== '') {
                $times[] = $time;
            }
        }

        return $times;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->andWhere('b.bookingStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCreator(Admin $creator): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->andWhere('b.admin = :creator')
            ->setParameter('creator', $creator)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCreatorAndStatus(Admin $creator, string $status): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->andWhere('b.admin = :creator')
            ->andWhere('b.bookingStatus = :status')
            ->setParameter('creator', $creator)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCreatorRole(string $role): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->innerJoin('b.admin', 'a')
            ->andWhere('a.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Booking[]
     */
    public function findUpcomingByCreator(Admin $creator, int $limit = 5): array
    {
        return $this->baseAdminQuery()
            ->andWhere('b.admin = :creator')
            ->andWhere('b.scheduleDate >= :today')
            ->andWhere('b.bookingStatus != :cancelled')
            ->setParameter('creator', $creator)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelled', Booking::STATUS_CANCELLED)
            ->orderBy('b.scheduleDate', 'ASC')
            ->addOrderBy('b.scheduleTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function referenceExists(string $reference): bool
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.bookingId)')
            ->andWhere('b.bookingReference = :reference')
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function generateUniqueReference(): string
    {
        do {
            $reference = 'BK-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
        } while ($this->referenceExists($reference));

        return $reference;
    }

    private function baseAdminQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->addSelect('c', 'p', 'a')
            ->leftJoin('b.customer', 'c')
            ->leftJoin('b.package', 'p')
            ->leftJoin('b.admin', 'a');
    }

    private function applyBookingSearch(QueryBuilder $qb, string $search): void
    {
        $term = '%' . $search . '%';
        $qb->andWhere(
            'b.bookingReference LIKE :term
             OR c.fullName LIKE :term
             OR c.email LIKE :term
             OR c.phone LIKE :term
             OR p.name LIKE :term
             OR a.fullName LIKE :term
             OR a.username LIKE :term'
        )->setParameter('term', $term);
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function normalizeDateTime(\DateTimeInterface|string $date, \DateTimeInterface|string $time): array
    {
        $dateValue = $date instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)
            : new \DateTimeImmutable($date);

        if ($time instanceof \DateTimeInterface) {
            $timeValue = \DateTimeImmutable::createFromInterface($time);
        } else {
            $normalized = strlen($time) === 5 ? $time . ':00' : $time;
            $timeValue = new \DateTimeImmutable($normalized);
        }

        return [$dateValue, $timeValue];
    }
}
