<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\BlockoutDate;
use App\Entity\Booking;
use App\Entity\Package;
use App\Repository\BlockoutDateRepository;
use App\Repository\BookingRepository;
use App\Repository\PackageRepository;
use App\Service\CalendarEventFactory;
use App\Service\WalkInBookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Staff portal.
 *
 * Staff may create walk-in bookings and blockout dates and may edit/delete
 * only the records they personally created. Ownership is checked on every
 * mutating action using bookings.admin_id / blockout_dates.admin_id.
 */
#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
class StaffController extends AbstractController
{
    private const CSRF_TOKEN = 'staff_action';

    #[Route('', name: 'staff_dashboard', methods: ['GET'])]
    public function dashboard(
        BookingRepository $bookingRepository,
        BlockoutDateRepository $blockoutDateRepository,
        CalendarEventFactory $calendarEventFactory,
    ): Response {
        $staff = $this->requireStaff();

        $today = new \DateTimeImmutable('today');

        // Staff can view every studio record. Mutating actions remain limited
        // to records they personally created (enforced in the edit/delete
        // actions below).
        $allBookings = $bookingRepository->findFilteredBookings();
        $allBlockouts = $blockoutDateRepository->findAllOrdered();

        return $this->render('staff/dashboard.html.twig', [
            'staff' => $staff,
            'totalBookings' => $bookingRepository->countAll(),
            'pendingBookings' => $bookingRepository->countByStatus(Booking::STATUS_PENDING),
            'confirmedBookings' => $bookingRepository->countByStatus(Booking::STATUS_CONFIRMED),
            'totalBlockouts' => $blockoutDateRepository->countAll(),
            'todaysBookings' => $bookingRepository->findDailySchedule($today),
            'allBookings' => $allBookings,
            'myBookings' => $bookingRepository->countByCreator($staff),
            'myPendingBookings' => $bookingRepository->countByCreatorAndStatus($staff, Booking::STATUS_PENDING),
            'myConfirmedBookings' => $bookingRepository->countByCreatorAndStatus($staff, Booking::STATUS_CONFIRMED),
            'myBlockouts' => $blockoutDateRepository->countByCreator($staff),
            'calendarEvents' => $calendarEventFactory->build($allBookings, $allBlockouts),
        ]);
    }

    #[Route('/records', name: 'staff_records', methods: ['GET'])]
    public function records(
        Request $request,
        BookingRepository $bookingRepository,
        BlockoutDateRepository $blockoutDateRepository,
    ): Response {
        $staff = $this->requireStaff();

        $search = trim((string) $request->query->get('search', ''));
        $status = (string) $request->query->get('status', 'All');
        $type = (string) $request->query->get('type', 'all');

        if ($status !== 'All' && !in_array($status, Booking::ALLOWED_STATUSES, true)) {
            $status = 'All';
        }
        if (!in_array($type, ['all', 'bookings', 'blockouts'], true)) {
            $type = 'all';
        }

        $bookings = $type === 'blockouts'
            ? []
            : $bookingRepository->findFilteredBookingsForCreator($staff, $search, $status);

        $blockouts = $type === 'bookings' ? [] : $blockoutDateRepository->findByCreator($staff);

        if ($search !== '' && $type !== 'bookings') {
            $term = mb_strtolower($search);
            $blockouts = array_values(array_filter(
                $blockouts,
                static function (BlockoutDate $blockout) use ($term): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) $blockout->getReason(),
                        $blockout->getBlockoutDate()?->format('Y-m-d') ?? '',
                        $blockout->getBlockoutDate()?->format('M d, Y') ?? '',
                    ]));

                    return str_contains($haystack, $term);
                }
            ));
        }

        return $this->render('staff/records.html.twig', [
            'staff' => $staff,
            'bookings' => $bookings,
            'blockouts' => $blockouts,
            'search' => $search,
            'status' => $status,
            'type' => $type,
        ]);
    }

    #[Route('/bookings/new', name: 'staff_booking_new', methods: ['GET', 'POST'])]
    public function bookingNew(
        Request $request,
        PackageRepository $packageRepository,
        WalkInBookingService $walkInBookingService,
    ): Response {
        $staff = $this->requireStaff();
        $packages = $packageRepository->findActive();
        $errors = [];
        $form = $this->readBookingForm($request);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } elseif ($packages === []) {
                $errors[] = 'There are no active packages available. Please contact an administrator.';
            } else {
                try {
                    $booking = $walkInBookingService->createFromRequest($request, $staff);
                    $this->addFlash('staff_msg', "Walk-in booking {$booking->getBookingReference()} created successfully as {$booking->getBookingStatus()}.");

                    return $this->redirectToRoute('staff_records');
                } catch (\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                } catch (\Throwable) {
                    $errors[] = 'There was an error processing the booking. Please try again.';
                }
            }
        }

        return $this->render('staff/booking_new.html.twig', [
            'staff' => $staff,
            'packages' => $packages,
            'errors' => $errors,
            'form' => $form,
        ]);
    }

    #[Route('/bookings/{id}/edit', name: 'staff_booking_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function bookingEdit(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $staff = $this->requireStaff();
        $booking = $bookingRepository->find($id);
        if (!$booking instanceof Booking) {
            throw $this->createNotFoundException('Booking not found.');
        }
        $this->denyUnlessOwner($booking->getAdmin(), $staff, 'You may only edit bookings that you created.');

        $packages = $packageRepository->findAllOrdered();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $date = trim((string) $request->request->get('schedule_date'));
                $time = trim((string) $request->request->get('schedule_time'));
                $package = $packageRepository->find((int) $request->request->get('package_id'));
                $status = (string) $request->request->get('booking_status', Booking::STATUS_PENDING);
                $participants = (int) $request->request->get('participants', 1);
                $notes = trim((string) $request->request->get('notes'));

                if (!$this->isValidDate($date)) {
                    $errors[] = 'Please provide a valid schedule date.';
                }
                if (!$this->isValidTime($time)) {
                    $errors[] = 'Please provide a valid schedule time.';
                }
                if (!$package instanceof Package) {
                    $errors[] = 'Please select a valid package.';
                }
                if (!in_array($status, Booking::ALLOWED_STATUSES, true)) {
                    $errors[] = 'Please select a valid booking status.';
                }
                if ($participants < 1) {
                    $errors[] = 'Participants must be at least 1.';
                }

                if ($errors === []) {
                    $booking->setScheduleDate(new \DateTimeImmutable($date));
                    $booking->setScheduleTimeFromString($time);
                    $booking->setPackage($package);
                    $booking->setBookingStatus($status);
                    $booking->setParticipants($participants);
                    $booking->setNotes($notes !== '' ? $notes : null);

                    $entityManager->flush();
                    $this->addFlash('staff_msg', "Booking {$booking->getDisplayReference()} updated successfully.");

                    return $this->redirectToRoute('staff_records');
                }
            }
        }

        return $this->render('staff/booking_form.html.twig', [
            'staff' => $staff,
            'booking' => $booking,
            'packages' => $packages,
            'errors' => $errors,
        ]);
    }

    #[Route('/bookings/{id}/delete', name: 'staff_booking_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function bookingDelete(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $staff = $this->requireStaff();
        $booking = $bookingRepository->find($id);
        if (!$booking instanceof Booking) {
            throw $this->createNotFoundException('Booking not found.');
        }
        $this->denyUnlessOwner($booking->getAdmin(), $staff, 'You may only delete bookings that you created.');

        if (!$this->isCsrfTokenValid('delete_booking_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('staff_msg', 'Invalid confirmation token. The booking was not deleted.');

            return $this->redirectToRoute('staff_records');
        }

        $reference = $booking->getDisplayReference();
        $entityManager->remove($booking);
        $entityManager->flush();

        $this->addFlash('staff_msg', "Booking {$reference} deleted successfully.");

        return $this->redirectToRoute('staff_records');
    }

    #[Route('/blockouts/new', name: 'staff_blockout_new', methods: ['GET', 'POST'])]
    public function blockoutNew(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $staff = $this->requireStaff();
        $blockout = new BlockoutDate();
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please try again.';
            } else {
                $error = $this->populateBlockoutFromRequest($blockout, $request);

                if ($error === null) {
                    $blockout->setAdmin($staff);
                    $entityManager->persist($blockout);
                    $entityManager->flush();
                    $this->addFlash('staff_msg', 'Blockout entry added successfully.');

                    return $this->redirectToRoute('staff_records');
                }
            }
        }

        return $this->render('staff/blockout_form.html.twig', [
            'staff' => $staff,
            'blockout' => $blockout,
            'error' => $error,
            'isEdit' => false,
        ]);
    }

    #[Route('/blockouts/{id}/edit', name: 'staff_blockout_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function blockoutEdit(
        int $id,
        Request $request,
        BlockoutDateRepository $blockoutDateRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $staff = $this->requireStaff();
        $blockout = $blockoutDateRepository->find($id);
        if (!$blockout instanceof BlockoutDate) {
            throw $this->createNotFoundException('Blockout entry not found.');
        }
        $this->denyUnlessOwner($blockout->getAdmin(), $staff, 'You may only edit blockout dates that you created.');

        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please try again.';
            } else {
                $error = $this->populateBlockoutFromRequest($blockout, $request);

                if ($error === null) {
                    $entityManager->flush();
                    $this->addFlash('staff_msg', 'Blockout entry updated successfully.');

                    return $this->redirectToRoute('staff_records');
                }
            }
        }

        return $this->render('staff/blockout_form.html.twig', [
            'staff' => $staff,
            'blockout' => $blockout,
            'error' => $error,
            'isEdit' => true,
        ]);
    }

    #[Route('/blockouts/{id}/delete', name: 'staff_blockout_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function blockoutDelete(
        int $id,
        Request $request,
        BlockoutDateRepository $blockoutDateRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $staff = $this->requireStaff();
        $blockout = $blockoutDateRepository->find($id);
        if (!$blockout instanceof BlockoutDate) {
            throw $this->createNotFoundException('Blockout entry not found.');
        }
        $this->denyUnlessOwner($blockout->getAdmin(), $staff, 'You may only delete blockout dates that you created.');

        if (!$this->isCsrfTokenValid('delete_blockout_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('staff_msg', 'Invalid confirmation token. The blockout was not deleted.');

            return $this->redirectToRoute('staff_records');
        }

        $entityManager->remove($blockout);
        $entityManager->flush();

        $this->addFlash('staff_msg', 'Blockout entry deleted successfully.');

        return $this->redirectToRoute('staff_records');
    }

    private function requireStaff(): Admin
    {
        $user = $this->getUser();
        if (!$user instanceof Admin || !$user->isStaff()) {
            throw $this->createAccessDeniedException('This area is only available to staff accounts.');
        }
        if (!$user->isActive()) {
            throw $this->createAccessDeniedException('This account is disabled or archived.');
        }

        return $user;
    }

    private function denyUnlessOwner(?Admin $creator, Admin $staff, string $message): void
    {
        if (!$creator instanceof Admin || $creator->getAdminId() !== $staff->getAdminId()) {
            throw $this->createAccessDeniedException($message);
        }
    }

    /**
     * @return array<string, string>
     */
    private function readBookingForm(Request $request): array
    {
        return [
            'fullName' => trim((string) $request->request->get('full_name', '')),
            'email' => trim((string) $request->request->get('email', '')),
            'phone' => trim((string) $request->request->get('phone', '')),
            'packageId' => (string) $request->request->get('package_id', ''),
            'scheduleDate' => trim((string) $request->request->get('schedule_date', '')),
            'scheduleTime' => trim((string) $request->request->get('schedule_time', '')),
            'backdrop' => trim((string) $request->request->get('backdrop', '')),
            'notes' => trim((string) $request->request->get('notes', '')),
            'paymentMethod' => (string) $request->request->get('payment_method', 'cash'),
        ];
    }

    private function populateBlockoutFromRequest(BlockoutDate $blockout, Request $request): ?string
    {
        $date = trim((string) $request->request->get('blockout_date'));
        $start = trim((string) $request->request->get('start_time'));
        $end = trim((string) $request->request->get('end_time'));
        $reason = trim((string) $request->request->get('reason')) ?: 'Unavailable / Holiday';

        if (!$this->isValidDate($date)) {
            return 'Please choose a valid blockout date.';
        }
        if (($start !== '' && !$this->isValidTime($start)) || ($end !== '' && !$this->isValidTime($end))) {
            return 'Please choose valid start and end times.';
        }

        $blockout->setBlockoutDate(new \DateTimeImmutable($date));
        $blockout->setReason($reason);
        $blockout->setStartTime($start !== ''
            ? new \DateTimeImmutable($date . ' ' . (strlen($start) === 5 ? $start . ':00' : $start))
            : null);
        $blockout->setEndTime($end !== ''
            ? new \DateTimeImmutable($date . ' ' . (strlen($end) === 5 ? $end . ':00' : $end))
            : null);

        return null;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isValidTime(string $time): bool
    {
        $normalized = strlen($time) === 5 ? $time . ':00' : $time;
        $parsed = \DateTimeImmutable::createFromFormat('!H:i:s', $normalized);

        return $parsed !== false && $parsed->format('H:i:s') === $normalized;
    }
}
