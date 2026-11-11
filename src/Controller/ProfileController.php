<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'account_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        AdminRepository $adminRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->requirePortalUser();
        $errors = [];

        $form = [
            'fullName' => (string) $user->getFullName(),
            'username' => (string) $user->getUsername(),
            'email' => (string) $user->getEmail(),
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('profile_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $form = [
                    'fullName' => trim((string) $request->request->get('full_name')),
                    'username' => trim((string) $request->request->get('username')),
                    'email' => trim((string) $request->request->get('email')),
                ];

                if ($form['fullName'] === '') {
                    $errors[] = 'Please enter your full name.';
                }
                if ($form['username'] === '') {
                    $errors[] = 'Please enter a username.';
                } elseif ($adminRepository->usernameExists($form['username'], $user->getAdminId())) {
                    $errors[] = 'That username is already in use.';
                }
                if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please enter a valid email address.';
                } elseif ($adminRepository->emailExists($form['email'], $user->getAdminId())) {
                    $errors[] = 'That email address is already in use.';
                }

                if ($errors === []) {
                    $user->setFullName($form['fullName'])
                        ->setUsername($form['username'])
                        ->setEmail($form['email'])
                        ->setUpdatedAt(new \DateTimeImmutable());

                    $entityManager->flush();

                    $request->getSession()->set('admin_name', $user->getFullName());
                    $request->getSession()->set('admin_user', $user->getUsername());

                    $this->addFlash('account_msg', 'Your profile has been updated.');

                    return $this->redirectToRoute('account_profile');
                }
            }
        }

        return $this->render('account/profile.html.twig', [
            'account' => $user,
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    #[Route('/password', name: 'account_password', methods: ['GET', 'POST'])]
    public function password(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->requirePortalUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('password_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $currentPassword = (string) $request->request->get('current_password', '');
                $newPassword = (string) $request->request->get('new_password', '');
                $confirmPassword = (string) $request->request->get('confirm_password', '');

                if ($currentPassword === '') {
                    $errors[] = 'Please enter your current password.';
                } elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $errors[] = 'Your current password is incorrect.';
                }

                if ($newPassword === '') {
                    $errors[] = 'Please enter a new password.';
                } elseif (strlen($newPassword) < 8) {
                    $errors[] = 'The new password must be at least 8 characters long.';
                } elseif ($newPassword !== $confirmPassword) {
                    $errors[] = 'The new password confirmation does not match.';
                } elseif ($currentPassword !== '' && $newPassword === $currentPassword) {
                    $errors[] = 'The new password must be different from your current password.';
                }

                if ($errors === []) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();

                    $this->addFlash('account_msg', 'Your password has been changed successfully.');

                    return $this->redirectToRoute('account_profile');
                }
            }
        }

        return $this->render('account/password.html.twig', [
            'account' => $user,
            'errors' => $errors,
        ]);
    }

    private function requirePortalUser(): Admin
    {
        $user = $this->getUser();
        if (!$user instanceof Admin) {
            throw $this->createAccessDeniedException('This area is only available to administrators and staff.');
        }

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException('This account is disabled or archived.');
        }

        return $user;
    }
}
