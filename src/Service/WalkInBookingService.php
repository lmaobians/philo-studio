<?php

namespace App\Service;

use App\Entity\Admin;
use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Package;
use App\Repository\CustomerRepository;
use App\Repository\PackageRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates walk-in bookings for administrators and staff.
 *
 * The staff member/admin who creates the record is stored on bookings.admin_id
 * so the application can enforce creator ownership for staff operations while
 * still letting administrators see every record.
 */
class WalkInBookingService
{
    private const PAYMENT_METHODS = ['cash', 'gcash', 'bdo', 'gotyme'];

    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @throws \InvalidArgumentException when the submitted data is invalid
     */
    public function createFromRequest(Request $request, Admin $creator): Booking
    {
        $customerName = trim((string) $request->request->get('full_name'));
        $customerEmail = filter_var(trim((string) $request->request->get('email')), FILTER_VALIDATE_EMAIL);
        $customerPhone = trim((string) $request->request->get('phone'));
        $package = $this->packageRepository->find((int) $request->request->get('package_id'));

        if ($customerName === '') {
            throw new \InvalidArgumentException('Please provide the client\'s full name.');
        }
        if (!$customerEmail) {
            throw new \InvalidArgumentException('Invalid email format provided.');
        }
        if (!$package instanceof Package) {
            throw new \InvalidArgumentException('Invalid package selected.');
        }

        $date = trim((string) $request->request->get('schedule_date'));
        $time = trim((string) $request->request->get('schedule_time'));
        if (!$this->isValidDate($date) || !$this->isValidTime($time)) {
            throw new \InvalidArgumentException('Please provide a valid schedule date and time.');
        }

        $paymentMethod = (string) $request->request->get('payment_method', 'cash');
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $paymentMethod = 'cash';
        }

        $proofPath = null;
        $file = $request->files->get('payment_proof');
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $proofPath = $this->storeUpload($file, 'walkin_proof_', ['jpg', 'jpeg', 'png', 'pdf'], ['image/jpeg', 'image/png', 'application/pdf'], '');
            } catch (\Throwable) {
                throw new \InvalidArgumentException('Invalid file type uploaded. Allowed formats: JPG, PNG, PDF.');
            }
        }

        $backdrop = trim((string) $request->request->get('backdrop'));
        $hasPets = (string) $request->request->get('has_pets') === '1';
        $petSize = trim((string) $request->request->get('pet_size'));
        $petCount = trim((string) $request->request->get('pet_count'));
        $petBreed = trim((string) $request->request->get('pet_breed'));
        $notes = trim((string) $request->request->get('notes'));

        $petDetails = '';
        if ($hasPets) {
            $details = [];
            if ($petCount !== '') {
                $details[] = 'Count: ' . $petCount;
            }
            if ($petSize !== '') {
                $details[] = 'Size: ' . $petSize;
            }
            if ($petBreed !== '') {
                $details[] = 'Breed: ' . $petBreed;
            }
            $petDetails = implode(', ', $details);
        }

        $fullNotes = 'Payment Method: ' . strtoupper($paymentMethod);
        if ($backdrop !== '') {
            $fullNotes .= ' | Backdrop: ' . $backdrop;
        }
        if ($hasPets) {
            $fullNotes .= ' | Pets: Yes - ' . ($petDetails !== '' ? $petDetails : 'No details');
        }
        if ($notes !== '') {
            $fullNotes .= ' | Notes: ' . $notes;
        }

        $initialStatus = ($paymentMethod === 'cash' || $proofPath !== null)
            ? Booking::STATUS_CONFIRMED
            : Booking::STATUS_PENDING;

        $customer = $this->customerRepository->findByEmailCaseInsensitive((string) $customerEmail);

        if (!$customer instanceof Customer) {
            $customer = new Customer();
            $customer->setFullName($customerName);
            $customer->setEmail((string) $customerEmail);
            $customer->setPhone($customerPhone);
            // The real customers.password column is NOT NULL. Walk-in accounts
            // are staff/admin-managed and do not get self-service login access;
            // this random password is intentionally unusable.
            $customer->setPassword($this->passwordHasher->hashPassword($customer, bin2hex(random_bytes(16))));
            $this->entityManager->persist($customer);
        }

        $booking = new Booking();
        $booking->setBookingReference($this->bookingRepository->generateUniqueReference());
        $booking->setCustomer($customer);
        $booking->setAdmin($creator);
        $booking->setPackage($package);
        $booking->setScheduleDate(new \DateTimeImmutable($date));
        $booking->setScheduleTimeFromString($time);
        $booking->setParticipants(1);
        $booking->setNotes($fullNotes);
        $booking->setPaymentProof($proofPath);
        $booking->setBookingStatus($initialStatus);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    /**
     * @param string[] $allowedExtensions
     * @param string[] $allowedMimeTypes
     */
    private function storeUpload(
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

        $uploadDir = $this->projectDir . '/public/uploads';
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
