<?php

namespace App\Repository;

use App\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    /**
     * Preserves the packages.php/booking.php rule: only active packages are
     * shown to customers, ordered by category then name.
     *
     * @return Package[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Package::STATUS_ACTIVE)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Package[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Package[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', Package::STATUS_ACTIVE)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    public function findActiveCategories(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->andWhere('p.status = :status')
            ->andWhere('p.category IS NOT NULL')
            ->setParameter('status', Package::STATUS_ACTIVE)
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(
            static fn (array $row): ?string => $row['category'] ?? null,
            $rows
        )));
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.packageId)')
            ->andWhere('p.status = :status')
            ->setParameter('status', Package::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.packageId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Used to guard deletion of packages that still have bookings. The FK is
     * ON DELETE CASCADE in the legacy schema, so this check prevents accidental
     * booking history loss.
     */
    public function hasBookings(Package $package): bool
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(b.bookingId)')
            ->join('p.bookings', 'b')
            ->andWhere('p = :package')
            ->setParameter('package', $package)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
