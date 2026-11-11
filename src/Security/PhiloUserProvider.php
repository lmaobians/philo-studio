<?php

namespace App\Security;

use App\Entity\Admin;
use App\Entity\Customer;
use App\Repository\AdminRepository;
use App\Repository\CustomerRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Unified provider for the two legacy account tables.
 *
 * The original application had separate customer (email) and admin (username)
 * login flows. Both are loaded from their real tables here; admin accounts are
 * checked first so the legacy "admin@philostudio.com" login keeps working.
 *
 * @implements UserProviderInterface<UserInterface>
 */
class PhiloUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $identifier = trim($identifier);

        if ($identifier !== '') {
            $admin = $this->adminRepository->findOneByUsernameOrEmail($identifier);

            if ($admin instanceof Admin) {
                return $admin;
            }

            $customer = $this->customerRepository->createQueryBuilder('c')
                ->andWhere('LOWER(c.email) = LOWER(:identifier)')
                ->setParameter('identifier', $identifier)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($customer instanceof Customer) {
                return $customer;
            }
        }

        throw new UserNotFoundException(sprintf('No account found for "%s".', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if ($user instanceof Admin) {
            $fresh = $this->adminRepository->find($user->getAdminId());
            if ($fresh instanceof Admin && $fresh->isActive()) {
                return $fresh;
            }

            // Deleted, disabled or archived accounts must lose access immediately.
            throw new UserNotFoundException(sprintf('Admin "%s" is not available.', $user->getUserIdentifier()));
        }

        if ($user instanceof Customer) {
            $fresh = $this->customerRepository->find($user->getCustomerId());
            if ($fresh instanceof Customer) {
                return $fresh;
            }

            throw new UserNotFoundException(sprintf('Customer "%s" no longer exists.', $user->getUserIdentifier()));
        }

        throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, Admin::class, true) || is_a($class, Customer::class, true);
    }
}
