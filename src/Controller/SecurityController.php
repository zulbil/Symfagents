<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


class SecurityController extends AbstractController
{
    private $security;
    private $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security     =   $security;
        $this->urlGenerator =   $urlGenerator;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $isAuthenticate     = $this->security->isGranted('IS_AUTHENTICATED_FULLY');
        if ($isAuthenticate) {
            $hasAccess = $this->security->isGranted('ROLE_ADMIN');
            if ($hasAccess) {
                return new RedirectResponse($this->urlGenerator->generate('projets'));
            }
            return new RedirectResponse($this->urlGenerator->generate('project_user'));
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error'         => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
        //return new RedirectResponse($this->urlGenerator->generate('list_agent'));
    }
}
