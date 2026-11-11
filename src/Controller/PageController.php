<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Package;
use App\Repository\PackageRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    private const CATEGORY_ORDER = ['The Classics', 'On Cloud', 'Full Grid+', 'Darkroom', 'Studio Access'];

    public function __construct(
        private readonly PackageRepository $packageRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('page/home.html.twig');
    }

    #[Route('/packages', name: 'app_packages', methods: ['GET', 'POST'])]
    public function packages(Request $request): Response
    {
        if ($request->isMethod('POST') && $request->request->has('select_package_id')) {
            $user = $this->getUser();
            if (!$user instanceof Customer) {
                return $this->redirectToRoute('app_login', ['redirect' => '/packages']);
            }

            if (!$this->isCsrfTokenValid('select_package', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Your session has expired. Please select the package again.');
                return $this->redirectToRoute('app_packages');
            }

            $package = $this->packageRepository->find((int) $request->request->get('select_package_id'));
            if ($package instanceof Package && $package->isActive()) {
                $this->storeBookingPackage($request, $package);

                return $this->redirectToRoute('booking_step2');
            }

            $this->addFlash('error', 'That package is not available.');
            return $this->redirectToRoute('app_packages');
        }

        return $this->render('page/packages.html.twig', [
            'categorizedPackages' => $this->categorize($this->packageRepository->findActive()),
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/gallery', name: 'app_gallery')]
    public function gallery(): Response
    {
        return $this->render('page/gallery.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('page/faq.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        #[Autowire('%env(SMTP_HOST)%')] string $smtpHost,
        #[Autowire('%env(SMTP_USER)%')] string $smtpUser,
        #[Autowire('%env(SMTP_PASS)%')] string $smtpPass,
        #[Autowire('%env(int:SMTP_PORT)%')] int $smtpPort,
        #[Autowire('%env(CONTACT_TO_EMAIL)%')] string $contactToEmail,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact', (string) $request->request->get('_token'))) {
                return $this->redirectToRoute('app_contact', ['status' => 'error']);
            }

            $name = trim((string) $request->request->get('name'));
            $email = filter_var(trim((string) $request->request->get('email')), FILTER_SANITIZE_EMAIL);
            $phone = trim((string) $request->request->get('phone'));
            $package = trim((string) $request->request->get('package'));
            $message = trim((string) $request->request->get('message'));

            if ($name === '' || !$email || $message === '') {
                return $this->redirectToRoute('app_contact', ['status' => 'error']);
            }

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtpPort;

                $mail->setFrom($smtpUser, 'PHILO Studio Website');
                $mail->addAddress($contactToEmail, 'Admin');
                $mail->addReplyTo($email, $name);

                $safe = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

                $mail->isHTML(true);
                $mail->Subject = 'New Inquiry from ' . $name;
                $mail->Body = '
                    <h2>New Contact Form Submission</h2>
                    <p><strong>Name:</strong> ' . $safe($name) . '</p>
                    <p><strong>Email:</strong> ' . $safe($email) . '</p>
                    <p><strong>Phone:</strong> ' . $safe($phone) . '</p>
                    <p><strong>Interested Package:</strong> ' . $safe($package) . '</p>
                    <p><strong>Message:</strong><br>' . nl2br($safe($message)) . '</p>
                ';

                $mail->send();

                return $this->redirectToRoute('app_contact', ['status' => 'success']);
            } catch (PHPMailerException) {
                return $this->redirectToRoute('app_contact', ['status' => 'error']);
            }
        }

        return $this->render('page/contact.html.twig');
    }

    /**
     * @param Package[] $packages
     * @return array<string, Package[]>
     */
    private function categorize(array $packages): array
    {
        $categorized = [];
        foreach (self::CATEGORY_ORDER as $category) {
            $categorized[$category] = [];
        }

        foreach ($packages as $package) {
            $category = (string) $package->getCategory();
            if (!array_key_exists($category, $categorized)) {
                $categorized[$category] = [];
            }
            $categorized[$category][] = $package;
        }

        return array_filter($categorized, static fn (array $items): bool => $items !== []);
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
}
