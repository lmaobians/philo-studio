<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findOneByEmail(string $email): ?Customer
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Preserves the admin customers search: name, email or phone substring.
     *
     * @return Customer[]
     */
    public function search(string $term = ''): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.customerId', 'DESC');

        $term = trim($term);
        if ($term !== '') {
            $qb->andWhere('c.fullName LIKE :term OR c.email LIKE :term OR c.phone LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.customerId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Raw booking dates grouped by customer id, used by the legacy admin
     * "Last Booked Date" search filter.
     *
     * @return array<int, string[]>
     */
    public function bookingDatesByCustomer(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.customerId AS id, b.scheduleDate AS bookingDate')
            ->leftJoin('c.bookings', 'b')
            ->andWhere('b.scheduleDate IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $dates = [];
        foreach ($rows as $row) {
            $date = $row['bookingDate'] ?? null;
            if ($date instanceof \DateTimeInterface) {
                $dates[(int) $row['id']][] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    public function findByEmailCaseInsensitive(string $email): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function bookingStatsByCustomer(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.customerId AS id, COUNT(b.bookingId) AS bookingCount, MAX(b.scheduleDate) AS lastBooking')
            ->leftJoin('c.bookings', 'b')
            ->groupBy('c.customerId')
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($rows as $row) {
            $last = $row['lastBooking'] ?? null;
            $stats[(int) $row['id']] = [
                'count' => (int) $row['bookingCount'],
                'last' => $last instanceof \DateTimeInterface ? $last->format('M j, Y') : null,
            ];
        }

        return $stats;
    }
}
