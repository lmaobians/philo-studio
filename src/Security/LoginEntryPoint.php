<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Sends unauthenticated visitors to the matching legacy login screen.
 */
class LoginEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/admin')) {
            return new RedirectResponse($this->urlGenerator->generate('admin_login'));
        }

        if (str_starts_with($path, '/staff')) {
            return new RedirectResponse($this->urlGenerator->generate('staff_login'));
        }

        $target = $path;
        if ($request->getQueryString()) {
            $target .= '?' . $request->getQueryString();
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login', ['redirect' => $target]));
    }
}
