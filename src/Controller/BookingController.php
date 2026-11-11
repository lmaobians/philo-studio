<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Package;
use App\Repository\BlockoutDateRepository;
use App\Repository\BookingRepository;
use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    /**
     * The wizard has always used one-hour slots from 10:00 to 20:00
     * (the last bookable slot starts at 19:00). The backend validates
     * against this list so a crafted request cannot book an off-grid time.
     */
    private const SLOT_MINUTES = 60;
    private const OPERATING_SLOTS = [
        '10:00:00', '11:00:00', '12:00:00',
        '13:00:00', '14:00:00', '15:00:00',
        '16:00:00', '17:00:00', '18:00:00', '19:00:00',
    ];

    /** The studio is closed on Mondays (ISO-8601 weekday 1). */
    private const CLOSED_WEEKDAYS = [1];

    /** Schedule comparisons use the studio's local time, not the server default (UTC in Docker). */
    private const SCHEDULE_TIMEZONE = 'Asia/Manila';

    private const STANDARD_BACKDROPS = ['Grey', 'White', 'Sage', 'Brown', 'Latte', 'Blush'];
    private const DARKROOM_BACKDROPS = ['Slate Grey', 'Red'];

    private const PET_MAX_BY_SIZE = [
        'S (1-10 lbs)' => 3,
        'M (11-25 lbs)' => 2,
        'L (26-40 lbs)' => 2,
        'XL (41-70 lbs)' => 1,
        'XXL (91-119 lbs)' => 1,
    ];

    private const PET_KEYWORDS = [
        'Dog', 'Cat', 'Kitten', 'Puppy', 'Rabbit', 'Bunny', 'Hamster', 'Guinea Pig', 'Bird', 'Parrot',
        'Turtle', 'Tortoise', 'Lizard', 'Snake', 'Ferret', 'Chinchilla', 'Hedgehog', 'Fish', 'Poodle',
        'Golden Retriever', 'Labrador Retriever', 'Bulldog', 'Beagle', 'Persian Cat', 'Siamese Cat',
        'Shih Tzu', 'Corgi', 'Siberian Husky', 'Pomeranian', 'Terrier', 'Pug', 'Chihuahua',
        'German Shepherd', 'Dachshund', 'Aspin', 'Puspin',
    ];

    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly BlockoutDateRepository $blockoutDateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/booking', name: 'booking_step1', methods: ['GET', 'POST'])]
    public function step1(Request $request): Response
    {
        $packages = $this->packageRepository->findActive();

        if ($request->isMethod('POST') && $request->request->has('select_package_id')) {
            if (!$this->isCsrfTokenValid('select_package', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Your session has expired. Please select the package again.');
                return $this->redirectToRoute('booking_step1');
            }

            $package = $this->packageRepository->find((int) $request->request->get('select_package_id'));
            if ($package instanceof Package && $package->isActive()) {
                $this->storeBookingPackage($request, $package);

                return $this->redirectToRoute('booking_step2');
            }

            $this->addFlash('error', 'That package is not available.');
            return $this->redirectToRoute('booking_step1');
        }

        return $this->render('booking/step1.html.twig', [
            'categorizedPackages' => $this->categorizeForBooking($packages),
        ]);
    }

    #[Route('/booking/date', name: 'booking_step2', methods: ['GET', 'POST'])]
    public function step2(Request $request): Response
    {
        $packageData = $request->getSession()->get('booking_package');
        if (!is_array($packageData) || !isset($packageData['package_id'])) {
            $this->addFlash('error', 'Please select a package first.');
            return $this->redirectToRoute('booking_step1');
        }

        $package = $this->packageRepository->find((int) $packageData['package_id']);
        if (!$package instanceof Package) {
            $request->getSession()->remove('booking_package');
            $this->addFlash('error', 'The selected package is no longer available.');
            return $this->redirectToRoute('booking_step1');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('booking_date', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Your session has expired. Please select the date and time again.');
                return $this->redirectToRoute('booking_step2');
            }

            $date = trim((string) $request->request->get('schedule_date'));
            $time = trim((string) $request->request->get('schedule_time'));

            $scheduleError = $this->validateScheduleSelection($date, $time);
            if ($scheduleError !== null) {
                $this->addFlash('error', $scheduleError);
                return $this->redirectToRoute('booking_step2');
            }

            if ($this->blockoutDateRepository->isBlocked($date, $time)) {
                $this->addFlash('error', 'That date or time is blocked out. Please choose another slot.');
                return $this->redirectToRoute('booking_step2');
            }

            if (!$this->bookingRepository->checkSlotAvailability($date, $time)) {
                $this->addFlash('error', 'That time slot was just taken by another client. Please select a different slot.');
                return $this->redirectToRoute('booking_step2');
            }

            $request->getSession()->set('booking_date', $date);
            $request->getSession()->set('booking_time', strlen($time) === 5 ? $time . ':00' : $time);

            return $this->redirectToRoute('booking_step3');
        }

        return $this->render('booking/step2.html.twig', [
            'package' => $package,
            'packageData' => $packageData,
            'slotMinutes' => self::SLOT_MINUTES,
        ]);
    }

    #[Route('/booking/customize', name: 'booking_step3', methods: ['GET', 'POST'])]
    public function step3(Request $request): Response
    {
        $packageData = $request->getSession()->get('booking_package');
        $date = $request->getSession()->get('booking_date');

        if (!is_array($packageData) || !isset($packageData['package_id']) || !is_string($date) || $date === '') {
            $this->addFlash('error', 'Please complete the previous booking steps.');
            return $this->redirectToRoute('booking_step1');
        }

        $isDarkroom = str_contains(strtolower((string) ($packageData['name'] ?? '')), 'darkroom');
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('booking_customize', (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please customize your session again.';
            } else {
            $backdrop = trim((string) $request->request->get('backdrop_color'));
            $hasPets = trim((string) $request->request->get('has_pets', 'No'));

            $allowedBackdrops = $isDarkroom ? self::DARKROOM_BACKDROPS : self::STANDARD_BACKDROPS;

            if (!in_array($backdrop, $allowedBackdrops, true)) {
                $error = 'Please select a backdrop color.';
            } elseif (!in_array($hasPets, ['Yes', 'No'], true)) {
                $error = 'Please indicate whether you are bringing pets.';
            } else {
                $petDetails = '';

                if ($hasPets === 'Yes') {
                    $numPets = (int) $request->request->get('num_pets', 0);
                    $petSize = trim((string) $request->request->get('pet_size', ''));
                    $petBreed = trim((string) $request->request->get('pet_breed', ''));

                    $maxAllowed = self::PET_MAX_BY_SIZE[$petSize] ?? 0;

                    $breedItems = array_values(array_filter(array_map('trim', explode(',', $petBreed))));
                    $invalidBreeds = [];
                    foreach ($breedItems as $breed) {
                        $isKnown = false;
                        foreach (self::PET_KEYWORDS as $keyword) {
                            if (stripos($breed, $keyword) !== false) {
                                $isKnown = true;
                                break;
                            }
                        }
                        if (!$isKnown) {
                            $invalidBreeds[] = $breed;
                        }
                    }

                    if ($petSize === '' || !array_key_exists($petSize, self::PET_MAX_BY_SIZE)) {
                        $error = 'Please select a pet size.';
                    } elseif ($numPets < 1) {
                        $error = 'Please enter a valid number of pets.';
                    } elseif ($maxAllowed > 0 && $numPets > $maxAllowed) {
                        $error = "Size {$petSize} allows a maximum of {$maxAllowed} pet(s).";
                    } elseif ($petBreed === '') {
                        $error = 'Please specify the pet breed(s).';
                    } elseif (count($breedItems) !== $numPets) {
                        $error = "You indicated {$numPets} pet(s), but listed " . count($breedItems) . " breed(s). Please separate {$numPets} breed(s) with commas.";
                    } elseif ($invalidBreeds !== []) {
                        $error = 'Unrecognized pet or breed: "' . implode(', ', $invalidBreeds) . '". Please select from the suggested breeds.';
                    }

                    $petDetails = "Quantity: {$numPets}, Size: {$petSize}, Breed: {$petBreed}";
                }

                if ($error === null) {
                    $request->getSession()->set('booking_backdrop', $backdrop);
                    $request->getSession()->set('booking_has_pets', $hasPets);
                    $request->getSession()->set('booking_pet_details', $hasPets === 'Yes' ? $petDetails : '');

                    return $this->redirectToRoute('booking_step4');
                }
            }
            }
        }

        return $this->render('booking/step3.html.twig', [
            'packageData' => $packageData,
            'isDarkroom' => $isDarkroom,
            'error' => $error,
        ]);
    }

    #[Route('/booking/review', name: 'booking_step4', methods: ['GET', 'POST'])]
    public function step4(Request $request): Response
    {
        $customer = $this->getUser();
        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('app_login', ['redirect' => '/booking/review']);
        }

        $packageData = $request->getSession()->get('booking_package');
        $date = $request->getSession()->get('booking_date');
        $time = $request->getSession()->get('booking_time');
        $backdrop = $request->getSession()->get('booking_backdrop');

        if (!is_array($packageData) || !isset($packageData['package_id'])
            || !is_string($date) || $date === ''
            || !is_string($time) || $time === ''
            || !is_string($backdrop) || $backdrop === '') {
            $this->addFlash('error', 'Please complete the previous booking steps.');
            return $this->redirectToRoute('booking_step1');
        }

        $scheduleError = $this->validateScheduleSelection($date, $time);
        if ($scheduleError !== null) {
            $this->addFlash('error', $scheduleError);
            return $this->redirectToRoute('booking_step2');
        }

        $package = $this->packageRepository->find((int) $packageData['package_id']);
        if (!$package instanceof Package) {
            $request->getSession()->remove('booking_package');
            $this->addFlash('error', 'The selected package is no longer available.');
            return $this->redirectToRoute('booking_step1');
        }

        $hasPets = (string) $request->getSession()->get('booking_has_pets', 'No');
        $petInfo = (string) $request->getSession()->get('booking_pet_details', '');
        $totalAmount = (float) $package->getPrice();
        $error = null;

        if ($request->isMethod('POST')) {
            $paymentMode = trim((string) $request->request->get('payment_mode'));
            $consent = trim((string) $request->request->get('privacy_consent'));
            $proofPath = null;

            if (!$this->isCsrfTokenValid('booking_review', (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please submit the form again.';
            } elseif (!in_array($paymentMode, ['BDO', 'GCash'], true)) {
                $error = 'Please choose a mode of payment.';
            } elseif (!in_array($consent, ['Yes', 'No'], true)) {
                $error = 'Please choose your privacy and consent preference.';
            } else {
                $file = $request->files->get('payment_proof');

                if ($file === null || !$file->isValid()) {
                    $error = 'Please upload your proof of payment before completing the booking.';
                } else {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                    $mimeType = (string) $file->getMimeType();

                    if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
                        $error = 'Upload failed. Allowed file types: JPG, PNG, WEBP, PDF.';
                    } elseif ($file->getSize() > 10 * 1024 * 1024) {
                        $error = 'Upload failed. The maximum allowed file size is 10 MB.';
                    } else {
                        $newFileName = 'proof_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

                        try {
                            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                                throw new \RuntimeException('Unable to create the uploads directory.');
                            }
                            $file->move($uploadDir, $newFileName);
                            $proofPath = 'uploads/' . $newFileName;
                        } catch (\Throwable) {
                            $error = "There was an error moving the uploaded file. Please ensure the 'uploads/' directory exists and is writable.";
                        }
                    }
                }
            }

            if ($error === null) {
                if ($this->blockoutDateRepository->isBlocked($date, $time)) {
                    $error = 'That date or time is blocked out. Please choose another slot.';
                } elseif (!$this->bookingRepository->checkSlotAvailability($date, $time)) {
                    $error = 'That time slot was just taken by another client. Please select a different date or time.';
                }
            }

            if ($error === null) {
                $notes = "Backdrop: {$backdrop} | Pets: {$hasPets}";
                if ($hasPets === 'Yes' && $petInfo !== '') {
                    $notes .= " ({$petInfo})";
                }
                $notes .= " | Payment: {$paymentMode} | Social Media Consent: {$consent} | Total Amount: ₱" . number_format($totalAmount, 2);

                $booking = new Booking();
                $booking->setBookingReference($this->bookingRepository->generateUniqueReference());
                $booking->setCustomer($customer);
                $booking->setPackage($package);
                $booking->setScheduleDate(new \DateTimeImmutable($date));
                $booking->setScheduleTimeFromString($time);
                $booking->setParticipants(1);
                $booking->setNotes($notes);
                $booking->setPaymentProof($proofPath);
                $booking->setBookingStatus(Booking::STATUS_PENDING);

                try {
                    $this->entityManager->persist($booking);
                    $this->entityManager->flush();
                } catch (\Throwable) {
                    $error = 'There was an error processing your booking. Please try again.';
                }

                if ($error === null) {
                    $session = $request->getSession();
                    $session->remove('booking_package');
                    $session->remove('booking_date');
                    $session->remove('booking_time');
                    $session->remove('booking_backdrop');
                    $session->remove('booking_has_pets');
                    $session->remove('booking_pet_details');
                    $session->getFlashBag()->add('booking_success', 'Your slot has been reserved! We will verify your payment shortly.');

                    return $this->redirectToRoute('customer_dashboard');
                }
            }
        }

        return $this->render('booking/step4.html.twig', [
            'package' => $package,
            'packageData' => $packageData,
            'customer' => $customer,
            'date' => $date,
            'time' => $time,
            'backdrop' => $backdrop,
            'hasPets' => $hasPets,
            'petInfo' => $petInfo,
            'totalAmount' => $totalAmount,
            'error' => $error,
        ]);
    }

    #[Route('/get-booked-slots', name: 'booking_slots', methods: ['GET'])]
    public function bookedSlots(Request $request): JsonResponse
    {
        $date = trim((string) $request->query->get('date', ''));
        if (!$this->isValidDate($date)) {
            return $this->json([]);
        }

        try {
            $blocked = $this->bookingRepository->findBookedTimesForDate($date);
            $blockouts = $this->blockoutDateRepository->findForDate($date);
            $dateValue = new \DateTimeImmutable($date);

            foreach ($blockouts as $blockout) {
                if ($blockout->isFullDay()) {
                    $blocked = array_merge($blocked, self::OPERATING_SLOTS);
                    break;
                }

                $start = $blockout->getStartTime();
                $end = $blockout->getEndTime();
                if (!$start || !$end) {
                    $blocked = array_merge($blocked, self::OPERATING_SLOTS);
                    break;
                }

                foreach (self::OPERATING_SLOTS as $slot) {
                    $slotTime = new \DateTimeImmutable($date . ' ' . $slot);
                    $blockStart = new \DateTimeImmutable($date . ' ' . $start->format('H:i:s'));
                    $blockEnd = new \DateTimeImmutable($date . ' ' . $end->format('H:i:s'));

                    if ($slotTime >= $blockStart && $slotTime < $blockEnd) {
                        $blocked[] = $slot;
                    }
                }
            }

            $formatted = [];
            foreach ($blocked as $slot) {
                try {
                    $timestamp = new \DateTimeImmutable($date . ' ' . (strlen($slot) === 5 ? $slot . ':00' : $slot));
                } catch (\Throwable) {
                    continue;
                }

                foreach (['H:i:00', 'g:i A', 'h:i A', 'H:i', 'g:i a', 'h:i a'] as $format) {
                    $formatted[] = $timestamp->format($format);
                }
            }

            return $this->json(array_values(array_unique($formatted)));
        } catch (\Throwable) {
            return $this->json([]);
        }
    }

    /**
     * @param Package[] $packages
     * @return array<string, Package[]>
     */
    private function categorizeForBooking(array $packages): array
    {
        $categorized = [];
        foreach ($packages as $package) {
            $categorized[(string) $package->getCategory()][] = $package;
        }

        foreach ($categorized as &$items) {
            usort($items, static fn (Package $a, Package $b): int => (float) $a->getPrice() <=> (float) $b->getPrice());
        }

        return $categorized;
    }

    private function storeBookingPackage(Request $request, Package $package): void
    {
        $request->getSession()->set('booking_package', [
            'package_id' => $package->getPackageId(),
            'name' => $package->getName(),
            'category' => $package->getCategory(),
            'duration' => $package->getDuration(),
            'price' => $package->getPrice(),
            'slot_minutes' => $package->getSlotMinutes(),
            'image_url' => $package->getImageUrl(),
            'inclusions' => $package->getInclusions(),
        ]);
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

    /**
     * Validates a date/time pair against the wizard's operating rules.
     * Returns an error message, or null when the selection is bookable.
     */
    private function validateScheduleSelection(string $date, string $time): ?string
    {
        if (!$this->isValidDate($date)) {
            return 'Please select a valid date.';
        }

        $normalizedTime = strlen($time) === 5 ? $time . ':00' : $time;
        if (!$this->isValidTime($normalizedTime)) {
            return 'Please select a valid time slot.';
        }

        if (!in_array($normalizedTime, self::OPERATING_SLOTS, true)) {
            return 'Please choose one of the available one-hour time slots.';
        }

        $today = new \DateTimeImmutable('today', new \DateTimeZone(self::SCHEDULE_TIMEZONE));
        $selectedDate = new \DateTimeImmutable($date, new \DateTimeZone(self::SCHEDULE_TIMEZONE));

        if ($selectedDate < $today) {
            return 'That date has already passed. Please choose an upcoming date.';
        }

        if (in_array((int) $selectedDate->format('N'), self::CLOSED_WEEKDAYS, true)) {
            return 'The studio is closed on Mondays. Please choose another day.';
        }

        if ($selectedDate == $today) {
            $timezone = new \DateTimeZone(self::SCHEDULE_TIMEZONE);
            $selectedDateTime = new \DateTimeImmutable($date . ' ' . $normalizedTime, $timezone);
            if ($selectedDateTime <= new \DateTimeImmutable('now', $timezone)) {
                return 'That time slot has already passed. Please choose a later slot.';
            }
        }

        return null;
    }
}
