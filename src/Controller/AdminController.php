<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\BlockoutDate;
use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Package;
use App\Repository\BlockoutDateRepository;
use App\Repository\BookingRepository;
use App\Repository\CustomerRepository;
use App\Repository\PackageRepository;
use App\Service\CalendarEventFactory;
use App\Service\WalkInBookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private const ADMIN_CSRF_TOKEN = 'admin_action';
    private const PACKAGE_CSRF_TOKEN = 'package_form';

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(
        Request $request,
        BookingRepository $bookingRepository,
        PackageRepository $packageRepository,
        BlockoutDateRepository $blockoutDateRepository,
        WalkInBookingService $walkInBookingService,
        CalendarEventFactory $calendarEventFactory,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::ADMIN_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $this->addFlash('admin_msg', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('admin_dashboard');
            }

            $action = (string) $request->request->get('action');

            if ($action === 'create_booking') {
                $creator = $this->getUser();
                if (!$creator instanceof Admin) {
                    throw $this->createAccessDeniedException('Only administrators and staff can create walk-in bookings.');
                }

                try {
                    $booking = $walkInBookingService->createFromRequest($request, $creator);
                    $this->addFlash('admin_msg', "Walk-in booking {$booking->getBookingReference()} created successfully as {$booking->getBookingStatus()}.");
                } catch (\InvalidArgumentException $exception) {
                    $this->addFlash('admin_msg', $exception->getMessage());
                } catch (\Throwable $exception) {
                    $this->addFlash('admin_msg', 'Error processing booking: ' . $exception->getMessage());
                }
            } elseif ($action === 'update_status') {
                $bookingId = (int) $request->request->get('booking_id');
                $status = (string) $request->request->get('status');
                $booking = $bookingRepository->find($bookingId);

                if ($booking instanceof Booking && in_array($status, Booking::ALLOWED_STATUSES, true)) {
                    $booking->setBookingStatus($status);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', "Booking status updated to {$status}.");
                } else {
                    $this->addFlash('admin_msg', 'Invalid booking or status.');
                }
            } elseif ($action === 'delete_booking') {
                $bookingId = (int) $request->request->get('booking_id');
                $booking = $bookingRepository->find($bookingId);

                if ($booking instanceof Booking) {
                    $entityManager->remove($booking);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', "Booking #{$bookingId} deleted successfully.");
                }
            }

            return $this->redirectToRoute('admin_dashboard');
        }

        $searchQuery = (string) $request->query->get('search', '');
        $statusFilter = (string) $request->query->get('status_filter', 'All');

        if ($statusFilter === '') {
            $statusFilter = 'All';
        }

        $bookings = $bookingRepository->findFilteredBookings($searchQuery, $statusFilter);
        $packages = $packageRepository->findAllOrdered();
        $blockouts = $blockoutDateRepository->findAllOrdered();

        return $this->render('admin/dashboard.html.twig', [
            'bookings' => $bookings,
            'packages' => $packages,
            'blockouts' => $blockouts,
            'searchQuery' => $searchQuery,
            'statusFilter' => $statusFilter,
            'totalBookings' => $bookingRepository->countAll(),
            'pendingCount' => $bookingRepository->countByStatus(Booking::STATUS_PENDING),
            'confirmedCount' => $bookingRepository->countByStatus(Booking::STATUS_CONFIRMED),
            'calendarEvents' => $calendarEventFactory->build($bookings, $blockouts),
        ]);
    }

    #[Route('/daily-schedule', name: 'admin_daily_schedule', methods: ['GET'])]
    public function dailySchedule(Request $request, BookingRepository $bookingRepository): Response
    {
        $filterDate = (string) $request->query->get('date', (new \DateTimeImmutable('today'))->format('Y-m-d'));
        $searchQuery = (string) $request->query->get('search', '');
        $statusFilter = (string) $request->query->get('status_filter', 'All');

        if (!$this->isValidDate($filterDate)) {
            $filterDate = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }
        if ($statusFilter === '') {
            $statusFilter = 'All';
        }

        return $this->render('admin/daily_schedule.html.twig', [
            'schedule' => $bookingRepository->findDailySchedule($filterDate, $searchQuery, $statusFilter),
            'filterDate' => $filterDate,
            'searchQuery' => $searchQuery,
            'statusFilter' => $statusFilter,
        ]);
    }

    #[Route('/blockouts', name: 'admin_blockouts', methods: ['GET', 'POST'])]
    public function blockouts(
        Request $request,
        BlockoutDateRepository $blockoutDateRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::ADMIN_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $this->addFlash('admin_msg', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('admin_blockouts');
            }

            $action = (string) $request->request->get('action');

            if ($action === 'add_blockout') {
                $blockout = new BlockoutDate();
                $error = $this->populateBlockoutFromRequest($blockout, $request);

                if ($error !== null) {
                    $this->addFlash('admin_msg', $error);
                } else {
                    $creator = $this->getUser();
                    if ($creator instanceof Admin) {
                        $blockout->setAdmin($creator);
                    }

                    $entityManager->persist($blockout);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Blockout entry added successfully.');
                }
            } elseif ($action === 'delete_blockout') {
                $blockout = $blockoutDateRepository->find((int) $request->request->get('blockout_id'));
                if ($blockout instanceof BlockoutDate) {
                    $entityManager->remove($blockout);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Blockout entry deleted.');
                }
            }

            return $this->redirectToRoute('admin_blockouts');
        }

        return $this->render('admin/blockouts.html.twig', [
            'blockouts' => $blockoutDateRepository->findBy([], ['blockoutDate' => 'DESC', 'startTime' => 'DESC']),
        ]);
    }

    #[Route('/blockouts/{id}/edit', name: 'admin_blockout_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function blockoutEdit(
        int $id,
        Request $request,
        BlockoutDateRepository $blockoutDateRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $blockout = $blockoutDateRepository->find($id);
        if (!$blockout instanceof BlockoutDate) {
            throw $this->createNotFoundException('Blockout entry not found.');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::ADMIN_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please try again.';
            } else {
                $error = $this->populateBlockoutFromRequest($blockout, $request);

                if ($error === null) {
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Blockout entry updated successfully.');

                    return $this->redirectToRoute('admin_blockouts');
                }
            }
        }

        return $this->render('admin/blockout_form.html.twig', [
            'blockout' => $blockout,
            'error' => $error,
        ]);
    }

    #[Route('/bookings/{id}/edit', name: 'admin_booking_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function bookingEdit(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $booking = $bookingRepository->find($id);
        if (!$booking instanceof Booking) {
            throw $this->createNotFoundException('Booking not found.');
        }

        $packages = $packageRepository->findAllOrdered();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::ADMIN_CSRF_TOKEN, (string) $request->request->get('_token'))) {
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
                    $this->addFlash('admin_msg', "Booking {$booking->getDisplayReference()} updated successfully.");

                    return $this->redirectToRoute('admin_dashboard');
                }
            }
        }

        return $this->render('admin/booking_form.html.twig', [
            'booking' => $booking,
            'packages' => $packages,
            'errors' => $errors,
        ]);
    }

    /**
     * Applies submitted blockout fields to the entity.
     *
     * @return string|null Validation error, or null on success
     */
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

    #[Route('/packages', name: 'admin_packages', methods: ['GET', 'POST'])]
    public function packages(
        Request $request,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::ADMIN_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $this->addFlash('admin_msg', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('admin_packages');
            }

            $action = (string) $request->request->get('action');
            $package = $packageRepository->find((int) $request->request->get('package_id'));

            if ($action === 'toggle_status' && $package instanceof Package) {
                $status = (string) $request->request->get('status');
                if (in_array($status, [Package::STATUS_ACTIVE, Package::STATUS_INACTIVE], true)) {
                    $package->setStatus($status);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Package status updated successfully.');
                }
            } elseif ($action === 'update_price' && $package instanceof Package) {
                $price = trim((string) $request->request->get('price'));
                if ($price !== '' && is_numeric($price)) {
                    $package->setPrice($price);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Package price updated successfully.');
                } else {
                    $this->addFlash('admin_msg', 'Please enter a valid package price.');
                }
            } elseif ($action === 'delete_package' && $package instanceof Package) {
                if ($packageRepository->hasBookings($package)) {
                    $this->addFlash('admin_msg', 'This package has existing bookings and cannot be deleted. Deactivate it instead.');
                } else {
                    $image = $package->getImageUrl();
                    $entityManager->remove($package);
                    $entityManager->flush();
                    $this->removeManagedPackageImage($image);
                    $this->addFlash('admin_msg', 'Package deleted successfully.');
                }
            }

            return $this->redirectToRoute('admin_packages');
        }

        $searchQuery = (string) $request->query->get('search', '');
        $searchBy = (string) $request->query->get('search_by', 'all');

        return $this->render('admin/packages.html.twig', [
            'packages' => $this->filterPackages($packageRepository->findAllOrdered(), $searchQuery, $searchBy),
            'searchQuery' => $searchQuery,
            'searchBy' => $searchBy,
        ]);
    }

    #[Route('/packages/new', name: 'admin_package_new', methods: ['GET', 'POST'])]
    public function packageNew(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): Response {
        $package = new Package();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::PACKAGE_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $errors = $this->populatePackageFromRequest($package, $request);
                $errors = array_merge($errors, $this->validateEntity($validator, $package));

                if ($errors === []) {
                    $entityManager->persist($package);
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Package created successfully.');

                    return $this->redirectToRoute('admin_packages');
                }
            }
        }

        return $this->render('admin/package_form.html.twig', [
            'package' => $package,
            'errors' => $errors,
            'isEdit' => false,
        ]);
    }

    #[Route('/packages/{id}/edit', name: 'admin_package_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function packageEdit(
        int $id,
        Request $request,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): Response {
        $package = $packageRepository->find($id);
        if (!$package instanceof Package) {
            throw $this->createNotFoundException('Package not found.');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::PACKAGE_CSRF_TOKEN, (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $errors = $this->populatePackageFromRequest($package, $request);
                $errors = array_merge($errors, $this->validateEntity($validator, $package));

                if ($errors === []) {
                    $entityManager->flush();
                    $this->addFlash('admin_msg', 'Package updated successfully.');

                    return $this->redirectToRoute('admin_packages');
                }
            }
        }

        return $this->render('admin/package_form.html.twig', [
            'package' => $package,
            'errors' => $errors,
            'isEdit' => true,
        ]);
    }

    #[Route('/customers', name: 'admin_customers', methods: ['GET'])]
    public function customers(Request $request, CustomerRepository $customerRepository): Response
    {
        $searchQuery = (string) $request->query->get('search', '');
        $searchBy = (string) $request->query->get('search_by', 'all');

        $customers = $customerRepository->search('');
        $stats = $customerRepository->bookingStatsByCustomer();
        $bookingDates = $customerRepository->bookingDatesByCustomer();
        $term = trim($searchQuery);

        if ($term !== '') {
            $customers = array_values(array_filter($customers, function (Customer $customer) use ($term, $searchBy, $stats, $bookingDates): bool {
                $id = (string) $customer->getCustomerId();
                $name = (string) $customer->getFullName();
                $email = (string) $customer->getEmail();
                $phone = (string) $customer->getPhone();
                $count = (string) ($stats[$customer->getCustomerId()]['count'] ?? 0);
                $dates = $bookingDates[$customer->getCustomerId()] ?? [];
                $formattedDates = array_map(
                    static fn (string $date): string => (new \DateTimeImmutable($date))->format('M d, Y'),
                    $dates
                );

                return match ($searchBy) {
                    'id' => str_contains($id, $term),
                    'name' => stripos($name, $term) !== false,
                    'email' => stripos($email, $term) !== false,
                    'phone' => stripos($phone, $term) !== false,
                    'bookings' => $count === $term,
                    'date' => $this->matchesAnyDate($dates, $formattedDates, $term),
                    default => stripos($name, $term) !== false
                        || stripos($email, $term) !== false
                        || stripos($phone, $term) !== false
                        || str_contains($id, $term),
                };
            }));
        }

        return $this->render('admin/customers.html.twig', [
            'customers' => $customers,
            'stats' => $stats,
            'searchQuery' => $searchQuery,
            'searchBy' => $searchBy,
            'totalClients' => count($customers),
        ]);
    }

    /**
     * @param Package[] $packages
     * @return Package[]
     */
    private function filterPackages(array $packages, string $searchQuery, string $searchBy): array
    {
        $term = trim($searchQuery);
        if ($term === '') {
            return $packages;
        }

        $lowerTerm = strtolower($term);

        $filtered = array_values(array_filter($packages, static function (Package $package) use ($term, $lowerTerm, $searchBy): bool {
            $name = (string) $package->getName();
            $category = (string) $package->getCategory();
            $duration = (string) $package->getDuration();
            $maxPax = (string) $package->getMaxPax();
            $price = (string) $package->getPrice();
            $status = (string) $package->getStatus();

            return match ($searchBy) {
                'name' => stripos($name, $term) !== false,
                'category' => stripos($category, $term) !== false,
                'duration' => stripos($duration, $term) !== false,
                'pax' => str_contains($maxPax, $term),
                'price' => str_contains($price, $term),
                'status' => match ($lowerTerm) {
                    'activated', 'active' => $status === Package::STATUS_ACTIVE,
                    'deactivated', 'inactive' => $status === Package::STATUS_INACTIVE,
                    default => stripos($status, $term) !== false,
                },
                default => stripos($name, $term) !== false
                    || stripos($category, $term) !== false
                    || stripos($duration, $term) !== false
                    || str_contains($maxPax, $term)
                    || str_contains($price, $term)
                    || stripos($status, $term) !== false,
            };
        }));

        usort($filtered, static function (Package $a, Package $b): int {
            return [(string) $a->getCategory(), (float) $a->getPrice()]
                <=> [(string) $b->getCategory(), (float) $b->getPrice()];
        });

        return $filtered;
    }

    /**
     * @return string[] Validation errors
     */
    private function populatePackageFromRequest(Package $package, Request $request): array
    {
        $errors = [];

        $name = trim((string) $request->request->get('name'));
        $category = trim((string) $request->request->get('category'));
        $description = trim((string) $request->request->get('description'));
        $inclusions = trim((string) $request->request->get('inclusions'));
        $duration = trim((string) $request->request->get('duration'));
        $minPax = (int) $request->request->get('min_pax', 1);
        $maxPax = (int) $request->request->get('max_pax', 1);
        $price = trim((string) $request->request->get('price'));
        $status = (string) $request->request->get('status', Package::STATUS_ACTIVE);

        if ($name === '') {
            $errors[] = 'Please enter the package name.';
        }
        if ($price === '' || !is_numeric($price)) {
            $errors[] = 'Please enter a valid price.';
        }
        if (!in_array($status, [Package::STATUS_ACTIVE, Package::STATUS_INACTIVE], true)) {
            $status = Package::STATUS_ACTIVE;
        }
        if ($minPax > $maxPax) {
            $errors[] = 'Minimum pax cannot be greater than maximum pax.';
        }

        $package->setName($name)
            ->setCategory($category !== '' ? $category : 'Standard')
            ->setDescription($description !== '' ? $description : null)
            ->setInclusions($inclusions !== '' ? $inclusions : null)
            ->setDuration($duration !== '' ? $duration : '30 mins')
            ->setMinPax($minPax)
            ->setMaxPax($maxPax)
            ->setStatus($status);

        if ($price !== '' && is_numeric($price)) {
            $package->setPrice($price);
        }

        $file = $request->files->get('image');
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $oldImage = $package->getImageUrl();
                $newImage = $this->storePackageUpload($file, 'package_', ['jpg', 'jpeg', 'png', 'webp'], ['image/jpeg', 'image/png', 'image/webp'], 'packages/');
                $package->setImageUrl($newImage);
                $this->removeManagedPackageImage($oldImage);
            } catch (\Throwable) {
                $errors[] = 'Invalid package image. Allowed formats: JPG, PNG, WEBP.';
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateEntity(ValidatorInterface $validator, object $entity): array
    {
        $errors = [];
        foreach ($validator->validate($entity) as $violation) {
            $errors[] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @param string[] $allowedExtensions
     * @param string[] $allowedMimeTypes
     */
    private function storePackageUpload(
        UploadedFile $file,
        string $prefix,
        array $allowedExtensions,
        array $allowedMimeTypes,
        string $subDirectory,
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = (string) $file->getMimeType();

        if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
            throw new \RuntimeException('Invalid upload type.');
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \RuntimeException('Upload is too large.');
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if ($subDirectory !== '') {
            $uploadDir .= '/' . trim($subDirectory, '/');
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException('Unable to create upload directory.');
        }

        $fileName = $prefix . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($uploadDir, $fileName);

        return 'uploads/' . ($subDirectory !== '' ? trim($subDirectory, '/') . '/' : '') . $fileName;
    }

    private function removeManagedPackageImage(?string $imageUrl): void
    {
        if (!$imageUrl || !str_starts_with($imageUrl, 'uploads/packages/')) {
            return;
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/' . $imageUrl;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param string[] $rawDates
     * @param string[] $formattedDates
     */
    private function matchesAnyDate(array $rawDates, array $formattedDates, string $term): bool
    {
        foreach ($rawDates as $date) {
            if (str_contains($date, $term)) {
                return true;
            }
        }
        foreach ($formattedDates as $date) {
            if (stripos($date, $term) !== false) {
                return true;
            }
        }

        return false;
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
