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

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    private const PER_PAGE = 10;

    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, AdminRepository $adminRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $role = (string) $request->query->get('role', 'All');
        $status = (string) $request->query->get('status', 'All');

        if ($role !== 'All' && !in_array($role, Admin::ALLOWED_ROLES, true)) {
            $role = 'All';
        }
        if ($status !== 'All' && !in_array($status, Admin::ALLOWED_STATUSES, true)) {
            $status = 'All';
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $total = $adminRepository->countFilteredUsers($search, $role, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $users = $adminRepository->findPaginatedUsers($search, $role, $status, $page, self::PER_PAGE);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        AdminRepository $adminRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $errors = [];
        $form = [
            'fullName' => '',
            'username' => '',
            'email' => '',
            'role' => Admin::ROLE_STAFF,
            'status' => Admin::STATUS_ACTIVE,
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('user_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $form = $this->readForm($request);
                $password = (string) $request->request->get('password', '');
                $confirmPassword = (string) $request->request->get('confirm_password', '');

                $errors = $this->validateForm($form, $password, $confirmPassword, $adminRepository, null);

                if ($errors === []) {
                    $user = new Admin();
                    $user->setFullName($form['fullName'])
                        ->setUsername($form['username'])
                        ->setEmail($form['email'])
                        ->setRole($form['role'])
                        ->setStatus($form['status']);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('admin_msg', sprintf('%s account "%s" created successfully.', $user->getRoleLabel(), $user->getUsername()));

                    return $this->redirectToRoute('admin_user_index');
                }
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => null,
            'form' => $form,
            'errors' => $errors,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        AdminRepository $adminRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $adminRepository->find($id);
        if (!$user instanceof Admin) {
            throw $this->createNotFoundException('User account not found.');
        }

        $currentUser = $this->getUser();
        $isSelf = $currentUser instanceof Admin && $currentUser->getAdminId() === $user->getAdminId();

        $errors = [];
        $form = [
            'fullName' => (string) $user->getFullName(),
            'username' => (string) $user->getUsername(),
            'email' => (string) $user->getEmail(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('user_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session has expired. Please try again.';
            } else {
                $form = $this->readForm($request);
                $newPassword = (string) $request->request->get('new_password', '');
                $confirmPassword = (string) $request->request->get('confirm_password', '');

                $errors = $this->validateForm($form, $newPassword, $confirmPassword, $adminRepository, $user->getAdminId(), true);

                if ($isSelf) {
                    // An administrator cannot elevate/demote or disable their own
                    // signed-in account from this screen.
                    $form['role'] = $user->getRole();
                    $form['status'] = $user->getStatus();
                }

                if ($errors === [] && !$isSelf
                    && $user->isAdmin()
                    && $user->isActive()
                    && ($form['role'] !== Admin::ROLE_ADMIN || $form['status'] !== Admin::STATUS_ACTIVE)
                    && $adminRepository->countActiveAdmins() <= 1
                ) {
                    $errors[] = 'The last active administrator account cannot be demoted or disabled.';
                }

                if ($errors === []) {
                    $user->setFullName($form['fullName'])
                        ->setUsername($form['username'])
                        ->setEmail($form['email'])
                        ->setRole($form['role'])
                        ->setStatus($form['status'])
                        ->setUpdatedAt(new \DateTimeImmutable());

                    if ($newPassword !== '') {
                        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    }

                    $entityManager->flush();

                    $this->addFlash('admin_msg', sprintf('Account "%s" updated successfully.', $user->getUsername()));

                    return $this->redirectToRoute('admin_user_index');
                }
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $user,
            'form' => $form,
            'errors' => $errors,
            'isEdit' => true,
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        AdminRepository $adminRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $adminRepository->find($id);
        if (!$user instanceof Admin) {
            throw $this->createNotFoundException('User account not found.');
        }

        $currentUser = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_user_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('admin_msg', 'Invalid confirmation token. The account was not deleted.');

            return $this->redirectToRoute('admin_user_index');
        }

        if ($currentUser instanceof Admin && $currentUser->getAdminId() === $user->getAdminId()) {
            $this->addFlash('admin_msg', 'You cannot delete the account you are currently signed in with.');

            return $this->redirectToRoute('admin_user_index');
        }

        if ($user->isAdmin() && $user->isActive() && $adminRepository->countActiveAdmins() <= 1) {
            $this->addFlash('admin_msg', 'The last active administrator account cannot be deleted.');

            return $this->redirectToRoute('admin_user_index');
        }

        $username = $user->getUsername();
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('admin_msg', sprintf('Account "%s" deleted. Existing booking and blockout records were preserved.', $username));

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @return array{fullName: string, username: string, email: string, role: string, status: string}
     */
    private function readForm(Request $request): array
    {
        return [
            'fullName' => trim((string) $request->request->get('full_name')),
            'username' => trim((string) $request->request->get('username')),
            'email' => trim((string) $request->request->get('email')),
            'role' => (string) $request->request->get('role', Admin::ROLE_STAFF),
            'status' => (string) $request->request->get('status', Admin::STATUS_ACTIVE),
        ];
    }

    /**
     * @param array{fullName: string, username: string, email: string, role: string, status: string} $form
     * @return string[]
     */
    private function validateForm(
        array $form,
        string $password,
        string $confirmPassword,
        AdminRepository $adminRepository,
        ?int $excludeId = null,
        bool $passwordOptional = false,
    ): array {
        $errors = [];

        if ($form['fullName'] === '') {
            $errors[] = 'Please enter the full name.';
        } elseif (mb_strlen($form['fullName']) > 100) {
            $errors[] = 'The full name must not exceed 100 characters.';
        }

        if ($form['username'] === '') {
            $errors[] = 'Please enter a username.';
        } elseif (mb_strlen($form['username']) > 50) {
            $errors[] = 'The username must not exceed 50 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9._@-]+$/', $form['username'])) {
            $errors[] = 'The username may only contain letters, numbers, dots, underscores, @ and hyphens.';
        } elseif ($adminRepository->usernameExists($form['username'], $excludeId)) {
            $errors[] = 'That username is already in use.';
        }

        if ($form['email'] === '') {
            $errors[] = 'Please enter an email address.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (mb_strlen($form['email']) > 180) {
            $errors[] = 'The email address must not exceed 180 characters.';
        } elseif ($adminRepository->emailExists($form['email'], $excludeId)) {
            $errors[] = 'That email address is already in use.';
        }

        if (!in_array($form['role'], Admin::ALLOWED_ROLES, true)) {
            $errors[] = 'Please choose a valid role.';
        }
        if (!in_array($form['status'], Admin::ALLOWED_STATUSES, true)) {
            $errors[] = 'Please choose a valid account status.';
        }

        if (!$passwordOptional || $password !== '' || $confirmPassword !== '') {
            if ($password === '') {
                $errors[] = 'Please enter a password.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'The password must be at least 8 characters long.';
            }
        }

        if ($password !== '' || $confirmPassword !== '') {
            if ($password !== $confirmPassword) {
                $errors[] = 'The password confirmation does not match.';
            }
        }

        return $errors;
    }
}
