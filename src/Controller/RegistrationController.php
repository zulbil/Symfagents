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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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
    private $mailer; 

    public function __construct(SessionInterface $session, MailerInterface $mailer) {
        $this->session  =   $session; 
        $this->mailer   =   $mailer;
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, 
            MailerInterface $mailer)
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

            $this->sendEmail($user, $form->get('plainPassword')->getData()); 

            return $this->redirectToRoute('show_message');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    public function sendEmail(User $user, $password)
    {
        $email = (new TemplatedEmail())
                    ->from('jkazdev@gmail.com')
                    //->from('joel.khang@hologram.cd')
                    ->to($user->getEmail())
                    ->subject('Bienvenue à SymfAgent')
                    ->htmlTemplate('app/mail.html.twig')
                    ->context([
                        'expiration_date' => new \DateTime('+7 days'),
                        'user' => $user, 
                        'plain_password' => $password
                    ]);

        $this->mailer->send($email);
    }
    /**
     * @Route("/activate/user/{id}", name="app_activate")
     */
    public function activate(Request $request, GuardAuthenticatorHandler $guardHandler, 
    LoginFormAuthenticator $authenticator,$id) {
        $entityManager  = $this->getDoctrine()->getManager(); 
        $user           = $entityManager->getRepository(User::class)->find($id); 

        if (!$user) {
            throw $this->createNotFoundException(
                'No User found for id '.$id
            );
        }

        $user->setStatut(1);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('app/activate-user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/activate/login/{id}", name="app_login_activate")
     */
    public function loginAfterActivation(Request $request, GuardAuthenticatorHandler $guardHandler, 
    LoginFormAuthenticator $authenticator, $id) {

        $entityManager  = $this->getDoctrine()->getManager(); 
        $user           = $entityManager->getRepository(User::class)->find($id);
         
        return $guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $authenticator,
            'main' // firewall name in security.yaml
        );
    }

    /**
     * @Route("/message/confirmation", name="show_message")
     */
    public function showMessage() {
        return $this->render('app/message-consultation.html.twig');
    }

    /**
     *@Route("/profil", name="user_profile")
     */
    public function userProfile () {
        $user = $this->getUser();

        return $this->render('app/profile.html.twig', ['user' => $user ]);
    }


}
