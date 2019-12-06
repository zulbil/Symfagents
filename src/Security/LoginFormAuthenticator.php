<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->entityManager        = $entityManager;
        $this->urlGenerator         = $urlGenerator;
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->passwordEncoder      = $passwordEncoder;
        $this->security             = $security;
    }
    /**
     * It checks to see if the current route is named app_login and if the request
     *  method is POST. 
     * If those things are true, Symfony will assume that a user is trying to login.
     * This function allow authentication on login page
     */
    public function supports(Request $request)
    {
         return 'app_login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    /**
     * This method’s job is to return the credentials that a user is trying to use to login. 
     * So in this case, it’s taking the credentials from the POST request that was made and 
     * returns them as an array. It’s also setting a session variable for the last username used. 
     * This session variable is used in the event the login fails so the email field is prefilled 
     * for the user when they get the error message.
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'email'         => $request->request->get('email'),
            'password'      => $request->request->get('password'),
            'csrf_token'    => $request->request->get('_csrf_token'),
        ];
         
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );
        
        return $credentials;
    }

    /**
     * The $credentials parameter that’s passed to this method is the same array that’s created in 
     * the getCredentials method. 
     * So first it’s checking that the csrf token passed is valid, 
     * then it tries to find a user by email. 
     * If it finds one, it returns that user object, otherwise an exception is thrown.
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException("Aucun compte ne correspond à cet email.");
        }

        if($user->getStatut() == 0) {
            throw new CustomUserMessageAuthenticationException("Votre compte n'est pas activé");
        }

        return $user;
    }

    /**
     * This is where the password is checked and should return either true or false.
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /*
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }
        */
        $hasAccess = $this->security->isGranted('ROLE_ADMIN');
        $user = $this->security->getUser();

        if($hasAccess) {
            return new RedirectResponse($this->urlGenerator->generate('projets'));
        }
        return new RedirectResponse($this->urlGenerator->generate('project_user'));
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('app_login');
    }
}
