<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'customer_dashboard', methods: ['GET'])]
    public function dashboard(BookingRepository $bookingRepository): Response
    {
        $customer = $this->getUser();
        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('app_login', ['redirect' => '/dashboard']);
        }

        $bookings = $bookingRepository->findByCustomer($customer);
        $rows = [];

        foreach ($bookings as $booking) {
            $package = $booking->getPackage();
            $packageName = $package?->getName() ?? 'Studio Package';
            $price = (float) ($package?->getPrice() ?? 0);
            $date = $booking->getScheduleDate();
            $time = $booking->getScheduleTime();
            $status = $booking->getBookingStatus() ?? Booking::STATUS_PENDING;

            $rows[] = [
                'id' => $booking->getBookingId(),
                'ref' => $booking->getDisplayReference(),
                'status' => $status,
                'client_name' => $customer->getFullName() ?? 'Client',
                'email' => $customer->getEmail() ?? '',
                'phone' => $customer->getPhone() ?: 'N/A',
                'package' => $packageName . ' (₱' . number_format($price, 0) . ')',
                'package_name' => $packageName,
                'price' => $price,
                'date' => $date?->format('M d, Y') ?? '',
                'time' => $time?->format('g:i A') ?? 'Scheduled',
                'datetime' => ($date?->format('M d, Y') ?? '') . ' | ' . ($time?->format('g:i A') ?? 'Scheduled'),
                'proof' => $booking->getPaymentProofUrl() ?? '',
                'tags' => $booking->getNotesTags(),
            ];
        }

        return $this->render('dashboard/index.html.twig', [
            'customer' => $customer,
            'bookingRows' => $rows,
        ]);
    }
}
