<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Booking;
use App\Repository\AdminRepository;
use App\Repository\BlockoutDateRepository;
use App\Repository\BookingRepository;
use App\Repository\CustomerRepository;
use App\Repository\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_entry', methods: ['GET'])]
    public function entry(): Response
    {
        return $this->redirectToRoute('admin_overview');
    }

    #[Route('/overview', name: 'admin_overview', methods: ['GET'])]
    public function overview(
        AdminRepository $adminRepository,
        CustomerRepository $customerRepository,
        BookingRepository $bookingRepository,
        PackageRepository $packageRepository,
        BlockoutDateRepository $blockoutDateRepository,
    ): Response {
        $totalUsers = $adminRepository->countAll();
        $totalAdmins = $adminRepository->countByRole(Admin::ROLE_ADMIN);
        $totalStaff = $adminRepository->countByRole(Admin::ROLE_STAFF);
        $activeAccounts = $adminRepository->countByStatus(Admin::STATUS_ACTIVE);
        $disabledAccounts = $adminRepository->countByStatus(Admin::STATUS_DISABLED) + $adminRepository->countByStatus(Admin::STATUS_ARCHIVED);

        $totalClients = $customerRepository->countAll();
        $totalBookings = $bookingRepository->countAll();
        $pendingBookings = $bookingRepository->countByStatus(Booking::STATUS_PENDING);
        $confirmedBookings = $bookingRepository->countByStatus(Booking::STATUS_CONFIRMED);
        $staffCreatedBookings = $bookingRepository->countByCreatorRole(Admin::ROLE_STAFF);
        $totalPackages = $packageRepository->countAll();
        $activePackages = $packageRepository->countActive();
        $totalBlockouts = $blockoutDateRepository->countAll();

        $totalRecords = $totalClients + $totalBookings + $totalPackages + $totalBlockouts;

        $recentBookings = array_slice($bookingRepository->findFilteredBookings(), 0, 8);

        return $this->render('admin/overview.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'activeAccounts' => $activeAccounts,
            'disabledAccounts' => $disabledAccounts,
            'totalClients' => $totalClients,
            'totalBookings' => $totalBookings,
            'pendingBookings' => $pendingBookings,
            'confirmedBookings' => $confirmedBookings,
            'staffCreatedBookings' => $staffCreatedBookings,
            'totalPackages' => $totalPackages,
            'activePackages' => $activePackages,
            'totalBlockouts' => $totalBlockouts,
            'totalRecords' => $totalRecords,
            'recentBookings' => $recentBookings,
        ]);
    }
}
