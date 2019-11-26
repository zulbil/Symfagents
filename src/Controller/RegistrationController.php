<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class RegistrationController extends AbstractController
{
    private $session; 

    public function __construct(SessionInterface $session) {
        $this->session  =   $session; 
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, 
            MailerInterface $mailer, GuardAuthenticatorHandler $guardHandler, 
            LoginFormAuthenticator $authenticator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_USER']); 
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendEmail($user, $mailer); 

            // do anything else you need here, like send an email 
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    public function sendEmail(User $user)
    {
        $email = (new TemplatedEmail())
                    ->from('admin@symfagents.cd')
                    ->to($user->getEmail())
                    ->subject('Bienvenue à SymfAgent')
                    ->htmlTemplate('app/mail.html.twig')
                    ->context([
                        'expiration_date' => new \DateTime('+7 days'),
                        'user' => $user
                    ]);

    }
    /**
     * @Route("/activate", name="app_activate")
     */
    public function activate() {
        // $entityManager  = $this->getDoctrine()->getManager(); 
        // $user           = $entityManager->getRepository(User::class)->find($id); 

        // if (!$user) {
        //     throw $this->createNotFoundException(
        //         'No User found for id '.$id
        //     );
        // }
        $user = new User();
        return $this->render('app/activate-user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/activate/login/{id}", name="app_login_activate")
     */
    public function loginAfterActivation(Request $request, GuardAuthenticatorHandler $guardHandler, 
    LoginFormAuthenticator $authenticator, User $user) {
        return $guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $authenticator,
            'main' // firewall name in security.yaml
        );
    }
}
