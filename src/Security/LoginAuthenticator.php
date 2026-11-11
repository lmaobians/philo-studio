<?php

namespace App\Security;

use App\Entity\Admin;
use App\Entity\Customer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Handles both legacy login forms:
 *   POST /login          email + password (customers, plus the legacy admin email)
 *   POST /admin/login    username + password (admins only)
 *
 * Passwords are still stored as password_hash() bcrypt values in the existing
 * tables, so the configured "auto" hasher verifies them as-is.
 */
class LoginAuthenticator extends AbstractAuthenticator
{
    /**
     * The pre-Symfony /login form allowed the studio admin to sign in with
     * this address. Other admins use the dedicated /admin/login form.
     */
    public const LEGACY_ADMIN_EMAIL = 'admin@philostudio.com';
    public const ERROR_SESSION_KEY = '_login_error';
    public const REDIRECT_SESSION_KEY = '_login_redirect';

    public function __construct(
        private readonly PhiloUserProvider $userProvider,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        return in_array($request->attributes->get('_route'), ['app_login', 'admin_login', 'staff_login'], true);
    }

    public function authenticate(Request $request): Passport
    {
        $isPortalLogin = in_array($request->attributes->get('_route'), ['admin_login', 'staff_login'], true);
        $identifier = trim((string) ($isPortalLogin
            ? $request->request->get('username')
            : $request->request->get('email')));
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new CustomUserMessageAuthenticationException('Your session has expired. Please try again.');
        }

        if ($identifier === '' || $password === '') {
            throw new CustomUserMessageAuthenticationException('Please fill in all fields.');
        }

        try {
            $user = $this->userProvider->loadUserByIdentifier($identifier);
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        if (!$user instanceof Admin && !$user instanceof Customer) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        if ($isPortalLogin) {
            if (!$user instanceof Admin) {
                throw new CustomUserMessageAuthenticationException('Invalid username or password.');
            }
            $verified = $this->isPasswordValid($user, $password);
            if (!$verified) {
                throw new CustomUserMessageAuthenticationException('Invalid username or password.');
            }
        } else {
            if ($user instanceof Admin && strcasecmp($identifier, self::LEGACY_ADMIN_EMAIL) !== 0) {
                throw new CustomUserMessageAuthenticationException('Invalid email or password.');
            }

            if (!$this->isPasswordValid($user, $password)) {
                throw new CustomUserMessageAuthenticationException('Invalid email or password.');
            }
        }

        if ($user instanceof Admin && !$user->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                'This account is disabled or archived. Please contact an administrator.'
            );
        }

        return new SelfValidatingPassport(
            new UserBadge($identifier, static fn (): UserInterface => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof Admin) {
            $request->getSession()->set('admin_id', $user->getAdminId());
            $request->getSession()->set('admin_user', $user->getUsername());
            $request->getSession()->set('admin_name', $user->getFullName());
            $request->getSession()->set('admin_role', $user->getRole());

            $targetRoute = $user->isAdmin() ? 'admin_overview' : 'staff_dashboard';

            return new RedirectResponse($this->urlGenerator->generate($targetRoute));
        }

        if ($user instanceof Customer) {
            $request->getSession()->set('customer_id', $user->getCustomerId());
            $request->getSession()->set('customer_name', $user->getFullName());
            $request->getSession()->set('customer_email', $user->getEmail());
        }

        $redirect = $request->getSession()->get(self::REDIRECT_SESSION_KEY);
        $request->getSession()->remove(self::REDIRECT_SESSION_KEY);

        $target = $this->safeRedirectTarget($redirect)
            ?? $this->urlGenerator->generate('customer_dashboard');

        return new RedirectResponse($target);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(
            self::ERROR_SESSION_KEY,
            $exception instanceof CustomUserMessageAuthenticationException
                ? $exception->getMessageKey()
                : 'Invalid credentials.'
        );

        $route = match ($request->attributes->get('_route')) {
            'admin_login' => 'admin_login',
            'staff_login' => 'staff_login',
            default => 'app_login',
        };

        return new RedirectResponse($this->urlGenerator->generate($route));
    }

    private function isPasswordValid(UserInterface $user, string $password): bool
    {
        try {
            return $this->passwordHasher->isPasswordValid($user, $password);
        } catch (\Throwable) {
            return false;
        }
    }

    private function safeRedirectTarget(mixed $redirect): ?string
    {
        if (!is_string($redirect) || $redirect === '') {
            return null;
        }

        if (preg_match('/[\r\n]/', $redirect)
            || str_starts_with($redirect, '//')
            || preg_match('#^[a-z][a-z0-9+.-]*://#i', $redirect)) {
            return null;
        }

        return $redirect;
    }
}
