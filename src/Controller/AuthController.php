<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Security $security,
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        if ($this->getUser() instanceof \App\Entity\Admin) {
            return $this->redirectAfterPortalLogin($this->getUser());
        }
        if ($this->getUser() instanceof Customer) {
            return $this->redirectToRoute('customer_dashboard');
        }

        $redirect = $this->safeRedirect($request->query->get('redirect'));
        if ($redirect !== null) {
            $request->getSession()->set(LoginAuthenticator::REDIRECT_SESSION_KEY, $redirect);
        }

        $error = $request->getSession()->get(LoginAuthenticator::ERROR_SESSION_KEY);
        $request->getSession()->remove(LoginAuthenticator::ERROR_SESSION_KEY);

        return $this->render('auth/login.html.twig', [
            'error' => $error,
            'redirect' => $redirect,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($this->getUser() instanceof Customer) {
            return $this->redirectToRoute('customer_dashboard');
        }
        if ($this->getUser() instanceof \App\Entity\Admin) {
            return $this->redirectAfterPortalLogin($this->getUser());
        }

        $error = null;
        $name = '';
        $email = '';
        $phone = '';
        $redirect = $this->safeRedirect($request->query->get('redirect'));
        if ($redirect !== null) {
            $request->getSession()->set(LoginAuthenticator::REDIRECT_SESSION_KEY, $redirect);
        }

        if ($request->isMethod('POST')) {
            $name = mb_strtoupper(trim((string) $request->request->get('name')), 'UTF-8');
            $email = trim((string) $request->request->get('email'));
            $phone = trim((string) $request->request->get('phone'));
            $password = (string) $request->request->get('password');
            $confirmPassword = (string) $request->request->get('confirm_password');

            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                $error = 'Your session has expired. Please try again.';
            } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
                $error = 'Please provide a valid name, email, and password.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif ($this->customerRepository->findOneByEmail($email) !== null) {
                $error = 'Email address is already registered.';
            } else {
                $customer = new Customer();
                $customer->setFullName($name)
                    ->setEmail($email)
                    ->setPhone($phone);
                $customer->setPassword($this->passwordHasher->hashPassword($customer, $password));

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                $this->security->login($customer);

                $target = $request->getSession()->get(LoginAuthenticator::REDIRECT_SESSION_KEY);
                $request->getSession()->remove(LoginAuthenticator::REDIRECT_SESSION_KEY);

                return $this->redirect($this->safeRedirect($target) ?? $this->generateUrl('customer_dashboard'));
            }
        }

        return $this->render('auth/register.html.twig', [
            'error' => $error,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'redirect' => $redirect,
        ]);
    }

    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function adminLogin(Request $request): Response
    {
        if ($this->getUser() instanceof \App\Entity\Admin) {
            return $this->redirectAfterPortalLogin($this->getUser());
        }

        $error = $request->getSession()->get(LoginAuthenticator::ERROR_SESSION_KEY);
        $request->getSession()->remove(LoginAuthenticator::ERROR_SESSION_KEY);

        return $this->render('admin/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/staff/login', name: 'staff_login', methods: ['GET', 'POST'])]
    public function staffLogin(Request $request): Response
    {
        if ($this->getUser() instanceof \App\Entity\Admin) {
            return $this->redirectAfterPortalLogin($this->getUser());
        }

        $error = $request->getSession()->get(LoginAuthenticator::ERROR_SESSION_KEY);
        $request->getSession()->remove(LoginAuthenticator::ERROR_SESSION_KEY);

        return $this->render('staff/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
    public function adminLogout(): Response
    {
        $this->security->logout(false);

        return $this->redirectToRoute('app_home');
    }

    #[Route('/staff/logout', name: 'staff_logout', methods: ['GET'])]
    public function staffLogout(): Response
    {
        $this->security->logout(false);

        return $this->redirectToRoute('app_home');
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        // Handled by the security firewall logout listener.
        throw new \LogicException('This route is handled by the security firewall.');
    }

    private function redirectAfterPortalLogin(\App\Entity\Admin $admin): Response
    {
        return $this->redirectToRoute($admin->isAdmin() ? 'admin_overview' : 'staff_dashboard');
    }

    private function safeRedirect(mixed $redirect): ?string
    {
        if (!is_string($redirect) || $redirect === '' || preg_match('/[\r\n]/', $redirect)) {
            return null;
        }

        if (str_starts_with($redirect, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $redirect)) {
            return null;
        }

        return $redirect;
    }
}
