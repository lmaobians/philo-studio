<?php

namespace App\Repository;

use App\Entity\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Admin>
 */
class AdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    public function findOneByUsername(string $username): ?Admin
    {
        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.username) = LOWER(:username)')
            ->setParameter('username', trim($username))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?Admin
    {
        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.email) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByUsernameOrEmail(string $identifier): ?Admin
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.username) = LOWER(:identifier) OR LOWER(a.email) = LOWER(:identifier)')
            ->setParameter('identifier', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->andWhere('LOWER(a.username) = LOWER(:username)')
            ->setParameter('username', trim($username));

        if ($excludeId !== null) {
            $qb->andWhere('a.adminId != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->andWhere('LOWER(a.email) = LOWER(:email)')
            ->setParameter('email', trim($email));

        if ($excludeId !== null) {
            $qb->andWhere('a.adminId != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return Admin[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.adminId', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function createUserFilterQueryBuilder(string $search = '', string $role = 'All', string $status = 'All'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'a.fullName LIKE :term
                 OR a.username LIKE :term
                 OR a.email LIKE :term'
            )->setParameter('term', '%' . $search . '%');
        }

        if ($role !== '' && $role !== 'All') {
            $qb->andWhere('a.role = :role')->setParameter('role', $role);
        }

        if ($status !== '' && $status !== 'All') {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }

    /**
     * @return Admin[]
     */
    public function findPaginatedUsers(string $search = '', string $role = 'All', string $status = 'All', int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return $this->createUserFilterQueryBuilder($search, $role, $status)
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.adminId', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countFilteredUsers(string $search = '', string $role = 'All', string $status = 'All'): int
    {
        return (int) $this->createUserFilterQueryBuilder($search, $role, $status)
            ->select('COUNT(a.adminId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->andWhere('a.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.adminId)')
            ->andWhere('a.role = :role')
            ->andWhere('a.status = :status')
            ->setParameter('role', Admin::ROLE_ADMIN)
            ->setParameter('status', Admin::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
